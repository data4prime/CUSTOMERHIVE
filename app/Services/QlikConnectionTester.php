<?php

namespace App\Services;

use DB;
use Firebase\JWT\JWT;
use Illuminate\Support\Carbon;
use Ramsey\Uuid\Uuid;

/**
 * Diagnostica della connessione a Qlik (pulsante "Prova connessione" nella
 * configurazione Qlik). Riproduce lato server la stessa costruzione del JWT
 * e lo stesso primo scambio di login usati davvero dall'app
 * (QlikHelper::getJWTToken / getJWTTokenOP, QlikHelper::createUser, JS
 * qlik_op_jwt_login.js), ma lavora su valori passati dal chiamante - anche
 * non ancora salvati - e restituisce un rapporto passo-passo con tutti i
 * dettagli utili per capire dove si rompe (chiave, JWT, DNS, TCP, TLS, HTTP).
 *
 * Nel rapporto non compare mai il contenuto della chiave privata ne' la firma
 * del JWT; Authorization e cookie sono mascherati.
 *
 * Non modifica nulla (nessuna scrittura su DB o file): sola lettura.
 */
class QlikConnectionTester
{
    const CONNECT_TIMEOUT = 5;

    const TIMEOUT = 15;

    const BODY_MAX = 2000;

    /** @var array rapporto in costruzione */
    private $steps = [];

    /** @var bool true se almeno un passo e' fallito */
    private $failed = false;

    /** @var string|null id del primo passo fallito */
    private $failedStep = null;

    /**
     * @param  array  $input  valori della configurazione (come nel form): type, auth, url, endpoint, keyid, issuer, web_int_id, private_key (path salvato)
     * @param  string|null  $uploadedKey  contenuto di una chiave appena caricata nel form (non ancora salvata)
     * @param  object|null  $savedConf  riga di qlik_confs se la configurazione esiste gia'
     * @param  object|null  $user  utente corrente (\App\User)
     * @return array {ok, summary, steps[]}
     */
    public function run(array $input, $uploadedKey, $savedConf, $user): array
    {
        $this->steps = [];
        $this->failed = false;
        $this->failedStep = null;

        $conf = $this->mergeConf($input, $savedConf);
        $isSaas = $conf['type'] === 'SAAS';

        if (! $this->stepConfig($conf, $isSaas)) {
            return $this->report();
        }

        $key = $this->stepKey($conf, $uploadedKey);
        if ($key === null) {
            return $this->report();
        }

        $token = $this->stepJwt($conf, $isSaas, $key, $savedConf, $user);
        if ($token === null) {
            return $this->report();
        }

        $parts = parse_url($conf['url']);
        $host = $parts['host'];
        $scheme = $parts['scheme'];
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        if (! $this->stepDns($host)) {
            return $this->report();
        }
        if (! $this->stepTcp($host, $port)) {
            return $this->report();
        }
        if ($scheme === 'https') {
            // Un problema di certificato non blocca i passi successivi: la
            // richiesta HTTP verifichera' comunque il certificato e dira' lei
            // se e' un vero ostacolo.
            $this->stepTls($host, $port);
        }

        if ($isSaas) {
            $this->stepSaasLogin($conf, $token);
        } else {
            $this->stepOnPremAbout($conf, $token);
        }

        return $this->report();
    }

    // ------------------------------------------------------------------
    // Passi
    // ------------------------------------------------------------------

    private function stepConfig(array $conf, bool $isSaas): bool
    {
        $t0 = microtime(true);
        $details = [];
        $hints = [];
        $status = 'ok';

        $details[trans('crudbooster.qlik_test_lbl_type')] = $conf['type'].' / '.$conf['auth'];
        $details['URL'] = $conf['url'] !== '' ? $conf['url'] : '—';
        $details[trans('crudbooster.qlik_test_lbl_endpoint')] = $conf['endpoint'] !== '' ? $conf['endpoint'] : '—';
        if ($isSaas) {
            $details['Key ID'] = $conf['keyid'] !== '' ? $conf['keyid'] : '—';
            $details['Issuer'] = $conf['issuer'] !== '' ? $conf['issuer'] : '—';
            $details['Web Int ID'] = $conf['web_int_id'] !== '' ? $conf['web_int_id'] : '—';
        }

        if ($conf['auth'] !== 'JWT') {
            $status = 'fail';
            $hints[] = trans('crudbooster.qlik_test_h_auth_unsupported', ['auth' => $conf['auth']]);
        }

        $parts = parse_url($conf['url']);
        if ($conf['url'] === '') {
            $status = 'fail';
            $hints[] = trans('crudbooster.qlik_test_h_missing_field', ['field' => 'URL']);
        } elseif (! is_array($parts) || empty($parts['host']) || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
            $status = 'fail';
            $hints[] = trans('crudbooster.qlik_test_h_url_invalid');
        }

        $required = $isSaas ? ['keyid' => 'Key ID', 'issuer' => 'Issuer', 'web_int_id' => 'Web Int ID'] : ['endpoint' => trans('crudbooster.qlik_test_lbl_endpoint')];
        foreach ($required as $field => $label) {
            if ($conf[$field] === '') {
                $status = 'fail';
                $hints[] = trans('crudbooster.qlik_test_h_missing_field', ['field' => $label]);
            }
        }

        if ($status === 'ok' && ($conf['port'] ?? '') !== '') {
            $urlPort = $parts['port'] ?? null;
            if ($urlPort === null || (string) $urlPort !== (string) $conf['port']) {
                $status = 'warn';
                $hints[] = trans('crudbooster.qlik_test_h_port_ignored', ['port' => $conf['port']]);
            }
        }

        $this->addStep('config', $status, $t0, $details, $hints);

        return $status !== 'fail';
    }

    /**
     * @return string|null contenuto PEM della chiave, null se non utilizzabile
     */
    private function stepKey(array $conf, $uploadedKey)
    {
        $t0 = microtime(true);
        $details = [];
        $hints = [];

        $pem = null;
        if ($uploadedKey !== null && $uploadedKey !== '') {
            $pem = $uploadedKey;
            $details[trans('crudbooster.qlik_test_lbl_key_source')] = trans('crudbooster.qlik_test_key_source_upload');
        } elseif ($conf['private_key'] !== '') {
            $tried = [];
            foreach ($this->keyPathCandidates($conf['private_key']) as $candidate) {
                $tried[] = $candidate.(is_file($candidate) ? '' : ' ('.trans('crudbooster.qlik_test_not_found').')');
                if (is_file($candidate) && is_readable($candidate)) {
                    $pem = (string) file_get_contents($candidate);
                    $details[trans('crudbooster.qlik_test_lbl_key_source')] = trans('crudbooster.qlik_test_key_source_saved').': '.$conf['private_key'];
                    $details[trans('crudbooster.qlik_test_lbl_key_file')] = $candidate;
                    break;
                }
            }
            if ($pem === null) {
                $details[trans('crudbooster.qlik_test_lbl_key_paths_tried')] = implode("\n", $tried);
                $hints[] = trans('crudbooster.qlik_test_h_key_unreadable');
                $this->addStep('key', 'fail', $t0, $details, $hints);

                return null;
            }
        } else {
            $hints[] = trans('crudbooster.qlik_test_h_key_missing');
            $this->addStep('key', 'fail', $t0, $details, $hints);

            return null;
        }

        $firstLine = trim(strtok($pem, "\n"));
        $details[trans('crudbooster.qlik_test_lbl_key_header')] = $firstLine !== '' ? $firstLine : '—';
        $details[trans('crudbooster.qlik_test_lbl_key_size')] = strlen($pem).' bytes';

        if (stripos($pem, 'ENCRYPTED') !== false) {
            $hints[] = trans('crudbooster.qlik_test_h_key_encrypted');
            $this->addStep('key', 'fail', $t0, $details, $hints);

            return null;
        }

        $res = @openssl_pkey_get_private($pem);
        if ($res === false) {
            $err = '';
            while ($e = openssl_error_string()) {
                $err .= ($err === '' ? '' : ' | ').$e;
            }
            $details['OpenSSL'] = $err !== '' ? $err : '—';
            $hints[] = trans('crudbooster.qlik_test_h_key_invalid');
            $this->addStep('key', 'fail', $t0, $details, $hints);

            return null;
        }

        $info = openssl_pkey_get_details($res);
        $typeNames = [OPENSSL_KEYTYPE_RSA => 'RSA', OPENSSL_KEYTYPE_DSA => 'DSA', OPENSSL_KEYTYPE_DH => 'DH', OPENSSL_KEYTYPE_EC => 'EC'];
        $keyType = $typeNames[$info['type'] ?? -1] ?? (string) ($info['type'] ?? '?');
        $bits = (int) ($info['bits'] ?? 0);
        $details[trans('crudbooster.qlik_test_lbl_key_type')] = $keyType.' '.$bits.' bit';
        if (! empty($info['key'])) {
            // Impronta della chiave PUBBLICA derivata: confrontabile con quella
            // registrata su Qlik senza esporre nulla di segreto.
            $der = base64_decode(preg_replace('/-----[^-]+-----|\s+/', '', $info['key']));
            $details[trans('crudbooster.qlik_test_lbl_key_fingerprint')] = 'SHA-256 '.hash('sha256', $der);
        }

        $status = 'ok';
        if ($keyType !== 'RSA') {
            $status = 'fail';
            $hints[] = trans('crudbooster.qlik_test_h_key_not_rsa');
        } elseif ($bits < 2048) {
            $status = 'fail';
            $hints[] = trans('crudbooster.qlik_test_h_key_small', ['bits' => $bits]);
        }

        $this->addStep('key', $status, $t0, $details, $hints);

        return $status === 'fail' ? null : $pem;
    }

    /**
     * @return string|null JWT firmato, null se la firma non e' riuscita
     */
    private function stepJwt(array $conf, bool $isSaas, string $pem, $savedConf, $user)
    {
        $t0 = microtime(true);
        $details = [];
        $hints = [];
        $status = 'ok';

        $confId = $savedConf->id ?? null;
        $qlikUser = null;
        if ($confId && $user) {
            $qlikUser = DB::table('qlik_users')->where('user_id', $user->id)->where('qlik_conf_id', $confId)->first();
        }

        $now = Carbon::now();

        if ($isSaas) {
            if ($qlikUser && ! empty($qlikUser->idp_qlik)) {
                $sub = $qlikUser->idp_qlik;
                $details[trans('crudbooster.qlik_test_lbl_identity')] = trans('crudbooster.qlik_test_identity_mapped');
            } else {
                $sub = bin2hex(random_bytes(16));
                $details[trans('crudbooster.qlik_test_lbl_identity')] = trans('crudbooster.qlik_test_identity_random');
                $hints[] = trans('crudbooster.qlik_test_h_identity_random');
                $status = 'warn';
            }
            $header = [
                'alg' => 'RS256',
                'algorithm' => 'RS256',
                'aud' => 'qlik.api/login/jwt-session',
                'iss' => $conf['issuer'],
                'kid' => $conf['keyid'],
                'typ' => 'JWT',
                'exp' => '1h',
            ];
            $payload = [
                'sub' => $sub,
                'subType' => 'user',
                'jti' => Uuid::uuid4()->toString(),
                'iat' => $now->getTimestamp(),
                'iss' => $conf['issuer'],
                'nbf' => $now->getTimestamp(),
                'exp' => $now->copy()->addMinutes(60)->timestamp,
                'aud' => 'qlik.api/login/jwt-session',
                'email' => $user->email ?? '',
                'email_verified' => true,
                'name' => $user->name ?? '',
            ];
            $kid = $conf['keyid'];
        } else {
            if ($qlikUser && ! empty($qlikUser->qlik_login)) {
                $userId = $qlikUser->qlik_login;
                $userDirectory = $qlikUser->user_directory;
                $details[trans('crudbooster.qlik_test_lbl_identity')] = trans('crudbooster.qlik_test_identity_mapped');
            } else {
                $userId = $user->email ?? 'customerhive';
                $userDirectory = 'CUSTOMERHIVE';
                $details[trans('crudbooster.qlik_test_lbl_identity')] = trans('crudbooster.qlik_test_identity_placeholder');
                $hints[] = trans('crudbooster.qlik_test_h_identity_placeholder');
                $status = 'warn';
            }
            $header = ['typ' => 'JWT', 'alg' => 'RS256'];
            $payload = ['userId' => $userId, 'userDirectory' => $userDirectory];
            $kid = null;
        }

        try {
            $token = JWT::encode($payload, $pem, 'RS256', $kid, $header);
        } catch (\Throwable $e) {
            $details[trans('crudbooster.qlik_test_lbl_error')] = get_class($e).': '.$e->getMessage();
            $hints[] = trans('crudbooster.qlik_test_h_jwt_failed');
            $this->addStep('jwt', 'fail', $t0, $details, $hints);

            return null;
        }

        // Header e payload decodificati (mai la firma): servono per confrontarli
        // con quanto atteso da Qlik (issuer, kid, audience, userId/directory...).
        $segments = explode('.', $token);
        $details['JWT header'] = $this->pretty(json_decode($this->b64url($segments[0]), true));
        $details['JWT payload'] = $this->pretty(json_decode($this->b64url($segments[1]), true));
        $details[trans('crudbooster.qlik_test_lbl_jwt_length')] = strlen($token).' chars';

        $this->addStep('jwt', $status, $t0, $details, $hints);

        return $token;
    }

    private function stepDns(string $host): bool
    {
        $t0 = microtime(true);
        $details = ['Host' => $host];
        $hints = [];

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $details[trans('crudbooster.qlik_test_lbl_ips')] = $host.' ('.trans('crudbooster.qlik_test_ip_literal').')';
            $this->addStep('dns', 'ok', $t0, $details, $hints);

            return true;
        }

        $ips = [];
        $records = @dns_get_record($host, DNS_A + DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $r) {
                if (! empty($r['ip'])) {
                    $ips[] = $r['ip'].' (A, TTL '.($r['ttl'] ?? '?').')';
                } elseif (! empty($r['ipv6'])) {
                    $ips[] = $r['ipv6'].' (AAAA, TTL '.($r['ttl'] ?? '?').')';
                }
            }
        }
        if (! $ips) {
            $fallback = @gethostbynamel($host);
            foreach ($fallback ?: [] as $ip) {
                $ips[] = $ip;
            }
        }

        if (! $ips) {
            $hints[] = trans('crudbooster.qlik_test_h_dns_fail', ['host' => $host]);
            $this->addStep('dns', 'fail', $t0, $details, $hints);

            return false;
        }

        $details[trans('crudbooster.qlik_test_lbl_ips')] = implode("\n", $ips);
        $this->addStep('dns', 'ok', $t0, $details, $hints);

        return true;
    }

    private function stepTcp(string $host, int $port): bool
    {
        $t0 = microtime(true);
        $details = ['Host:Port' => $host.':'.$port];
        $hints = [];

        $errno = 0;
        $errstr = '';
        $fp = @stream_socket_client('tcp://'.$host.':'.$port, $errno, $errstr, self::CONNECT_TIMEOUT);
        if (! $fp) {
            $details[trans('crudbooster.qlik_test_lbl_error')] = '['.$errno.'] '.($errstr !== '' ? $errstr : 'timeout / unreachable');
            $hints[] = trans('crudbooster.qlik_test_h_tcp_fail', ['port' => $port]);
            $this->addStep('tcp', 'fail', $t0, $details, $hints);

            return false;
        }

        $details[trans('crudbooster.qlik_test_lbl_remote')] = stream_socket_get_name($fp, true) ?: '—';
        $details[trans('crudbooster.qlik_test_lbl_local')] = stream_socket_get_name($fp, false) ?: '—';
        fclose($fp);
        $this->addStep('tcp', 'ok', $t0, $details, $hints);

        return true;
    }

    /**
     * Ispezione del certificato TLS SENZA verifica (per poterlo leggere anche
     * se non valido), poi valutazione manuale di scadenza e hostname.
     * Non blocca il flusso: e' informativo (la richiesta HTTP verifica
     * comunque il certificato).
     */
    private function stepTls(string $host, int $port): void
    {
        $t0 = microtime(true);
        $details = [];
        $hints = [];
        $status = 'ok';

        $ctx = stream_context_create(['ssl' => [
            'capture_peer_cert' => true,
            'capture_peer_cert_chain' => true,
            'verify_peer' => false,
            'verify_peer_name' => false,
            'SNI_enabled' => true,
            'peer_name' => $host,
        ]]);
        $errno = 0;
        $errstr = '';
        $fp = @stream_socket_client('ssl://'.$host.':'.$port, $errno, $errstr, self::CONNECT_TIMEOUT, STREAM_CLIENT_CONNECT, $ctx);
        if (! $fp) {
            $details[trans('crudbooster.qlik_test_lbl_error')] = '['.$errno.'] '.$errstr;
            $hints[] = trans('crudbooster.qlik_test_h_tls_handshake');
            $this->addStep('tls', 'fail', $t0, $details, $hints);

            return;
        }

        $meta = stream_get_meta_data($fp);
        $crypto = $meta['crypto'] ?? [];
        $params = stream_context_get_params($fp);
        fclose($fp);

        $details[trans('crudbooster.qlik_test_lbl_tls_protocol')] = ($crypto['protocol'] ?? '?').' / '.($crypto['cipher_name'] ?? '?');

        $cert = $params['options']['ssl']['peer_certificate'] ?? null;
        $parsed = $cert ? openssl_x509_parse($cert) : false;
        if (! $parsed) {
            $this->addStep('tls', 'warn', $t0, $details, [trans('crudbooster.qlik_test_h_tls_no_cert')]);

            return;
        }

        $subjectCn = $parsed['subject']['CN'] ?? '—';
        $issuerCn = $parsed['issuer']['CN'] ?? '—';
        $san = $parsed['extensions']['subjectAltName'] ?? '';
        $from = Carbon::createFromTimestampUTC($parsed['validFrom_time_t']);
        $to = Carbon::createFromTimestampUTC($parsed['validTo_time_t']);
        $daysLeft = (int) floor(($to->timestamp - time()) / 86400);

        $details[trans('crudbooster.qlik_test_lbl_cert_subject')] = $subjectCn;
        $details[trans('crudbooster.qlik_test_lbl_cert_issuer')] = $issuerCn;
        $details['SAN'] = $san !== '' ? $san : '—';
        $details[trans('crudbooster.qlik_test_lbl_cert_valid_from')] = $from->toDateTimeString().' UTC';
        $details[trans('crudbooster.qlik_test_lbl_cert_valid_to')] = $to->toDateTimeString().' UTC ('.$daysLeft.' '.trans('crudbooster.qlik_test_days').')';
        $details[trans('crudbooster.qlik_test_lbl_cert_chain_len')] = (string) count($params['options']['ssl']['peer_certificate_chain'] ?? []);

        if ($daysLeft < 0) {
            $status = 'fail';
            $hints[] = trans('crudbooster.qlik_test_h_tls_expired');
        } elseif ($daysLeft < 14) {
            $status = 'warn';
            $hints[] = trans('crudbooster.qlik_test_h_tls_expiring', ['days' => $daysLeft]);
        }

        $names = [];
        foreach (explode(',', $san) as $entry) {
            $entry = trim($entry);
            if (stripos($entry, 'DNS:') === 0) {
                $names[] = substr($entry, 4);
            }
        }
        if (! $names && $subjectCn !== '—') {
            $names[] = $subjectCn;
        }
        $match = false;
        foreach ($names as $n) {
            if ($this->hostMatches($host, $n)) {
                $match = true;
                break;
            }
        }
        $details[trans('crudbooster.qlik_test_lbl_cert_hostname_match')] = $match ? trans('crudbooster.qlik_test_yes') : trans('crudbooster.qlik_test_no');
        if (! $match && ! filter_var($host, FILTER_VALIDATE_IP)) {
            $status = 'fail';
            $hints[] = trans('crudbooster.qlik_test_h_tls_mismatch', ['host' => $host]);
        }

        if (($parsed['subject'] ?? null) == ($parsed['issuer'] ?? null)) {
            $status = 'fail';
            $hints[] = trans('crudbooster.qlik_test_h_tls_selfsigned');
        }

        $this->addStep('tls', $status, $t0, $details, $hints);
    }

    /**
     * SaaS: POST {url}/login/jwt-session (Authorization Bearer + qlik-web-integration-id,
     * risposta attesa "OK"), poi GET {url}/api/v1/users/me sulla stessa sessione.
     */
    private function stepSaasLogin(array $conf, string $token): void
    {
        $base = rtrim($conf['url'], '/');
        $headers = [
            'qlik-web-integration-id: '.$conf['web_int_id'],
            'Authorization: Bearer '.$token,
        ];

        $curl = curl_init();

        $login = $this->httpStep($curl, 'login', 'POST', $base.'/login/jwt-session', $headers);
        $ok = $login['status'] === 'ok';

        if ($ok && trim($login['body']) !== 'OK') {
            $login['hints'][] = trans('crudbooster.qlik_test_h_saas_body_unexpected');
            $login['status'] = 'warn';
        }
        $this->addStep('login', $login['status'], $login['t0'], $login['details'], $login['hints']);

        if ($ok) {
            $me = $this->httpStep($curl, 'api', 'GET', $base.'/api/v1/users/me', $headers);
            // Qlik Cloud risponde 301 verso /api/v1/users/<id>: e' il
            // comportamento normale (l'app in produzione segue i redirect),
            // si segue una sola volta se resta sullo stesso host e sotto /api/.
            if (in_array($me['code'], [301, 302, 307, 308], true) && strpos($me['redirect'], $base.'/api/') === 0) {
                $first = $me;
                $me = $this->httpStep($curl, 'api', 'GET', $me['redirect'], $headers);
                $me['t0'] = $first['t0'];
                $me['details'] = $first['details'] + $me['details'];
            }
            $decoded = json_decode($me['body'], true);
            if ($me['status'] === 'ok' && is_array($decoded)) {
                foreach (['id', 'subject', 'name', 'email', 'status', 'tenantId'] as $f) {
                    if (isset($decoded[$f])) {
                        $me['details']['users/me.'.$f] = (string) $decoded[$f];
                    }
                }
                if (! isset($decoded['subject'])) {
                    $me['status'] = 'warn';
                    $me['hints'][] = trans('crudbooster.qlik_test_h_saas_no_subject');
                }
            } elseif ($me['status'] === 'ok') {
                $me['status'] = 'warn';
                $me['hints'][] = trans('crudbooster.qlik_test_h_not_json');
            }
            $this->addStep('api', $me['status'], $me['t0'], $me['details'], $me['hints']);
        }

        curl_close($curl);
    }

    /**
     * On-premise: GET {url}/{endpoint}/qrs/about con Bearer JWT, lo stesso
     * scambio che il browser fa in qlik_op_jwt_login.js.
     */
    private function stepOnPremAbout(array $conf, string $token): void
    {
        $xrf = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 16);
        $url = rtrim($conf['url'], '/').'/'.trim($conf['endpoint'], '/').'/qrs/about?xrfkey='.$xrf;
        $headers = [
            'Authorization: Bearer '.$token,
            'X-Qlik-Xrfkey: '.$xrf,
        ];

        $curl = curl_init();
        $r = $this->httpStep($curl, 'qrs', 'GET', $url, $headers);
        curl_close($curl);

        $decoded = json_decode($r['body'], true);
        if ($r['status'] === 'ok' && is_array($decoded)) {
            foreach (['buildVersion', 'buildDate', 'databaseProvider', 'nodeType', 'sharedPersistence', 'schemaPath'] as $f) {
                if (isset($decoded[$f])) {
                    $r['details']['qrs/about.'.$f] = is_scalar($decoded[$f]) ? (string) $decoded[$f] : json_encode($decoded[$f]);
                }
            }
        } elseif ($r['status'] === 'ok') {
            $r['status'] = 'warn';
            $r['hints'][] = trans('crudbooster.qlik_test_h_not_json');
        }
        if ($r['status'] === 'ok') {
            $r['hints'][] = trans('crudbooster.qlik_test_h_op_client_side');
            $r['status'] = 'warn';
        }

        $this->addStep('qrs', $r['status'], $r['t0'], $r['details'], $r['hints']);
    }

    // ------------------------------------------------------------------
    // HTTP
    // ------------------------------------------------------------------

    /**
     * Esegue una richiesta e restituisce tutti i dettagli. Non segue i
     * redirect (vanno visti, non nascosti), verifica il certificato come fa
     * l'app in produzione.
     *
     * @return array{status:string,body:string,details:array,hints:array,t0:float}
     */
    private function httpStep($curl, string $id, string $method, string $url, array $headers): array
    {
        $t0 = microtime(true);
        $respHeaders = [];

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_COOKIEFILE => '',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_HTTPHEADER => $headers,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$respHeaders) {
                $respHeaders[] = rtrim($line, "\r\n");

                return strlen($line);
            },
        ]);
        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, '');
        } else {
            curl_setopt($curl, CURLOPT_HTTPGET, true);
        }

        $body = curl_exec($curl);
        $errno = curl_errno($curl);
        $info = curl_getinfo($curl);

        $details = [];
        $hints = [];
        $details[trans('crudbooster.qlik_test_lbl_request')] = $method.' '.$url."\n".$this->maskRequestHeaders($headers);

        $status = 'ok';

        if ($body === false) {
            $status = 'fail';
            $details[trans('crudbooster.qlik_test_lbl_error')] = 'cURL ['.$errno.'] '.curl_error($curl);
            $hints[] = $this->curlHint($errno);
        }

        $code = (int) ($info['http_code'] ?? 0);
        if ($code > 0) {
            $details[trans('crudbooster.qlik_test_lbl_http_status')] = (string) $code;
        }
        if (! empty($info['primary_ip'])) {
            $details[trans('crudbooster.qlik_test_lbl_remote')] = $info['primary_ip'].':'.($info['primary_port'] ?? '');
        }
        $details[trans('crudbooster.qlik_test_lbl_timings')] = sprintf(
            'DNS %d ms, TCP %d ms, TLS %d ms, TTFB %d ms, total %d ms',
            ($info['namelookup_time'] ?? 0) * 1000,
            max(0, (($info['connect_time'] ?? 0) - ($info['namelookup_time'] ?? 0)) * 1000),
            max(0, (($info['appconnect_time'] ?? 0) - ($info['connect_time'] ?? 0)) * 1000),
            ($info['starttransfer_time'] ?? 0) * 1000,
            ($info['total_time'] ?? 0) * 1000
        );
        if (isset($info['ssl_verify_result']) && stripos($url, 'https://') === 0) {
            $details[trans('crudbooster.qlik_test_lbl_ssl_verify')] = $info['ssl_verify_result'].' (0 = OK)';
        }
        if ($respHeaders) {
            $details[trans('crudbooster.qlik_test_lbl_resp_headers')] = $this->maskResponseHeaders($respHeaders);
        }
        $bodyStr = is_string($body) ? $body : '';
        if ($bodyStr !== '') {
            $details[trans('crudbooster.qlik_test_lbl_resp_body')] = mb_strlen($bodyStr) > self::BODY_MAX
                ? mb_substr($bodyStr, 0, self::BODY_MAX).'… ['.trans('crudbooster.qlik_test_truncated', ['total' => strlen($bodyStr)]).']'
                : $bodyStr;
        } elseif ($body !== false) {
            $details[trans('crudbooster.qlik_test_lbl_resp_body')] = '('.trans('crudbooster.qlik_test_empty').')';
        }

        if ($body !== false) {
            if ($code >= 200 && $code < 300) {
                // ok
            } else {
                $status = 'fail';
                if (! empty($info['redirect_url'])) {
                    $details[trans('crudbooster.qlik_test_lbl_redirect')] = $info['redirect_url'];
                }
                $hints[] = $this->httpHint($code);
            }
        }

        return ['status' => $status, 'body' => $bodyStr, 'details' => $details, 'hints' => array_values(array_filter($hints)), 't0' => $t0, 'code' => $code, 'redirect' => (string) ($info['redirect_url'] ?? '')];
    }

    private function httpHint(int $code): string
    {
        if ($code === 400) {
            return trans('crudbooster.qlik_test_h_http_400');
        }
        if ($code === 401) {
            return trans('crudbooster.qlik_test_h_http_401');
        }
        if ($code === 403) {
            return trans('crudbooster.qlik_test_h_http_403');
        }
        if ($code === 404) {
            return trans('crudbooster.qlik_test_h_http_404');
        }
        if ($code >= 300 && $code < 400) {
            return trans('crudbooster.qlik_test_h_http_redirect');
        }
        if ($code >= 500) {
            return trans('crudbooster.qlik_test_h_http_5xx');
        }

        return trans('crudbooster.qlik_test_h_http_other', ['code' => $code]);
    }

    private function curlHint(int $errno): string
    {
        if ($errno === 6) {
            return trans('crudbooster.qlik_test_h_curl_resolve');
        }
        if ($errno === 7) {
            return trans('crudbooster.qlik_test_h_curl_connect');
        }
        if ($errno === 28) {
            return trans('crudbooster.qlik_test_h_curl_timeout');
        }
        if (in_array($errno, [35, 51, 58, 59, 60, 64, 77, 83, 90, 91], true)) {
            return trans('crudbooster.qlik_test_h_curl_ssl');
        }

        return trans('crudbooster.qlik_test_h_curl_other', ['errno' => $errno]);
    }

    // ------------------------------------------------------------------
    // Utilita'
    // ------------------------------------------------------------------

    private function mergeConf(array $input, $saved): array
    {
        $get = function (string $field, $default = '') use ($input, $saved) {
            if (array_key_exists($field, $input) && $input[$field] !== null && $input[$field] !== '') {
                return trim((string) $input[$field]);
            }
            if ($saved && isset($saved->{$field}) && $saved->{$field} !== '') {
                return trim((string) $saved->{$field});
            }

            return $default;
        };

        return [
            'type' => $get('type', 'On-Premise'),
            'auth' => $get('auth', 'JWT'),
            'url' => rtrim($get('url'), '/'),
            'port' => $get('port'),
            'endpoint' => trim($get('endpoint'), '/'),
            'keyid' => $get('keyid'),
            'issuer' => $get('issuer'),
            'web_int_id' => $get('web_int_id'),
            'private_key' => $get('private_key'),
        ];
    }

    /**
     * Il valore salvato per la chiave e' il path restituito da
     * CRUDBooster::uploadFile() ("/storage/uploads/<utente>/<mese>/<file>",
     * relativo alla public root), ma QlikHelper lo legge con
     * file_get_contents() cosi' com'e': si provano tutte le interpretazioni
     * plausibili e il rapporto dice quale ha funzionato.
     */
    private function keyPathCandidates(string $value): array
    {
        $candidates = [$value];
        $candidates[] = public_path($value);
        if (strpos($value, '/storage/') === 0) {
            $candidates[] = storage_path('app/public/'.substr($value, strlen('/storage/')));
            $candidates[] = storage_path('app/'.substr($value, strlen('/storage/')));
        }
        $candidates[] = base_path(ltrim($value, '/'));

        return array_values(array_unique($candidates));
    }

    private function hostMatches(string $host, string $pattern): bool
    {
        $host = strtolower($host);
        $pattern = strtolower($pattern);
        if ($host === $pattern) {
            return true;
        }
        if (strpos($pattern, '*.') === 0) {
            $suffix = substr($pattern, 1);
            $dot = strpos($host, '.');

            return $dot !== false && substr($host, $dot) === $suffix;
        }

        return false;
    }

    private function maskRequestHeaders(array $headers): string
    {
        $out = [];
        foreach ($headers as $h) {
            if (stripos($h, 'Authorization:') === 0) {
                $token = trim(substr($h, strlen('Authorization:')));
                $out[] = 'Authorization: '.substr($token, 0, 20).'… ('.strlen($token).' chars)';
            } else {
                $out[] = $h;
            }
        }

        return implode("\n", $out);
    }

    private function maskResponseHeaders(array $lines): string
    {
        $out = [];
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            if (preg_match('/^(set-cookie):\s*([^=;]+)=/i', $line, $m)) {
                $out[] = $m[1].': '.$m[2].'=***';
            } else {
                $out[] = $line;
            }
        }

        return implode("\n", $out);
    }

    private function b64url(string $s): string
    {
        return (string) base64_decode(strtr($s, '-_', '+/'));
    }

    private function pretty($data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '—';
    }

    private function addStep(string $id, string $status, float $t0, array $details, array $hints): void
    {
        if ($status === 'fail' && ! $this->failed) {
            $this->failed = true;
            $this->failedStep = $id;
        }
        $this->steps[] = [
            'id' => $id,
            'title' => trans('crudbooster.qlik_test_step_'.$id),
            'status' => $status,
            'duration_ms' => (int) round((microtime(true) - $t0) * 1000),
            'details' => $details,
            'hints' => array_values($hints),
        ];
    }

    private function report(): array
    {
        return [
            'ok' => ! $this->failed,
            'summary' => $this->failed
                ? trans('crudbooster.qlik_test_summary_fail', ['step' => trans('crudbooster.qlik_test_step_'.$this->failedStep)])
                : trans('crudbooster.qlik_test_summary_ok'),
            'steps' => $this->steps,
        ];
    }
}

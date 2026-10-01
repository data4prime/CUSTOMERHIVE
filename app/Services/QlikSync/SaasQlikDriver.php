<?php

namespace App\Services\QlikSync;

use WebSocket\Client as WebSocketClient;

/**
 * Qlik Cloud (SaaS).
 *
 * App: REST GET /api/v1/items?resourceType=app, dopo il login JWT
 * (POST /login/jwt-session, lo stesso scambio di QlikHelper::createUser).
 *
 * Fogli: Engine API via WebSocket (OpenDoc -> oggetto di sessione "sheet" ->
 * GetLayout). NON VERIFICATO contro un tenant reale: l'autenticazione del
 * WebSocket (cookie di sessione + qlik-csrf-token) e' la parte da confermare
 * con lo spike (docs/piano-qlik-sync-app-items.md). In caso di problemi il
 * run fallisce con un messaggio chiaro, senza scrivere nulla su Qlik.
 */
class SaasQlikDriver extends AbstractQlikDriver
{
    const MAX_PAGES = 200;

    /** @var resource|\CurlHandle|null sessione curl con il cookie del login */
    private $curl = null;

    /** @var bool */
    private $loggedIn = false;

    public function __destruct()
    {
        if ($this->curl !== null) {
            curl_close($this->curl);
            $this->curl = null;
        }
    }

    private function headers(): array
    {
        return [
            'qlik-web-integration-id: '.$this->conf->web_int_id,
            'Authorization: Bearer '.$this->token,
        ];
    }

    private function handle()
    {
        if ($this->curl === null) {
            $this->curl = curl_init();
        }

        return $this->curl;
    }

    /**
     * @throws QlikSyncException
     */
    private function login(): void
    {
        if ($this->loggedIn) {
            return;
        }

        $response = $this->request($this->handle(), 'POST', $this->baseUrl().'/login/jwt-session', $this->headers());
        if ($response['code'] < 200 || $response['code'] >= 300) {
            throw new QlikSyncException(trans('crudbooster.qlik_sync_err_login', ['code' => $response['code']]));
        }
        $this->loggedIn = true;
    }

    public function listApps(): array
    {
        $this->login();

        $apps = [];
        $url = $this->baseUrl().'/api/v1/items?resourceType=app&limit=100&sort=%2Bname'; // "sort=name" senza segno: HTTP 400 (COLLECTIONS-2-0)
        $pages = 0;

        while ($url !== null && $pages < self::MAX_PAGES) {
            $pages++;
            $decoded = $this->decode($this->request($this->handle(), 'GET', $url, $this->headers()), 'api/v1/items');

            foreach ($decoded['data'] ?? [] as $item) {
                $id = $item['resourceId'] ?? ($item['resourceAttributes']['id'] ?? null);
                if (! is_string($id) || $id === '') {
                    continue;
                }
                $name = $item['name'] ?? ($item['resourceAttributes']['name'] ?? '');
                $apps[] = ['id' => $id, 'name' => (string) $name];
            }

            $next = $decoded['links']['next']['href'] ?? null;
            $url = $this->sameHostUrl($next);
        }

        return $apps;
    }

    /**
     * Segue il link "next" solo se resta sullo stesso host del tenant
     * (evita di mandare il token altrove).
     */
    private function sameHostUrl($href): ?string
    {
        if (! is_string($href) || $href === '') {
            return null;
        }
        if ($href[0] === '/') {
            return $this->baseUrl().$href;
        }
        $host = parse_url($href, PHP_URL_HOST);
        $baseHost = parse_url($this->baseUrl(), PHP_URL_HOST);

        return ($host !== null && $host === $baseHost) ? $href : null;
    }

    public function listSheets(string $appId): array
    {
        if (! preg_match('/^[A-Za-z0-9\-]+$/', $appId)) {
            throw new QlikSyncException(trans('crudbooster.qlik_sync_err_bad_app_id'));
        }

        $this->login();

        $ws = $this->openEngine($appId);

        try {
            $id = 0;
            $open = $this->engineCall($ws, $id, -1, 'OpenDoc', [$appId]);
            $docHandle = $open['result']['qReturn']['qHandle'] ?? null;
            if (! is_int($docHandle)) {
                throw new QlikSyncException(trans('crudbooster.qlik_sync_err_engine_open'));
            }

            $created = $this->engineCall($ws, $id, $docHandle, 'CreateSessionObject', [[
                'qInfo' => ['qType' => 'SheetList'],
                'qAppObjectListDef' => [
                    'qType' => 'sheet',
                    'qData' => ['title' => '/qMetaDef/title', 'description' => '/qMetaDef/description'],
                ],
            ]]);
            $listHandle = $created['result']['qReturn']['qHandle'] ?? null;
            if (! is_int($listHandle)) {
                throw new QlikSyncException(trans('crudbooster.qlik_sync_err_engine_open'));
            }

            $layout = $this->engineCall($ws, $id, $listHandle, 'GetLayout', []);
            $items = $layout['result']['qLayout']['qAppObjectList']['qItems'] ?? [];
        } finally {
            try {
                $ws->close();
            } catch (\Throwable $e) {
                // chiusura best-effort
            }
        }

        $sheets = [];
        foreach ($items as $item) {
            $sheetId = $item['qInfo']['qId'] ?? null;
            if (! is_string($sheetId) || $sheetId === '') {
                continue;
            }
            // Qlik Cloud: titolo e descrizione veri stanno in qMeta, mentre qData arriva con
            // stringhe vuote (verificato sul tenant D4P): si prende il primo valore non vuoto.
            $sheets[] = [
                'id' => $sheetId,
                'title' => self::firstFilled([$item['qMeta']['title'] ?? null, $item['qData']['title'] ?? null], $sheetId),
                'description' => self::firstFilled([$item['qMeta']['description'] ?? null, $item['qData']['description'] ?? null], ''),
            ];
        }

        return $sheets;
    }

    /**
     * Primo valore stringa non vuoto (dopo trim) tra i candidati, altrimenti $default.
     */
    private static function firstFilled(array $candidates, string $default): string
    {
        foreach ($candidates as $value) {
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return $default;
    }

    /**
     * @throws QlikSyncException
     */
    private function openEngine(string $appId): WebSocketClient
    {
        $csrf = $this->csrfToken();

        $parts = parse_url($this->baseUrl());
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        $query = http_build_query(array_filter([
            'qlik-web-integration-id' => $this->conf->web_int_id,
            'qlik-csrf-token' => $csrf,
        ]));
        $url = 'wss://'.$host.$port.'/app/'.$appId.($query !== '' ? '?'.$query : '');

        $headers = [];
        $cookie = $this->cookieHeader();
        if ($cookie !== '') {
            $headers['Cookie'] = $cookie;
        }

        try {
            return new WebSocketClient($url, [
                'timeout' => self::TIMEOUT,
                'headers' => $headers,
                'context' => stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Qlik sync: WebSocket engine non raggiungibile (conf '.$this->conf->id.'): '.get_class($e));
            throw new QlikSyncException(trans('crudbooster.qlik_sync_err_engine_connect'));
        }
    }

    /**
     * Token CSRF che Qlik Cloud richiede per le connessioni WebSocket con
     * autenticazione a cookie (header di risposta qlik-csrf-token).
     */
    private function csrfToken(): string
    {
        $token = '';
        $curl = $this->handle();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->baseUrl().'/api/v1/csrf-token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => $this->headers(),
            CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$token) {
                if (stripos($line, 'qlik-csrf-token:') === 0) {
                    $token = trim(substr($line, strlen('qlik-csrf-token:')));
                }

                return strlen($line);
            },
        ]);
        curl_exec($curl);
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, null);

        return $token;
    }

    private function cookieHeader(): string
    {
        $pairs = [];
        foreach ((array) curl_getinfo($this->handle(), CURLINFO_COOKIELIST) as $line) {
            $line = preg_replace('/^#HttpOnly_/', '', (string) $line);
            $cols = explode("\t", $line);
            if (count($cols) >= 7) {
                $pairs[] = $cols[5].'='.$cols[6];
            }
        }

        return implode('; ', $pairs);
    }

    /**
     * Una chiamata JSON-RPC all'Engine: invia e attende la risposta con lo
     * stesso id, ignorando le notifiche del server.
     *
     * @throws QlikSyncException
     */
    private function engineCall(WebSocketClient $ws, int &$id, int $handle, string $method, array $params): array
    {
        $id++;
        $ws->send(json_encode([
            'jsonrpc' => '2.0',
            'id' => $id,
            'handle' => $handle,
            'method' => $method,
            'params' => $params,
        ]));

        for ($i = 0; $i < 50; $i++) {
            try {
                $raw = $ws->receive();
            } catch (\Throwable $e) {
                throw new QlikSyncException(trans('crudbooster.qlik_sync_err_engine_timeout'));
            }
            $msg = json_decode((string) $raw, true);
            if (! is_array($msg)) {
                continue;
            }
            if (($msg['method'] ?? null) === 'OnAuthenticationInformation' && ! empty($msg['params']['mustAuthenticate'])) {
                throw new QlikSyncException(trans('crudbooster.qlik_sync_err_engine_auth'));
            }
            if (($msg['id'] ?? null) === $id) {
                if (isset($msg['error'])) {
                    throw new QlikSyncException(trans('crudbooster.qlik_sync_err_engine_call', ['method' => $method, 'code' => $msg['error']['code'] ?? '?']));
                }

                return $msg;
            }
        }

        throw new QlikSyncException(trans('crudbooster.qlik_sync_err_engine_timeout'));
    }

    public function sheetUrl(string $appId, string $sheetId): string
    {
        return $this->baseUrl().'/sense/app/'.$appId.'/sheet/'.$sheetId.'/state/analysis';
    }

    public function sheetUrlNeedle(string $appId, string $sheetId): string
    {
        return '/app/'.$appId.'/sheet/'.$sheetId;
    }
}

<?php

namespace App\Services\QlikSync;

/**
 * Parte comune ai driver: richieste HTTP con gli stessi timeout del test di
 * connessione (connect 5s, totale 15s), TLS verificato, nessun redirect
 * seguito e nessun token nei messaggi d'errore.
 */
abstract class AbstractQlikDriver implements QlikDriver
{
    const CONNECT_TIMEOUT = 5;

    const TIMEOUT = 15;

    /** @var object riga di qlik_confs */
    protected $conf;

    /** @var string JWT dell'utente che sincronizza */
    protected $token;

    public function __construct($conf, string $token)
    {
        $this->conf = $conf;
        $this->token = $token;
    }

    protected function baseUrl(): string
    {
        return rtrim((string) $this->conf->url, '/');
    }

    /**
     * @param  resource|\CurlHandle  $curl
     * @return array{code:int, body:string}
     *
     * @throws QlikSyncException
     */
    protected function request($curl, string $method, string $url, array $headers): array
    {
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_COOKIEFILE => '', // cookie di sessione solo in memoria
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, '');
        } else {
            curl_setopt($curl, CURLOPT_HTTPGET, true);
        }

        $body = curl_exec($curl);
        if ($body === false) {
            throw new QlikSyncException(trans('crudbooster.qlik_sync_err_connection', ['error' => curl_error($curl)]));
        }

        return ['code' => (int) curl_getinfo($curl, CURLINFO_HTTP_CODE), 'body' => (string) $body];
    }

    /**
     * @return array decodifica JSON di una risposta 2xx
     *
     * @throws QlikSyncException
     */
    protected function decode(array $response, string $what): array
    {
        if ($response['code'] < 200 || $response['code'] >= 300) {
            throw new QlikSyncException(trans('crudbooster.qlik_sync_err_http', ['what' => $what, 'code' => $response['code']]));
        }
        $decoded = json_decode($response['body'], true);
        if (! is_array($decoded)) {
            throw new QlikSyncException(trans('crudbooster.qlik_sync_err_not_json', ['what' => $what]));
        }

        return $decoded;
    }
}

<?php

namespace App\Services\QlikSync;

/**
 * Qlik Sense on-premise: Repository Service (QRS) attraverso il virtual proxy
 * JWT, con lo stesso scambio del test di connessione
 * (GET {url}/{endpoint}/qrs/... con Bearer JWT e xrfkey).
 *
 * NON VERIFICATO contro un'installazione reale: dipende dai permessi che
 * l'utente Qlik dell'importatore ha sul QRS (docs/piano-qlik-sync-app-items.md,
 * spike). Se il QRS risponde 401/403 il run fallisce con un messaggio chiaro.
 */
class OnPremQlikDriver extends AbstractQlikDriver
{
    private function qrsBase(): string
    {
        $endpoint = trim((string) $this->conf->endpoint, '/');

        return $this->baseUrl().($endpoint !== '' ? '/'.$endpoint : '');
    }

    /**
     * @return array lista di oggetti QRS
     *
     * @throws QlikSyncException
     */
    private function qrsGet(string $path, array $query = []): array
    {
        $xrf = substr(bin2hex(random_bytes(8)), 0, 16);
        $query['xrfkey'] = $xrf;

        $url = $this->qrsBase().'/qrs/'.$path.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $headers = [
            'Authorization: Bearer '.$this->token,
            'X-Qlik-Xrfkey: '.$xrf,
        ];

        $curl = curl_init();
        try {
            $response = $this->request($curl, 'GET', $url, $headers);
        } finally {
            curl_close($curl);
        }

        return $this->decode($response, 'qrs/'.$path);
    }

    public function listApps(): array
    {
        $apps = [];
        foreach ($this->qrsGet('app') as $app) {
            $id = $app['id'] ?? null;
            if (! is_string($id) || $id === '') {
                continue;
            }
            $apps[] = ['id' => $id, 'name' => (string) ($app['name'] ?? '')];
        }

        return $apps;
    }

    public function listSheets(string $appId): array
    {
        // L'id finisce dentro un filtro QRS: solo caratteri di un GUID.
        if (! preg_match('/^[A-Za-z0-9\-]+$/', $appId)) {
            throw new QlikSyncException(trans('crudbooster.qlik_sync_err_bad_app_id'));
        }

        $objects = $this->qrsGet('app/object/full', [
            'filter' => "app.id eq {$appId} and objectType eq 'sheet'",
        ]);

        $sheets = [];
        foreach ($objects as $object) {
            $id = $object['engineObjectId'] ?? ($object['id'] ?? null);
            if (! is_string($id) || $id === '') {
                continue;
            }
            $sheets[] = [
                'id' => $id,
                'title' => (string) ($object['name'] ?? $id),
                'description' => (string) ($object['description'] ?? ''),
            ];
        }

        return $sheets;
    }

    public function sheetUrl(string $appId, string $sheetId): string
    {
        return $this->qrsBase().'/sense/app/'.$appId.'/sheet/'.$sheetId.'/state/analysis';
    }

    public function sheetUrlNeedle(string $appId, string $sheetId): string
    {
        return '/app/'.$appId.'/sheet/'.$sheetId;
    }
}

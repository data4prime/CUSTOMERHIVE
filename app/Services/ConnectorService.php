<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

use LaravelReady\LicenseConnector\Traits\CacheKeys;
use LaravelReady\LicenseConnector\Exceptions\AuthException;

class ConnectorService
{
    use CacheKeys;

    public $license;

    private $licenseKey;
    private $accessToken;

    public function __construct(string $licenseKey)
    {
        $this->licenseKey = $licenseKey;

        $this->accessToken = $this->getAccessToken($licenseKey);
    }

    /**
     * Check license status
     *
     * @param string $licenseKey
     * @param array $data
     *
     * @return boolean
     */
    public function validateLicense(array $data = []): bool
    {
        if ($this->accessToken) {
            $url = Config::get('license-connector.license_server_url') . '/api/api-license/license-server/license';

            $response = Http::withHeaders([
                'x-host' => Config::get('app.url'),
                'x-host-name' => Config::get('app.name'),
                'Authorization' => "Bearer {$this->accessToken}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($url, $data);
            //dd($response);

            if ($response->ok()) {
                $license = $response->json();

                //dd($license);

                $this->license = $license;

                if (isset($data['tenants_number'])) {
                    return $license && $license['status'] == 'active' && $license['tenants_number'] >= $data['tenants_number'];
                }

                return $license && $license['status'] == 'active';
            }
        }

        return false;
    }

    public function checkLicense(array $data = []): bool
    {
        if ($this->accessToken) {
            $url = Config::get('license-connector.license_server_url') . '/api/api-license/license-server/license';

            $response = Http::withHeaders([
                'x-host' => Config::get('app.url'),
                'x-host-name' => Config::get('app.name'),
                'Authorization' => "Bearer {$this->accessToken}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($url, $data);
            //dd($response);

            if ($response->ok()) {
                $license = $response->json();
                //dd($license);   

                return $license->tenants_number >= $data['tenants_number'];
            }
        }

        return false;
    }

    /**
     * Get access token for the given domain
     *
     * @param string $licenseKey
     *
     * @return string
     */
    private function getAccessToken(string $licenseKey): null | string
    {
        $accessTokenCacheKey = $this->getAccessTokenKey($licenseKey);

        $accessToken = Cache::get($accessTokenCacheKey, null);

        if ($accessToken) {
            return $accessToken;
        }

        $url = Config::get('license-connector.license_server_url') . '/api/api-license/license-server/auth/login';

        $response = Http::withHeaders([
            'x-host' => Config::get('app.url'),
            'x-host-name' => Config::get('app.name'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($url, [
            'license_key' => $licenseKey,
            'ls_domain' => $_SERVER['HTTP_HOST'],
        ]);

        $data = $response->json();

        if ($response->ok()) {
            if ($data['status'] === true) {
                if (!empty($data['access_token'])) {
                    $accessToken = $data['access_token'];

                    Cache::put($accessTokenCacheKey, $accessToken, now()->addMinutes(60));

                    return $accessToken;
                } else {
                    throw new AuthException($data['message']);
                }
            }
        }

        throw new AuthException($data['message']);
    }
}
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

use LaravelReady\LicenseConnector\Traits\CacheKeys;
use LaravelReady\LicenseConnector\Exceptions\AuthException;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Storage;

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


            try {
                $response = Http::withHeaders([
                    'x-host' => Config::get('app.url'),
                    'x-host-name' => Config::get('app.name'),
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(5)->post($url, $data);
                $license = $response->json();
            } catch (ConnectionException | RequestException $e) {
                Log::error("License server timeout or request failed: " . $e->getMessage());

    
                $license = $this->getLicenseFromFile();
            } catch (\Exception $e) {
                Log::error("Unexpected license validation error: " . $e->getMessage());
            }
            //dd($response);

            if ($license) {
                //$license = $response->json();
                Storage::disk('license')->put('license.json', json_encode($license));

                dd($license);

                $this->license = $license;

                $ret = $license && $license['status'] == 'active';

                if (isset($data['tenants_number'])) {
                    $ret = $ret && $license['tenants_number'] >= $data['tenants_number'];
                }

                if (isset($data['clients_number'])) {
                    $ret = $ret && $license['clients_number'] >= $data['clients_number'];
                }

                if (isset($data['path'])) {
                    $ret = $ret && $license['path'] == $data['path'];
                } else {
                    $ret = $ret && $license['path'] == env('APP_PATH'); //default path
                }

                if (isset($data['domain'])) {
                    $ret = $ret && $license['domain'] == $data['domain'];
                } else {

                    //get domain from $_SERVER['HTTP_HOST']
                    $domain = $_SERVER['HTTP_HOST'];

                    //if domain has a subdomain, get the subdomain
                    if (strpos($domain, '.') !== false) {
                        $domain = explode('.', $domain);
                        $domain = $domain[0];
                    }


                    $ret = $ret && $license['domain'] == $domain;
                }

                return $ret;
            }

            //delete record from licenses table
            DB::table('license')->where('license_key', $this->licenseKey)->delete();


        }

        return false;
    }

    public function getLicense(array $data = []): array | bool
    {
        if ($this->accessToken) {
            $url = Config::get('license-connector.license_server_url') . '/api/api-license/license-server/license';

            try {
                $response = Http::withHeaders([
                    'x-host' => Config::get('app.url'),
                    'x-host-name' => Config::get('app.name'),
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(5)->post($url, $data);

                $license = $response->json();


                if ($license) {
                    Storage::disk('license')->put('license.json', json_encode($license));
                    return $license;
                }

            } catch (ConnectionException | RequestException $e) {
                Log::error("License server timeout or request failed: " . $e->getMessage());

    
                return $this->getLicenseFromFile();
            } catch (\Exception $e) {
                Log::error("Unexpected license validation error: " . $e->getMessage());
            }
        }

        return false;
    }

    protected function getLicenseFromFile(): bool
    {

        Log::info("getLicenseFromFile");
        $path = storage_path('app/license.json');

        if (!file_exists($path)) {
            Log::warning("License fallback file not found at: {$path}");
            return false;
        }

        try {
            $json = file_get_contents($path);
            $license = json_decode($json, true);



            // Aggiungi logica di validazione aggiuntiva qui se necessario
            Log::info("License validated using fallback file.");
            return $license;

        } catch (\Exception $e) {
            Log::error("Error reading fallback license file: " . $e->getMessage());
        }

        return false;
    }

    public function checkLicense(array $data = []): bool
    {
        if ($this->accessToken) {
            $url = Config::get('license-connector.license_server_url') . '/api/api-license/license-server/license';

            try {
                $response = Http::withHeaders([
                    'x-host' => Config::get('app.url'),
                    'x-host-name' => Config::get('app.name'),
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(5)->post($url, $data);
            } catch (ConnectionException | RequestException $e) {
                Log::error("License server timeout or request failed: " . $e->getMessage());

    
                $response =  $this->getLicenseFromFile();
            } catch (\Exception $e) {
                Log::error("Unexpected license validation error: " . $e->getMessage());
            }
            //dd($response);

            if ($response) {
                $license = $response->json();
                Storage::disk('license')->put('license.json', json_encode($license));
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

        try {
            $response = Http::withHeaders([
                'x-host' => Config::get('app.url'),
                'x-host-name' => Config::get('app.name'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(5)->post($url, [
                'license_key' => $licenseKey,
                'ls_domain' => $_SERVER['HTTP_HOST'],
            ]);
        } catch (ConnectionException | RequestException $e) {
            Log::error("License server timeout or request failed: " . $e->getMessage());

    
        } catch (\Exception $e) {
            Log::error("Unexpected license validation error: " . $e->getMessage());
        }

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
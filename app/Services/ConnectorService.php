<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

use App\Exceptions\AuthException;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Log;

class ConnectorService
{
    public $license;

    private $licenseKey;
    private $accessToken;

    public function __construct(string $licenseKey, bool $forceTokenRefresh = false)
    {
        $this->licenseKey = $licenseKey;

        $this->accessToken = $this->getAccessToken($licenseKey, $forceTokenRefresh);
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
        $license = $this->getLicenseFromFile();

        if (!$license) {
            // Nessuna licenza locale valida: la entry per questa chiave non
            // ha piu' senso, ripulisce lo stato invece di lasciarla orfana.
            DB::table('license')->where('license_key', $this->licenseKey)->delete();

            return false;
        }

        $this->license = $license;
        Log::info($license);

        return $license['status'] == 'active'
            && $this->licenseMeetsQuota($license, $data, 'tenants_number')
            && $this->licenseMeetsQuota($license, $data, 'clients_number')
            && $this->licenseMatchesPath($license, $data)
            && $this->licenseMatchesDomain($license, $data);
    }

    private function licenseMeetsQuota(array $license, array $data, string $key): bool
    {
        if (!isset($data[$key])) {
            return true;
        }

        return $license[$key] >= $data[$key];
    }

    private function licenseMatchesPath(array $license, array $data): bool
    {
        $path = $data['path'] ?? env('APP_PATH');

        return $license['path'] == $path;
    }

    /**
     * Nota: se $data['domain'] e' presente ma non corrisponde esattamente al
     * dominio salvato nella licenza, NON si fallisce subito - si ricade sul
     * confronto con env('APP_DOMAIN') (con lo stesso taglio sul primo punto
     * usato altrove). Serve perche' i chiamanti (LicenseHelper) passano
     * spesso env('APP_DOMAIN') grezzo come $data['domain'], che su domini
     * con sottodominio (es. "dev.thecustomerhive.com") non combacia mai col
     * dominio salvato in licenza (gia' tagliato a "dev"); il fallback e' ciò
     * che fa effettivamente combaciare in quel caso. Comportamento esistente
     * preservato as-is, non e' un bug introdotto da questo refactoring.
     */
    private function licenseMatchesDomain(array $license, array $data): bool
    {
        if (isset($data['domain']) && $license['domain'] == $data['domain']) {
            return true;
        }

        $domain = env('APP_DOMAIN');

        if (strpos($domain, '.') !== false) {
            $domain = explode('.', $domain)[0];
        }

        return $license['domain'] == $domain;
    }

    public function writeLicense(array $data = []): array | bool
    {
        if ($this->accessToken) {
            $url = Config::get('license-connector.license_server_url') . '/api/api-license/license-server/license';
            Log::info(json_encode($url));

            try {
                $response = Http::withHeaders([
                    'x-host' => Config::get('app.url'),
                    'x-host-name' => Config::get('app.name'),
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(5)->post($url, $data);

                $body = $response->json();
                $license = $body['data'] ?? null;
                Log::info($body);


                if ($license && isset($license['id'])) {
                    Storage::disk('license')->put('license.json', json_encode($license));
                    Log::info(json_encode($license));
                    return $license;
                }

                // Il server e' stato raggiunto ed ha risposto, ma senza una
                // licenza valida (es. cancellata lato server): il file
                // locale stale non va lasciato intatto come nel caso
                // "server down" (catch sotto) - va invalidato, cosi'
                // getLicenseFromFile()/validateLicense() non si fidano piu'
                // di dati ormai inesistenti sul server.
                Storage::disk('license')->delete('license.json');

            } catch (ConnectionException | RequestException $e) {
                Log::error("License server timeout or request failed: " . $e->getMessage());
            } catch (\Exception $e) {
                Log::error("Unexpected license validation error: " . $e->getMessage());
            }
        }

        return false;
    }

    public function getLicense(array $data = []): array | bool
    {
        $license = $this->getLicenseFromFile();
        if ($license) {
            return $license;
        }
        return false;
    }

    protected function getLicenseFromFile(): ?array
    {

        Log::info(json_encode("getLicenseFromFile"));
        $path = storage_path('app/license.json');

        if (!file_exists($path)) {
            Log::warning("License fallback file not found at: {$path}");
            $customData = ['license_key' => $this->licenseKey , 'domain' => env('APP_DOMAIN')];
            $this->writeLicense($customData);
        }

        // Nessun file locale disponibile (prima attivazione, o scrittura fallita
        // sopra): nessuna licenza valida ancora, non un errore - il chiamante
        // (getAccessToken/validateLicense) gestisce già il caso "nessuna licenza".
        if (!file_exists($path)) {
            return null;
        }

        try {
            $json = file_get_contents($path);
            $license = json_decode($json, true);

            if (!is_array($license)) {
                Log::error("License fallback file is not valid JSON: {$path}");
                return null;
            }

            // Aggiungi logica di validazione aggiuntiva qui se necessario
            Log::info(json_encode("License validated using fallback file."));
            Log::info($license);
            return $license;

        } catch (\Exception $e) {
            Log::error("Error reading fallback license file: " . $e->getMessage());
        }

        return null;
    }

    public function checkLicense(array $data = []): bool
    {
        $license = $this->getLicenseFromFile();
        if ($license) {
            Storage::disk('license')->put('license.json', json_encode($license));
            return $license->tenants_number >= $data['tenants_number'];
        }
        return false;
    }

    /**
     * Get access token for the given domain
     *
     * @param string $licenseKey
     * @param bool $forceTokenRefresh Se true, ignora il token in cache e
     *   rifà l'autenticazione col license server. Serve al gate di login
     *   (LicenseHelper::canLicenseLogin()): senza questo, un token ancora
     *   in cache (fino a 60 minuti) evita del tutto la chiamata di auth che
     *   scoprirebbe che licenza/utente sono stati eliminati lato server, e
     *   il login continuerebbe a fidarsi del license.json locale stale.
     *
     * @return string
     */
    private function getAccessToken(string $licenseKey, bool $forceTokenRefresh = false): null | string
    {
        $accessTokenCacheKey = $this->getAccessTokenKey($licenseKey);

        $accessToken = $forceTokenRefresh ? null : Cache::get($accessTokenCacheKey, null);

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
                'ls_domain' => env('APP_DOMAIN'),
            ]);
        } catch (ConnectionException | RequestException $e) {
            Log::error("License server timeout or request failed: " . $e->getMessage());

            // Server di licenza irraggiungibile: nessun token, ma non e' un
            // errore fatale - i chiamanti (writeLicense/getLicenseFromFile)
            // gestiscono gia' un accessToken nullo senza fare la richiesta.
            return null;
        } catch (\Exception $e) {
            Log::error("Unexpected license validation error: " . $e->getMessage());

            return null;
        }

        $data = $response->json();

        if ($response->ok() && is_array($data) && ($data['success'] ?? false) === true) {
            if (!empty($data['data']['access_token'])) {
                $accessToken = $data['data']['access_token'];

                Cache::put($accessTokenCacheKey, $accessToken, now()->addMinutes(60));

                return $accessToken;
            }
        }

        // Risposta del license server non nella busta {success, message,
        // data} attesa (formato inatteso, non solo "success:false") - non
        // si assume piu' che $data['message'] esista.
        $message = is_array($data) ? ($data['message'] ?? 'Risposta del license server non valida') : 'Risposta del license server non valida (formato inatteso)';

        throw new AuthException($message);
    }

    /**
     * Prima fornito da LaravelReady\LicenseConnector\Traits\CacheKeys,
     * inlineato per togliere la dipendenza dal pacchetto (era l'unico
     * metodo del trait usato in tutto il progetto).
     */
    private function getAccessTokenKey(string $licenseKey): string
    {
        return "license-connector:access-token-{$licenseKey}";
    }
}
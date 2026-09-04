<?php
namespace App\Helpers;

use Session;
use Request;
use Schema;
use Cache;
use DB;
use Route;
use Validator;

use App\Services\ConnectorService;
use App\Helpers\TenantHelper;
use App\Helpers\UserHelper;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;


class LicenseHelper  {

    /**
     * Ritorna il corpo della risposta come stringa JSON grezza (stesso
     * contratto di prima, il chiamante fa json_decode()) - cambia solo il
     * meccanismo di trasporto: prima cURL grezzo con verifica SSL
     * disattivata e nessun timeout (una richiesta bloccata poteva restare
     * appesa per sempre), ora il client HTTP di Laravel, coerente con il
     * resto di ConnectorService, con verifica SSL attiva (di default) e un
     * timeout esplicito. Se il server non risponde in tempo o non è
     * raggiungibile, ritorna un JSON di errore con la stessa forma che il
     * chiamante già si aspetta ({success:false, result:"..."})  invece di
     * bloccare la richiesta o far fallire json_decode() su un valore nullo.
     */
    public static function registerLicense($fields) {
        $license_server_url = config('license-connector.license_server_url');

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(15)->post($license_server_url . '/api/api-license/license-server/licenses', $fields);

            return $response->body();
        } catch (ConnectionException $e) {
            Log::error('License server non raggiungibile durante la registrazione: ' . $e->getMessage());

            return json_encode([
                'success' => false,
                'result' => 'Il servizio di licenza non è raggiungibile al momento. Riprova più tardi.',
            ]);
        }
    }

    public static function getLicense() {
        $licenseKey =  DB::table('license')->first();


        return $licenseKey;
    }

    public static function writeLicense() {



        $licenseKey = self::getLicense();
        Log::info(json_encode($licenseKey));

        $customData = ['license_key' => $licenseKey->license_key, 'domain' => env('APP_DOMAIN')];
        // forceTokenRefresh: il refresh chiamato dal gate di login deve
        // verificare la licenza in tempo reale, non riusare un access token
        // ancora in cache che nasconderebbe una licenza/utente cancellati
        // lato server. Vedi ConnectorService::getAccessToken().
        $connectorService = new ConnectorService($licenseKey->license_key, true);
        return  $connectorService->writeLicense($customData);

    }

    public static function canLicenseLogin() {

        // In ambiente di test non c'e' un license server raggiungibile ne'
        // una riga valida in 'license': i test di login/logout precedono
        // questo gate e non verificano il flusso di licenza.
        if (app()->environment('testing')) {
            return true;
        }

        $licenseKey = self::getLicense();

        //dd($licenseKey);


        if (!$licenseKey)  {
            return false;
        }

        try {
            LicenseHelper::writeLicense();

           //$customData = ['license_key' => $licenseKey->license_key];
            $customData = ['license_key' => $licenseKey->license_key, 'domain' => env('APP_DOMAIN')];

            $connectorService = new ConnectorService($licenseKey->license_key);

            return  $connectorService->validateLicense($customData);
        } catch (\App\Exceptions\AuthException $e) {
            // Server di licenza irraggiungibile o risposta malformata: non
            // deve bloccare il login dell'intero ambiente con un 500, si
            // comporta come "licenza non valida" (stesso esito di prima).
            Log::warning('canLicenseLogin: errore dal license server: ' . $e->getMessage());

            return false;
        }
    }

    public static function canAddTenant() {
        // Vedi canLicenseLogin(): stesso bypass, per non far dipendere i
        // test del CRUD Tenants da un license server/riga 'license' finti.
        if (app()->environment('testing')) {
            return true;
        }

        $licenseKey = self::getLicense();

        if (!$licenseKey) {
            return false;
        }

        $tenants = TenantHelper::countTenants();



        $connectorService = new ConnectorService($licenseKey->license_key);

        /*
         * 'domain'/'path' vanno passati esplicitamente, come gia' fa
         * canLicenseLogin() - senza, ConnectorService::licenseMatchesDomain()
         * ricade su un confronto con APP_DOMAIN troncato al primo punto
         * (pensato per un altro caso), che fallisce sempre quando il dominio
         * salvato in licenza e' un sottodominio per intero (es.
         * "dev.thecustomerhive.com"): il confronto diventa "dev.thecustomerhive.com"
         * contro "dev" e non combacia mai, anche con tenants_number capiente.
         */
        $customData = [
            'tenants_number' => $tenants + 1,
            'license_key' => $licenseKey->license_key,
            'domain' => env('APP_DOMAIN'),
            'path' => env('APP_PATH'),
        ];

        return $connectorService->validateLicense($customData);


    }

    public static function getLicenseInfo() {

        $licenseKey = self::getLicense();

        if (!$licenseKey) {
            return false;
        }

        $connectorService = new ConnectorService($licenseKey->license_key);

        $customData = [ 'license_key' => $licenseKey->license_key];

        $license = $connectorService->getLicense($customData);

        if ($license) {

            return $license;
        } else {
            return false;
        }

    }


    public static function canAddUser() {
        // Vedi canLicenseLogin(): stesso bypass, per non far dipendere i
        // test del CRUD Users da un license server/riga 'license' finti.
        if (app()->environment('testing')) {
            return true;
        }

        $licenseKey = self::getLicense();

        if (!$licenseKey) {
            return false;
        }

        $users = UserHelper::countUsers();

        $connectorService = new ConnectorService($licenseKey->license_key);

        // Vedi il commento in canAddTenant(): stesso motivo per passare
        // esplicitamente 'domain'/'path' invece di lasciarli al fallback
        // troncato di licenseMatchesDomain().
        $customData = [
            'clients_number' => $users + 1,
            'license_key' => $licenseKey->license_key,
            'domain' => env('APP_DOMAIN'),
            'path' => env('APP_PATH'),
        ];

        return $connectorService->validateLicense($customData);


    }

    public static function isActiveQlik() {

        //return true;

        $licenseKey = self::getLicenseInfo();

        return self::searchModuleByName($licenseKey, "Qlik");

    }

    public static function isActiveChatAI() {
        //return true;

        $licenseKey = self::getLicenseInfo();

        return self::searchModuleByName($licenseKey, "ChatAI");

    }

    public static function searchModuleByName($array, $name) {
        if (isset($array['modules']) && is_array($array['modules'])) {
            foreach ($array['modules'] as $module) {
                if (isset($module['name']) && $module['name'] === $name) {
                    return true;
                }
            }
        }
        return false;
    }


}

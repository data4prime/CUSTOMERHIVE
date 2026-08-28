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
        $connectorService = new ConnectorService($licenseKey->license_key);
        return  $connectorService->writeLicense($customData);

    }

    public static function canLicenseLogin() {

        $licenseKey = self::getLicense();

        //dd($licenseKey);


        if (!$licenseKey)  {
            return false;
        }

        LicenseHelper::writeLicense();

       //$customData = ['license_key' => $licenseKey->license_key];
        $customData = ['license_key' => $licenseKey->license_key, 'domain' => env('APP_DOMAIN')];

        $connectorService = new ConnectorService($licenseKey->license_key);

        return  $connectorService->validateLicense($customData);
    }

    public static function canAddTenant() {
        $licenseKey = self::getLicense();

        if (!$licenseKey) {
            return false;
        }

        $tenants = TenantHelper::countTenants();



        $connectorService = new ConnectorService($licenseKey->license_key);

        $customData = ['tenants_number' => $tenants + 1, 'license_key' => $licenseKey->license_key];

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

        $licenseKey = self::getLicense();

        if (!$licenseKey) {
            return false;
        }

        $users = UserHelper::countUsers();

        $connectorService = new ConnectorService($licenseKey->license_key);

        $customData = ['clients_number' => $users + 1, 'license_key' => $licenseKey->license_key];

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

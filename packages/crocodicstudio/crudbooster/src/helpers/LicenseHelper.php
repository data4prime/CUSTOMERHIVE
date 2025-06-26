<?php
namespace crocodicstudio\crudbooster\helpers;

use Session;
use Request;
use Schema;
use Cache;
use DB;
use Route;
use Validator;

use App\Services\ConnectorService;


class LicenseHelper  {

    public static function registerLicense($fields) {
        $license_server_url = config('license-connector.license_server_url');
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $license_server_url.'/api/api-license/license-server/licenses',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
        ),

        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POSTFIELDS => json_encode($fields),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public static function getLicense() {
        $licenseKey =  DB::table('license')->first();


        return $licenseKey;
    }

    public static function canLicenseLogin() {

        return true;


        $licenseKey = self::getLicense();

                
        if (!$licenseKey)  {
            return false;
        }

        $customData = ['license_key' => $licenseKey->license_key];

        $connectorService = new ConnectorService($licenseKey->license_key);

        return  $connectorService->validateLicense($customData);
    }

    public static function canAddTenant() {
        return true;
        $licenseKey = self::getLicense();

        $tenants = TenantHelper::countTenants();
  

        

        $connectorService = new ConnectorService($licenseKey->license_key);

        $customData = ['tenants_number' => $tenants + 1, 'license_key' => $licenseKey->license_key];

        return $connectorService->validateLicense($customData);

        
    }

    public static function getLicenseInfo() {
        
        return false;
        $licenseKey = self::getLicense();

          

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

        return true;
        $licenseKey = self::getLicense();

        $users = UserHelper::countUsers();

        $connectorService = new ConnectorService($licenseKey->license_key);

        $customData = ['clients_number' => $users + 1, 'license_key' => $licenseKey->license_key];

        return $connectorService->validateLicense($customData);

        
    }

    public static function isActiveQlik() {

        return true;

        $licenseKey = self::getLicenseInfo();

        return self::searchModuleByName($licenseKey, "Qlik");

    }

    public static function isActiveChatAI() {
        return true;

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

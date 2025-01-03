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

    public static function getLicense() {
        $licenseKey =  DB::table('license')->first();


        return $licenseKey;
    }

    public static function canLicenseLogin() {

        //return true;


        $licenseKey = self::getLicense();

                
        if (!$licenseKey)  {
            return false;
        }

        $customData = ['license_key' => $licenseKey->license_key];

        $connectorService = new ConnectorService($licenseKey->license_key);

        return  $connectorService->validateLicense($customData);
    }

    public static function canAddTenant() {
        //return true;
        $licenseKey = self::getLicense();

        $tenants = TenantHelper::countTenants();
  

        

        $connectorService = new ConnectorService($licenseKey->license_key);

        $customData = ['tenants_number' => $tenants + 1, 'license_key' => $licenseKey->license_key];

        return $connectorService->validateLicense($customData);

        
    }

    public static function getLicenseInfo() {
        
        //return false;
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

        //return true;
        $licenseKey = self::getLicense();

        $users = UserHelper::countUsers();

        $connectorService = new ConnectorService($licenseKey->license_key);

        $customData = ['clients_number' => $users + 1, 'license_key' => $licenseKey->license_key];

        return $connectorService->validateLicense($customData);

        
    }

    public static function isActiveQlik() {

        $licenseKey = self::getLicenseInfo();

        return self::searchModuleByName($licenseKey, "Qlik");

    }

    public static function isActiveChatAI() {

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

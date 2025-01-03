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
            file_put_contents(__DIR__."/license.txt", $license ."\n", FILE_APPEND);
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

        //return true;
        $licenseKey = self::getLicense();


        $connectorService = new ConnectorService($licenseKey->license_key);

        $customData = ['is_module_active' => 'Qlik'];

        return $connectorService->validateLicense($customData);

        
    }


}

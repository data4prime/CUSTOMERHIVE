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
        if (!$licenseKey)  {
            return redirect()->route('getLicenseScreen');
        }

        return $licenseKey;
    }

    public static function canLicenseLogin() {
        $licenseKey = self::getLicense();

        $customData = ['license_key' => $licenseKey->license_key];

        $connectorService = new ConnectorService($licenseKey->license_key);

        return  $connectorService->validateLicense($customData);
    }

    public static function canAddTenant() {
        $licenseKey = self::getLicense();
  

        

        $connectorService = new ConnectorService($licenseKey->license_key);

        $customData = ['tenants_number' => 3];

        return $connectorService->validateLicense($customData);

        
    }


}

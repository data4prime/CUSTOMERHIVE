<?php
namespace crocodicstudio\crudbooster\helpers;

use \App\Tenant;
use \App\Modules;
use \App\ModuleTenants;
use \App\Role;
use DB;

class TenantHelper  {

  /**
  * Find tenant's login path
  *
  * @param int tenant's id
  *
  * @return string tenant's login page URI
  */
  public static function loginPath($tenant_id){
    $admin_path = CRUDBooster::adminpath();
    $tenant_domain_name = Tenant::find($tenant_id)->domain_name;
    $result = preg_replace('/\/\/(\w*)\./','//'.$tenant_domain_name.'.',$admin_path);
    return $result.'/login';
  }

  /**
  *	Encode a tenant name
  *
  * @param string name as user input
  *
  * @return string cleaned name
  */
  public static function domain_name_encode($name) {
    //lower case only
    $name = strtolower(trim($name));
    //delete spaces
    $name = preg_replace('/\s+/', '',$name);
    //lettere accentate
    $name = str_replace('à', 'a',$name);
    $name = str_replace('è', 'e',$name);
    $name = str_replace('é', 'e',$name);
    $name = str_replace('ì', 'i',$name);
    $name = str_replace('ò', 'o',$name);
    $name = str_replace('ù', 'u',$name);
    //remove all special characters
    $name = preg_replace('/[^A-Za-z0-9]/', '', $name);
    //if domain name $name already exists..
    $name = TenantHelper::unique_domain_name($name);
    return $name;
  }

  /**
  * if a tenant with domain name $name already exists,
  * then add a digit at the end of the domain name
  */
  public static function unique_domain_name($name){
    if(Tenant::where('domain_name',$name)->count()>0){
      //.. then ensure unique domain name
      preg_match('/(\d+)$/', $name, $matches);
      if(empty($matches[1])){
        $name .= '1';
      }
      else{
        $new_number = $matches[0]+1;
        $name_without_number = substr($name,0,strlen($matches[0])*-1);
        $name = $name_without_number.$new_number;
      }
      //recursive call
      $name = TenantHelper::unique_domain_name($name);
    }
    else{
      return $name;
    }
  }

  /**
  * Check if a tenant has all modules enabled
  *
  * @param int tenant's id
  *
  * @return boolean true if the module is enabled,
  * @return boolean false otherwise
  */
  public static function is_bulk_enabled($tenant_id)
  {
    $modules_enabled_count = ModuleTenants::where('tenant_id',$tenant_id)->count();
    $modules_count = ModuleHelper::getEditableModules()->count();

    if($modules_enabled_count == $modules_count)
    {
      return true;
    }
    return false;
  }

  public static function countTenants()
  {
    $tenants = DB::table('tenants')->where('deleted_at', null)->count();
    return $tenants;
  }

  public static function getTenantAdmins($tenant_id) {
    $users_tenants = DB::table('cms_users')
      ->where('tenant', $tenant_id)
      ->where('id_cms_privileges', 2)
      ->where('status', 'Active')
      ->get();

    $ret = [];

    foreach ($users_tenants as $user) {
      if (Role::find($user->id_cms_privileges)->is_tenantadmin == 1) {
        $ret [] = $user;
      }
    }

    return $ret;
  }



}

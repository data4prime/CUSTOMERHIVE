<?php
namespace App\Helpers;

use \App\Models\Tenant;
use \App\Models\ModuleTenants;
use \App\Models\Role;
use DB;
use App\Helpers\CRUDBooster;
use App\Helpers\ModuleHelper;

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
      // Il valore ricalcolato veniva scartato: mancava questo return, il
      // ramo "if" (l'unico che gestisce un domain_name gia' esistente)
      // ritornava sempre null - un tenant il cui nome genera un domain_name
      // duplicato falliva con un errore SQL ("domain_name cannot be null"),
      // anche in produzione.
      return TenantHelper::unique_domain_name($name);
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

    // Tutti gli utenti trovati hanno lo stesso privilegio (2): una sola lettura del ruolo.
    $role = $users_tenants->isEmpty() ? null : Role::find(2);

    if (! $role || $role->is_tenantadmin != 1) {
      return [];
    }

    return $users_tenants->all();
  }

  /**
   * Tabella HTML (nome, email, data scadenza) degli utenti in scadenza, usata
   * nelle email di avviso a tenant admin e superadmin al posto del segnaposto
   * |CUSTOMFUNCTION|TenantHelper::build_utenti_scadenza_tenantadmin|CUSTOMFUNCTIONEND|.
   * I valori degli utenti sono escapati: finiscono in un corpo email HTML.
   *
   * @param iterable $users oggetti con name, email, data_scadenza
   */
  public static function build_utenti_scadenza_tenantadmin($users)
  {
    $cell = "padding: 12px; border-bottom: 1px solid #ddd;text-align: center;";
    $head = "padding: 12px; border-bottom: 2px solid #ddd;text-align: center;";

    $table = "<table style='width: 100%; border-collapse: collapse; font-family: Arial, sans-serif;'>";
    $table .= "<tr style='background-color: #f2f2f2; '>";
    $table .= "<th style='width: 35%; $head'>" . e(trans('crudbooster.user_expiry_col_name')) . "</th>";
    $table .= "<th style='width: 35%; $head'>" . e(trans('crudbooster.user_expiry_col_email')) . "</th>";
    $table .= "<th style='width: 30%; $head'>" . e(trans('crudbooster.user_expiry_col_date')) . "</th>";
    $table .= "</tr>";

    $index = 0;
    foreach ($users as $user) {
      $backgroundColor = ($index++ % 2 == 0) ? "#f9f9f9" : "#ffffff"; // Colore alternato per le righe
      $table .= "<tr style='background-color: $backgroundColor;'>";
      $table .= "<td style='$cell'>" . e($user->name) . "</td>";
      $table .= "<td style='$cell'>" . e($user->email) . "</td>";
      $table .= "<td style='$cell'>" . e($user->data_scadenza) . "</td>";
      $table .= "</tr>";
    }

    return $table . "</table>";
  }



}

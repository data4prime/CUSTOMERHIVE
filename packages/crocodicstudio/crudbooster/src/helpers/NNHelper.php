<?php

namespace crocodicstudio\crudbooster\helpers;

use Session;
use Request;
use Schema;
use Cache;
use DB;
use Route;
use Validator;
use App\QlikItem;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Carbon;
use Firebase\JWT\JWT;

class QlikHelper
{

  public static function getConfFromItem($id_item) {

    $conf_id = DB::table('qlik_items')->where('id', $id_item)->first()->qlik_conf;

    return DB::table('qlik_confs')->where('id', $conf_id)->first();


  }

  public static function getTypeConf($id) {
	
    return DB::table('qlik_confs')->where('id', $id)->first()->type;

  }

  public static function confIsSAAS($id) {

    return DB::table('qlik_confs')->where('id', $id)->first()->type == 'SAAS';

  }



  /**
   *	Verifica se l'utente corrente è abilitato a vedere un oggetto qlik
   * superadmin può sempre
   * se è public tutti possono sempre
   * se è tenantadmin controlla solo tenants_allowed
   * se è basic controlla anche gruppi
   *
   * @param int id dell'item
   *
   * @return boolean true se l'utente è abilitato, false altrimenti
   */
  public static function can_see_item($qlik_item_id)
  {

    //super admin sempre allowed
    if (CRUDBooster::isSuperadmin()) {
      return true;
    }

    $qlik_item = \App\QlikItem::find($qlik_item_id);

    if (empty($qlik_item)) {
      add_log_ch('can see item', 'qlik item not found id: ' . $qlik_item_id);
      return false;
    }
    if (!MyHelper::is_int($qlik_item_id)) {
      add_log_ch('can see item', 'qlik item id is not int: ' . $qlik_item_id);
      return false;
    }

    if ($qlik_item->isPublic()) {
      //tutti possono vedere un item pubblico
      return true;
    }

    //check tenants_allowed
    //get user tenant
    $current_user_tenant = UserHelper::current_user_tenant();
    //get item allowed tenants
    $allowed_tenants = $qlik_item->allowedTenants();
    // var_dump($qlik_item_id);
    // var_dump($qlik_item->isPublic());
    // var_dump(CRUDBooster::isSuperadmin());
    // var_dump(UserHelper::isTenantAdmin());
    // var_dump($allowed_tenants);
    // var_dump($current_user_tenant);
    // exit;
    if (in_array($current_user_tenant, $allowed_tenants)) {
      //tenant abilitato
      if (UserHelper::isTenantAdmin()) {
        //Tenantadmin non è limitato dal gruppo per la visibilità dei qlik item
        //è sufficente il controllo sul tenant
        return true;
      }
    } else {
      //tenant non abilitato
      add_log_ch('can see item', 'tenant non abilitato qlik item id: ' . $qlik_item_id);
      return false;
    }

    //check groups
    //get user groups
    $current_user_groups = GroupHelper::myGroups();
    //get item allowed groups
    $allowed_groups = $qlik_item->allowedGroups();

    foreach ($allowed_groups as $allowed_group) {
      if (in_array($allowed_group, $current_user_groups)) {
        //trovato un gruppo abilitato tra i gruppi di cui fa parte l'utente
        return true;
      }
    }
    add_log_ch('can see item', 'nessun gruppo dell\'utente abilitato, qlik item id: ' . $qlik_item_id);
    return false;
  }



  public static function randString($length, $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
  {
    $str = '';
    $count = strlen($charset);
    while ($length--) {
      $str .= $charset[mt_rand(0, $count - 1)];
    }
    return $str;
  }
}

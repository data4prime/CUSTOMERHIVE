<?php

namespace crocodicstudio\crudbooster\helpers;

use Session;
use Request;
use Schema;
use Cache;
use DB;
use Route;
use Validator;
use App\ChatAIConf;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Carbon;
use Firebase\JWT\JWT;

class ChatAIHelper
{

  public static function getConfFromItem($id_item) {


    return DB::table('chatai_confs')->where('id', $id_item)->first();


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
  public static function can_see_item($chat_ai_id)
  {

    //super admin sempre allowed
    if (CRUDBooster::isSuperadmin()) {
      return true;
    }

    $chat_ai = \App\ChatAIConf::find($chat_ai_id);

    if (empty($chat_ai)) {
      add_log_ch('can see item', 'Chat AI not found id: ' . $chat_ai_id);
      return false;
    }
    if (!MyHelper::is_int($chat_ai_id)) {
      add_log_ch('can see item', 'Chat AI id is not int: ' . $chat_ai_id);
      return false;
    }

    if ($chat_ai->isPublic()) {
      //tutti possono vedere un item pubblico
      return true;
    }

    //check tenants_allowed
    //get user tenant
    $current_user_tenant = UserHelper::current_user_tenant();
    //get item allowed tenants
    $allowed_tenants = $chat_ai->allowedTenants();
    // var_dump($chat_ai_id);
    // var_dump($chat_ai->isPublic());
    // var_dump(CRUDBooster::isSuperadmin());
    // var_dump(UserHelper::isTenantAdmin());
    // var_dump($allowed_tenants);
    // var_dump($current_user_tenant);
    // exit;
    if (in_array($current_user_tenant, $allowed_tenants)) {
      //tenant abilitato
      if (UserHelper::isTenantAdmin()) {
        //Tenantadmin non è limitato dal gruppo per la visibilità dei Chat AI
        //è sufficente il controllo sul tenant
        return true;
      }
    } else {
      //tenant non abilitato
      add_log_ch('can see item', 'tenant non abilitato Chat AI id: ' . $chat_ai_id);
      return false;
    }

    //check groups
    //get user groups
    $current_user_groups = GroupHelper::myGroups();
    //get item allowed groups
    $allowed_groups = $chat_ai->allowedGroups();

    foreach ($allowed_groups as $allowed_group) {
      if (in_array($allowed_group, $current_user_groups)) {
        //trovato un gruppo abilitato tra i gruppi di cui fa parte l'utente
        return true;
      }
    }
    add_log_ch('can see item', 'nessun gruppo dell\'utente abilitato, Chat AI id: ' . $chat_ai_id);
    return false;
  }

  /**
   *	Check if a Chat AI is currently enabled for public access
   *
   * @return string Chat AI public URL
   */
  public static function buildPublicUrl($proxy_token)
  {
    return env('APP_URL') . '/qi/' . $proxy_token;
  }
  /**
   *	Enable/Disable a public page which grant access to the Chat AI content anonymously
   *
   * @param string ' ' if public access is enabled,
   *               '' if public access is disabled
   *
   */
  public static function toggle_public_access($input_field_value, $chat_ai_id)
  {
    $chat_ai = ChatAIConf::find($chat_ai_id);
    //get current status
    if ($input_field_value === 'public_access') {
      //enable public access
      $chat_ai->enablePublicAccess();
    } else {
      //disable public_access
      $chat_ai->disablePublicAccess();
    }
  }



}

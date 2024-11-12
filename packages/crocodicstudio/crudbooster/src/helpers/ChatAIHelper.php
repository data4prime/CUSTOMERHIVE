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

/*
    if ($chat_ai->isPublic()) {
      return true;
    }
*/

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


  //function to save chat history
  public static function saveChatHistory($chat_ai_id, $messages)
  {

    //prepare json of messsages
    $messages_json = json_encode($messages);

    //json object 

    $json_obj = [
      'messages' => $messages_json,
      'created_by' => CRUDBooster::myId(),
      'created_at' => Carbon::now(),
    ];


    //current tenant
    $current_tenant = UserHelper::current_user_tenant();


    //get last chat history
    $last_chat_history = DB::table('chat_ai_history')
                        ->where('chat_ai_id', $chat_ai_id)
                        ->where('tenant', $current_tenant)
                        ->where('user_id', CRUDBooster::myId())
                        ->orderBy('id', 'desc')->first();

    if ($last_chat_history) {

        $last_messages = $last_chat_history->messages;

    } else {

        $last_messages = '';

    }

    $count_last_messages = strlen($last_messages);
    $count_json_obj = strlen(json_encode($json_obj));

    //file_put_contents(__DIR__.'/logchatai.txt', 'count_last_messages: ' . $count_last_messages . ' count_json_obj: ' . $count_json_obj . PHP_EOL, FILE_APPEND);

    if (($count_json_obj + $count_last_messages > 65500) || !$last_chat_history) {

      $array_messages = [];
      $array_messages[] = $json_obj;

      DB::table('chat_ai_history')->insert([
        'chat_ai_id' => $chat_ai_id,
        'messages' => json_encode($array_messages),
        'tenant' => $current_tenant,
        'user_id' => CRUDBooster::myId(),
      ]);



    } else {

      $array_messages = json_decode($last_messages, true);
      $array_messages[] = $json_obj;

      DB::table('chat_ai_history')->where('id', $last_chat_history->id)->update([
        'messages' => json_encode($array_messages),
      ]);



    }




  }

  public static function getToken($conf_id) {

    $conf = ChatAIConf::find($conf_id);

    if (!$conf) {
      return false;
    }


    $url = $conf->url;
    $url_parts = parse_url($url);
    $url_base = $url_parts['scheme'] . '://' . $url_parts['host'];



    // Dati del payload per il token
    $payload = [
        'iss' => $url_base,  // Issuer
        'aud' => $url_base,  // Audience
        'iat' => time(),                 // Tempo di emissione (Issued At)
        'exp' => time() + 3600,          // Tempo di scadenza (1 ora da ora)
        //'user_id' => 123
    ];

    // Passphrase segreta per firmare il token
    $secretKey = $conf->token;

    // Generazione del token
    $jwt = JWT::encode($payload, $secretKey, 'HS256');

    return $jwt;



  }


}

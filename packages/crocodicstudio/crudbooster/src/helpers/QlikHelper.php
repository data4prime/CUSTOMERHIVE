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
    if (!MyHelper::is_int($qlik_item)) {
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

  /**
   *	Check if a qlik item is currently enabled for public access
   *
   * @return string qlik item public URL
   */
  public static function buildPublicUrl($proxy_token)
  {
    return env('APP_URL') . '/qi/' . $proxy_token;
  }
  /**
   *	Enable/Disable a public page which grant access to the qlik item content anonymously
   *
   * @param string ' ' if public access is enabled,
   *               '' if public access is disabled
   *
   */
  public static function toggle_public_access($input_field_value, $qlik_item_id)
  {
    $qlik_item = QlikItem::find($qlik_item_id);
    //get current status
    if ($input_field_value === 'public_access') {
      //enable public access
      $qlik_item->enablePublicAccess();
    } else {
      //disable public_access
      $qlik_item->disablePublicAccess();
    }
  }

  /**
   *	Ottiene il qlik ticket per la connessione
   *
   * @param
   *
   * @return string qlik ticket
   */
  public static function getTicket($qlik_item_id)
  {
    //get user data
    $current_user_id = CRUDBooster::myId();
    $current_user = \App\User::find($current_user_id);

    $conf_id = DB::table('qlik_items')->where('id',$qlik_item_id )->first();//->qlik_conf;
	if (!$conf_id) {
		$qlik_conf = DB::table('qlik_confs')->where('id', $qlik_item_id)->first();
	} else {
		$qlik_conf = DB::table('qlik_confs')->where('id', $conf_id->qlik_conf)->first();
	}
    

    //dd($qlik_conf->id);

    //dd(DB::table('qlik_users')->where('user_id', $current_user_id)->where('qlik_conf_id', $qlik_conf->id)->toSql());


    $qlik_user = DB::table('qlik_users')->where('user_id', $current_user_id)->where('qlik_conf_id', $qlik_conf->id)->first();
    if (!$qlik_user) {
      $data['error'] = 'User credentials missing. Ask an admin to set your qlik id and user directory';
      CRUDBooster::redirect(CRUDBooster::adminPath(), $data['error']);
      exit;
    }

    //UserId
    $qlik_login = $qlik_user->qlik_login;
    //User Directory
    $user_directory = $qlik_user->user_directory;

    if (empty($qlik_login) or empty($user_directory)) {
      $data['error'] = 'User credentials missing. Ask an admin to set your qlik id and user directory';
      CRUDBooster::redirect(CRUDBooster::adminPath(), $data['error']);
      exit;
    }


    $QRSurl = $qlik_conf->qrsurl .':'.$qlik_conf->port;

    //$QRSurl = CRUDBooster::getSetting('qrsurl');

    $xrfkey = '0123456789abcdef';
    $endpoint = $qlik_conf->endpoint . "/ticket?xrfkey=" . $xrfkey;
    //$endpoint = CRUDBooster::getSetting('endpoint') . "/ticket?xrfkey=" . $xrfkey;

    //$QRSCertfile = asset(CRUDBooster::getSetting('QRSCertfile'));
    //$QRSCertkeyfile = asset(CRUDBooster::getSetting('QRSCertkeyfile'));

    //$QRSCertfile = env('APP_PATH').'/storage/app/'.CRUDBooster::getSetting('QRSCertfile');
    //$QRSCertfile = env('APP_PATH').'/storage/app/public'.$qlik_conf->QRSCertfile;
	$QRSCertfile =$qlik_conf->tenant_path.$qlik_conf->QRSCertfile;

    $QRSCertkeyfile = $qlik_conf->tenant_path.$qlik_conf->QRSCertkeyfile;

    //$QRSCertkeyfile = env('APP_PATH').'/storage/app/'.CRUDBooster::getSetting('QRSCertkeyfile');
    $QRSCertkeyfilePassword =$qlik_conf->QRSCertkeyfilePassword;
    //$QRSCertkeyfilePassword = CRUDBooster::getSetting('QRSCertkeyfilePassword');

    $headers = array(
      'Accept: application/json',
      'Content-Type: application/json',
      'x-qlik-xrfkey: ' . $xrfkey,
      'X-Qlik-User: UserDirectory=' . $user_directory . ';UserId=' . $qlik_login
    );
	
	//dd($QRSurl . $endpoint);

    $ch = curl_init($QRSurl . $endpoint);

    curl_setopt($ch, CURLOPT_VERBOSE, true);
    // curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{
      "UserId":"' . $qlik_login . '",
      "UserDirectory":"' . $user_directory . '"
    }');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSLCERT, $QRSCertfile);
    curl_setopt($ch, CURLOPT_SSLKEY, $QRSCertkeyfile);
    // #RAMA no password
    if (!empty($QRSCertkeyfilePassword)) {
      curl_setopt($ch, CURLOPT_KEYPASSWD, $QRSCertkeyfilePassword);
    }
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    //Execute and get response
    $raw_response = curl_exec($ch);
	//dd($raw_response);

    if (curl_errno($ch)) {
      $error_msg = curl_error($ch);
    }
    //dd($error_msg);


    $response = json_decode($raw_response);
    //dd($response);

    return isset($response->Ticket) ? $response->Ticket : '';
    //return isset($response['Ticket']) ? $response['Ticket'] : '';
  }

  public static function getJWTToken($id, $conf_id)
  {

    $current_user = \App\User::find($id);

    $qlik_conf = DB::table('qlik_confs')->where('id', $conf_id)->first();

    $issuedAt = Carbon::now();
    $issuedA2 = Carbon::now();

    $expire = $issuedA2->addMinutes(60)->timestamp;

    $check_idp = $current_user->idp_qlik;

    //$privateKey = CRUDBooster::getSetting('private_key');
    $privateKey = $qlik_conf->private_key;

    $privateKey =$qlik_conf->tenant_path .$privateKey;
    $privateKey = file_get_contents($privateKey);

    if (empty($check_idp)) {
      $check_idp = QlikHelper::randString(64);
    }

    $keyid = $qlik_conf->keyid;



    $header = [

      'alg' => 'RS256',
      'algorithm' => 'RS256',
      'aud' => 'qlik.api/login/jwt-session',
      'iss' => $qlik_conf->issuer,
      'kid' => $keyid,
      'typ' => 'JWT',
      'exp' => '1h'


    ];


    // Payload data
    $payload = [

      'sub' => $check_idp,
      'subType' => 'user',
      'jti' => Uuid::uuid4()->toString(),
      'iat'  => $issuedAt->getTimestamp(),
      'iss' => $qlik_conf->issuer,
      'nbf'  => $issuedAt->getTimestamp(),
      'exp'  => $expire,
      'aud' => 'qlik.api/login/jwt-session',
      'email' => $current_user->email,
      'email_verified' => true,
      'name' => $current_user->name
    ];

    $myToken = JWT::encode($payload, $privateKey, 'RS256', $keyid, $header);
    //file_put_contents(__DIR__."/log.txt", $myToken."\n", FILE_APPEND);

    return $myToken;
  }

  public static function createUser($id, $conf_id)
  {

    $current_user = \App\User::find($id);

/*
    if (!empty($current_user->idp_qlik)) {
      return ['mex' => 'crudbooster.alert_userqlik_exists', 'style' => 'danger'];
    }
*/

    $qlik_conf = DB::table('qlik_confs')->where('id', $conf_id)->first();

    $token = QlikHelper::getJWTToken($id, $conf_id);

    //file_put_contents(__DIR__."/log.txt", $token."\n\n", FILE_APPEND);

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $qlik_conf->url . '/login/jwt-session',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_COOKIESESSION => true,
      CURLOPT_COOKIEJAR => 'cookie-name',
      CURLOPT_COOKIEFILE => __DIR__ . "/cookie.txt",

      CURLOPT_HTTPHEADER => array(
        "qlik-web-integration-id: " . $qlik_conf->web_int_id,
        "Authorization: Bearer {$token}"
      ),
    ));

    $response = curl_exec($curl);

    if ($response == "OK") {

      curl_setopt_array($curl, array(
        CURLOPT_URL => $qlik_conf->url . "/api/v1/users/me",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
          "qlik-web-integration-id: " . $qlik_conf->web_int_id,
          "Authorization: Bearer {$token}"
        ),
      ));

      $response = curl_exec($curl);


      $response = json_decode($response);

      //file_put_contents(__DIR__."/log.txt", json_encode($response)."\n\n", FILE_APPEND);

      $sub = $response->subject;

      //file_put_contents(__DIR__."/log.txt", $sub."\n\n", FILE_APPEND);
      curl_close($curl);
      return $sub;
      //$current_user = \App\User::find($id);

      //$current_user->idp_qlik = $sub;

      //$current_user->save();
      //return ['mex' => 'crudbooster.alert_userqlik_created', 'style' => 'success'];


      
    }
    //return ['mex' => 'crudbooster.alert_userqlik_error', 'style' => 'danger'];
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

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
  public static function getTicketFromConf($conf_id)
  {
    //get user data
    $current_user_id = CRUDBooster::myId();
    $current_user = \App\User::find($current_user_id);

    $qlik_conf = DB::table('qlik_confs')->where('id', $conf_id)->first();


    $qlik_user = DB::table('qlik_users')->where('user_id', $current_user_id)->where('qlik_conf_id', $qlik_conf->id)->first();
    if (!$qlik_user) {
      $data['error'] = 'User not found!';
      CRUDBooster::redirect(CRUDBooster::adminPath(), $data['error']);
      exit;
    }

    $qlik_login = $qlik_user->qlik_login;
    $user_directory = $qlik_user->user_directory;

    if (empty($qlik_login) or empty($user_directory)) {
      $data['error'] = 'User credentials missing. Ask an admin to set your qlik id and user directory';
      CRUDBooster::redirect(CRUDBooster::adminPath(), $data['error']);
      exit;
    }


    $QRSurl = $qlik_conf->qrsurl .':'.$qlik_conf->port;

    $xrfkey = '0123456789abcdef';
    $endpoint =   "qps/".$qlik_conf->endpoint."/ticket?xrfkey=" . $xrfkey;

    $QRSCertfile =$qlik_conf->QRSCertfile;


    $QRSCertkeyfile = $qlik_conf->QRSCertkeyfile;

    $QRSCertkeyfilePassword =$qlik_conf->QRSCertkeyfilePassword;

    //get host with protocol
    $host = $_SERVER['HTTP_HOST'];
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";



    $QRSCertfile = str_replace($protocol.'://'.$host, env('APP_PATH').'/public', $QRSCertfile);
    $QRSCertkeyfile = str_replace($protocol.'://'.$host, env('APP_PATH').'/public', $QRSCertkeyfile);

    $headers = array(
      'Accept: application/json',
      'Content-Type: application/json',
      'x-qlik-xrfkey: ' . $xrfkey,
      'X-Qlik-User: UserDirectory=' . $user_directory . ';UserId=' . $qlik_login
    );
    //DEBUG Qlik Ticket

    file_put_contents(__DIR__ . '/qlik_ticket2.txt', $QRSurl . '/'.$endpoint."\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/qlik_ticket2.txt', json_encode($headers)."\n", FILE_APPEND);

    file_put_contents(__DIR__ . '/qlik_ticket2.txt', $QRSCertfile."\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/qlik_ticket2.txt', $QRSCertkeyfile."\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/qlik_ticket2.txt', $QRSCertkeyfilePassword."\n", FILE_APPEND);


    $ch = curl_init($QRSurl . '/'.$endpoint);

    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{
      "UserId":"' . $qlik_login . '",
      "UserDirectory":"' . $user_directory . '"
    }');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSLCERT, $QRSCertfile);
    curl_setopt($ch, CURLOPT_SSLKEY, $QRSCertkeyfile);
    if (!empty($QRSCertkeyfilePassword)) {
      curl_setopt($ch, CURLOPT_KEYPASSWD, $QRSCertkeyfilePassword);
    }
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    //Execute and get response
    $raw_response = curl_exec($ch);
    //DEBUG Qlik Ticket
    file_put_contents(__DIR__ . '/qlik_ticket2.txt', $raw_response."\n", FILE_APPEND);

    if (curl_errno($ch)) {
      $error_msg = curl_error($ch);
    }
    $response = json_decode($raw_response);
    return isset($response->Ticket) ? $response->Ticket : '';
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
    
    $qlik_user = DB::table('qlik_users')->where('user_id', $current_user_id)->where('qlik_conf_id', $qlik_conf->id)->first();
    if (!$qlik_user) {
      $data['error'] = 'User not found!';
      CRUDBooster::redirect(CRUDBooster::adminPath(), $data['error']);
      exit;
    }

    $qlik_login = $qlik_user->qlik_login;
    $user_directory = $qlik_user->user_directory;

    if (empty($qlik_login) or empty($user_directory)) {
      $data['error'] = 'User credentials missing. Ask an admin to set your qlik id and user directory';
      CRUDBooster::redirect(CRUDBooster::adminPath(), $data['error']);
      exit;
    }

    $QRSurl = $qlik_conf->qrsurl .':'.$qlik_conf->port;

    $xrfkey = '0123456789abcdef';
    //$endpoint = $qlik_conf->endpoint ."/qps". "/ticket?xrfkey=" . $xrfkey;

    $endpoint =   "qps/".$qlik_conf->endpoint."/ticket?xrfkey=" . $xrfkey;

    $QRSCertfile =$qlik_conf->QRSCertfile;

    $QRSCertkeyfile = $qlik_conf->QRSCertkeyfile;

    $QRSCertkeyfilePassword =$qlik_conf->QRSCertkeyfilePassword;

    $host = $_SERVER['HTTP_HOST'];
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";

    $QRSCertfile = str_replace($protocol.'://'.$host, env('APP_PATH').'/public', $QRSCertfile);
    $QRSCertkeyfile = str_replace($protocol.'://'.$host, env('APP_PATH').'/public', $QRSCertkeyfile);

    $headers = array(
      'Accept: application/json',
      'Content-Type: application/json',
      'x-qlik-xrfkey: ' . $xrfkey,
      'X-Qlik-User: UserDirectory=' . $user_directory . ';UserId=' . $qlik_login
    );
    //file_put_contents(__DIR__ . '/qlik_ticket.txt', $QRSurl . '/'.$endpoint."\n", FILE_APPEND);
    $ch = curl_init($QRSurl . '/'.$endpoint);

    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{
      "UserId":"' . $qlik_login . '",
      "UserDirectory":"' . $user_directory . '"
    }');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSLCERT, $QRSCertfile);
    curl_setopt($ch, CURLOPT_SSLKEY, $QRSCertkeyfile);
    if (!empty($QRSCertkeyfilePassword)) {
      curl_setopt($ch, CURLOPT_KEYPASSWD, $QRSCertkeyfilePassword);
    }
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    //Execute and get response
    $raw_response = curl_exec($ch);

    if (curl_errno($ch)) {
      $error_msg = curl_error($ch);
      file_put_contents(__DIR__ . '/qlik_ticket.txt', $error_msg."\n", FILE_APPEND);
    }

    $response = json_decode($raw_response);
    return isset($response->Ticket) ? $response->Ticket : '';
  }

  public static function dataForTicketConf($conf_id) 
  {
    //get user data
    $current_user_id = CRUDBooster::myId();
    $current_user = \App\User::find($current_user_id);

    $qlik_conf = DB::table('qlik_confs')->where('id', $conf_id)->first();

    $qlik_user = DB::table('qlik_users')->where('user_id', $current_user_id)->where('qlik_conf_id', $qlik_conf->id)->first();
    if (!$qlik_user) {
      $data['error'] = 'User not found!';
      CRUDBooster::redirect(CRUDBooster::adminPath(), $data['error']);
      exit;
    }

    $qlik_login = $qlik_user->qlik_login;
    $user_directory = $qlik_user->user_directory;

    if (empty($qlik_login) or empty($user_directory)) {
      $data['error'] = 'User credentials missing. Ask an admin to set your qlik id and user directory';
      CRUDBooster::redirect(CRUDBooster::adminPath(), $data['error']);
      exit;
    }

    $QRSurl = $qlik_conf->qrsurl .':'.$qlik_conf->port;

    $xrfkey = '0123456789abcdef';
    $endpoint = $qlik_conf->endpoint . "/ticket?xrfkey=" . $xrfkey;

    $QRSCertfile =$qlik_conf->QRSCertfile;


    $QRSCertkeyfile = $qlik_conf->QRSCertkeyfile;

    $QRSCertkeyfilePassword =$qlik_conf->QRSCertkeyfilePassword;

    //get host with protocol
    $host = $_SERVER['HTTP_HOST'];
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";

    $QRSCertfile = str_replace($protocol.'://'.$host, env('APP_PATH').'/public', $QRSCertfile);
    $QRSCertkeyfile = str_replace($protocol.'://'.$host, env('APP_PATH').'/public', $QRSCertkeyfile);

    $headers = array(
      'Accept: application/json',
      'Content-Type: application/json',
      'x-qlik-xrfkey: ' . $xrfkey,
      'X-Qlik-User: UserDirectory=' . $user_directory . ';UserId=' . $qlik_login
    );
    $ret = json_encode([
      'QRSurl' => $QRSurl,
      'endpoint' => $endpoint,
      'headers' => $headers,
      'url' => $QRSurl . '/'.$endpoint,
      'QRSCertfile' => $QRSCertfile,
      'QRSCertkeyfile' => $QRSCertkeyfile,
      'QRSCertkeyfilePassword' => $QRSCertkeyfilePassword,
      'headers' => $headers,
      'body' => [
        "UserId" => $qlik_login,
        "UserDirectory" => $user_directory  
      ],


    ]);
    //file_put_contents(__DIR__ . '/qlik_ticket3.txt', $ret."\n", FILE_APPEND);

    return $ret;

  }

  public static function getJWTTokenOP($id, $conf_id)
  {

    //return "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VySWQiOiJkYXN5c2VydmljZSIsInVzZXJEaXJlY3RvcnkiOiJEQVNZIn0.KBmZFS4AOFwxnqkmDp6-RrHwKW6tRpXpRinoaCXaoT5k-k78CxMONTK9cVK533qYk9snUD1l_CdUB1_3lukJGdD-hABss67GramtU07DA70uzNVreoaOn6_Vz4RTaioJbBnyfyWB6js7BNDRcLOsdbnlQyK_ilfWy6Fc-koolYNsNoKn9VOhnoRwXM5JAPGsSGW2SZuBQ5y_6m17tX-4XTxqyZjqgizo1BnWtSfMBdNeFVK7vcsOlasPIwp4x5fM3Nca_BJWuDXXcDT8bG9gyuG9YmlcGgtcUQo76oec7o6797MxUjqZ-HFtAEDFghMvRXK7TjeFa25JQDP3HlSV0A";

    $current_user = \App\User::find($id);

    $qlik_conf = DB::table('qlik_confs')->where('id', $conf_id)->first();

    $expire = $issuedA2->addMinutes(60)->timestamp;


    $privateKey = $qlik_conf->private_key;

    //if provateKey is not empty, get the content of the file
    if (!empty($privateKey)) {
      $privateKey = file_get_contents($privateKey);
    } else {
      $privateKey = "";
    }


    $qlik_user = DB::table('qlik_users')->where('user_id', $id)->where('qlik_conf_id', $conf_id)->first();
    if (!$qlik_user) {
      $data['error'] = 'User not found!';
      CRUDBooster::redirect(CRUDBooster::adminPath(), $data['error']);
      exit;
    }

    $qlik_login = $qlik_user->qlik_login;
    $user_directory = $qlik_user->user_directory;

    $header = [
      'alg' => 'RS256',
      'typ' => 'JWT',

    ];


    // Payload data
    $payload = [

      'userId' => $qlik_login,
      'userDirectory' => $user_directory,
    ];

    file_put_contents(__DIR__ . '/qlik_token.txt', json_encode($payload)."\n", FILE_APPEND);

    $myToken = JWT::encode($payload, $privateKey, 'RS256');
    //$myToken = JWT::encode($payload, $privateKey, 'RS256', $keyid, $header);

    return $myToken;
  }

  public static function getJWTToken($id, $conf_id)
  {

    $current_user = \App\User::find($id);

    $qlik_conf = DB::table('qlik_confs')->where('id', $conf_id)->first();

    $issuedAt = Carbon::now();
    $issuedA2 = Carbon::now();

    $expire = $issuedA2->addMinutes(60)->timestamp;

    $privateKey = $qlik_conf->private_key;

    //if provateKey is not empty, get the content of the file
    if (!empty($privateKey)) {
      $privateKey = file_get_contents($privateKey);
    } else {
      $privateKey = "";
    }

    

    $keyid = $qlik_conf->keyid;

    $check_idp = DB::table('qlik_users')->where('user_id', $id)->where('qlik_conf_id', $conf_id)->first();//->idp_qlik;

    if (!$check_idp) {
      $check_idp = QlikHelper::randString(64);
    } else {
      $check_idp = $check_idp->idp_qlik;
    }

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

    return $myToken;
  }

  public static function createUser($id, $conf_id)
  {

    $current_user = \App\User::find($id);


    $qlik_conf = DB::table('qlik_confs')->where('id', $conf_id)->first();


    $token = QlikHelper::getJWTToken($id, $conf_id);


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

      $sub = $response->subject;

      curl_close($curl);
      return $sub;

      
    }

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

<?php

namespace App\Helpers;

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
use App\Helpers\MyHelper;
use App\Helpers\GroupHelper;
use App\Helpers\CRUDBooster;
use App\Helpers\UserHelper;

class QlikHelper
{

  public static function getConfFromItem($id_item) {

    $conf_id = DB::table('qlik_items')->where('id', $id_item)->value('qlik_conf');

    if (empty($conf_id)) {
      return null;
    }

    return DB::table('qlik_confs')->where('id', $conf_id)->first();


  }

  public static function getTypeConf($id) {

    return DB::table('qlik_confs')->where('id', $id)->value('type');

  }

  public static function confIsSAAS($id) {

    return DB::table('qlik_confs')->where('id', $id)->value('type') == 'SAAS';

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

    if (!MyHelper::is_int($qlik_item_id)) {
      add_log_ch('can see item', 'qlik item id is not int: ' . $qlik_item_id);
      return false;
    }

    $qlik_item = \App\QlikItem::find($qlik_item_id);

    if (empty($qlik_item)) {
      add_log_ch('can see item', 'qlik item not found id: ' . $qlik_item_id);
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
    return config('app.url') . '/qi/' . $proxy_token;
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
    if ($input_field_value === '1') {
      //enable public access
      $qlik_item->enablePublicAccess();
    } else {
      //disable public_access
      $qlik_item->disablePublicAccess();
    }
  }

  /**
   * Legge il contenuto della chiave privata salvata in qlik_confs.private_key.
   * CRUDBooster::uploadFile() salva un path relativo alla public root
   * ("/storage/uploads/..."), che file_get_contents() non risolve da solo;
   * i valori legacy (URL assoluto o path di filesystem) restano invariati.
   */
  public static function readPrivateKey($path)
  {
    if (strpos($path, '/storage/') === 0 && is_file(public_path($path))) {
      $path = public_path($path);
    }

    return file_get_contents($path);
  }

  public static function getJWTTokenOP($id, $conf_id)
  {

    $current_user = \App\User::find($id);

    $qlik_conf = DB::table('qlik_confs')->where('id', $conf_id)->first();


    $privateKey = $qlik_conf->private_key;


    if (!empty($privateKey)) {
      $privateKey = self::readPrivateKey($privateKey);
    } else {
      $privateKey = "";
    }

    $qlik_user = DB::table('qlik_users')->where('user_id', $id)->where('qlik_conf_id', $conf_id)->first();
    if (!$qlik_user) {
      $data['error'] = 'User not found!';
      CRUDBooster::redirectBack($data['error'], 'error');
      exit;
    }

    $qlik_login = $qlik_user->qlik_login;
    $user_directory = $qlik_user->user_directory;

    $header = [
      'typ' => 'JWT',
      'alg' => 'RS256',
    ];

    $payload = [

      'userId' => $qlik_login,
      'userDirectory' => $user_directory,
    ];


    try {
      $myToken = JWT::encode($payload, $privateKey, 'RS256', null, $header);
    } catch (\DomainException $e) {
      return self::jwtKeyError($conf_id, $e);
    }


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
      $privateKey = self::readPrivateKey($privateKey);
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

    try {
      $myToken = JWT::encode($payload, $privateKey, 'RS256', $keyid, $header);
    } catch (\DomainException $e) {
      return self::jwtKeyError($conf_id, $e);
    }

    return $myToken;
  }

  /**
   * Chiave privata Qlik non utilizzabile per firmare il JWT: vuota/non
   * valida (gia' con php-jwt 6.x) o RSA < 2048 bit (rifiutata da 7.x, fix
   * CVE-2025-45769). Prima l'eccezione arrivava fino alla pagina (500); ora
   * si logga e si restituisce un token vuoto, che i chiamanti gestiscono
   * gia' ("JWT Token generation failed!"). Vedi docs/refactoring/083.
   */
  private static function jwtKeyError($conf_id, \DomainException $e): string
  {
    \Log::warning('Qlik JWT non generato (conf ' . $conf_id . '): ' . $e->getMessage());

    return '';
  }

  public static function createUser($id, $conf_id)
  {

    $qlik_conf = DB::table('qlik_confs')->where('id', $conf_id)->first();

    if (!$qlik_conf) {
      return null;
    }

    $token = QlikHelper::getJWTToken($id, $conf_id);

    $headers = array(
      "qlik-web-integration-id: " . $qlik_conf->web_int_id,
      "Authorization: Bearer {$token}"
    );

    $curl = curl_init();

    // COOKIEFILE '' abilita il cookie engine solo in memoria: il cookie di
    // sessione ottenuto dal login serve alla chiamata users/me sullo stesso
    // handle, senza file condivisi su disco.
    curl_setopt_array($curl, array(
      CURLOPT_URL => $qlik_conf->url . '/login/jwt-session',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 5,
      CURLOPT_CONNECTTIMEOUT => 5,
      CURLOPT_TIMEOUT => 15,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_COOKIEFILE => '',
      CURLOPT_SSL_VERIFYPEER => true,
      CURLOPT_SSL_VERIFYHOST => 2,
      CURLOPT_HTTPHEADER => $headers,
    ));

    $response = curl_exec($curl);

    if ($response === false) {
      \Log::warning('Qlik createUser: login fallito (conf ' . $conf_id . '): ' . curl_error($curl));
      curl_close($curl);
      return null;
    }

    if ($response != "OK") {
      \Log::warning('Qlik createUser: login rifiutato (conf ' . $conf_id . ', HTTP ' . curl_getinfo($curl, CURLINFO_HTTP_CODE) . ')');
      curl_close($curl);
      return null;
    }

    curl_setopt_array($curl, array(
      CURLOPT_URL => $qlik_conf->url . "/api/v1/users/me",
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => $headers,
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $user = ($response === false) ? null : json_decode($response);

    if (!is_object($user) || !isset($user->subject)) {
      \Log::warning('Qlik createUser: risposta users/me priva di subject (conf ' . $conf_id . ')');
      return null;
    }

    return $user->subject;
  }

  /**
   * Valore CSS di larghezza/altezza sicuro da stampare in un <style>:
   * solo numero + unita' (px, %, vh, vw); altrimenti il default.
   * (Menus lo salva gia' come numero + px/%, questo protegge da valori
   * anomali presenti nel DB.)
   */
  public static function safeCssSize($value, $default = '100%')
  {
    $value = trim((string) $value);
    return preg_match('/^\d+(\.\d+)?(px|%|vh|vw)$/', $value) ? $value : $default;
  }

  public static function randString($length, $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
  {
    $str = '';
    $count = strlen($charset);
    while ($length--) {
      $str .= $charset[random_int(0, $count - 1)];
    }
    return $str;
  }
}

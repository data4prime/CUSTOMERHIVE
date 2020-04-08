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

class QlikHelper  {

  /**
  *	Check if a qlik item is currently enabled for public access
  *
  * @return string qlik item public URL
  */
  public static function buildPublicUrl($proxy_token) {
    return env('APP_URL').'/qi/'.$proxy_token;
  }
  /**
  *	Enable/Disable a public page which grant access to the qlik item content anonymously
  *
  * @param string ' ' if public access is enabled,
  *               '' if public access is disabled
  *
  */
  public static function toggle_public_access($input_field_value, $qlik_item_id) {
    $qlik_item = QlikItem::find($qlik_item_id);
    //get current status
    if($input_field_value===' '){
      //enable public access
      $qlik_item->enablePublicAccess();
    }
    else{
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
  public static function getTicket() {

    //get user data
    $current_user_id = CRUDBooster::myId();
    $current_user = \App\User::find($current_user_id);

    //UserId
    $qlik_login = $current_user->qlik_login;
    //User Directory
    $user_directory = $current_user->user_directory;

    if(empty($qlik_login) OR empty($user_directory)){
      $data['error'] = 'User credentials missing. Ask an admin to set your qlik id and user directory';
      $this->cbView('qlik_items.no_credentials',$data);
      exit;
    }

    //base path to call
    $QRSurl = config('app.qlik_sense_app_base_path').':'.config('app.qrs_port').config('app.qlik_sense_virtual_proxy').'/';
    $xrfkey = config('app.xrfkey');
    //Path to call (with xrfkey parameter added)
    $endpoint = "qps/ticket?xrfkey=".$xrfkey;
    //Location of QRS client certificate and certificate key, assuming key is included separately
    $QRSCertfile = config('app.qrs_certificate_base_path').config('app.qrs_certificate_file_relative_path');
    $QRSCertkeyfile = config('app.qrs_certificate_base_path').config('app.qrs_certificate_key_relative_path');
    // #RAMA no password
    // $QRSCertkeyfilePassword = "Passw0rd!";

    //Set up the required headers
    $headers = array(
      'Accept: application/json',
      'Content-Type: application/json',
      'x-qlik-xrfkey: '.$xrfkey,
      'X-Qlik-User: UserDirectory='.$user_directory.';UserId='.$qlik_login
    );

    //Create Connection using Curl
    $ch = curl_init($QRSurl.$endpoint);

    curl_setopt($ch, CURLOPT_VERBOSE, true);
    // curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{
      "UserId":"'.$qlik_login.'",
      "UserDirectory":"'.$user_directory.'"
    }');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSLCERT, $QRSCertfile);
    curl_setopt($ch, CURLOPT_SSLKEY, $QRSCertkeyfile);
    // #RAMA no password
    // curl_setopt($ch, CURLOPT_KEYPASSWD, $QRSCertkeyfilePassword);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    //Execute and get response
    $raw_response = curl_exec($ch);
    $response = json_decode($raw_response);

    return $response->Ticket;
  }

}

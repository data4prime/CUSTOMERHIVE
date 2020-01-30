<?php

// esqogito_eiframe
//URL of the server
// #RAMA che variabili contiene?
// include_once("../../../../../config.inc.php");
// #RAMA credo si possa eliminare
// chdir($root_directory);

//UserId
$user = 'PLATFORMQ\customerhive';
//User Directory
$password = 'TwyT(vYtWxtZ';
//base path to call
$QRSurl = 'https://platformq.dasycloud.com:4243';
$xrfkey = "0123456789abcdef";
//Path to call (with xrfkey parameter added)
$endpoint = "/qps/ticket?xrfkey=".$xrfkey;

//Location of QRS client certificate and certificate key, assuming key is included separately
$base_path = '/var/www/customerhive/storage/app/';
$QRSCertfile = $base_path."certificates/client.pem";
$QRSCertkeyfile = $base_path."certificates/client_key.pem";
// #RAMA no password
// $QRSCertkeyfilePassword = "Passw0rd!";

//Set up the required headers
$headers = array(
	'Accept: application/json',
	'Content-Type: application/json',
	'x-qlik-xrfkey: '.$xrfkey,
	'X-Qlik-User: UserDirectory='.$password.';UserId='.$user
);
// #RAMA TODO elimina
echo $QRSurl.$endpoint;

//Create Connection using Curl
$ch = curl_init($QRSurl.$endpoint);

curl_setopt($ch, CURLOPT_VERBOSE, true);
// curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, '{
	"UserId":"'.$user.'",
	"UserDirectory":"'.$password.'"
}');
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSLCERT, $QRSCertfile);
curl_setopt($ch, CURLOPT_SSLKEY, $QRSCertkeyfile);
// curl_setopt($ch, CURLOPT_KEYPASSWD, $QRSCertkeyfilePassword);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

//Execute and print response
$data = curl_exec($ch);

$jd = json_decode($data);

// #RAMA TODO elimina

var_dump($jd);exit;

// redirect
// header('Location: '.base64_decode($_REQUEST['url']).'&qlikTicket='.$jd->Ticket);

?>

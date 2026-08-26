<?php

namespace crocodicstudio\crudbooster\controllers;

use CRUDBooster;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use \App\Tenant;
use \App\Modules;
use \App\QlikItem;
use \App\Group;
use \App\AccessLog;
use crocodicstudio\crudbooster\helpers\UserHelper;

use crocodicstudio\crudbooster\helpers\LicenseHelper;

use Illuminate\Support\Facades\Log;

use App\Services\ConnectorService;

use Illuminate\Support\Facades\Storage;

//use App\Classes\Custom\ChiveLicenseService;

class AdminController extends CBController
{
  function getIndex()
  {

  


    $data = [];
    $data['page_title'] = '<strong>Dashboard</strong>';
    //dashboard data
    $data['modules_count']    = Modules::count();
    $data['qlik_items_count'] = QlikItem::count();
    $data['total_groups']     = Group::count();
    $data['log_in_count']     = AccessLog::count();

    $data['weekly_new_users_count']   = UserHelper::new_users_count();
    $data['latest_users']             = UserHelper::latest_users($data['weekly_new_users_count']);

    return view('crudbooster::home', $data);
  }

  public function getLockscreen()
  {

    if (!CRUDBooster::myId()) {
      Session::flush();

      return redirect()->route('getLogin')->with('message', trans('crudbooster.alert_session_expired'));
    }

    Session::put('admin_lock', 1);

    return view('crudbooster::lockscreen');
  }


  /**
   * path/domain per il license server vengono presi da APP_PATH/APP_DOMAIN
   * nel .env (non dal form) per evitare che chi attiva la licenza da
   * un'installazione possa scriverli a mano e attivare/manomettere la
   * licenza di un ambiente diverso - vedi docs/login-e-licensing.md.
   * Se non sono configurati, meglio dirlo subito con un messaggio chiaro
   * che lasciar proseguire verso un errore generico del license server.
   */
  private function licenseEnvironmentIsConfigured()
  {
    return env('APP_PATH') && env('APP_DOMAIN');
  }

  public function getLicensescreen()
  {

    if (!$this->licenseEnvironmentIsConfigured()) {
      return redirect()->route('getLogin')->with('message', 'Questo ambiente non è configurato per attivare una licenza (APP_PATH/APP_DOMAIN mancanti nel .env). Contatta chi gestisce il deploy.');
    }

  //current domain
  $array = isset($_SERVER) && isset($_SERVER['HTTP_HOST']) ? explode('.', $_SERVER['HTTP_HOST']) : [];

  //get path from env
  $path = env('APP_PATH');

  //get tenant domain name
  $tenant_domain_name = isset($array[0]) ? $array[0] : '';
$tenant_domain_name = $_SERVER['HTTP_HOST'];


  ob_start();

  system("ip addr"); // if windows system(“ipconfig -all”);

  $mycom = ob_get_contents();

  ob_clean();

  $findme = "link/ether";

  $pmac = strpos($mycom , $findme);




  $mac_address= substr($mycom , ($pmac+36) , 17);

  $mac_address = "";


    return view('crudbooster::license', compact('path', 'tenant_domain_name', 'mac_address'));
  }

  public function postActivateLicense()
  {

    if (!$this->licenseEnvironmentIsConfigured()) {
      return redirect()->route('getLogin')->with('message', 'Questo ambiente non è configurato per attivare una licenza (APP_PATH/APP_DOMAIN mancanti nel .env). Contatta chi gestisce il deploy.');
    }

    $licenseKey = LicenseHelper::getLicense();

    $license_key = "";

    if ($licenseKey) {
      $license_key = $licenseKey->license_key;
    }



    $fields = [
      'domain' => Request::input('domain'),
      'email_user' => Request::input('email'),


      'clients_number' => Request::input('clients_number'),
      'tenants_number' => Request::input('tenants_number'),
      //'mac_address' => Request::input('mac_address'),
      'path' => env('APP_PATH'),
      'license_key' => $license_key,
      'isTrial' => 1,

    ];


/*
    $license_server_url = config('license-connector.license_server_url');
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => $license_server_url.'/api/api-license/license-server/licenses',
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
      ),

      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_SSL_VERIFYHOST => 0,
      CURLOPT_SSL_VERIFYPEER => 0,
      CURLOPT_POSTFIELDS => json_encode($fields),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
*/

    $response = LicenseHelper::registerLicense($fields);

    $response = json_decode($response);

    // La risposta del server di licenza non è sempre nella forma attesa
    // ({success, result}) - può arrivare malformata, con un formato di
    // errore diverso (es. validazione Laravel: {message, errors}), o non
    // essere JSON valido. isset() qui non genera errori anche se $response
    // è null o non ha le proprietà attese, a differenza dell'accesso
    // diretto usato prima (causava un 500 su qualsiasi risposta inattesa).
    $success = isset($response->success) && $response->success == true;

    if ($success && isset($response->result->license_key)) {

      DB::table('license')->insert(['license_key' => $response->result->license_key]);
      $response->result->status = "active";

      LicenseHelper::writeLicense();

      //$json_file = json_encode($response->result);
      //storage_path('app/license.json')
      //Storage::disk('license')->put('license.json', $json_file);

      return redirect(CRUDBooster::adminPath())->with('message', 'License activated successfully');


      //return redirect()->route('getLogin')->with('message', 'License activated successfully');
    }

    Log::warning('Registrazione licenza trial fallita o risposta inattesa dal server: ' . json_encode($response));

    $errorMessage = $response->result ?? $response->message ?? null;

    if (is_array($errorMessage) || is_object($errorMessage)) {
      $errorMessage = json_encode($errorMessage);
    }

    if (!$errorMessage) {
      $errorMessage = 'Richiesta di licenza non riuscita. Verifica i dati inseriti e riprova, oppure contatta chi gestisce il servizio di licenza.';
    }

    return redirect()->route('getLicenseScreen')->with('message', $errorMessage);

  }

  /**
   * Attivazione con una licenza già esistente (es. ottenuta fuori dal flusso
   * trial, o riattivata dopo aver cambiato ambiente): salta la registrazione
   * remota su /licenses e usa direttamente la chiave inserita, riusando lo
   * stesso meccanismo di scrittura/validazione già usato per il trial
   * (LicenseHelper::writeLicense()) - vedi docs/login-e-licensing.md.
   */
  public function postActivateExistingLicense()
  {

    if (!$this->licenseEnvironmentIsConfigured()) {
      return redirect()->route('getLogin')->with('message', 'Questo ambiente non è configurato per attivare una licenza (APP_PATH/APP_DOMAIN mancanti nel .env). Contatta chi gestisce il deploy.');
    }

    $license_key = trim((string) Request::input('license_key'));

    if (!$license_key) {
      return redirect()->route('getLicenseScreen')->with('message', 'Inserisci una chiave di licenza valida.');
    }

    Storage::disk('license')->delete('license.json');
    DB::table('license')->delete();
    DB::table('license')->insert(['license_key' => $license_key]);

    try {
      $license = LicenseHelper::writeLicense();
    } catch (\LaravelReady\LicenseConnector\Exceptions\AuthException $e) {
      // Il server di licenza rifiuta la chiave (formato non valido, non
      // trovata, ecc.): non è un errore applicativo, è l'esito atteso di
      // una chiave sbagliata, quindi non deve arrivare come 500.
      Log::warning('Attivazione licenza esistente rifiutata dal server: ' . $e->getMessage());
      $license = false;
    }

    if ($license) {
      return redirect(CRUDBooster::adminPath())->with('message', 'License activated successfully');
    }

    DB::table('license')->delete();

    return redirect()->route('getLicenseScreen')->with('message', 'Chiave di licenza non valida o server di licenza non raggiungibile. Riprova.');
  }


  public function postUnlockScreen()
  {
    $id = CRUDBooster::myId();
    $password = Request::input('password');
    $users = DB::table(config('crudbooster.USER_TABLE'))->where('id', $id)->first();

    if (\Hash::check($password, $users->password)) {
      Session::put('admin_lock', 0);

      return redirect(CRUDBooster::adminPath());
    } else {
      echo "<script>alert('" . trans('crudbooster.alert_password_wrong') . "');history.go(-1);</script>";
    }
  }



  public function getLogin()
  {
    if (CRUDBooster::myId()) {
      return redirect(CRUDBooster::adminPath());
    }


    $array = isset($_SERVER) && isset($_SERVER['HTTP_HOST']) ? explode('.', $_SERVER['HTTP_HOST']) : [];

    //tenant specific login page
    $tenant_domain_name = isset($array[0]) ? $array[0] : '';
    $tenant = Tenant::where('domain_name', $tenant_domain_name)->first();
    $favicon = CRUDBooster::getFavicon($tenant);

    $logo = CRUDBooster::getLogo($tenant);

    $background_color = CRUDBooster::getBackgroundColor($tenant);
    $front_color = CRUDBooster::frontColor($tenant);

    $background_image_src = CRUDBooster::getBackgroundImage($tenant);
    $background = $background_color . " url(" . $background_image_src . ")";

    return view('crudbooster::login', compact('tenant', 'favicon', 'logo', 'background', 'front_color'));
  }

  public function postLogin()
  {



      $isLicenseValid = LicenseHelper::canLicenseLogin();

      if (!$isLicenseValid) {
        return redirect()->route('getLicenseScreen')->with('message', 'License is missing or not valid');
      }




    $validator = Validator::make(Request::all(), [
      'email' => 'required|email|exists:' . config('crudbooster.USER_TABLE'),
      'password' => 'required',
    ]);

    if ($validator->fails()) {
      $message = $validator->errors()->all();

      return redirect()->back()->with(['message' => implode(', ', $message), 'message_type' => 'danger']);
    }

    $email = Request::input("email");
    $password = Request::input("password");
    $users = DB::table(config('crudbooster.USER_TABLE'))->where("email", $email)->first();

    $tenant = DB::table("tenants")
        ->where("id", $users->tenant)
        ->first()->domain_name;

    $array = isset($_SERVER) && isset($_SERVER['HTTP_HOST']) ? explode('.', $_SERVER['HTTP_HOST']) : [];

    //tenant specific login page
    $tenant_domain_name = isset($array[0]) ? $array[0] : '';
    $tenant_domain_name = Tenant::where('domain_name', $tenant_domain_name)->first() !== null ? Tenant::where('domain_name', $tenant_domain_name)->first()->domain_name : '';

    $priv = DB::table("cms_privileges")
        ->where("id", $users->id_cms_privileges)
        ->first();




    if (
          \Hash::check($password, $users->password) && $users->status == "Active" &&
            ( ($tenant_domain_name == $tenant || !$tenant_domain_name) || $priv->is_superadmin == 1)
        ) {
      $priv = DB::table("cms_privileges")
        ->where("id", $users->id_cms_privileges)
        ->first();

      $roles = DB::table('cms_privileges_roles')
        ->where('id_cms_privileges', $users->id_cms_privileges)
        ->join('cms_moduls', 'cms_moduls.id', '=', 'id_cms_moduls')
        ->select('cms_moduls.name', 'cms_moduls.path', 'is_visible', 'is_create', 'is_read', 'is_edit', 'is_delete')
        ->where('cms_moduls.deleted_at', null)
        ->get();

      $photo = UserHelper::icon($users->id);
      Session::put('admin_id', $users->id);
      Session::put('admin_is_superadmin', $priv->is_superadmin);
      Session::put('admin_name', $users->name);
      Session::put('admin_photo', $photo);
      Session::put('admin_privileges_roles', $roles);
      Session::put("admin_privileges", $users->id_cms_privileges);
      Session::put('admin_privileges_name', $priv->name);
      Session::put('admin_lock', 0);
      Session::put('theme_color', $priv->theme_color);
      Session::put("appname", CRUDBooster::getSetting('appname'));

      // Aggiuntivo, non sostituisce niente sopra: popola anche il guard
      // Laravel standard (config/auth.php: guard 'web' -> App\User, gia'
      // mappato su cms_users) in vista della migrazione dell'auth verso i
      // guard nativi. Il codice esistente continua a leggere le chiavi di
      // sessione admin_* esattamente come prima - vedi
      // docs/refactoring/README.md per il piano.
      $userModel = \App\User::find($users->id);
      if ($userModel) {
          Auth::login($userModel);
      }

      CRUDBooster::insertLog(trans("crudbooster.log_login", ['email' => $users->email, 'ip' => Request::server('REMOTE_ADDR')]));

      $cb_hook_session = new \App\Http\Controllers\CBHook;
  

      $cb_hook_session->afterLogin();




      $isLicenseValid = LicenseHelper::canLicenseLogin();



      if (!$isLicenseValid) {
        return redirect()->route('getLicenseScreen')->with('message', 'License is missing or not valid');
      }


      return redirect(CRUDBooster::adminPath());
    } else {
      return redirect()->route('getLogin')->with('message', trans('crudbooster.alert_password_wrong'));
    }
  }

  public function getForgot()
  {
    if (CRUDBooster::myId()) {
      return redirect(CRUDBooster::adminPath());
    }

    $array = isset($_SERVER) && isset($_SERVER['HTTP_HOST']) ? explode('.', $_SERVER['HTTP_HOST']) : [];

    //tenant specific login page
    $tenant_domain_name = isset($array[0]) ? $array[0] : '';
    $tenant = Tenant::where('domain_name', $tenant_domain_name)->first();
    $favicon = CRUDBooster::getFavicon($tenant);

    $logo = CRUDBooster::getLogo($tenant);

    $background_color = CRUDBooster::getBackgroundColor($tenant);

    $background_image_src = CRUDBooster::getBackgroundImage($tenant);
    $background = $background_color . " url(" . $background_image_src . ")";


    return view('crudbooster::forgot',compact('tenant', 'favicon', 'logo', 'background'));
  }

  public function postForgot()
  {
    $validator = Validator::make(Request::all(), [
      'email' => 'required|email|exists:' . config('crudbooster.USER_TABLE'),
    ]);

    if ($validator->fails()) {
      $message = $validator->errors()->all();

      return redirect()->back()->with(['message' => implode(', ', $message), 'message_type' => 'danger']);
    }

    $rand_string = str_random(5);
    $password = \Hash::make($rand_string);

    DB::table(config('crudbooster.USER_TABLE'))->where('email', Request::input('email'))->update(['password' => $password]);

    $appname = CRUDBooster::getSetting('appname');
    $user = CRUDBooster::first(config('crudbooster.USER_TABLE'), ['email' => g('email')]);
    $user->password = $rand_string;
    CRUDBooster::sendEmail(['to' => $user->email, 'data' => $user, 'template' => 'forgot_password_backend']);

    CRUDBooster::insertLog(trans("crudbooster.log_forgot", ['email' => g('email'), 'ip' => Request::server('REMOTE_ADDR')]));

    return redirect()->route('getLogin')->with('message', trans("crudbooster.message_forgot_password"));
  }

  public function getLogout()
  {

    $me = CRUDBooster::me();
    CRUDBooster::insertLog(trans("crudbooster.log_logout", ['email' => $me->email]));

    // Aggiuntivo (vedi Fase 1 del refactoring auth, postLogin()): il guard
    // Laravel non viene invalidato da Session::flush() da solo - va
    // disconnesso esplicitamente, altrimenti dopo il logout la sessione
    // legacy risulta pulita ma Auth::check() resta vero.
    Auth::logout();

    Session::flush();

    return redirect()->route('getLogin')->with('message', trans("crudbooster.message_after_logout"));
  }
}

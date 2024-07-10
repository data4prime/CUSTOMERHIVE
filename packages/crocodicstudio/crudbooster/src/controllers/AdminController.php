<?php

namespace crocodicstudio\crudbooster\controllers;

use CRUDBooster;
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
    if (isset($tenant->favicon)) {
      //tenant favicon
      $favicon = $tenant->favicon;
    } elseif (CRUDBooster::getSetting('favicon')) {
      //default site favicon
      $favicon = asset(CRUDBooster::getSetting('favicon'));
    } else {
      //default crudbooster favicon
      $favicon = asset('vendor/crudbooster/assets/logo_crudbooster.png');
    }

    if (isset($tenant->logo)) {
      //tenant logo
      //$logo = $tenant->tenant_path.'/storage'.$tenant->logo;
      $logo = $tenant->logo;
    } elseif (CRUDBooster::getSetting('logo')) {
      //default site logo
      $logo = asset(CRUDBooster::getSetting('logo'));
    } else {
      //default crudbooster logo
      //$logo = asset('vendor/crudbooster/assets/logo_crudbooster.png');
      $logo = "";
    }

    if (isset($tenant->login_background_color)) {
      //tenant background_image
      $background_color = $tenant->login_background_color;
    } elseif (CRUDBooster::getSetting('login_background_color')) {
      //default site background_image
      $background_color = asset(CRUDBooster::getSetting('login_background_color'));
    } else {
      //default crudbooster background_image
      $background_color = '#dddddd';
    }

    if (isset($tenant->login_background_image)) {
      //tenant background_image
      $background_image_src =  $tenant->login_background_image;
    } elseif (CRUDBooster::getSetting('login_background_image')) {
      //default site background_image
      $background_image_src = asset(CRUDBooster::getSetting('login_background_image'));
    } else {
      //default crudbooster background_image
      $background_image_src = asset('vendor/crudbooster/assets/bg_blur3.jpg');
    }
    $background = $background_color . " url(" . $background_image_src . ")";

    return view('crudbooster::login', compact('tenant', 'favicon', 'logo', 'background'));
  }

  public function postLogin()
  {

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

    if (\Hash::check($password, $users->password) && $users->status == "Active" && ($tenant_domain_name == $tenant || $priv->is_superadmin == 1)) {
      $priv = DB::table("cms_privileges")
        ->where("id", $users->id_cms_privileges)
        ->first();

      $roles = DB::table('cms_privileges_roles')
        ->where('id_cms_privileges', $users->id_cms_privileges)
        ->join('cms_moduls', 'cms_moduls.id', '=', 'id_cms_moduls')
        ->select('cms_moduls.name', 'cms_moduls.path', 'is_visible', 'is_create', 'is_read', 'is_edit', 'is_delete')
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

      CRUDBooster::insertLog(trans("crudbooster.log_login", ['email' => $users->email, 'ip' => Request::server('REMOTE_ADDR')]));

      $cb_hook_session = new \App\Http\Controllers\CBHook;
      $cb_hook_session->afterLogin();

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

    return view('crudbooster::forgot');
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

    Session::flush();

    return redirect()->route('getLogin')->with('message', trans("crudbooster.message_after_logout"));
  }
}

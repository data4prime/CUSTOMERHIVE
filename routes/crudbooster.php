<?php

//DB
use Illuminate\Support\Facades\DB;

/* ROUTER FOR API GENERATOR */
// I controller "di sistema" (schermate admin CRUDBooster) vivono in
// App\Http\Controllers\System (vedi docs/refactoring/006-*): CBController/
// ApiController restano nel namespace del pacchetto, non sono modulo
// routabili di per se'.
$namespace = 'App\Http\Controllers\System';

Route::group(['middleware' => ['api', '\App\Http\Middleware\CBAuthAPI'], 'namespace' => 'App\Http\Controllers'], function () {
    //Router for custom api defeault

    //$apis = DB::table('cms_apicustom')->get();

    /*foreach($apis as $k => $v) {
        if (isset($v->permalink)) {
            Route::any('api/'.$v->permalink, $v->controller.'@execute_api');
        }
    }*/






    $dir = scandir(base_path("app/Http/Controllers"));
    foreach ($dir as $v) {
        // is_dir() esclude anche System/ (controller "di sistema", non scandito
        // qui) oltre ad Auth/ - lo scan e' pensato solo per i file generati
        // direttamente dentro app/Http/Controllers.
        if ($v == '.' || $v == '..' || $v == 'Auth' || is_dir(base_path("app/Http/Controllers/{$v}"))) {
            continue;
        }
        $v = str_replace('.php', '', $v);



        $controller = "App\Http\Controllers\\".$v;



        $controller = app($controller);
        if (isset($controller->permalink)) {
            $permalink = $controller->permalink;
        }


        $names = array_filter(preg_split('/(?=[A-Z])/', str_replace('Controller', '', $v)));

        $names = strtolower(implode('_', $names));


        if (substr($names, 0, 4) == 'api_') {
            $names = str_replace('api_', '', $names);
            if (isset($permalink)) {
                Route::any('api/'.$permalink, $v.'@execute_api');
                /*$api_endpoint = DB::table('cms_apicustom')->where('permalink', $permalink)->first();
                if ($api_endpoint && isset($api_endpoint->permalink)) {
                    Route::any('api/'.$api_endpoint->permalink, $v.'@execute_api');
                } else {
                    Route::any('api/'.$names, $v.'@execute_api');
                }*/
            } /* else {
                Route::any('api/'.$names, $v.'@execute_api');
            }*/

        }
    }
});

/* ROUTER FOR UPLOADS */
Route::group(['middleware' => ['web'], 'namespace' => $namespace], function () {
    Route::get('api-documentation', ['uses' => 'ApiCustomController@apiDocumentation', 'as' => 'apiDocumentation']);
    Route::get('download-documentation-postman', ['uses' => 'ApiCustomController@getDownloadPostman', 'as' => 'downloadDocumentationPostman']);
    Route::get('uploads/{one?}/{two?}/{three?}/{four?}/{five?}', ['uses' => 'FileController@getPreview', 'as' => 'fileControllerPreview']);
});

/* ROUTER FOR WEB */
Route::group(['middleware' => ['web'], 'prefix' => config('crudbooster.ADMIN_PATH'), 'namespace' => $namespace], function () {

    Route::post('activate-license', ['uses' => 'AdminController@postActivateLicense', 'as' => 'postActivateLicense']);
    Route::post('activate-existing-license', ['uses' => 'AdminController@postActivateExistingLicense', 'as' => 'postActivateExistingLicense']);

    Route::post('unlock-screen', ['uses' => 'AdminController@postUnlockScreen', 'as' => 'postUnlockScreen']);

    Route::get('lock-screen', ['uses' => 'AdminController@getLockscreen', 'as' => 'getLockScreen']);
    Route::get('register-license', ['uses' => 'AdminController@getLicensescreen', 'as' => 'getLicenseScreen']);
    Route::post('forgot', ['uses' => 'AdminController@postForgot', 'as' => 'postForgot']);
    Route::get('forgot', ['uses' => 'AdminController@getForgot', 'as' => 'getForgot']);
    Route::post('register', ['uses' => 'AdminController@postRegister', 'as' => 'postRegister']);
    Route::get('register', ['uses' => 'AdminController@getRegister', 'as' => 'getRegister']);
    Route::get('logout', ['uses' => 'AdminController@getLogout', 'as' => 'getLogout']);
    Route::post('login', ['uses' => 'AdminController@postLogin', 'as' => 'postLogin']);
    Route::get('login', ['uses' => 'AdminController@getLogin', 'as' => 'getLogin']);
});

// ROUTER FOR OWN CONTROLLER FROM CB
Route::group([
    'middleware' => ['web', '\App\Http\Middleware\CBBackend'],
    'prefix' => config('crudbooster.ADMIN_PATH'),
    'namespace' => 'App\Http\Controllers',
], function () use ($namespace) {

    Route::get('/',function () {
    });
    try {
        $moduls = DB::table('cms_moduls')->where('path', '!=', '')->where('controller', '!=', '')
            ->where('is_protected', 0)->where('deleted_at', null)->get();
        foreach ($moduls as $v) {
            CRUDBooster::routeController($v->path, $v->controller);
        }
    } catch (Exception $e) {

    }
});

// Route::get('/admin/mg_ordini/{ordine}/righe/add', [AdminRigheController::class, 'show']);

/* ROUTER FOR BACKEND CRUDBOOSTER */
Route::group([
    'middleware' => ['web', '\App\Http\Middleware\CBBackend'],
    'prefix' => config('crudbooster.ADMIN_PATH'),
    'namespace' => $namespace,
], function () {

    /* DO NOT EDIT THESE LINES */
    if (Request::is(config('crudbooster.ADMIN_PATH'))) {
        $menus = DB::table('cms_menus')->where('is_dashboard', 1)->first();
        if (! $menus) {
            CRUDBooster::routeController('/', 'AdminController', $namespace = 'App\Http\Controllers\System');
        }
    }

    CRUDBooster::routeController('api_generator', 'ApiCustomController', $namespace = 'App\Http\Controllers\System');

    try {

        // I controller "di sistema" vivono fisicamente in app/Http/Controllers/System
        // (non piu' in questa cartella): e' questo il criterio con cui si distingue
        // un modulo di sistema (cms_moduls.controller punta a un file qui presente)
        // da un modulo generato da interfaccia (finisce in App\Http\Controllers).
        $master_controller = glob(app_path('Http/Controllers/System/*.php'));
        foreach ($master_controller as &$m) {
            $m = str_replace('.php', '', basename($m));
        }

        $moduls = DB::table('cms_moduls')->whereIn('controller', $master_controller)->get();

        foreach ($moduls as $v) {
            if (@$v->path && @$v->controller) {
                CRUDBooster::routeController($v->path, $v->controller, $namespace = 'App\Http\Controllers\System');
            }
        }
    } catch (Exception $e) {

    }
});

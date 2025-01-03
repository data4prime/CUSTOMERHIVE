<?php

//DB 
use Illuminate\Support\Facades\DB;

/* ROUTER FOR API GENERATOR */
$namespace = '\crocodicstudio\crudbooster\controllers';

Route::group(['middleware' => ['api', '\crocodicstudio\crudbooster\middlewares\CBAuthAPI'], 'namespace' => 'App\Http\Controllers'], function () {
    //Router for custom api defeault

    //$apis = DB::table('cms_apicustom')->get();

    /*foreach($apis as $k => $v) {
        if (isset($v->permalink)) {
            Route::any('api/'.$v->permalink, $v->controller.'@execute_api');
        } 
    }*/






    $dir = scandir(base_path("app/Http/Controllers"));
    foreach ($dir as $v) {
        if ($v == '.' || $v == '..' || $v == 'Auth') {
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
    'middleware' => ['web', '\crocodicstudio\crudbooster\middlewares\CBBackend'],
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
    'middleware' => ['web', '\crocodicstudio\crudbooster\middlewares\CBBackend'],
    'prefix' => config('crudbooster.ADMIN_PATH'),
    'namespace' => $namespace,
], function () {

    /* DO NOT EDIT THESE LINES */
    if (Request::is(config('crudbooster.ADMIN_PATH'))) {
        $menus = DB::table('cms_menus')->where('is_dashboard', 1)->first();
        if (! $menus) {
            CRUDBooster::routeController('/', 'AdminController', $namespace = '\crocodicstudio\crudbooster\controllers');
        }
    }

    CRUDBooster::routeController('api_generator', 'ApiCustomController', $namespace = '\crocodicstudio\crudbooster\controllers');

    try {

        $master_controller = glob(__DIR__.'/controllers/*.php');
        foreach ($master_controller as &$m) {
            $m = str_replace('.php', '', basename($m));
        }

        $moduls = DB::table('cms_moduls')->whereIn('controller', $master_controller)->get();

        foreach ($moduls as $v) {
            if (@$v->path && @$v->controller) {
                CRUDBooster::routeController($v->path, $v->controller, $namespace = '\crocodicstudio\crudbooster\controllers');
            }
        }
    } catch (Exception $e) {

    }
});
$controllers_base_path = '\crocodicstudio\crudbooster\controllers\\';


<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|vendor/laravel/framework/src/Illuminate/Container/Container.php:1301
*/

use crocodicstudio\crudbooster\helpers\QlikHelper;

$controllers_base_path = '\crocodicstudio\crudbooster\controllers\\';

Route::get('/', function () {
    //esiste ancora la view('welcome')
    //TODO spostare tutto /admin in / per migliorare url se stiamo usando solo /admin e non c'è nessuna pagina pubblicata in /
    return redirect('admin');
});

//create qlik user
Route::get('admin/qlik/user/creare/{id}', $controllers_base_path . 'AdminCmsUsersController@create_qlik_user');


//qlik items
Route::get('admin/qlik_items/content/{qlik_item_id}', $controllers_base_path . 'AdminQlikItemsController@content_view');
//items authorization
Route::get('admin/qlik_items/access/{qlik_item_id}/alert/{alert_id}', $controllers_base_path . 'AdminQlikItemsController@access');
Route::get('admin/qlik_items/access/{qlik_item_id}', $controllers_base_path . 'AdminQlikItemsController@access');
Route::post('admin/qlik_items/{qlik_item_id}/auth', $controllers_base_path . 'AdminQlikItemsController@add_authorization');
Route::get('admin/qlik_items/{qlik_item_id}/deauth/{group_id}', $controllers_base_path . 'AdminQlikItemsController@remove_authorization');
Route::get('admin/qlik_items/tenant/{qlik_item_id}/alert/{alert_id}', $controllers_base_path . 'AdminQlikItemsController@tenant');
Route::get('admin/qlik_items/tenant/{qlik_item_id}', $controllers_base_path . 'AdminQlikItemsController@tenant');
Route::post('admin/qlik_items/{qlik_item_id}/add_tenant', $controllers_base_path . 'AdminQlikItemsController@add_tenant');
Route::get('admin/qlik_items/{qlik_item_id}/remove_tenant/{tenant_id}', $controllers_base_path . 'AdminQlikItemsController@remove_tenant');
//public url to access qlik public items
Route::get('qi/{proxy_token}', $controllers_base_path . 'QlikItemsController@show');

//menù edit customization
// Route::get('admin/menu_management', '\crocodicstudio\crudbooster\controllers\MenusController@getIndex');
Route::get('admin/menu_management/edit/{id}', $controllers_base_path . 'MenusController@customEdit')->name('MenusControllerGetEdit');
//Route::get('admin/menu_management/edit/{id}', $controllers_base_path . 'MenusController@getEdit')->name('MenusControllerGetEdit');
// Route::post('/admin/menu_management/save-menu')->name('MenusControllerPostSaveMenu');
// Route::delete('/admin/menu_management/save-menu')->name('MenusControllerGetDelete');
//tenant members
Route::get('admin/tenants/members/{tenant_id}', $controllers_base_path . 'AdminTenantsController@members');
//group tenants
Route::get('admin/tenants/group/{tenant_id}/alert/{alert_id}', $controllers_base_path . 'AdminTenantsController@group');
Route::get('admin/tenants/group/{tenant_id}', $controllers_base_path . 'AdminTenantsController@group');
Route::post('admin/tenants/{tenant_id}/add_group', $controllers_base_path . 'AdminTenantsController@add_group');
Route::get('admin/tenants/{tenant_id}/remove_group/{group_id}', $controllers_base_path . 'AdminTenantsController@remove_group');

//group members
Route::get('admin/groups/members/{group_id}/alert/{alert_id}', $controllers_base_path . 'AdminGroupsController@members');
Route::get('admin/groups/members/{group_id}', $controllers_base_path . 'AdminGroupsController@members');
Route::post('admin/groups/{group_id}/add_member', $controllers_base_path . 'AdminGroupsController@add_member');
Route::get('admin/groups/{group_id}/remove_member/{user_id}', $controllers_base_path . 'AdminGroupsController@remove_member');

//group allowed items
Route::get('admin/groups/items/{group_id}/alert/{alert_id}', $controllers_base_path . 'AdminGroupsController@items');
Route::get('admin/groups/items/{group_id}/alert/{alert_id}', $controllers_base_path . 'AdminGroupsController@items');
Route::get('admin/groups/items/{group_id}', $controllers_base_path . 'AdminGroupsController@items');
Route::post('admin/groups/{group_id}/add_item', $controllers_base_path . 'AdminGroupsController@add_item');
Route::get('admin/groups/{group_id}/remove_item/{item_id}', $controllers_base_path . 'AdminGroupsController@remove_item');

//group tenants
Route::get('admin/groups/tenant/{group_id}/alert/{alert_id}', $controllers_base_path . 'AdminGroupsController@tenant');
Route::get('admin/groups/tenant/{group_id}', $controllers_base_path . 'AdminGroupsController@tenant');
Route::post('admin/groups/{group_id}/add_tenant', $controllers_base_path . 'AdminGroupsController@add_tenant');
Route::get('admin/groups/{group_id}/remove_tenant/{tenant_id}', $controllers_base_path . 'AdminGroupsController@remove_tenant');

//user groups
Route::get('admin/users/groups/{user_id}/alert/{alert_id}', $controllers_base_path . 'AdminCmsUsersController@groups');
Route::get('admin/users/groups/{user_id}', $controllers_base_path . 'AdminCmsUsersController@groups');
Route::post('admin/users/{user_id}/add_group', $controllers_base_path . 'AdminCmsUsersController@add_group');
Route::get('admin/users/{user_id}/remove_group/{group_id}', $controllers_base_path . 'AdminCmsUsersController@remove_group');

//Qlik Server Routes
Route::get('admin/qlik_confs/QlikServerSenseHub/{id}', $controllers_base_path . 'AdminQlikItemsController@GetRouteSenseHub')->name('QlikServerSenseHub');
Route::get('admin/qlik_confs/QlikServerSenseQMC/{id}', $controllers_base_path . 'AdminQlikItemsController@GetRouteSenseQMC')->name('QlikServerSenseQMC');

//modules
Route::get('admin/module_generator/enable', $controllers_base_path . 'ModulsController@enable');
Route::post('admin/module_generator/save_enable', $controllers_base_path . 'ModulsController@saveEnable');

Route::get('/mashup/{componentID}', function ($componentID) {

    $mashups = DB::table('cms_statistic_components')->where('componentID', $componentID)->first();



    if ($mashups) {
        $mashups = json_decode($mashups->config);

        if (isset($mashups->mashups)){
             $mashup = DB::table('qlik_mashups')->where('id', $mashups->mashups)->first();
            if ($mashup) {
             $qlik_conf = DB::table('qlik_confs')->where('id', $mashup->conf)->first()->id;
            } else {
                $qlik_conf = null;
            }
        } else {
            $mashup = null;
            $qlik_conf = null;
        }


   

    
    }  else {
        $mashup = null;
        $qlik_conf = null;
    }
    

    if ($mashup && $qlik_conf) {
        return view('mashup', compact('componentID', 'mashup', 'qlik_conf', 'mashups'));
    }


    
});

Route::get('/mashup-objects/{mashup}/{componentID}/{objectid}', function ($mashup, $componentID, $objectid) {

$comp = DB::table('cms_statistic_components')->where('componentID', $componentID)->first();



    if ($comp) {
        $config = json_decode($comp->config);
    }

    if (!$config) {
        $config = new \stdClass();
        $config->mashups = 0;
        $config->object = 0;
    }

    $mashup = DB::table('qlik_mashups')->where('id', $mashup)->first();
    //dd($mashup);
    if ($mashup) {
        $qlik_conf = DB::table('qlik_confs')->where('id', $mashup->conf)->first()->id;
    } else {
        $qlik_conf = null;
    }
    



    if ($mashup && $qlik_conf) {
        return view('mashup_objects', compact('componentID', 'mashup', 'qlik_conf', 'config'));
    }


    
});
<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    //esiste ancora la view('welcome')
    //TODO spostare tutto /admin in / per migliorare url se stiamo usando solo /admin e non c'è nessuna pagina pubblicata in /
    return redirect('admin');
});

//qlik items
Route::get('admin/qlik_items/content/{qlik_item_id}', 'AdminQlikItemsController@content_view');
//items authorization
Route::get('admin/qlik_items/access/{qlik_item_id}/alert/{alert_id}', 'AdminQlikItemsController@access');
Route::get('admin/qlik_items/access/{qlik_item_id}', 'AdminQlikItemsController@access');
Route::post('admin/qlik_items/{qlik_item_id}/auth', 'AdminQlikItemsController@add_authorization');
Route::get('admin/qlik_items/{qlik_item_id}/deauth/{group_id}', 'AdminQlikItemsController@remove_authorization');

//group members
Route::get('admin/groups/members/{group_id}/alert/{alert_id}', 'AdminGroupsController@members');
Route::get('admin/groups/members/{group_id}', 'AdminGroupsController@members');
Route::post('admin/groups/{group_id}/add_member', 'AdminGroupsController@add_member');
Route::get('admin/groups/{group_id}/remove_member/{user_id}', 'AdminGroupsController@remove_member');

//group allowed items
Route::get('admin/groups/items/{group_id}/alert/{alert_id}', 'AdminGroupsController@items');
Route::get('admin/groups/items/{group_id}/alert/{alert_id}', 'AdminGroupsController@items');
Route::get('admin/groups/items/{group_id}', 'AdminGroupsController@items');
Route::post('admin/groups/{group_id}/add_item', 'AdminGroupsController@add_item');
Route::get('admin/groups/{group_id}/remove_item/{item_id}', 'AdminGroupsController@remove_item');

//user groups
Route::get('admin/users/groups/{user_id}/alert/{alert_id}', 'AdminCmsUsersController@groups');
Route::get('admin/users/groups/{user_id}', 'AdminCmsUsersController@groups');
Route::post('admin/users/{user_id}/add_group', 'AdminCmsUsersController@add_group');
Route::get('admin/users/{user_id}/remove_group/{group_id}', 'AdminCmsUsersController@remove_group');

//Qlik Server Routes
Route::get('admin/QlikServerSenseHub', 'AdminQlikItemsController@GetRouteSenseHub')->name('QlikServerSenseHub');
Route::get('admin/QlikServerSenseQMC', 'AdminQlikItemsController@GetRouteSenseQMC')->name('QlikServerSenseQMC');

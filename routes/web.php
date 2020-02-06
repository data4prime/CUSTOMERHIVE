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
    return view('welcome');
});

//qlik items
Route::get('admin/qlik_items/content/{qlik_item_id}', 'AdminQlikItemsController@content_view');
//items authorization
Route::get('admin/qlik_items/access/{qlik_item_id}', 'AdminQlikItemsController@access');
Route::post('admin/qlik_items/{qlik_item_id}/auth', 'AdminQlikItemsController@add_authorization');
Route::get('admin/qlik_items/{qlik_item_id}/deauth/{group_id}', 'AdminQlikItemsController@remove_authorization');

//group members
Route::get('admin/groups/members/{group_id}', 'AdminGroupsController@members');
Route::post('admin/groups/{group_id}/add_member', 'AdminGroupsController@add_member');
Route::get('admin/groups/{group_id}/remove_member/{user_id}', 'AdminGroupsController@remove_member');

//user groups
Route::get('admin/users/groups/{user_id}', 'AdminCmsUsersController@groups');
Route::post('admin/users/{user_id}/add_group', 'AdminCmsUsersController@add_group');
Route::get('admin/users/{user_id}/remove_group/{group_id}', 'AdminCmsUsersController@remove_group');

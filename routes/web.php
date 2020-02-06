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

//group members
Route::get('admin/groups/members/{group_id}', 'AdminGroupsController@members');
Route::get('admin/groups/{group_id}/remove_member/{user_id}', 'AdminGroupsController@remove_member');
Route::post('admin/groups/{group_id}/add_member', 'AdminGroupsController@add_member');

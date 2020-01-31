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

Route::get('admin/qlik_items/content/{qlik_item_id}', 'AdminQlikItemsController@content_view');

Route::get('admin/qlik_items/qliktest', function () {
    return view('qliktest');
});

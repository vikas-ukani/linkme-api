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

Route::view('/', "welcome");
Route::get('password/find/{token}', 'API\UserController@find');
Route::post('password/reset', 'API\UserController@reset');
Route::view('/terms-conditions', 'terms-conditions');
Route::view('/privacy-policy', 'privacy-policy');
Route::view('/provider-payment-account-setup-redirection', 'provider-payment-account-setup-redirection');

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\Admin\ReportController;
use App\Http\Controllers\API\Admin\ProfileController;
use App\Http\Controllers\API\Admin\CustomerController;
use App\Http\Controllers\API\Admin\ProviderController;
use App\Http\Controllers\API\Admin\TransactionController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('admin/login', 'API\AdminController@adminLogin');
Route::group(['prefix' => 'admin', 'middleware' => ['auth:admin-api', 'scopes:admin']], function () {
    // authenticated staff routes here
    /* AdminController Route */
    Route::get('profile/{id}', [ProfileController::class, 'profile']);
    Route::put('profile/{id}', [ProfileController::class, 'updateProfile']);

    Route::get('topproviders', 'API\AdminController@topProviders');
    Route::get('topcustomers', 'API\AdminController@topCustomers');
    Route::get('top-services', [AdminController::class, 'topServices']);
    Route::get('totalbookings', 'API\AdminController@totalBookings');
    Route::get('activeuserscount', 'API\AdminController@activeUsersCount');
    Route::get('platformearning', 'API\AdminController@platformEarning');

    // Modules
    Route::resource('customers', 'API\Admin\CustomerController');
    Route::put('customers-partial-update/{id}', [CustomerController::class, 'partialUpdates']);
    Route::resource('bookings', 'API\Admin\BookingController');
    Route::resource('providers', 'API\Admin\ProviderController');
    Route::put('providers-partial-update/{id}', [ProviderController::class, 'partialUpdates']);
    Route::resource('paymentlogs', 'API\Admin\PaymentLogController');

    // Transactions
    Route::get('transactions', [TransactionController::class, 'index']);
    Route::get('reports', [ReportController::class, 'getReport']);
});

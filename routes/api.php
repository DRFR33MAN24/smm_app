<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group(['namespace' => 'api', 'middleware' => ['api_lang']], function () {

    Route::group(['prefix' => 'auth', 'namespace' => 'auth'], function () {
        Route::post('register', 'AuthController@register');
        Route::post('login', 'AuthController@login');


        Route::post('check-phone', 'PhoneVerificationController@check_phone');
        Route::post('resend-otp-check-phone', 'PhoneVerificationController@resend_otp_check_phone');
        Route::post('verify-phone', 'PhoneVerificationController@verify_phone');
        Route::post('check-email', 'EmailVerificationController@check_email');
        Route::post('resend-otp-check-email', 'EmailVerificationController@resend_otp_check_email');
        Route::post('verify-email', 'EmailVerificationController@verify_email');

        Route::post('forgot-password', 'ForgotPassword@reset_password_request');
        Route::post('verify-otp', 'ForgotPassword@otp_verification_submit');
        Route::put('reset-password', 'ForgotPassword@reset_password_submit');

        Route::any('social-login', 'SocialAuthController@social_login');
    });



    Route::group(['prefix' => 'categories'], function () {
        Route::get('categories', 'CategoryController@get_categories');
        Route::get('tags', 'CategoryController@get_categories_tags');
    });

    Route::group(['prefix' => 'payment', 'middleware' => 'auth:api'], function () {

        Route::get('packages', 'PaymentController@get_packages');
    });

    Route::group(['prefix' => 'customer', 'middleware' => 'auth:api'], function () {
        Route::get('info', 'CustomerController@info');
        Route::put('update-profile', 'CustomerController@update_profile');
        Route::get('account-delete/{id}', 'CustomerController@account_delete');
        Route::put('cm-firebase-token', 'CustomerController@update_cm_firebase_token');
    });

    Route::group(['prefix' => 'orders', 'middleware' => 'auth:api'], function () {
        Route::post('place-order', 'OrderController@place_order');
        Route::post('place-transfer-order', 'OrderController@place_transfer_order');
        Route::post('place-telecom-credit-transfer-order', 'OrderController@place_telecom_credit_transfer_order');
        Route::post('place-telecom-order', 'OrderController@place_telecom_order');
        Route::get('orders', 'OrderController@get_orders');
    });

    Route::group(['prefix' => 'transactions', 'middleware' => 'auth:api'], function () {

        Route::get('transactions', 'TransactionController@get_transactions');
    });
    Route::group(['prefix' => 'notifications', 'middleware' => 'auth:api'], function () {

        Route::get('/', 'NotificationController@get_notifications');
    });

    Route::group(['prefix' => 'config'], function () {
        Route::get('/', 'ConfigController@configuration');
    });
});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

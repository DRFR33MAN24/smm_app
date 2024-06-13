<?php

namespace App\Http\Controllers\api;

use App\CPU\Helpers;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;


use Illuminate\Http\Request;


class ConfigController extends Controller
{
    public function configuration()
    {

        $social_login = [];
        foreach (Helpers::get_business_settings('social_login') as $social) {
            $config = [
                'login_medium' => $social['login_medium'],
                'status' => (boolean)$social['status']
            ];
            array_push($social_login, $config);
        }

        $languages = Helpers::get_business_settings('pnc_language');
        $lang_array = [];
        foreach ($languages as $language) {
            array_push($lang_array, [
                'code' => $language,
                'name' => Helpers::get_language_name($language)
            ]);
        }
        $payment = [

            'ssl_commerz_payment' => Helpers::get_business_settings('ssl_commerz_payment')['status'] == 1 ?? 0,
            'paypal' => Helpers::get_business_settings('paypal')['status'] == 1 ?? 0,
            'stripe' => Helpers::get_business_settings('stripe')['status'] == 1 ?? 0,
            'razor_pay' => Helpers::get_business_settings('razor_pay')['status'] == 1 ?? 0,
            'senang_pay' => Helpers::get_business_settings('senang_pay')['status'] == 1 ?? 0,
            'paytabs' => Helpers::get_business_settings('paytabs')['status'] == 1 ?? 0,
            'paystack' => Helpers::get_business_settings('paystack')['status'] == 1 ?? 0,
            'paymob_accept' => Helpers::get_business_settings('paymob_accept')['status'] == 1 ?? 0,
            'fawry_pay' => Helpers::get_business_settings('fawry_pay')['status'] == 1 ?? 0,
            'mercadopago' => Helpers::get_business_settings('mercadopago')['status'] == 1 ?? 0,
            'liqpay' => Helpers::get_business_settings('liqpay')['status'] == 1 ?? 0,

            'paytm' => Helpers::get_business_settings('paytm')['status'] == 1 ?? 0,
            'bkash' => Helpers::get_business_settings('bkash')['status'] == 1 ?? 0
        ];





        return response()->json([
            'about_us' => Helpers::get_business_settings('about_us'),
            'privacy_policy' => Helpers::get_business_settings('privacy_policy'),
            'company_email' => Helpers::get_business_settings('company_email'),
            'company_phone' => (string)Helpers::get_business_settings('company_phone'),
            'company_phone2' => (string)Helpers::get_business_settings('company_phone2'),
            'usdt_wallet' => Helpers::get_business_settings('usdt_wallet'),

            'terms_and_conditions' => Helpers::get_business_settings('terms_and_conditions'),
            'refund_policy' => Helpers::get_business_settings('refund-policy'),
            'return_policy' => Helpers::get_business_settings('return-policy'),
            'cancellation_policy' => Helpers::get_business_settings('cancellation-policy'),
            'currency_conversion_factor' => Helpers::get_business_settings('currency_conversion_factor'),
            'maintenance_mode' => (boolean)Helpers::get_business_settings('maintenance_mode') ?? 0,
            'language' => $lang_array,
          
            'email_verification' => (boolean)Helpers::get_business_settings('email_verification'),
            'phone_verification' => (boolean)Helpers::get_business_settings('phone_verification'),
            'country_code' => Helpers::get_business_settings('country_code'),
            'social_login' => $social_login,
            'currency_model' => Helpers::get_business_settings('currency_model'),
            'forgot_password_verification' => Helpers::get_business_settings('forgot_password_verification'),
            'announcement'=> Helpers::get_business_settings('announcement'),
            'pixel_analytics'=> Helpers::get_business_settings('pixel_analytics'),
            'software_version'=>env('SOFTWARE_VERSION'),
            'payment_methods' => $payment,
        ]);
    }
}
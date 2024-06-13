<?php

namespace App\Http\Controllers\api;


use App\CPU\Helpers;
use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\CreditBundle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class PaymentController extends Controller
{
    public function payment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'credit_bundle_id' => 'required',

        ]);

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }
        $value = CreditBundle::where('id',$request['credit_bundle_id'])->value;
      //  session()->put('user_id', $request['user_id']);
        session()->put('value',$value);


        $payment_method = $request->payment_method;
        



      //  $user = User::find($request['user_id']);
      $user =$request->user()->id;

        if (isset($user)) {
            return view('payment', compact('payment_method'));
        }

        return response()->json(['errors' => ['code' => 'order-payment', 'message' => 'Data not found']], 403);
    }

    public function get_packages(Request $request){
        $packages =[
            'packages'=>CreditBundle::all()
        ];
        return response()->json($packages,200);
    }

    public function success()
    {
        return response()->json(['message' => 'Payment succeeded'], 200);
    }

    public function fail()
    {
        return response()->json(['message' => 'Payment failed'], 403);
    }
}
<?php

namespace App\Http\Controllers\api;

use App\CPU\Helpers;
use App\Http\Controllers\Controller;
use App\CPU\ImageManager;
use App\Jobs\ProcessOrder;
use App\Models\ApiProvider;
use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Validator;
use App\Models\Order;
use App\Models\Service;
use App\Models\Transaction;
use App\Providers\OrderPlaced;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Stevebauman\Purify\Facades\Purify;
use Ixudra\Curl\Facades\Curl;
use App\Helper\DhruFusion;
use App\Models\Card;
use App\Models\Category;
use App\Models\User;
use App\Http\Traits\Notify;
use App\Services\ApiProviderFactory;

class OrderController extends Controller
{
    use Notify;

    protected $apiProviderFactory;

    public function __construct(ApiProviderFactory $apiProviderFactory)
    {
        $this->apiProviderFactory = $apiProviderFactory;
    }

    public function get_orders(Request $request)
    {
        //LOG::info($request);
        $limit = $request["limit"];
        $date = $request['date'];

        $offset = $request["offset"];
        $paginator = Order::query();

        $paginator->with(['service']);
        $paginator->where('user_id', '=', auth()->user()->id);



        if ($date) {
            $paginator = $paginator->whereDate(
                'created_at',
                '=',
                Carbon::parse($date)->toDateString()
            );
        }

        $paginator = $paginator->latest()->paginate($limit, ['*'], 'page', $offset);

        /*$paginator->count();*/
        $orders = [
            'total_size' => $paginator->total(),
            'limit' => (int) $limit,
            'offset' => (int) $offset,
            'orders' => $paginator->items()
        ];
        // Log::info(json_encode($paginator->items()));

        return response()->json($orders, 200);
    }

    public function place_telecom_credit_transfer_order(Request $request)
    {
        $req = Purify::clean($request->all());



        $rules = [

            'phone' => 'required',
            'amount' => "required",


        ];

        $validator = Validator::make($req, $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $basic = (object) config('basic');
        $amount = round($req['amount'], $basic->fraction_number);

        $user = Auth::user();


        if ($user->lbalance < $amount) {
            return response()->json(['message' => Helpers::translate('Insufficient balance in your wallet.!')], 200);
        }


        if ($amount > 0) {
            $category = Category::where('category_type', '=', 'telecom credit')->first();
            $service = Service::where('category_id', '=', $category->id)->first();
            $order = new Order();
            $order->user_id = $user->id;
            $order->category_id = $category->id;
            $order->service_id = $service->id;
            // $order->link = $req['link'];
            $order->quantity = 1;
            $order->status = 'processing';
            $order->reason = 'Amount:' . $amount . ',' . 'phone:' . $req['phone'];
            $order->price = $amount;
            $order->currency = 'LBP';
            $order->runs = isset($req['runs']) && !empty($req['runs']) ? $req['runs'] : null;
            $order->interval = isset($req['interval']) && !empty($req['interval']) ? $req['interval'] : null;

            $order->save();
            $user->lbalance -= $amount;

            $user->save();


            $transaction = new Transaction();
            $transaction->user_id = $user->id;
            $transaction->trx_type = '-';
            $transaction->currency = 'LBP';
            $transaction->amount = $amount;
            $transaction->remarks = 'Telecom Credit Transfer';
            $transaction->trx_id = strRandom();
            $transaction->charge = 0;
            $transaction->save();

            return response()->json(['message' => Helpers::translate('successfully created!')], 200);

        } else {

            return response()->json(['message' => Helpers::translate('enter amount to complete transfer!')], 200);

        }






    }
    public function place_transfer_order(Request $request)
    {
        $req = Purify::clean($request->all());



        $rules = [

            'phone' => 'required',
            'amount' => "required",
            'amountLBP' => 'required'

        ];

        $validator = Validator::make($req, $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $basic = (object) config('basic');
        $amount = round($req['amount'], $basic->fraction_number);
        $amountLBP = round($req['amountLBP'], $basic->fraction_number);

        if ($amount == 0 && $amountLBP == 0) {
            return response()->json(['message' => Helpers::translate('enter amount to complete transfer!')], 200);
        }
        $user = Auth::user();
        $target_user = User::where(['phone' => $req['phone']])->first();
        if ($user->balance < $amount) {
            return response()->json(['message' => Helpers::translate('Insufficient balance in your wallet.!')], 200);
        }
        if ($user->lbalance < $amountLBP) {
            return response()->json(['message' => Helpers::translate('Insufficient balance in your wallet.!')], 200);
        }
        if (!isset($target_user)) {
            return response()->json(['message' => Helpers::translate('User not found, please enter correct phone number')], 200);
        }

        if ($amount > 0) {
            $category = Category::where('category_type', '=', 'balance')->first();
            $service = Service::where('category_id', '=', $category->id)->first();
            $order = new Order();
            $order->user_id = $user->id;
            $order->category_id = $category->id;
            $order->service_id = $service->id;
            // $order->link = $req['link'];
            $order->quantity = 1;
            $order->status = 'completed';
            $order->price = $amount;
            $order->runs = isset($req['runs']) && !empty($req['runs']) ? $req['runs'] : null;
            $order->interval = isset($req['interval']) && !empty($req['interval']) ? $req['interval'] : null;

            $order->save();
            $user->balance -= $amount;
            $target_user->balance += $amount;
            $user->save();
            $target_user->save();

            $transaction = new Transaction();
            $transaction->user_id = $user->id;
            $transaction->trx_type = '-';
            $transaction->amount = $amount;
            $transaction->remarks = 'Balance Transfer';
            $transaction->trx_id = strRandom();
            $transaction->charge = 0;
            $transaction->save();

            $msg = [
                'description' => $user->phone . ' sent you ' . $amount . '$',
                'title' => 'Balance Update',
                'image' => ''
            ];
            Helpers::send_push_notif_to_device($target_user->cm_firebase_token, $msg);
        }
        if ($amountLBP > 0) {
            $category = Category::where('category_type', '=', 'balance')->first();
            $service = Service::where('category_id', '=', $category->id)->first();
            $order = new Order();
            $order->user_id = $user->id;
            $order->category_id = $category->id;
            $order->service_id = $service->id;
            // $order->link = $req['link'];
            $order->quantity = 1;
            $order->status = 'completed';
            $order->price = $amountLBP;
            $order->currency = "LBP";
            $order->runs = isset($req['runs']) && !empty($req['runs']) ? $req['runs'] : null;
            $order->interval = isset($req['interval']) && !empty($req['interval']) ? $req['interval'] : null;

            $order->save();
            $user->lbalance -= $amountLBP;
            $target_user->lbalance += $amountLBP;
            $user->save();
            $target_user->save();

            $transaction = new Transaction();
            $transaction->user_id = $user->id;
            $transaction->trx_type = '-';
            $transaction->currency = 'LBP';
            $transaction->amount = $amountLBP;
            $transaction->remarks = 'Balance Transfer';
            $transaction->trx_id = strRandom();
            $transaction->charge = 0;
            $transaction->save();

            $msg = [
                'description' => $user->phone . ' sent you ' . $amountLBP . 'LBP',
                'title' => 'Balance Update',
                'image' => ''
            ];
            Helpers::send_push_notif_to_device($target_user->cm_firebase_token, $msg);
        }



        return response()->json(['message' => Helpers::translate('successfully created!')], 200);


    }

    public function place_telecom_order(Request $request)
    {
        $req = Purify::clean($request->all());



        $rules = [
            'category' => 'required|integer|min:1|not_in:0',
            'service' => 'required|integer|min:1|not_in:0',

        ];
        $validator = Validator::make($req, $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $service = Service::findOrFail($request->service);


        $basic = (object) config('basic');
        $quantity = $request->quantity;

        if ($service->min_amount <= $quantity && $service->max_amount >= $quantity) {
            $user = Auth::user();
            if ($user->is_reseller) {
                $userRate = ($service->user_rate) ?? $service->reseller_price;

            } else {
                $userRate = ($service->user_rate) ?? $service->price;
            }


            $price = round(($quantity * $userRate), $basic->fraction_number);

            if ($user->lbalance < $price) {
                return response()->json(['message' => Helpers::translate('Insufficient balance in your wallet.!')], 200);
            }


            $available_cards = Card::where([
                'status' => '1',
                'service_id' => $service->id
            ])->limit($quantity)->get();

            if ($quantity > count($available_cards)) {
                return response()->json(['message' => Helpers::translate('Quantity not available.!')], 200);
            }
            $tokens = '';
            foreach ($available_cards as $card) {
                $tokens .= 'SERIAL NUM: ' . $card->serial . '%SECRET CODE: ' . $card->token . '%EXPIRY DATE: ' . $card->expiry . ',';
                Card::where('id', '=', $card->id)->update(['status' => '0']);
            }
            $order = new Order();
            $order->user_id = $user->id;
            $order->category_id = $req['category'];
            $order->service_id = $req['service'];
            $order->reason = $tokens;
            $order->quantity = $req['quantity'];
            $order->status = 'completed';
            $order->price = $price;
            $order->currency = 'LBP';
            $order->runs = isset($req['runs']) && !empty($req['runs']) ? $req['runs'] : null;
            $order->interval = isset($req['interval']) && !empty($req['interval']) ? $req['interval'] : null;
            $order->save();
            $user->lbalance -= $price;
            $user->save();

            $transaction = new Transaction();
            $transaction->user_id = $user->id;
            $transaction->trx_type = '-';
            $transaction->amount = $price;
            $transaction->remarks = 'Place order';
            $transaction->currency = 'LBP';
            $transaction->trx_id = strRandom();
            $transaction->charge = 0;
            $transaction->save();

            return response()->json(['message' => Helpers::translate('successfully created!')], 200);


        } else {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
    }

    public function place_order(Request $request)
    {



        $req = Purify::clean($request->all());
        Log::info($req);


        $rules = [
            'category' => 'required|integer|min:1|not_in:0',
            'service' => 'required|integer|min:1|not_in:0',

        ];
        $validator = Validator::make($req, $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $service = Service::findOrFail($request->service);
        $provider = null;
        $providerInstance= null;
        if (isset($service->api_provider_id)) {

            $provider = ApiProvider::find($service->api_provider_id);
            // TODO
            $providerInstance = $this->apiProviderFactory->createProvider($provider->type);
        }


        $basic = (object) config('basic');
        $quantity = $request->quantity;
        $conversion_rate = $req['currency'] != 'USD' ?
        (float) BusinessSetting::where('type', '=', 'currency_conversion_factor')->first()->value:1;

       
            if ($provider != null) {
               
                    if ($service->min_amount <= $quantity && $service->max_amount >= $quantity) {
                        $user = Auth::user();
                        if ($user->is_reseller) {
                            $userRate = ($service->user_rate) ?? $service->reseller_price;

                        } else {
                            $userRate = ($service->user_rate) ?? $service->price;
                        }

                        $price = round(($quantity * $userRate*$conversion_rate), $basic->fraction_number);



                        if ($req['currency'] == "USD") {
                      
                            if ($user->balance < $price) {
                                return response()->json(['message' => Helpers::translate('Insufficient balance in your wallet.!')], 200);
                            }
                        } else {
                            if ($user->lbalance < $price) {
                                return response()->json(['message' => Helpers::translate('Insufficient balance in your wallet.!')], 200);
                            }
                        }



                        if (isset($service->api_provider_id)) {
                            $apiproviderdata = ApiProvider::find($service->api_provider_id);


                            $custom_fields = explode(",", $service['custom_fields']);
                            Log::info($custom_fields);
                            $params = [];
                            foreach ($custom_fields as $field) {
                                if ($field != "") {
                                    // $fieldFiltered=str_replace(' ','_',$field);
                    
                                    $params[$field] = $req[$field];
                                }
                            }
                    
                            if (isset($service->max_amount)) {
                                if ($service->max_amount > 1) {
                                    $params['QNT'] = $req['quantity'];
                                }
                            }

                            $details = [
                               
                                'service_id'=>$req['service_id'],
                                'link'=>$req['link'],
                                'quantity'=>$req['quantity'],
                                'Zone_ID'=>$req['Zone_ID'],
                                'User_ID'=>$req['User_ID'],
                                'params'=>$params
                            ];


                           $result = $providerInstance->placeOrder($apiproviderdata,$details);

                            if (!isset($result['error'])) {
                                $order = new Order();
                                $order->user_id = $user->id;
                                $order->category_id = $req['category'];
                                $order->service_id = $req['service'];
                                $order->link = isset($req['link']) && !empty($req['link']) ? $req['link'] : '';
                                $order->quantity = $req['quantity'];
                                $order->status = 'processing';
                                $order->price = $price;
                                $order->runs = isset($req['runs']) && !empty($req['runs']) ? $req['runs'] : 1;
                                $order->interval = isset($req['interval']) && !empty($req['interval']) ? $req['interval'] : 1;
                                $order->status_description = $result["order_id"];
                                $order->api_order_id = $result["order_id"];
                                $order->currency = $req['currency'];
                                $order->save();

                                if ($req['currency'] == "USD") {
                      

                                    $user->balance -= $price;
                                } else {
                                    $user->lbalance -= $price;
                                }

                                $user->save();

                                $transaction = new Transaction();
                                $transaction->user_id = $user->id;
                                $transaction->trx_type = '-';
                                $transaction->amount = $price;
                                $transaction->currency = $req['currency'];
                                $transaction->remarks = 'Place order';
                                $transaction->trx_id = strRandom();
                                $transaction->charge = 0;
                                $transaction->save();


                                $msg = [
                                    'username' => $user->username,
                                    'price' => $price,
                                    'currency' => $basic->currency
                                ];
                                $action = [
                                    "link" => route('admin.order.edit', $order->id),
                                    "icon" => "fas fa-cart-plus text-white"
                                ];
                                $this->adminPushNotification('ORDER_CREATE', $msg, $action);




                                return response()->json(['message' => Helpers::translate('successfully created!')], 200);

                            } else {
                                return response()->json(['message' => $result['error']], 200);

                            }
                        }



                    } else {
                        return response()->json(['errors' => Helpers::error_processor($validator)], 403);
                    }
                }
                else {


                    $user = Auth::user();
                    if ($user->is_reseller) {
                        $userRate = ($service->user_rate) ?? $service->reseller_price;
    
                    } else {
                        $userRate = ($service->user_rate) ?? $service->price;
                    }
    
                    $price = round(($quantity * $userRate*$conversion_rate), $basic->fraction_number);
    
                    if ($req['currency'] == "USD") {
                      
                        if ($user->balance < $price) {
                            return response()->json(['message' => Helpers::translate('Insufficient balance in your wallet.!')], 200);
                        }
                    } else {
                        if ($user->lbalance < $price) {
                            return response()->json(['message' => Helpers::translate('Insufficient balance in your wallet.!')], 200);
                        }
                    }
                    
    
    
                    // $order->runs = isset($req['runs']) && !empty($req['runs']) ? $req['runs'] : null;
                    // $order->interval = isset($req['interval']) && !empty($req['interval']) ? $req['interval'] : null;
    
                    $custom_fields = explode(",", $service->custom_fields);
                    Log::info($custom_fields);
                    $params = "";
                    foreach ($custom_fields as $field) {
                        if ($field != "") {
                            $fieldFiltered = str_replace(' ', '_', $field);
    
                            $params = $params . $field . ':' . $req[$fieldFiltered] . ",";
                        }
                    }
    
    
    
    
    
    
    
    
                    $order = new Order();
                    $order->user_id = $user->id;
                    $order->category_id = $req['category'];
                    $order->service_id = $req['service'];
    
                    $order->quantity = $req['quantity'];
                    $order->status = 'processing';
                    $order->price = $price;
                    $order->currency = $req['currency'];
                    $order->reason = $params;
                    // $order->api_order_id = $response['SUCCESS'][0]['REFERENCEID'];
                    // $order->status_description = $response['SUCCESS'][0]['MESSAGE'];
                    $order->save();
                    if ($req['currency'] == "USD") {
                      

                        $user->balance -= $price;
                    } else {
                        $user->lbalance -= $price;
                    }
                    $user->save();
    
                    $transaction = new Transaction();
                    $transaction->user_id = $user->id;
                    $transaction->trx_type = '-';
                    $transaction->amount = $price;
                    $transaction->currency = $req['currency'];
                    $transaction->remarks = 'Place order';
                    $transaction->trx_id = strRandom();
                    $transaction->charge = 0;
                    $transaction->save();
    
    
                    $msg = [
                        'username' => $user->username,
                        'price' => $price,
                        'currency' => $basic->currency
                    ];
                    $action = [
                        "link" => route('admin.order.edit', $order->id),
                        "icon" => "fas fa-cart-plus text-white"
                    ];
                    $this->adminPushNotification('ORDER_CREATE', $msg, $action);
                    return response()->json(['message' => Helpers::translate('successfully created!')], 200);
    
    
    
                }
            
       




    }
}

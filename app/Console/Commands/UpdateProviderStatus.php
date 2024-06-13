<?php

namespace App\Console\Commands;

use App\CPU\Helpers;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Ixudra\Curl\Facades\Curl;
use Illuminate\Console\Command;
use App\Helper\DhruFusion;
use Illuminate\Support\Facades\Log;

class UpdateProviderStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-provider:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Provider Status for Order';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
       
    


        $orders = Order::with(['service', 'service.provider', 'user'])->whereNotIn('status', ['completed', 'refunded', 'canceled'])->whereHas('service', function ($query) {
            $query->whereNotNull('api_provider_id')->orWhere('api_provider_id', '!=', 0);
        })->get();

        foreach ($orders as $order) {
            $service = $order->service;
            if (isset($service->api_provider_id)) {
                $apiproviderdata = $service->provider;
                if ($apiproviderdata->type=="SMM") {
                    $apiservicedata = Curl::to($apiproviderdata['url'])->withData(['key' => $apiproviderdata['api_key'], 'action' => 'status', 'order' => $order->api_order_id])->post();
        Log::info($apiservicedata);
                    $apidata = json_decode($apiservicedata);
                    if (isset($apidata->status)) {
                        $order->status = (strtolower($apidata->status) == 'in progress') ? 'progress' : strtolower($apidata->status);
                        $order->start_counter = @$apidata->start_count;
                        $order->remains = @$apidata->remains;
                        $order->reason = @$apidata->reason;
                    }
    
                    if (isset($apidata->error)) {
                        $order->status_description = "error: {" . @$apidata->error . "}";
                    }
                    $order->save();
    
    
                    if ($order->status == 'refunded' && $order->remains != 0) {
                        $perOrder = $order->price / $order->quantity;
                        $getBackAmo = $order->remains * $perOrder;
    
                        $user = $order->user;
                        $user->balance += $getBackAmo;
                        $user->save();
    
                        $transaction = new Transaction();
                        $transaction->user_id = $user->id;
                        $transaction->trx_type = '+';
                        $transaction->amount = $getBackAmo;
                        $transaction->remarks = 'Refunded order on #'.$order->id;
                        $transaction->trx_id = strRandom();
                        $transaction->charge = 0;
                        $transaction->save();
    
                    }
    
                    if ($order->status == 'canceled') {
                        $getBackAmo = $order->price;
    
                        $user = $order->user;
                        $user->balance += $getBackAmo;
                        $user->save();
    
                        $transaction = new Transaction();
                        $transaction->user_id = $user->id;
                        $transaction->trx_type = '+';
                        $transaction->amount = $getBackAmo;
                        $transaction->remarks = 'Canceled order on #'.$order->id;
                        $transaction->trx_id = strRandom();
                        $transaction->charge = 0;
                        $transaction->save();
    
                    }
                } else {
                    $api = new DhruFusion($apiproviderdata->api_user,$apiproviderdata->api_key,$apiproviderdata->url);
                    $para["ID"]=$order->api_order_id;
                    $response = $api->action('getimeiorder', $para);
                     Log::info($response);
                     
                  

                    if (isset($response['SUCCESS'])) {
                        switch ($response['SUCCESS'][0]['STATUS']) {
                                                        case 0:
                                # code...
                                $order->status = 'processing';
                                break;
                            case 1:
                                # code...
                                $order->status = 'processing';
                                break;
                                case 4:
                                    # code...
                                    $order->status = 'completed';
                                    $order->reason = $response['SUCCESS'][0]['CODE'];
                                    break;
                                    case 3:
                                        # code...
                                        $order->status = 'refunded';
                                        break;
  
                            
                            default:
                            $order->status = 'progress';
                                break;
                        }
                
                    }
    
                    else  {
                        $order->status_description = $response['ERROR'][0]['MESSAGE'];
                         $order->reason = $response['ERROR'][0]['MESSAGE'];
                    }
                    $order->save();
    
    
                    if ($order->status == 'refunded' ) {
                        $perOrder = $order->price / $order->quantity;
                        $getBackAmo = $order->remains * $perOrder;
    
                        $user = $order->user;
                        $user->balance += $getBackAmo;
                        $user->save();
    
                        $transaction = new Transaction();
                        $transaction->user_id = $user->id;
                        $transaction->trx_type = '+';
                        $transaction->amount = $getBackAmo;
                        $transaction->remarks = 'Refunded order on #'.$order->id;
                        $transaction->trx_id = strRandom();
                        $transaction->charge = 0;
                        $transaction->save();
    
                    }
    
                    if ($order->status == 'canceled') {
                        $getBackAmo = $order->price;
    
                        $user = $order->user;
                        $user->balance += $getBackAmo;
                        $user->save();
    
                        $transaction = new Transaction();
                        $transaction->user_id = $user->id;
                        $transaction->trx_type = '+';
                        $transaction->amount = $getBackAmo;
                        $transaction->remarks = 'Canceled order on #'.$order->id;
                        $transaction->trx_id = strRandom();
                        $transaction->charge = 0;
                        $transaction->save();
    
                    }
                }
                
      


            }
            if ($order->status == 'refunded' || $order->status == 'canceled'|| $order->status == 'completed') {
                # code...
                $msg = [
                    'description'=>$order->service->service_title.' order is '.$order->status,
                    'title'=>'Order Update',
                    'image'=>''
                ];
                Helpers::send_push_notif_to_device($order->user->cm_firebase_token, $msg);
            }
        };

        $this->info('status');
    }
}

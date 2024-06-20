<?php

namespace App\Console\Commands;

use App\CPU\Helpers;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Ixudra\Curl\Facades\Curl;
use Illuminate\Console\Command;
use App\Helper\DhruFusion;
use App\Services\ApiProviderFactory;
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
    protected $apiProviderFactory;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ApiProviderFactory $apiProviderFactory)
    {
        parent::__construct();
        $this->apiProviderFactory = $apiProviderFactory;
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

                $providerInstance = $this->apiProviderFactory->createProvider($apiproviderdata->type);
                $result = $providerInstance->getOrderStatus($apiproviderdata,$order);

                

    
    
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

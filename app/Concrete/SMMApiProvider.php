<?php

namespace App\Concrete;

use App\Contracts\ApiProviderInterface;
use App\Models\ApiProvider;
use App\Models\Category;
use App\Models\Service;
use Ixudra\Curl\Facades\Curl;

class SMMApiProvider implements ApiProviderInterface
{
    public function getAllProviderServices(ApiProvider $apiProvider){
        $apiLiveData = Curl::to($apiProvider->url)->withData(['key' => $apiProvider->api_key, 'action' => 'services'])->post();

        $apiServiceLists = json_decode($apiLiveData);
        return $apiServiceLists;
    }

    public function importMulti(ApiProvider $apiProvider,array $req){
        $apiLiveData = Curl::to($apiProvider['url'])
        ->withData(['key' => $apiProvider['api_key'], 'action' => 'services'])->post();
    $apiServicesData = json_decode($apiLiveData);





$getService = [];
if($req['import_quantity'] == 'selectItem'){
    $getService = explode(',', $req['selectService']);
    $apiServicesData = collect($apiServicesData)->whereIn('service',$getService)->values();
}


// $apiServicesData = collect($apiServicesData)->where('refill',1)->values();


$count = 0;
foreach ($apiServicesData as $apiService):
    $all_category = Category::all();
    $services = Service::all();
    $insertCat = 1;
    $existService = 0;
    foreach ($all_category as $categories):
        if ($categories->category_title == $apiService->category):
            $insertCat = 0;
        endif;
    endforeach;
    if ($insertCat == 1):
        $cat = new Category();
        $cat->category_title = $apiService->category;
        $cat->category_type = $req['category_type'];
        $cat->status = 1;
        $cat->save();
    endif;
    foreach ($services as $service):
        if ($service->api_service_id == $apiService->service):
            $existService = 1;
        endif;
    endforeach;
    if ($existService != 1):
        $service = new Service();
        $idCat = Category::where('category_title', $apiService->category)->first()->id ?? null;
        $service->service_title = $apiService->name;
        $service->category_id = $idCat;
       // dd($apiService);
        if (isset($apiService->min)) {
            # code...
        
            $service->min_amount = $apiService->min;
        } else {
            # code...
            $service->min_amount = null;
        }

        if (isset($apiService->max)) {
            # code...
         
            $service->max_amount = $apiService->max;
        } else {
            # code...
            $service->max_amount = null;
        }

        

            if (isset($apiService->params)) {
                $p = json_decode($apiService->params);
                $strArry ="";
                foreach($p as $field){
                    $strArry.= $field.',';
                   
                }
                $service->custom_fields = $strArry;
            }
        
        else{
            $service->custom_fields = "link";
        }

      
            $apiService->rate = $apiService->rate /$apiProvider->rate;
   
            $basic = (object)config('basic');
        $increased_price = ($apiService->rate * 10) / 100;

        $increased_price = ($apiService->rate * $req['price_percentage_increase']) / 100;

       $service->price = round(($apiService->rate + $increased_price)*$apiProvider->convention_rate,$basic->fraction_number);

       $reseller_increased_price = ($apiService->rate * $req['reseller_price_percentage_increase']) / 100;

       $service->reseller_price = round(($apiService->rate + $reseller_increased_price)*$apiProvider->convention_rate,$basic->fraction_number);
//                $service->price = $apiService->rate;

        $service->service_status = 1;
        $service->api_provider_id = $req['provider'];
        $service->api_service_id = $apiService->service;
       // $service->drip_feed = @$apiService->dripfeed;
        $service->api_provider_price =round( $apiService->rate,$basic->fraction_number);

        // if(isset($apiService->refill)){
        //     $service->refill = $apiService->refill;
        // }

        if (isset($apiService->desc)) {
            $service->description = @$apiService->desc;
        } else {
            $service->description = @$apiService->description;
        }

        $service->save();
    endif;
    $count++;
    if ($req['import_quantity'] == 'all'):
        continue;
    elseif ($req['import_quantity'] == $count):
        break;
    elseif ($req['import_quantity'] == 'selectItem' ):
        continue;
    endif;
endforeach;
    }

    public function updateProviderServicesPrices(ApiProvider $apiProvider){
        $apiLiveData = Curl::to($apiProvider->url)->withData(['key' => $apiProvider->api_key, 'action' => 'services'])->post();
        $currencyData = json_decode($apiLiveData);
        foreach ($apiProvider->services as $k => $data) {
            if (isset($data->price)){
                $data->update([
                    'api_provider_price' => collect($currencyData)->where('service', $data->api_service_id)->pluck('price')[0] ?? $data->api_provider_price ?? $data->price,
                     'price' => collect($currencyData)->where('service', $data->api_service_id)->pluck('price')[0] /$apiProvider->rate ?? $data->price
                ]);
            }
        }
    }

    public function updateProviderBalance(ApiProvider $apiProvider){
        $apiLiveData = Curl::to($apiProvider->url)->withData(['key' => $apiProvider->api_key, 'action' => 'balance'])->post();
        $currencyData = json_decode($apiLiveData);

        
        $result = [];
        if (isset($currencyData->balance)):
            $apiProvider->balance = $currencyData->balance;
            $apiProvider->currency = $currencyData->currency;

            $apiProvider->save();
           
        elseif (isset($currencyData->error)):
            $result['error'] = $currencyData->error;
            return $result;
        else:
            $result['error'] = "Please Check your API URL Or API Key";
            return $result;
        endif;
    }

    public function getOrderStatus(ApiProvider $apiProvider, string $orderId){

    }

    public function placeOrder(ApiProvider $apiProvider, array $detials){

    }

    public function reMapServiceArrayKeys(array $apiResponse) : array {
        $result = [];
        foreach ($apiResponse as $key=> $service) {
            $normalizedService = [
                'id'=>$service->service,
                'name'=>$service->name,
                'category'=>$service->category,
                'rate'=>$service->rate,
                'dripfeed'=>$service->dripfeed,
                'min'=>$service->min,
                'max'=>$service->max,
                'params'=>$service->params
            ];
            array_push($result,$normalizedService);
        }

        return $result;

    }
}
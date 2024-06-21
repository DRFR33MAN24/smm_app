<?php

namespace App\Concrete;

use App\Contracts\ApiProviderInterface;
use App\Models\ApiProvider;
use App\Models\Category;
use App\Models\Order;
use App\Models\Service;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Ixudra\Curl\Facades\Curl;

class ZDDKApiProvider implements ApiProviderInterface
{
    public function getAllProviderServices(ApiProvider $apiProvider)
    {
        $apiLiveData = Curl::to($apiProvider->url . '/products')->withHeaders(array('api-token' => $apiProvider['api_key']))->get();

        $apiServiceLists = json_decode($apiLiveData);


        return $apiServiceLists;
    }

    public function importMulti(ApiProvider $apiProvider, array $req)
    {

        $apiServicesData = $this->getAllProviderServices($apiProvider);

        $getService = [];
        if ($req['import_quantity'] == 'selectItem') {
            $getService = explode(',', $req['selectService']);
            $apiServicesData = collect($apiServicesData)->whereIn('service', $getService)->values();
        }

        // $apiServicesData = collect($apiServicesData)->where('refill',1)->values();

        $count = 0;
        foreach ($apiServicesData as $apiService) {
            $all_category = Category::all();
            $services = Service::all();
            $insertCat = 1;
            $existService = 0;
            foreach ($all_category as $categories) {
                if ($categories->category_title == $apiService->category_name) {
                    $insertCat = 0;
                }
            }
            if ($insertCat == 1) {
                $cat = new Category();
                $cat->category_title = $apiService->category_name;
                $cat->category_type = $req['category_type'];
                $cat->status = 1;
                $cat->save();
            }
            foreach ($services as $service) {
                if ($service->api_service_id == $apiService->id) {
                    $existService = 1;
                }
            }
            if ($existService != 1) {
                $service = new Service();
                $idCat = Category::where('category_title', $apiService->category_name)->first()->id ?? null;
                $service->service_title = $apiService->name;
                $service->category_id = $idCat;
                // dd($apiService);
                if (isset($apiService->qty_values)) {
                    // code...

                    $service->min_amount = $apiService->min;
                } else {
                    // code...
                    $service->min_amount = null;
                }

                if (isset($apiService->qty_values)) {
                    // code...

                    $service->max_amount = $apiService->max;
                } else {
                    // code...
                    $service->max_amount = null;
                }

                if (isset($apiService->params)) {

                    $strArry = '';
                    foreach ($apiService->params as $field) {
                        $strArry .= $field . ',';

                    }
                    $service->custom_fields = $strArry;
                }

                $apiService->price = $apiService->price / $apiProvider->rate;

                $basic = (object) config('basic');
                $increased_price = ($apiService->price * 10) / 100;

                $increased_price = ($apiService->price * $req['price_percentage_increase']) / 100;

                $service->price = round(($apiService->price + $increased_price) * $apiProvider->convention_rate, $basic->fraction_number);

                $reseller_increased_price = ($apiService->price * $req['reseller_price_percentage_increase']) / 100;

                $service->reseller_price = round(($apiService->price + $reseller_increased_price) * $apiProvider->convention_rate, $basic->fraction_number);
                //                $service->price = $apiService->rate;

                $service->service_status = 1;
                $service->api_provider_id = $req['provider'];
                $service->api_service_id = $apiService->id;
                // $service->drip_feed = @$apiService->dripfeed;
                $service->api_provider_price = round($apiService->price, $basic->fraction_number);

                // if(isset($apiService->refill)){
                //     $service->refill = $apiService->refill;
                // }

                if (isset($apiService->desc)) {
                    $service->description = @$apiService->desc;
                } else {
                    $service->description = @$apiService->description;
                }

                $service->save();
            }
            $count++;
            if ($req['import_quantity'] == 'all') {
                continue;
            } elseif ($req['import_quantity'] == $count) {
                break;
            } elseif ($req['import_quantity'] == 'selectItem') {
                continue;
            }
        }
    }

    public function updateProviderServicesPrices(ApiProvider $apiProvider)
    {

        $currencyData = $this->getAllProviderServices($apiProvider);
        foreach ($apiProvider->services as $k => $data) {
            if (isset($data->price)) {
                $data->update([
                    'api_provider_price' => collect($currencyData)->where('service', $data->api_service_id)->pluck('price')[0] ?? $data->api_provider_price ?? $data->price,
                    'price' => collect($currencyData)->where('service', $data->api_service_id)->pluck('price')[0] / $apiProvider->rate ?? $data->price,
                ]);
            }
        }
    }

    public function updateProviderBalance(ApiProvider $apiProvider)
    {
        $apiLiveData = Curl::to($apiProvider->url . '/profile')->withHeaders(array('api-token' => $apiProvider['api_key']))->get();
        $currencyData = json_decode($apiLiveData);

        $result = [];
        if (isset($currencyData->balance)) {
            $apiProvider->balance = $currencyData->balance;
            $apiProvider->currency = "USD";

            $apiProvider->save();

        } elseif (isset($currencyData->error)) {
            $result['error'] = $currencyData->error;

            return $result;
        } else {
            $result['error'] = 'Please Check your API URL Or API Key';

            return $result;
        }
    }

    public function getOrderStatus(ApiProvider $apiProvider, Order $order)
    {
        $apiservicedata = Curl::to($apiProvider['url'] . '/check')
            ->withHeaders(array('api-token' => $apiProvider['api_key']))
            ->withData(
                ['orders' => '[' . $order->api_order_id . ']']
            )
            ->get();

        $apidata = json_decode($apiservicedata);
        if ($apidata->status == "OK") {
            $order->status = (strtolower($apidata->data[0]->status) == 'wait') ? 'progress' : strtolower($apidata->data[0]->status);

            $order->reason = @$apidata->reason;
        }

        if (isset($apidata->error)) {
            $order->status_description = 'error: {' . @$apidata->error . '}';
        }
        $order->save();
    }

    public function placeOrder(ApiProvider $apiProvider, array $detials)
    {

        $result = [];
        $postData = [



            'qty' => $detials['quantity'],
            'order_uuid' => Str::uuid()->toString()
        ];

        $postData = array_merge($postData, $detials['params']);





        Log::info($postData);
        $apiservicedata = Curl::to($apiProvider['url'] . '/newOrder/' . $detials['service_id'] . '/params')->
            withHeaders(array('api-token' => $apiProvider['api_key']))->
            withData($postData)->get();

        Log::info($apiservicedata);
        $apidata = json_decode($apiservicedata);

        if ($apidata->status == "OK") {
            $result['order_id'] = $apidata->data->order_id;
            $result['message'] = "Order placed successfully";

        } else {
            $result['error'] = $apidata->msg;
        }

        return $result;
    }

    public function updateServicePrice(ApiProvider $apiProvider, string $serviceId)
    {
        $result = [];

        $apiServiceData = $this->getAllProviderServices($apiProvider);
        $success = false;
        foreach ($apiServiceData as $current) {

            if ($current->id == $serviceId) {
                $success = true;
                $result['rate'] = $current->price / $apiProvider->rate;

                return $result;

            }
        }
        if (!isset($success)) {
            $result['error'] = 'Error';

            return $result;
        }
    }

    public function reMapServiceArrayKeys(array $apiResponse): array
    {
        $result = [];
        foreach ($apiResponse as $key => $service) {
            $normalizedService = [
                'id' => $service->id,
                'name' => $service->name,
                'category' => $service->category_name,
                'rate' => $service->price,
                'dripfeed' => null,
                'min' => $service->min ?? 1,
                'max' => $service->max ?? 1,
                'params' => $service->params,
            ];
            array_push($result, $normalizedService);
        }

        return $result;

    }
}

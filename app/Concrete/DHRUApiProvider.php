<?php

namespace App\Concrete;

use App\Contracts\ApiProviderInterface;
use App\Models\ApiProvider;
use App\Helper\DhruFusion;
use App\Models\Category;
use App\Models\Order;
use App\Models\Service;
use \Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DHRUApiProvider implements ApiProviderInterface
{
    public function getAllProviderServices(ApiProvider $apiProvider)
    {
        $api = new DhruFusion($apiProvider->api_user, $apiProvider->api_key, $apiProvider->url);
        // $response = $api->action('imeiservicelist');
        // file_put_contents(public_path('res1.txt'), json_encode($response));
        //$api->debug=true;
        if ($apiProvider->api_name == "Halab") {
            $file = file_get_contents(public_path('res.txt'));
            $res = json_decode($file, true);
            // dd($response);
            $apiGroupLists = $res["SUCCESS"][0]["LIST"];

        } else {

            $response = $api->action('imeiservicelist');
            $apiGroupLists = $response["SUCCESS"][0]["LIST"];
        }

        return $apiGroupLists;
    }

    public function importMulti(ApiProvider $apiProvider, array $req)
    {
        $api = new DhruFusion($apiProvider->api_user, $apiProvider->api_key, $apiProvider->url);
        if ($apiProvider->api_name == "Halab") {
            $file = file_get_contents(public_path('res.txt'));
            $res = json_decode($file, true);
            // dd($response);
            $apiGroupLists = $res["SUCCESS"][0]["LIST"];

        } else {

            $response = $api->action('imeiservicelist');
            $apiGroupLists = $response["SUCCESS"][0]["LIST"];
        }



        $apiServicesData = [];

        foreach ($apiGroupLists as $group) {
            foreach ($group["SERVICES"] as $service) {
                $service['GROUPNAME'] = $group["GROUPNAME"];
                array_push($apiServicesData, $service);
            }
        }




        $getService = [];
        if ($req['import_quantity'] == 'selectItem') {
            $getService = explode(',', $req['selectService']);
            $apiServicesData = collect($apiServicesData)->whereIn('SERVICEID', $getService)->values();

        }


        // $apiServicesData = collect($apiServicesData)->where('refill',1)->values();


        $count = 0;
        foreach ($apiServicesData as $apiService):
            $all_category = Category::all();
            $services = Service::all();
            $insertCat = 1;
            $existService = 0;
            foreach ($all_category as $categories):
                if ($categories->category_title == $apiService["GROUPNAME"]):
                    $insertCat = 0;
                endif;
            endforeach;
            if ($insertCat == 1):
                $cat = new Category();
                $cat->category_title = $apiService["GROUPNAME"];
                $cat->category_type = $req['category_type'];
                $cat->status = 1;
                $cat->save();
            endif;
            foreach ($services as $service):
                if ($service->api_service_id == $apiService["SERVICEID"]):
                    $existService = 1;
                endif;
            endforeach;
            if ($existService != 1):
                $service = new Service();
                $idCat = Category::where('category_title', $apiService["GROUPNAME"])->first()->id ?? null;
                $service->service_title = $apiService["SERVICENAME"];
                $service->category_id = $idCat;
                if (isset($apiService["MINQNT"])) {
                    # code...
                    $service->min_amount = $apiService["MINQNT"];
                } else {
                    # code...
                    $service->min_amount = null;
                }

                if (isset($apiService["MAXQNT"])) {
                    # code...
                    $service->max_amount = $apiService["MAXQNT"];
                } else {
                    # code...
                    $service->max_amount = null;
                }

                if (isset($apiService["Requires.Custom"])) {
                    $strArry = "";
                    foreach ($apiService["Requires.Custom"] as $field) {
                        $strArry .= $field['fieldname'] . ',';

                    }
                    $service->custom_fields = $strArry;
                }
                if (isset($apiService["CUSTOM"])) {

                    $service->custom_fields = 'IMEI';
                }
                $basic = (object) config('basic');

                $increased_price = ($apiService["CREDIT"] * 10) / 100;

                $increased_price = ($apiService["CREDIT"] * $req['price_percentage_increase']) / 100;

                $service->price = round(($apiService["CREDIT"] + $increased_price) * $apiProvider->convention_rate, $basic->fraction_number);

                $reseller_increased_price = ($apiService["CREDIT"] * $req['reseller_price_percentage_increase']) / 100;

                $service->reseller_price = round(($apiService["CREDIT"] + $reseller_increased_price) * $apiProvider->convention_rate, $basic->fraction_number);
                //                $service->price = $apiService->rate;

                $service->service_status = 1;
                $service->api_provider_id = $req['provider'];
                $service->api_service_id = $apiService["SERVICEID"];
                // $service->drip_feed = @$apiService->dripfeed;
                $service->api_provider_price = $apiService["CREDIT"];

                // if(isset($apiService->refill)){
                //     $service->refill = $apiService->refill;
                // }

                if (isset($apiService["INFO"])) {
                    $service->description = @$apiService["INFO"];
                } else {
                    $service->description = "";
                }

                $service->save();
            endif;
            $count++;
            if ($req['import_quantity'] == 'all'):
                continue;
            elseif ($req['import_quantity'] == $count):
                break;
            elseif ($req['import_quantity'] == 'selectItem'):
                continue;
            endif;
        endforeach;
    }

    public function updateProviderServicesPrices(ApiProvider $apiProvider)
    {
        $api = new DhruFusion($apiProvider->api_user, $apiProvider->api_key, $apiProvider->url);
        $response = $api->action('imeiservicelist');

        $apiGroupLists = $response["SUCCESS"][0]["LIST"];
        $apiServicesData = [];

        foreach ($apiGroupLists as $group) {
            foreach ($group["SERVICES"] as $service) {

                array_push($apiServicesData, $service);
            }
        }
        foreach ($apiProvider->services as $k => $data) {
            if (isset($data->price)) {
                $data->update([
                    'api_provider_price' => collect($apiServicesData)->where('SERVICEID', $data->api_service_id)->pluck('CREDIT')[0] ?? $data->api_provider_price ?? $data->price,
                    'price' => collect($apiServicesData)->where('SERVICEID', $data->api_service_id)->pluck('CREDIT')[0] ?? $data->price
                ]);
            }
        }
    }

    public function updateProviderBalance(ApiProvider $apiProvider)
    {
        $api = new DhruFusion($apiProvider->api_user, $apiProvider->api_key, $apiProvider->url);
        $response = $api->action('accountinfo');
        $account = $response["SUCCESS"][0]["AccoutInfo"];

        $result = [];
        if (isset($account)) {
            $apiProvider->balance = $account["creditraw"];
            $apiProvider->currency = $account["currency"];

            $apiProvider->save();
        } else {
            $result['error'] = "Please Check your API URL Or API Key";
            return $result;

        }
    }

    public function getOrderStatus(ApiProvider $apiProvider, Order $order)
    {
        $api = new DhruFusion($apiProvider->api_user, $apiProvider->api_key, $apiProvider->url);
        $para["ID"] = $order->api_order_id;
        $response = $api->action('getimeiorder', $para);




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

        } else {
            $order->status_description = $response['ERROR'][0]['MESSAGE'];
            $order->reason = $response['ERROR'][0]['MESSAGE'];
        }
        $order->save();
    }

    public function placeOrder(ApiProvider $apiProvider, array $detials)
    {

        $result = [];

        $api = new DhruFusion($apiProvider->api_user, $apiProvider->api_key, $apiProvider->url);


        $params["ID"] = $detials['service_id'];
        $params = array_merge($params, $detials['params']);

        Log::info($params);
        $response = $api->action('placeimeiorder', $params);
        Log::info($response);

        if (isset($response['SUCCESS'])) {
            $result['order_id'] = $response['SUCCESS'][0]['REFERENCEID'];
            $result['message'] = $response['SUCCESS'][0]['MESSAGE'];

        } else {
            $result['error'] = $$response['ERROR'][0]['MESSAGE'];
        }

        return $result;
    }

    public function updateServicePrice(ApiProvider $apiProvider, string $serviceId)
    {
        $result = [];
        $api = new DhruFusion($apiProvider->api_user, $apiProvider->api_key, $apiProvider->url);
        $para['ID'] = $serviceId;
        if ($apiProvider->api_name == "Halab") {
            $file = file_get_contents(public_path('res.txt'));
            $res = json_decode($file, true);

            $result['rate'] = $res["SUCCESS"][0]["LIST"]['credit'];

        } else {

            $response = $api->action('getimeiservicedetails', $para);
            $result['rate'] = $response["SUCCESS"][0]["LIST"]['credit'];
        }

        return $result;
    }

    public function reMapServiceArrayKeys(array $apiResponse): array
    {
        $result = [];
        foreach ($apiResponse as $key => $group) {
            foreach ($group['SERVICES'] as $key => $service) {

                $normalizedService = [
                    'id' => $service['SERVICEID'],
                    'name' => $service['SERVICENAME'],
                    'category' => $group['GROUPNAME'],
                    'rate' => $service['CREDIT'],
                    'dripfeed' => null,
                    'min' => $service['MINQNT'] ?? 1,
                    'max' => $service['MAXQNT'] ?? 1,
                    'params' => $service['Requires.Custom'] ?? 'IMEI'
                ];
                array_push($result, $normalizedService);
            }
        }

        return $result;

    }
}
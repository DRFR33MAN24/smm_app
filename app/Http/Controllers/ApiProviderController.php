<?php

namespace App\Http\Controllers;

use App\Models\ApiProvider;
use App\Models\Category;
use App\Models\Service;
use Illuminate\Http\Request;
use Ixudra\Curl\Facades\Curl;
use Stevebauman\Purify\Facades\Purify;
use Illuminate\Support\Facades\Validator;
use App\Helper\DhruFusion;
use App\Services\ApiProviderFactory;

class ApiProviderController extends Controller
{

    protected $apiProviderFactory;

    public function __construct(ApiProviderFactory $apiProviderFactory)
    {
        $this->apiProviderFactory = $apiProviderFactory;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $api_providers = ApiProvider::orderBy('id', 'DESC')->get();

        return view('admin.pages.api_providers.show', compact('api_providers'));
    }

    public function create()
    {
        return view('admin.pages.api_providers.add');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $apiProviderData = Purify::clean($request->all());
        $rules = [
            'api_name' => 'sometimes|required',
            'api_key' => 'sometimes|required',
            'url' => 'sometimes|required',
            'convention_rate' => 'sometimes|required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }


        $ApiProvider = new ApiProvider();
        $ApiProvider->api_name = $apiProviderData['api_name'];
        $ApiProvider->api_key = $apiProviderData['api_key'];
        $ApiProvider->url = $apiProviderData['url'];

        $ApiProvider->convention_rate = $apiProviderData['convention_rate'];
        $ApiProvider->rate = $request['rate'];

        $ApiProvider->status = $apiProviderData['status'];
        $ApiProvider->type = $apiProviderData['type'];
        $ApiProvider->description = $apiProviderData['description'];
        $ApiProvider->api_user = $request['api_user'];


        if (isset($error)):
            return back()->with('error', $error)->withInput();
        endif;
        $ApiProvider->save();
        $providerInstance = $this->apiProviderFactory->createProvider($ApiProvider->type);
        $result = $providerInstance->updateProviderBalance($ApiProvider);
        return back()->with('success', 'Successfully Added');
    }

    public function activeMultiple(Request $request)
    {
        if ($request->strIds == null) {
            session()->flash('error', 'You do not select Id!');
            return response()->json(['error' => 1]);
        } else {
            $ids = explode(",", $request->strIds);
            $apiProvider = ApiProvider::whereIn('id', $ids);
            $apiProvider->update([
                'status' => 1,
            ]);
            session()->flash('success', 'Updated Successfully!');
            return response()->json(['success' => 1]);
        }

    }

    public function deActiveMultiple(Request $request)
    {
        if ($request->strIds == null) {
            session()->flash('error', 'You do not select Id!');
            return response()->json(['error' => 1]);
        } else {
            $ids = explode(",", $request->strIds);
            $apiProvider = ApiProvider::whereIn('id', $ids);
            $apiProvider->update([
                'status' => 0,
            ]);
            session()->flash('success', 'Updated Successfully.');
            return response()->json(['success' => 1]);
        }
    }


    public function edit(ApiProvider $apiProvider)
    {
        $provider = ApiProvider::find($apiProvider->id);
        return view('admin.pages.api_providers.edit', compact('provider'));
    }


    public function update(Request $request, ApiProvider $apiProvider)
    {
        $rules = [
            'api_name' => 'sometimes|required',
            'api_key' => 'sometimes|required',
            'url' => 'sometimes|required',
            'convention_rate' => 'sometimes|required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $provider = ApiProvider::find($apiProvider->id);

        $providerInstance = $this->apiProviderFactory->createProvider($provider->type);
        $result = $providerInstance->updateProviderBalance($provider);


        $provider->api_name = $request['api_name'];


        $provider->status = $request['status'];
        $provider->type = $request['type'];
        $provider->convention_rate = $request['convention_rate'];
        $provider->rate = $request['rate'];
        $provider->description = $request['description'];
        if (isset($result['error'])):
            return back()->with('error', $result['error'])->withInput();
        endif;
        $provider->save();
        return back()->with('success', 'successfully updated');


    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(ApiProvider $apiProvider)
    {
        $apiProvider->delete();
        return back()->with('success', 'Successfully Deleted');
        ;
    }

    /*
     ** multiple delete
     */
    public function deleteMultiple(Request $request)
    {
        $ids = $request->strIds;
        ApiProvider::whereIn('id', explode(",", $ids))->delete();
        return back()->with('success', 'Delete Success');
    }

    public function changeStatus($id)
    {
        $apiProvider = ApiProvider::find($id);
        if ($apiProvider['status'] == 0) {
            $status = 1;
        } else {
            $status = 0;
        }
        $apiProvider->status = $status;
        $apiProvider->save();
        return back()->with('success', 'Successfully Changed');
    }

    public function setCurrency(Request $request, $id)
    {
        $apiProvider = ApiProvider::find($id);
        $apiProvider->convention_rate = $request->convention_rate;
        $apiProvider->save();
        return back()->with('success', 'Successfully Changed');
    }


    public function priceUpdate($id)
    {
        $provider = ApiProvider::with('services')->findOrFail($id);

        $providerInstance = $this->apiProviderFactory->createProvider($provider->type);
        $result = $providerInstance->updateProviderServicesPrices($provider);

        //check result for errors
        return back()->with('success', 'Successfully updated');

    }

    public function balanceUpdate($id)
    {
        $provider = ApiProvider::findOrFail($id);
        $providerInstance = $this->apiProviderFactory->createProvider($provider->type);
        $result = $providerInstance->updateProviderBalance($provider);

        return back()->with('success', 'Successfully updated');
    }


    public function getApiServices(Request $request)
    {
        $rules = [
            'api_provider_id' => 'required|string|max:150'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $provider = ApiProvider::find($request->api_provider_id);
        $providerInstance = $this->apiProviderFactory->createProvider($provider->type);
        $result = $providerInstance->getAllProviderServices($provider);

        $result = $providerInstance->reMapServiceArrayKeys($result);
        return view('admin.pages.services.show-api-services', compact('result', 'provider'));





    }

    public function import(Request $request)
    {
        $req = $request->all();

        $provider = ApiProvider::find($req['provider']);
        $all_category = Category::all();
        $services = Service::all();
        $insertCat = 1;
        $existService = 0;
        foreach ($all_category as $categories):
            if ($categories->category_title == $req['category']):
                $insertCat = 0;
            endif;
        endforeach;
        if ($insertCat == 1):
            $cat = new Category();
            $cat->category_title = $req['category'];
            $cat->category_type = $req['category_type'];
            $cat->status = 1;
            $cat->save();
        endif;
        foreach ($services as $service):
            if ($service->api_service_id == $req['id']):
                $existService = 1;
            endif;
        endforeach;
        if ($existService != 1):
            $service = new Service();
            $idCat = Category::where('category_title', $req['category'])->first()->id;
            $service->service_title = $req['name'];
            $service->category_id = $idCat;
            if (isset($req['min'])) {
                # code...

                $service->min_amount = $req['min'];
            } else {
                # code...
                $service->min_amount = null;
            }

            if (isset($req['max'])) {
                # code...

                $service->max_amount = $req['max'];
            } else {
                # code...
                $service->max_amount = null;
            }

            if ($provider->type == "DHRU") {
                # code...
                if (isset($req["params"])) {

                    if (($req["params"] == "IMEI")) {
                        $service->custom_fields = 'IMEI';
                    } else {

                        $strArry = "";
                        foreach ($req["params"] as $field) {
                            if (isset($field['fieldname'])) {
                                # code...
                                $strArry .= $field['fieldname'] . ',';
                            }

                        }
                        $service->custom_fields = $strArry;
                    }


                }
            } else if ($provider->type == "SMM") {
                if (isset($req["params"])) {
                    $p = json_decode($req['params']);
                    $strArry = "";
                    foreach ($p as $field) {
                        $strArry .= $field . ',';

                    }
                    $service->custom_fields = $strArry;
                } else {
                    $service->custom_fields = "link";
                }
            } else {
                if (isset($req["params"])) {

                    $strArry = "";
                    foreach ($req['params'] as $field) {
                        $strArry .= $field . ',';

                    }
                    $service->custom_fields = $strArry;
                }
            }




            $req['rate'] = $req['rate'] / $provider->rate;

            $basic = (object) config('basic');
            $increased_price = ($req['rate'] * $req['price_percentage_increase']) / 100;
            $service->price = round(($req['rate'] + $increased_price) * $provider->convention_rate, $basic->fraction_number);

            $reseller_increased_price = ($req['rate'] * $req['reseller_price_percentage_increase']) / 100;
            $service->reseller_price = round(($req['rate'] + $reseller_increased_price) * $provider->convention_rate, $basic->fraction_number);
            $service->service_status = 1;
            $service->api_provider_id = $req['provider'];
            $service->api_service_id = @$req['id'];
            // $service->drip_feed = @$req['dripfeed'];
            $service->api_provider_price = round(@$req['rate'], $basic->fraction_number);
            // if(isset($req['refill'])){
            //     $service->refill = @$req['refill'];
            // }
            $service->save();
            return redirect()->route('admin.service.show')->with('success', 'Service Imported Successfully');
            ;
        else:
            return redirect()->route('admin.service.show')->with('success', 'Already Have this service');
        endif;

    }


    public function importMulti(Request $request)
    {


        $req = $request->all();
        $provider = ApiProvider::find($req['provider']);

        $providerInstance = $this->apiProviderFactory->createProvider($provider->type);
        $result = $providerInstance->importMulti($provider, $req);





        return redirect()->route('admin.service.show')->with('success', 'Services Imported Successfully');
    }


    public function providerShow(Request $request)
    {
        $provider = ApiProvider::where('api_name', 'LIKE', "%{$request->data}%")->get()->pluck('api_name');
        return response()->json($provider);
    }


    public function search(Request $request)
    {
        $search = $request->all();
        $api_providers = ApiProvider::when(isset($search['provider']), function ($query) use ($search) {
            return $query->where('api_name', 'LIKE', "%{$search['provider']}%");
        })->when(isset($search['status']), function ($query) use ($search) {
            return $query->where('status', $search['status']);
        })->get();
        $api_providers->append($search);
        return view('admin.pages.api_providers.show', compact('api_providers'));
    }
}

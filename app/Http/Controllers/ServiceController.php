<?php

namespace App\Http\Controllers;

use App\Models\ApiProvider;
use App\Models\Category;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Ixudra\Curl\Facades\Curl;
use Stevebauman\Purify\Facades\Purify;
use App\Rules\FileTypeValidate;
use App\Http\Traits\Upload;
use Illuminate\Support\Facades\Log;
use App\Helper\DhruFusion;
use App\Services\ApiProviderFactory;

class ServiceController extends Controller
{
    use Upload;

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
        $categories = Category::with('services', 'services.provider')->has('services')->paginate(config('basic.paginate'));

        $apiProviders = ApiProvider::all();
        return view('admin.pages.services.show-service', compact('categories', 'apiProviders'));
    }

    /*
     * search
     */
    public function search(Request $request)
    {
        $categories = Category::with('services')->get();
        $apiProviders = ApiProvider::all();

        $search = $request->all();

        $services = Service::with(['category', 'provider'])
            ->when(isset($search['service']), function ($query) use ($search) {
                return $query->where('service_title', 'LIKE', "%{$search['service']}%");
            })
            ->when(isset($search['category']), function ($query) use ($search) {
                if ($search['category'] == -1) {
                    return $query->where('category_id', '!=', '-1');
                }
                return $query->where('category_id', $search['category']);
            })
            ->when(isset($search['provider']), function ($query) use ($search) {

                if ($search['provider'] == -1) {
                    return $query->where('api_provider_id', null);
                }
                return $query->where('api_provider_id', $search['provider']);
            })
            ->when($search['status'] != -1, function ($query) use ($search) {
                return $query->where('service_status', $search['status']);
            })
            ->get()
            ->groupBy('category.category_title');
        return view('admin.pages.services.search-service', compact('services', 'categories', 'apiProviders'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = Category::orderBy('id', 'DESC')->where('status', 1)->get();
        $apiProviders = ApiProvider::orderBy('id', 'DESC')->where('status', 1)->get();
        return view('admin.pages.services.add-service', compact('categories', 'apiProviders'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $req = Purify::clean($request->except('_token', '_method'));

        $rules = [
            'service_title' => 'required|string|max:150',
            'category_id' => 'required|string',
            'min_amount' => 'required|numeric',
            'max_amount' => 'required|numeric',
            'price' => 'required|numeric',
            'manual_api' => 'required|numeric|in:0,1',

            'api_provider_id' => 'exclude_if:manual_api,0|exists:api_providers,id',
            'api_service_id' => 'exclude_if:manual_api,0|numeric|not_in:0',
            'image' => ['nullable', 'image', new FileTypeValidate(['jpeg', 'jpg', 'png'])]
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }


        $service = new Service();
        if ($request->hasFile('image')) {
            try {
                $service->image = $this->uploadImage($request['image'], config('location.service.path'), config('location.service.size'));
            } catch (\Exception $exp) {
                return back()->with('error', 'Image could not be uploaded.');
            }
        }
        $category_type = Category::find($req['category_id'])->category_type;

        $service->service_title = $req['service_title'];
        $service->service_type = $category_type;
        $service->category_id = $req['category_id'];
        $service->min_amount = $req['min_amount'];
        $service->max_amount = $req['max_amount'];
        $service->price = $req['price'];
        $service->custom_fields = $req['custom_fields'];
        $service->reseller_price = $req['reseller_price'];
        $service->service_status = $req['service_status'];
        $service->api_provider_id = ($req['api_provider_id'] == 0) ? null : $req['api_provider_id'];
        $service->api_service_id = (empty($req['api_service_id'])) ? 0 : $req['api_service_id'];

        $service->description = $req['description'];



        $provider = ApiProvider::find($req['api_provider_id']);

        if ($req['manual_api'] == 1):
            $providerInstance = $this->apiProviderFactory->createProvider($provider->type);
            $result = $providerInstance->updateServicePrice($provider, $req['api_service_id']);
            if (isset($result['error'])) {
                return back()->with('success', $result['error']);
            } else {
                $service->api_provider_price = $result['rate'];
            }





        else:
            $success = "Successfully Updated";
        endif;

        $service->save();
        return back()->with('success', $success);
    }


    public function serviceActive(Request $request)
    {
        $service = Service::all();
        foreach ($service as $data) {
            $ser = Service::find($data->id);
            $ser->service_status = 1;
            $ser->save();
        }
        return back()->with('success', 'Successfully Updated');
    }

    public function serviceDeActive(Request $request)
    {
        $service = Service::all();
        foreach ($service as $data) {
            $ser = Service::find($data->id);
            $ser->service_status = 0;
            $ser->save();
        }
        return back()->with('success', 'Successfully Updated');
    }

    public function edit($id)
    {
        $service = Service::find($id);
        $categories = Category::orderBy('id', 'DESC')->where('status', 1)->get();
        $apiProviders = ApiProvider::orderBy('id', 'DESC')->where('status', 1)->get();
        return view('admin.pages.services.edit-service', compact('service', 'categories', 'apiProviders'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Service $service)
    {


        $req = Purify::clean($request->all());

        //        return $req;
        $rules = [
            'service_title' => 'required|string|max:150',
            'category_id' => 'required|string',
            // 'min_amount' => 'required|numeric',
            // 'max_amount' => 'required|numeric',
            'price' => 'required|numeric',
            'manual_api' => 'required|numeric|in:0,1',

            'api_provider_id' => 'exclude_if:manual_api,0|exists:api_providers,id',
            'api_service_id' => 'exclude_if:manual_api,0|numeric|not_in:0',
            // 'image' => ['nullable', 'image', new FileTypeValidate(['jpeg','jpg','png'])]
        ];
        $validator = Validator::make($req, $rules);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }


        $service = Service::find($request->id);

        if ($request->hasFile('image')) {
            try {
                $old = $service->image;
                $service->image = $this->uploadImage($request['image'], config('location.service.path'), config('location.service.size'), $old);
            } catch (\Exception $exp) {
                return back()->with('error', 'Image could not be uploaded.');
            }
        }
        $category_type = Category::find($req['category_id'])->category_type;
        $service->service_title = $req['service_title'];
        $service->service_type = $category_type;
        $service->category_id = $req['category_id'];
        $service->min_amount = $req['min_amount'] ?? null;
        $service->max_amount = $req['max_amount'] ?? null;
        $service->price = $req['price'];
        $service->custom_fields = $req['custom_fields'];
        $service->reseller_price = $req['reseller_price'];
        $service->service_status = $req['service_status'];
        if ($req['manual_api'] == 1) {
            $service->api_provider_id = $req['api_provider_id'];
        }
        $service->api_service_id = $req['api_service_id'];

        $service->description = $req['description'];


        $provider = ApiProvider::find($req['api_provider_id']);

        if ($req['manual_api'] == 1):

            $providerInstance = $this->apiProviderFactory->createProvider($provider->type);
            $result = $providerInstance->updateServicePrice($provider, $req['api_service_id']);
            if (isset($result['error'])) {
                return back()->with('error', $result['error']);
            } else {
                $service->api_provider_price = $result['rate'];
                $success = "Successfully Updated";
            }




        else:
            $success = "Successfully Updated";
        endif;

        $service->save();
        return back()->with('success', $success);
    }


    public function activeMultiple(Request $request)
    {
        if ($request->strIds == null) {
            session()->flash('error', "You didn't select any row");
            return response()->json(['error' => 1]);
        } else {
            $ids = explode(",", $request->strIds);
            if (count($ids) > 0) {
                $services = Service::whereIn('id', $ids);
                $services->update([
                    'service_status' => 1,
                ]);
                session()->flash('success', 'Updated Successfully.');
                return response()->json(['success' => 1]);
            }
        }
    }


    public function deactiveMultiple(Request $request)
    {
        if ($request->strIds == null) {
            session()->flash('error', "You didn't select any row");
            return response()->json(['error' => 1]);
        } else {
            $ids = explode(",", $request->strIds);
            if (count($ids) > 0) {
                $services = Service::whereIn('id', $ids);
                $services->update([
                    'service_status' => 0,
                ]);
                session()->flash('success', 'Updated Successfully.');
                return response()->json(['success' => 1]);
            }
        }
    }


    public function deleteMultiple(Request $request)
    {
        if ($request->strIds == null) {
            session()->flash('error', "You didn't select any row");
            return response()->json(['error' => 1]);
        } else {
            $ids = explode(",", $request->strIds);
            if (count($ids) > 0) {
                $notHaveOrderServices = Service::whereIn('id', $ids)->doesntHave('orders')->get();
                if (count($notHaveOrderServices) > 0) {
                    foreach ($notHaveOrderServices as $key => $data) {
                        $service = Service::where('id', $data->id)->first();
                        $service->delete();
                    }
                    session()->flash('success', 'Deleted Successfully.');
                    return response()->json(['success' => 1]);
                } else {
                    session()->flash('error', "Service which have order can't be deleted.");
                    return response()->json(['success' => 1]);
                }
            }
        }
    }

    public function priceUpdateMultiple(Request $request)
    {

        if ($request->strIds == null) {
            session()->flash('error', "You didn't select any row");
            return response()->json(['error' => 1]);
        } else {
            $ids = explode(",", $request->strIds);
            if (count($ids) > 0) {
                if ($request->type == "increase") {

                    DB::statement("UPDATE services SET price = price + price * $request->percentage / 100 WHERE id IN ($request->strIds)");
                    DB::statement("UPDATE services SET reseller_price = reseller_price + reseller_price * $request->percentage / 100 WHERE id IN ($request->strIds)");
                } else {
                    DB::statement("UPDATE services SET price = price - price * $request->percentage / 100 WHERE id IN ($request->strIds)");
                    DB::statement("UPDATE services SET reseller_price = reseller_price - reseller_price * $request->percentage / 100 WHERE id IN ($request->strIds)");
                }
                session()->flash('success', 'Price updated Successfully.');
                return response()->json(['success' => 1]);
            }
        }
    }

    /*
     * search drop
     */
    public function getService(Request $request)
    {
        $service = Service::where('service_title', 'LIKE', "%{$request->data}%")->get()->pluck('service_title');
        return response()->json($service);
    }

    public function statusChange(Request $request, $id)
    {
        $cat = Service::find($id);

        if (!$cat) {
            return back()->with('error', 'Data Not Found');
        }
        if ($cat['service_status'] == 0) {
            $status = 1;
        } else {
            $status = 0;
        }
        $cat->service_status = $status;
        $cat->save();
        return back()->with('success', 'Successfully Updated');
    }
}

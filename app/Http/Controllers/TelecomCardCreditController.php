<?php

namespace App\Http\Controllers;

use App\Http\Traits\Upload;
use App\Models\Card;
use App\Models\Category;
use App\Models\Service;
use App\Models\TelecomProvider;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;
use Stevebauman\Purify\Facades\Purify;
use Illuminate\Support\Facades\Validator;

class TelecomCardCreditController extends Controller
{
    use Upload;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $cards = Card::latest()->get();
        return view('admin.pages.services.show-card-credit', compact('cards'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        
        $services = Service::whereHas('category',function($query){
            $query->where('category_type','=','telecom');
        })->get();
       
        return view('admin.pages.services.add-card-credit',compact('services'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $req = Purify::clean($request->except('_token', '_method'));

        $rules = [
          
            'token' => 'required',
            'service_id' => 'required',
        
        ];


        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }


        $card = new Card();



    
        $card->token = $req['token'];
        $card->serial = $req['serial'];
        $card->expiry=$req['expiry'];
        $card->service_id = $req['service_id'];
        $card->status = $req['status'];
        $card->save();
        return back()->with('success', 'Successfully Updated');
    }


    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $cards = Card::where('token', 'LIKE', "%{$request->data}%")->get()->pluck('token');
        return response()->json($cards);
    }

    public function edit($id)
    {
        $card = Card::find($id);
        $telecom_cat = Category::where('category_type','=','telecom')->first();
        $services = Service::where('category_id','=',$telecom_cat->id)->get();
        return view('admin.pages.services.edit-card-credit', compact('card','services'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        $cardData = Purify::clean($request->all());
        $card = Card::find($request->id);

        $rules = [
            'service_id' => 'required',
            'token' => 'nullable',
         
        ];


        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }



       
        $card->token = $cardData['token'];
         $card->serial = $cardData['serial'];
         $card->expiry=$cardData['expiry'];
        $card->service_id = $cardData['service_id'];
        $card->status = $cardData['status'];
        $card->save();
        return back()->with('success', 'Successfully Updated');
    }


    public function cardActive(Request $request)
    {
        $cards = Card::all();
         foreach ($cards as $card) {
            $re = Card::find($card->id);
            if(!$re){
                continue;
            }else{
                $re->status = 1;
                $re->save();
            }
        }
        return back()->with('success', 'Successfully Updated');
    }


    public function cardDeactive(Request $request)
    {
        $cards = Card::all();
        foreach ($cards as $card) {
            $re = Card::find($card->id);
            if(!$re){
                continue;
            }else{
                $re->status = 0;
                $re->save();   
            }
        }
        return back()->with('success', 'Successfully Updated');
    }


    public function statusChange(Request $request, $id)
    {
        $card = Card::find($id);
        if(!$card){
             return back()->with('error', 'Data Not Found');
        }
        if ($card['status'] == 0) {
            $status = 1;
        } else {
            $status = 0;
        }
        $card->status = $status;
        $card->save();
        return back()->with('success', 'Successfully Updated');
    }


    public function search(Request $request)
    {
        $search = $request->all();
        $cards = Card::when(isset($search['token']), function ($query) use ($search) {
            return $query->where('token', 'LIKE', "%{$search['token']}%");
        })->when(isset($search['status']), function ($query) use ($search) {
            return $query->where('status', $search['status']);
        })->get();
        $cards->append($search);
        return view('admin.pages.services.show-card-credit', compact('cards'));
    }

    //multiple active check
    public function activeMultiple(Request $request)
    {
        if ($request->strIds == null) {
            session()->flash('error', 'You do not select User Id!!');
            return response()->json(['error' => 1]);
        } else {
            $ids = explode(",", $request->strIds);
            if (count($ids) > 0) {
                $cards = Card::whereIn('id', $ids);
                $cards->update([
                    'status' => 1,
                ]);
            }
            session()->flash('success', 'User Active Updated Successfully!!');
            return response()->json(['success' => 1]);
        }

    }

    //multiple inactive check
    public function deactiveMultiple(Request $request)
    {
        if ($request->strIds == null) {
            session()->flash('error', 'You do not select User Id!!');
            return response()->json(['error' => 1]);
        } else {
            $ids = explode(",", $request->strIds);
            if (count($ids) > 0) {
                $cards = Card::whereIn('id', $ids);
                $cards->update([
                    'status' => 0,
                ]);
            }
            session()->flash('success', 'User Active Updated Successfully!!');
            return response()->json(['success' => 1]);
        }
    }
}

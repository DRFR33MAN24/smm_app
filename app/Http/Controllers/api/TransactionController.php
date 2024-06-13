<?php

namespace App\Http\Controllers\api;

use App\CPU\Helpers;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Validator;
use App\Models\Transaction;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;



class TransactionController extends Controller
{

    public function get_transactions(Request $request)
    {
        //LOG::info($request);
        $limit = $request["limit"];
        $date = $request['date'];

        $offset = $request["offset"];
        $paginator = Transaction::query();
        $paginator->where('user_id','=',auth()->user()->id);



        if ($date) {
            $paginator= $paginator->whereDate('created_at', '=', Carbon::parse($date)->toDateString()
         );
         }

        $paginator =$paginator->latest()->paginate($limit, ['*'], 'page', $offset);

        /*$paginator->count();*/
        $transactions = [
            'total_size' => $paginator->total(),
            'limit' => (int)$limit,
            'offset' => (int)$offset,
            'transactions' => $paginator->items()
        ];
       //Log::info(json_encode($paginator->items()));

        return response()->json($transactions, 200);
    }

  
}
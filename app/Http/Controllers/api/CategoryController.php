<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class CategoryController extends Controller
{

    public function get_categories(Request $request)
    {
        //LOG::info($request);
        $limit = $request["limit"];
        $tag = $request['tag'];
        $search = $request['search'];
        $offset = $request["offset"];
        // $paginator = Category::with([ 'tags','services']);
                $paginator = Category::with([ 'tags'=>function($q){
                    $q->where('status', '=', 1);
                },'services' =>function($q){
    // Query the name field in status table
    $q->where('service_status', '=', 1); // '=' is optional
}]);
        $paginator= $paginator->where('status','=',"1");
        $paginator= $paginator->where('category_type','!=',"balance");
        if ($tag) {
            //$paginator = $paginator->whereRelation('tags','tag','=',$tag);
            $paginator = $paginator->where('category_type','LIKE',"%{$tag}%");
            
        }


        if ($search) {
           $paginator= $paginator->where('category_title','LIKE',"%{$search}%");
        }

        $paginator =$paginator->latest()->paginate($limit, ['*'], 'page', $offset);

        /*$paginator->count();*/
        $categories = [
            'total_size' => $paginator->total(),
            'limit' => (int)$limit,
            'offset' => (int)$offset,
            'categories' => $paginator->items()
        ];
      // Log::info(json_encode($paginator->items()));

        return response()->json($categories, 200);
    }

    public function get_categories_tags(Request $request){
        $tags =[
            'tags'=>Tag::where('status','=',"1")->orderBy('sort_id','asc')->get()
        ];
        return response()->json($tags,200);
    }
}
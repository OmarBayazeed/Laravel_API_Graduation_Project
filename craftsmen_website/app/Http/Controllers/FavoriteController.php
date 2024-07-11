<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Craft;
use App\Models\Craftsman;
use App\Models\CraftsmanDoneJobs;
use App\Models\CraftsmanDoneJobsRating;
use App\Models\Favorite;
use App\Models\FavoriteList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FavoriteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function get_FavoriteList(Request $request)
    {
        $client_id = $request->client_id;
        if (!$client_id) {
            return response()->json(['message' => 'you should give me the id of the client in parameter(client_id)','status' => false],404);
        }
        $list_id = $request->list_id;
        if (!$list_id) {
            return response()->json(['message' => 'you should give me the id of the list in parameter(list_id)','status' => false],404);
        }
        $pagination = $request->pagination;
        if (!$pagination) {
            return response()->json(['message' => 'you should give me the value of the pagination in parameter(pagination)','status' => false],404);
        }
        $client = Client::find($client_id);
        if (!$client) {
            return response()->json(['message'=>'client not found','status'=>false,],404);
        }
        $list = FavoriteList::where('id',$list_id)->where('client_id',$client_id)->first();
        if (!$list) {
            return response()->json(['message'=>'list not found or the list does not belong to this client','status'=>false,],404);
        }
        $favorite_list =Favorite::select('id','craftsman_id','craft')->where('client_id',$client_id)->where('list_id',$list_id)->paginate($pagination)->through(function($fv) {
            $craftsman = Craftsman::find($fv->craftsman_id);
            if ($craftsman) {
                $fv['craftsman_name'] = $craftsman->name;
                $fv['craftsman_image'] = $craftsman->image;
                $fv['craftsman_created_at'] = $craftsman->created_at;
                // ratings
                $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $fv->craftsman_id)->where('status','finished')->get();
                if ($done_jobs) {
                    $Ratings =false;
                    foreach ($done_jobs as $done) {
                        $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                    }
                    if ($Ratings) {
                        foreach ($Ratings as $rating) {
                            $totalrates = array_sum(array_map("count", [$rating]));
                            for ($j=0; $j < $totalrates; $j++) {
                                $ratings_values[] = $rating[$j]['rating'];
                            }
                        }
                        if (isset($ratings_values)) {
                            $ratingValuesSum = array_sum($ratings_values);
                            $ratingValuesCount = array_sum(array_map("count", $Ratings));
                            $avgRating = round($ratingValuesSum/$ratingValuesCount);
                            if (!$avgRating) {
                                $avgRating = null;
                            }
                        }else {
                            $avgRating = null;
                            $ratingValuesCount = null;
                        }
                    }else {
                        $avgRating = null;
                        $ratingValuesCount = null;
                    }
                }else {
                    $avgRating = null;
                    $ratingValuesCount = null;
                }
                $fv['average_rating'] = $avgRating;
                $fv['number_of_ratings'] = $ratingValuesCount;
            }else{
                $fv['craftsman_name'] = null;
                $fv['craftsman_image'] = null;
                $fv['average_rating'] = null;
                $fv['number_of_ratings'] = null;
            }
            return $fv;
        });
        if ($favorite_list) {
            return response()->json(['data' => ['title'=>$list->title, 'description' => $list->description,'craftsmen' => $favorite_list],'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'هذه القائمة فارغة أو غير موجودة','status' => false]);
        }
    }

    public function get_favorites(Request $request)
    {
        $client_id = $request->client_id;
        if (!$client_id) {
            return response()->json(['message' => 'you should give me the id of the client in parameter(client_id)','status' => false],404);
        }
        $client = Client::find($client_id);
        if (!$client) {
            return response()->json(['message'=>'client not found','status'=>false,],404);
        }
        $_SESSION['client_id'] = $client_id;
        $favorites =FavoriteList::select('id','title','description')->where('client_id',$client_id)->get()->map(function($ft){
            $favorite_list =Favorite::select('id','craftsman_id','craft')->where('client_id',$_SESSION['client_id'])->where('list_id',$ft->id)->take(4)->get();
            if ($favorite_list) {
                $ft['craftsmen'] = Craftsman::select('id','name','image')->whereIn('id', $favorite_list->pluck('craftsman_id'))->get();
            }else {
                $ft['craftsmen'] = null;
            }
            return $ft;
        });
        if ($favorites->count() > 0) {
            return response()->json(['data' => $favorites,'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'لا يوجد قوائم مفضلة عندك','status' => false]);
        }
    }
    //Craftsman::select('name','image')->whereIn('id',$favorite_list->craftsman_id)

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function add_to_FavoriteList(Request $request)
    {
        $client_id = $request->client_id;
        if (!$client_id) {
            return response()->json(['message' => 'you should give me the id of the client in parameter(client_id)','status' => false],404);
        }
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $list_id = $request->list_id;
        if (!$list_id) {
            return response()->json(['message' => 'you should give me the id of the list in parameter(list_id)','status' => false],404);
        }

        $client = Client::find($client_id);
        if (!$client) {
            return response()->json(['message'=>'client not found','status'=>false,],404);
        }

        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message'=>'craftsman not found','status'=>false,],404);
        }

        $list = FavoriteList::find($list_id);
        if (!$list) {
            return response()->json(['message'=>'list not found','status'=>false,],404);
        }

        $favorite_craftsman = Favorite::all()->where('craftsman_id' , $craftsman_id)->where('client_id' , $client_id)->where('list_id' , $list_id)->first();
        if ($favorite_craftsman) {
            return response()->json([
                'message' => 'الصنايعي موجود في هذه القائمة بالفعل',
                'status' => false,
            ],400);
        }
        $client_favorite_list = FavoriteList::where('client_id' , $client_id)->where('id' , $list_id)->first();
        if (!$client_favorite_list) {
            return response()->json([
                'message' => 'this list is not for this client',
                'status' => false,
            ],400);
        }

        $craft = Craft::select()->where('id',$craftsman->craft_id)->first();
        $save_favorite = Favorite::create(['client_id' => $client_id,'craftsman_id' => $craftsman_id,'craft' => $craft->name,'list_id' => $list_id]);

        if ($save_favorite) {
            return response()->json([
                'message' => 'تم إضافة هذا الصنايعي الى قائمة المفضلة بنجاح',
                'status' => true,
            ],200);
        }
        else
        {
            return response()->json([
                'message' => 'لم نستطع إضافة هذا الصنايعي الى قائمة المفضلة',
                'status' => false,
            ],400);
        }
    }

    public function add_a_favorite(Request $request)
    {
        $client_id = $request->client_id;
        if (!$client_id) {
            return response()->json(['message' => 'you should give me the id of the client in parameter(client_id)','status' => false],404);
        }
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required|max:1000',
        ],[
            'title.required' => 'يجب أن تُرسل العنوان',
            'description.required' => 'يجب أن تُرسل الوصف',
            'description.max:1000' => 'الوصف يجب أن يكون أقل من 1000 حرف',
        ]);
        if ($validator->fails()) {
            return response()->json([$validator->errors(),'status'=>false],400);
        }

        $client = Client::find($client_id);
        if (!$client) {
            return response()->json(['message'=>'client not found','status'=>false,],404);
        }

        $save_favorite = FavoriteList::create(['title' => $request->title,'description' => $request->description,'client_id' => $client_id]);

        if ($save_favorite) {
            return response()->json([
                'message' => 'تم إضافة هذه القائمة الى قوائم المفضلة الخاصة بك',
                'status' => true,
            ],200);
        }
        else
        {
            return response()->json([
                'message' => 'لم نستطع إنشاء هذه القائمة',
                'status' => false,
            ],400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete_from_FavoriteList(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json([
            'message' => 'the craftsman not found',
            'status' => false,
        ],404);
        }
        $client_id = $request->client_id;
        if (!$client_id) {
            return response()->json(['message' => 'you should give me the id of the client in parameter(client_id)','status' => false],404);
        }
        $client = Client::find($client_id);
        if (!$client) {
            return response()->json([
            'message' => 'the client not found',
            'status' => false,
        ],404);
        }
        $list_id = $request->list_id;
        if (!$list_id) {
            return response()->json(['message' => 'you should give me the id of the list in parameter(list_id)','status' => false],404);
        }
        $list = FavoriteList::find($list_id);
        if (!$list) {
            return response()->json(['message'=>'list not found','status'=>false,],404);
        }
        $favorite = Favorite::where('craftsman_id',$craftsman_id)->where('client_id',$client_id)->where('list_id',$list_id);
        if ($favorite) {
            $favorite->delete();
            return response()->json(['message' => 'تم إزالة الصنايعي من قائمتك بنجاح','status' => true],200);
        }else {
            return response()->json(['message' => 'لم نستطع إزالة الصنايعي من قائمتك','status' => false],400);
        }
    }


    public function delete_a_favorite(Request $request)
    {
        $list_id = $request->list_id;
        if (!$list_id) {
            return response()->json(['message' => 'you should give me the id of the list in parameter(list_id)','status' => false],404);
        }
        $list = FavoriteList::find($list_id);
        if (!$list) {
            return response()->json(['message'=>'list not found','status'=>false,],404);
        }
        $list = FavoriteList::where('id',$list_id);
        if ($list) {
            $list->delete();
            return response()->json(['message' => 'تم إزالة هذه القائمة بنجاح','status' => true],200);
        }else {
            return response()->json(['message' => 'لم نستطع إزالة هذه القائمة','status' => false],400);
        }
    }
}

<?php

namespace App\Http\Controllers\craftsman;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientsRating;
use App\Models\Craftsman;
use App\Models\CraftsmanDoneJobs;
use App\Models\CraftsmanDoneJobsimage;
use App\Models\CraftsmanDoneJobsRating;
use App\Models\CraftsmanJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CraftsmanDoneJobsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function get_done_jobs(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $pagination = $request->pagination;
        if (!$pagination) {
            return response()->json(['message' => 'you should give me the value of the pagination in parameter(pagination)','status' => false],404);
        }
        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message'=>'craftsman not found','status'=>false,],404);
        }
        $done_jobs =CraftsmanDoneJobs::select('id','title','description','price','city',"address",'phone','created_at','craftsman_id','client_id')->orderBy('id', 'desc')->where('craftsman_id',$craftsman_id)->where('status','finished')->paginate($pagination)->through(function($job){
            if ($job->done_job_ratings()->first()) {
                $job['craftsman_rating'] = $job->done_job_ratings()->get(['rating','comment','client_id','created_at']);
            }else{
                $job['craftsman_rating'] = [];
            }
            if ($job->client_rating()->first()) {
                $job['clint_rating'] = $job->client_rating()->get(['rating','comment','craftsman_id','created_at']);
            }else{
                $job['clint_rating'] = [];
            }
            if ($job->done_job_images()->get()) {
                $job['images'] = $job->done_job_images()->get(['image']);
            }else{
                $job['images'] = [];
            }
            $client = Client::where('id',$job->client_id)->get()->map(function($cl){
                // ratings
            $Ratings =false;
            if (ClientsRating::select('rating')->where('client_id' , $cl->id)->get()->count() > 0) {
                $Ratings[] = ClientsRating::select('rating')->where('client_id' , $cl->id)->get();
            }
            if ($Ratings) {
                foreach ($Ratings as $rating) {
                    $totalrates = array_sum(array_map("count", [$rating]));
                    for ($j=0; $j < $totalrates; $j++) {
                        $ratings_values[] = $rating[$j]['rating'];
                    }
                }
                if ($ratings_values) {
                    $ratingValuesSum = array_sum($ratings_values);
                    $ratingValuesCount = array_sum(array_map("count", $Ratings));
                    $avgRating = round($ratingValuesSum/$ratingValuesCount);
                    if (!$avgRating) {
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
            $cl['average_rating'] = $avgRating;
            $cl['ratings_num'] = $ratingValuesCount;
            $done_jobs = CraftsmanDoneJobs::where('client_id',$cl->id)->get()->count();
            if ($done_jobs) {
                $cl['doneJobs_num'] = $done_jobs;
            }else {
                $cl['doneJobs_num'] = null;
            }
            return $cl;
            });
            if ($client) {
                $job['client_data'] = $client;
            }else {
                $job['client_data'] = null;
            }
            return $job;
        });

        if ($done_jobs) {
            return response()->json(['data' =>$done_jobs,'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'لا يوجد أعمال منتهية','status' => false]);
        }
    }


    public function get_done_job(Request $request)
    {
        $done_job_id = $request->done_job_id;
        if (!$done_job_id) {
            return response()->json(['message' => 'you should give me the id of the done_job in parameter(done_job_id)','status' => false],404);
        }
        $done_jobs =CraftsmanDoneJobs::select('id','title','description','price','city',"address",'phone','created_at','client_id')->where('id',$done_job_id)->where('status','finished')->get()->map(function($job){
            if ($job->done_job_ratings()->first()) {
                $job['craftsman_rating'] = $job->done_job_ratings()->get(['rating','comment','client_id','created_at']);
            }else{
                $job['craftsman_rating'] = [];
            }
            if ($job->client_rating()->first()) {
                $job['clint_rating'] = $job->client_rating()->get(['rating','comment','craftsman_id','created_at']);
            }else{
                $job['clint_rating'] = [];
            }
            if ($job->done_job_images()->get()) {
                $job['images'] = $job->done_job_images()->get(['image']);
            }else{
                $job['images'] = [];
            }
            $client = Client::where('id',$job->client_id)->get()->map(function($cl){
                // ratings
            $Ratings =false;
            if (ClientsRating::select('rating')->where('client_id' , $cl->id)->get()->count() > 0) {
                $Ratings[] = ClientsRating::select('rating')->where('client_id' , $cl->id)->get();
            }
            if ($Ratings) {
                foreach ($Ratings as $rating) {
                    $totalrates = array_sum(array_map("count", [$rating]));
                    for ($j=0; $j < $totalrates; $j++) {
                        $ratings_values[] = $rating[$j]['rating'];
                    }
                }
                if ($ratings_values) {
                    $ratingValuesSum = array_sum($ratings_values);
                    $ratingValuesCount = array_sum(array_map("count", $Ratings));
                    $avgRating = round($ratingValuesSum/$ratingValuesCount);
                    if (!$avgRating) {
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
            $cl['average_rating'] = $avgRating;
            $cl['ratings_num'] = $ratingValuesCount;
            $done_jobs = CraftsmanDoneJobs::where('client_id',$cl->id)->get()->count();
            if ($done_jobs) {
                $cl['doneJobs_num'] = $done_jobs;
            }else {
                $cl['doneJobs_num'] = null;
            }
            return $cl;
            });
            if ($client) {
                $job['client_data'] = $client;
            }else {
                $job['client_data'] = null;
            }
            return $job;
        });

        if ($done_jobs->count() > 0) {
            return response()->json(['data' =>$done_jobs,'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'هذا العمل المنتهي غير موجود','status' => false]);
        }
    }



    public function get_all_ratings(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $pagination = $request->pagination;
        if (!$pagination) {
            return response()->json(['message' => 'you should give me the value of the pagination in parameter(pagination)','status' => false],404);
        }
        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message'=>'craftsman not found','status'=>false,],404);
        }
        $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $craftsman_id)->where('status','finished')->paginate($pagination);
        if (!$done_jobs) {
            return response()->json([
                'message' => 'there is no done jobs',
                'status' => false,
            ],404);
        }
        $Ratings =false;
        foreach ($done_jobs as $done) {
            $Ratings[] = CraftsmanDoneJobsRating::select('rating','comment','created_at','client_id')->where('craftsmanDoneJob_id' , $done->id)->first();
        }
        if ($Ratings) {
            $reversed = array_reverse($Ratings);
            return response()->json([
                'data' => $reversed,
                'status' => true,
            ],200);
        }else {
            return response()->json([
                'message' => 'لا يوجد تقييمات بعد',
                'status' => false,
            ]);
        }
    }


    public function portfolio(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message'=>'craftsman not found','status'=>false,],404);
        }
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required|max:1000',
            'image' => 'required|array',
            'image.*' => 'mimes:png,jpg,jpeg|max:50120',
        ],[
            'title.required' => 'يجب أن تُرسل العنوان',
            'description.required' => 'يجب أن تُرسل الوصف',
            'description.max:1000' => 'الوصف يجب أن يكون أقل من 1000 حرف',
            'image.required' => 'يجب أن تُرسل صورة واحدة على الأقل',
            'image.array' => 'يجب أن تُرسل الصورة على هيئة مصفوفة',
            'image.*.mimes:png,jpg,jpeg' => 'الإمتداد يجب أن يكون png أو jpg أو jpeg',
            'image.*.max:50120' => 'يجب أن يكون حجم الصورة أقل من 50 ميجا',
        ]);
        if ($validator->fails()) {
            return response()->json([$validator->errors(),'status'=>false],401);
        }

        $status = 'portfolio';

        $portfolio = CraftsmanDoneJobs::create($request->post()+['craftsman_id' => $craftsman_id, 'status' => $status,'price'=>0,'city'=>Null,'client_id'=>NULL]);
        if ($portfolio) {
            foreach ($request->file('image') as $img) {
                $done_job_image = new CraftsmanDoneJobsimage();
                if ($done_job_image->image) {
                    $exist = Storage::disk('public')->exists('images/'. $done_job_image->image);
                    if ($exist) {
                        $exist = Storage::disk('public')->delete('images/'. $done_job_image->image);
                    }
                }
                $imageName = 'done_jobs/'.Str::random().'.'.$img->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/', $img, $imageName);
                $done_job_image->image = $imageName;
                $done_job_image->craftsmanDoneJob_id = $portfolio->id;
                $done_job_image->save();
            }
            return response()->json([
                'message' => 'تم إضافة العمل الى المعرض بنجاح',
                'status' => true,
                ],201);
        }
        else
        {
            return response()->json(['message' => 'لم نستطع اضافة العمل الى المعرض', 'status' => false],401);
        }
    }

    public function get_portfolio(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $pagination = $request->pagination;
        if (!$pagination) {
            return response()->json(['message' => 'you should give me the value of the pagination in parameter(pagination)','status' => false],404);
        }
        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message'=>'craftsman not found','status'=>false,],404);
        }
        $portfolio =CraftsmanDoneJobs::select('id','title','description')->orderBy('id', 'desc')->where('craftsman_id',$craftsman_id)->where('status','portfolio')->paginate($pagination)->through(function($job){
            if ($job->done_job_images()->get()) {
                $job['images'] = $job->done_job_images()->get(['id','image']);
            }else {
                $job['images'] = [];
            }
            return $job;
        });
        if ($portfolio) {
            return response()->json(['data' =>$portfolio,'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'لا يوجد أعمال في المعرض','status' => false]);
        }
    }


    public function get_one_portfolio(Request $request)
    {
        $portfolio_id = $request->portfolio_id;
        if (!$portfolio_id) {
            return response()->json(['message' => 'you should give me the id of the portfolio in parameter(portfolio_id)','status' => false],404);
        }
        $portfolio = CraftsmanDoneJobs::select('id','title','description')->where('status','portfolio')->where('id',$portfolio_id)->get()->map(function($job){
            if ($job->done_job_images()->get()) {
                $job['images'] = $job->done_job_images()->get(['id','image']);
            }else {
                $job['images'] = [];
            }
            return $job;
        });
        if ($portfolio->count() == 0)
        {
            return response()->json(['message' => 'هذا العمل غير موجود', 'status' => false]);
        }
        elseif ($portfolio) {
            return response()->json(['data' =>$portfolio,'status' => true],200);
        }
    }


    public function update_portfolio(Request $request)
    {
        $portfolio_id = $request->portfolio_id;
        if (!$portfolio_id) {
            return response()->json(['message' => 'you should give me the id of the portfolio in parameter(portfolio_id)','status' => false],404);
        }
        $validator = Validator::make($request->all(), [
            'title' => 'nullable',
            'description' => 'nullable|max:1000',
            'image' => 'required|array',
            'image.*' => 'mimes:png,jpg,jpeg|max:50120',
        ],[
            'description.max:1000' => 'الوصف يجب أن يكون أقل من 1000 حرف',
            'image.required' => 'يجب أن تُرسل صور',
            'image.array' => 'يجب أن تُرسل الصورة على هيئة مصفوفة',
            'image.*.mimes:png,jpg,jpeg' => 'الإمتداد يجب أن يكون png أو jpg أو jpeg',
            'image.*.max:50120' => 'يجب أن يكون حجم الصورة أقل من 50 ميجا',
        ]);
        if ($validator->fails()) {
            return response()->json([$validator->errors(),'status'=>false],400);
        }
        $portfolio = CraftsmanDoneJobs::select()->where('status','portfolio')->where('id',$portfolio_id)->first();
        if (!$portfolio) {
            return response()->json(['message' => 'can not find this portfolio', 'status' => false],400);
        }
        $status = 'portfolio';
        $portfolio->fill($request->post()+['status' => $status,'price'=>0,'city'=>null])->update();
        if ($request->hasFile('image')) {
            $done_job_images = CraftsmanDoneJobsimage::select('image')->where('craftsmanDoneJob_id',$portfolio_id)->get();
            foreach ($done_job_images as $done_job_image) {
                if ($done_job_image->image) {
                    $exist = Storage::disk('public')->exists('images/'. $done_job_image->image);
                    if ($exist) {
                        $exist = Storage::disk('public')->delete('images/'. $done_job_image->image);
                    }
                    $done_job_image->delete();
                }
            }
            CraftsmanDoneJobsimage::select('image')->where('craftsmanDoneJob_id',$portfolio_id)->delete();
            foreach ($request->file('image') as $img) {
                $imageName = 'done_jobs/'.Str::random().'.'.$img->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/', $img, $imageName);
                $done_job_image = new CraftsmanDoneJobsimage();
                $done_job_image->image = $imageName;
                $done_job_image->craftsmanDoneJob_id = $portfolio_id;
                $done_job_image->save();
            }
            return response()->json([
                'message' => 'تم التعديل بنجاح',
                'status' => true,
                ],201);
        }
        else
        {
            return response()->json(['message' => 'لم نستطع تعديل العمل', 'status' => false],400);
        }
    }


    public function delete_portfolio(Request $request)
    {
        $portfolio_id = $request->portfolio_id;
        if (!$portfolio_id) {
            return response()->json(['message' => 'you should give me the id of the portfolio in parameter(portfolio_id)','status' => false],404);
        }
        $done_jobs = CraftsmanDoneJobs::where('id' , $portfolio_id)->where('status','portfolio');
        if ($done_jobs) {
            foreach ($done_jobs as $done) {
                $done_job_images = CraftsmanDoneJobsimage::where('craftsmanDoneJob_id',$done->id);
                foreach ($done_job_images as $done_job_image) {
                    if ($done_job_image->image) {
                        $exist = Storage::disk('public')->exists('images/'. $done_job_image->image);
                        if ($exist) {
                            $exist = Storage::disk('public')->delete('images/'. $done_job_image->image);
                        }
                        $done_job_image->delete();
                    }
                }
            }
            $done_jobs->delete();
            return response()->json(['message' => 'تم إزالته بنجاح','status' => true],200);
        }else {
            return response()->json(['message' => 'لم نستطع إزالة العمل','status' => false],400);
        }
    }

    public function delete_portfolio_image(Request $request)
    {
        $image_id = $request->image_id;
        if (!$image_id) {
            return response()->json(['message' => 'you should give me the id of the image in parameter(image_id)','status' => false],404);
        }
        $done_job_image = CraftsmanDoneJobsimage::where('id',$image_id)->first();
        if ($done_job_image) {
            $portfolio = CraftsmanDoneJobs::where('id',$done_job_image->craftsmanDoneJob_id)->where('status','portfolio')->first();
            if(!$portfolio)
            {
                return response()->json(['message' => 'there is no image with this id in the portfolio section','status' => false],400);
            }
            $exist = Storage::disk('public')->exists('images/'. $done_job_image->image);
            if ($exist) {
                $exist = Storage::disk('public')->delete('images/'. $done_job_image->image);
            }
            $done_job_image->delete();
            if ($done_job_image) {
                return response()->json(['message' => 'تم إزالة الصورة بنجاح','status' => true],200);
            }
            else {
                return response()->json(['message' => 'لم نستطع إزالة الصورة','status' => false],400);
            }
        }
        else{
            return response()->json(['message' => 'there is no image with this id','status' => false],400);
        }
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

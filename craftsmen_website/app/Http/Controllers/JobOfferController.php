<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Client;
use App\Models\ClientsRating;
use App\Models\Craft;
use App\Models\Craftsman;
use App\Models\CraftsmanDoneJobs;
use App\Models\CraftsmanDoneJobsRating;
use App\Models\JobsOffer;
use App\Models\JobsOfferImage;
use App\Models\JobsOfferInspection;
use App\Models\JobsOfferReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JobOfferController extends Controller
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
    public function add_job_offer(Request $request)
    {
        $client_id = $request->client_id;
        if (!$client_id) {
            return response()->json(['message' => 'you should give me the id of the client in parameter(client_id)','status' => false],404);
        }
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required|max:1000',
            'client_price' => 'nullable|numeric',
            'address' => 'nullable',
            'phone' => 'nullable',
            'city' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'craft_id' => 'required',
            'image' => 'nullable|array',
            'image.*' => 'mimes:png,jpg,jpeg|max:50120',
        ],[
            'title.required' => 'يجب أن تُرسل العنوان',
            'description.required' => 'يجب أن تُرسل الوصف',
            'description.max:1000' => 'الوصف يجب أن يكون أقل من 1000 حرف',
            'client_price.numeric' => 'يجب أن يتكون السعر المعروض من أرقام فقط',
            'city.required' => 'يجب أن تُرسل اسم المدينة التي سيتم فيها العمل',
            'start_date.required' => 'يجب أن تُرسل الموعد الذي سيبدأ فيه العمل',
            'start_date.date' => 'يجب أن يكون موعد البدأ على هيئة تاريخ',
            'end_date.date' => 'يجب أن يكون موعد الإنتهاء على هيئة تاريخ',
            'craft_id.required' => 'يجب أن تختار الصنعة التي يندرج تحتها العمل',
            'image.array' => 'يجب أن تُرسل الصورة على هيئة مصفوفة',
            'image.*.mimes:png,jpg,jpeg' => 'الإمتداد يجب أن يكون png أو jpg أو jpeg',
            'image.*.max:50120' => 'يجب أن يكون حجم الصورة أقل من 50 ميجا',
        ]);
        if ($validator->fails()) {
            return response()->json([$validator->errors(),'status'=>false],400);
        }
        $client = Client::find($client_id);
        if (!$client) {
            return response()->json(['message'=>'client not found','status'=>false,],404);
        }
        $craft = Craft::find($request->craft_id);
        if (!$craft) {
            return response()->json(['message'=>'craft not found','status'=>false,],404);
        }
        $job_offer = JobsOffer::create($request->post()+['client_id' => $client_id]);
        if ($job_offer) {
            if ($request->hasFile('image')) {
                foreach ($request->file('image') as $img) {
                    $job_offer_image = new JobsOfferImage();
                    $imageName = 'jobs_offers/'.Str::random().'.'.$img->getClientOriginalExtension();
                    Storage::disk('public')->putFileAs('images/', $img, $imageName);
                    $job_offer_image->image = $imageName;
                    $job_offer_image->job_offer_id = $job_offer->id;
                    $job_offer_image->save();
                }
            }
            return response()->json([
                'message' => 'تم إضافة العرض الخاص بك في قائمة العروض',
                'status' => true,
            ],200);
        }
        else
        {
            return response()->json([
                'message' => 'لم نستطع إضافة العرض الخاص بك في قائمة العروض',
                'status' => false,
            ],400);
        }
    }


    public function delete_job_offer(Request $request)
    {
        $job_offer_id = $request->job_offer_id;
        if (!$job_offer_id) {
            return response()->json(['message' => 'you should give me the id of the job_offer in parameter(job_offer_id)','status' => false],404);
        }
        $job_offer = JobsOffer::find($job_offer_id);
        if (!$job_offer) {
            return response()->json([
            'message' => 'the job offer not found',
            'status' => false,
        ],404);
        }
        if ($job_offer) {
                $offer_job_images = JobsOfferImage::where('Job_offer_id',$job_offer->id);
                foreach ($offer_job_images as $offer_job_image) {
                    if ($offer_job_image->image) {
                        $exist = Storage::disk('public')->exists('images/'. $offer_job_image->image);
                        if ($exist) {
                            $exist = Storage::disk('public')->delete('images/'. $offer_job_image->image);
                        }
                        $offer_job_image->delete();
                    }
                }
                $replies = JobsOfferReply::where('job_offer_id',$job_offer->id);
                if ($replies) {
                    $replies->delete();
                }
            $job_offer->delete();
            return response()->json(['message' => 'تم إزالة العرض الخاص بك من قائمة العروض بنجاح','status' => true],200);
        }else {
            return response()->json(['message' => 'لم نستطع إزالة العرض الخاص بك من قائمة العروض','status' => false],400);
        }
    }

    public function get_one_job_offer(Request $request)
    {
        $job_offer_id = $request->job_offer_id;
        if (!$job_offer_id) {
            return response()->json(['message' => 'you should give me the id of the job offer in parameter(job_offer_id)','status' => false],404);
        }
        $job_offer =JobsOffer::select('id','title','description','address','phone','city','start_date','end_date','client_price','client_id','craft_id','created_at')->where('id',$job_offer_id)->get()->map(function($jf){
            if ($jf->job_offer_images()->get()) {
                $jf['images'] = $jf->job_offer_images()->get('image');
            }else {
                $jf['images'] = [];
            }
            if ($jf->craft()->first()) {
                $jf['craftName'] = $jf->craft()->first('name');
            }else{
                $jf['craftName'] = [];
            }
            $client = Client::where('id',$jf->client_id)->get()->map(function($cl){
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
                $jf['client_data'] = $client;
            }else {
                $jf['client_data'] = null;
            }
            return $jf;
        });
        if ($job_offer->count() > 0) {
            return response()->json(['data' =>$job_offer,'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'هذا العرض غير موجود','status' => false]);
        }
    }

    public function get_client_job_offers(Request $request)
    {
        $client_id = $request->client_id;
        if (!$client_id) {
            return response()->json(['message' => 'you should give me the id of the client in parameter(client_id)','status' => false],404);
        }
        $pagination = $request->pagination;
        if (!$pagination) {
            return response()->json(['message' => 'you should give me the value of the pagination in parameter(pagination)','status' => false],404);
        }
        $client = Client::find($client_id);
        if (!$client) {
            return response()->json(['message'=>'client not found','status'=>false,],404);
        }
        $job_offer = JobsOffer::where('client_id',$client_id)->get();
        if ($job_offer->count() == 0) {
            return response()->json(['message'=>'client does not have job offers','status'=>false,],404);
        }
        $job_offers =JobsOffer::select('id','title','description','address','phone','city','start_date','end_date','client_price','client_id','craft_id','created_at')->orderBy('id', 'desc')->where('client_id',$client_id)->paginate($pagination)->through(function($jf){
            if ($jf->job_offer_images()->get()) {
                $jf['images'] = $jf->job_offer_images()->get('image');
            }else {
                $jf['images'] = [];
            }
            if ($jf->craft()->first()) {
                $jf['craftName'] = $jf->craft()->first('name');
            }else{
                $jf['craftName'] = [];
            }
            $client = Client::where('id',$jf->client_id)->get()->map(function($cl){
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
                $jf['client_data'] = $client;
            }else {
                $jf['client_data'] = null;
            }
            return $jf;
        });
        if ($job_offers) {
            return response()->json(['data' => $job_offers,'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'قائمة العروض الخاصة بك فارغة','status' => false]);
        }
    }


    public function get_city_job_offers(Request $request)
    {
        $cities = $request->city;
        if (!$cities) {
            return response()->json(['message' => 'you should give me the name of the city in parameter(city)','status' => false],404);
        }
        $craft = $request->craft;
        if (!$craft) {
            return response()->json(['message' => 'you should give me the name of the craft in parameter(craft)','status' => false],404);
        }
        $pagination = $request->pagination;
        if (!$pagination) {
            return response()->json(['message' => 'you should give me the value of the pagination in parameter(pagination)','status' => false],404);
        }
        $crafts = Craft::select('id')->where('name' , $craft)->first();
        if ($crafts) {
            $craft_id = $crafts->id;
        }else {
            return response()->json(['message' => 'there is no craft in this name','status' => false],404);
        }
        foreach ($cities as $city) {
            $cityName = City::select('city')->where('city' , $city)->get();
            $totalCities = array_sum(array_map("count", [$cityName]));
            for ($j=0; $j < $totalCities; $j++) {
                $citiesNames[] = $cityName[$j]['city'];
            }
        }
        $job_offers =JobsOffer::select('id','title','description','address','phone','city','start_date','end_date','client_price','client_id','craft_id','created_at')->orderBy('id', 'desc')->whereIn('city',$citiesNames)->where('craft_id',$craft_id)->paginate($pagination)->through(function($jf){
            if ($jf->job_offer_images()->get()) {
                $jf['images'] = $jf->job_offer_images()->get('image');
            }else {
                $jf['images'] = [];
            }
            if ($jf->craft()->first()) {
                $jf['craftName'] = $jf->craft()->first('name');
            }else{
                $jf['craftName'] = [];
            }
            $client = Client::where('id',$jf->client_id)->get()->map(function($cl){
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
                $jf['client_data'] = $client;
            }else {
                $jf['client_data'] = null;
            }
            return $jf;
        });
        if ($job_offers->count() > 0) {
            return response()->json(['data' => ['jobOffers'=>$job_offers],'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'لا يوجد عروض في هذه المدينة','status' => false]);
        }
    }


    public function add_reply(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $job_offer_id = $request->job_offer_id;
        if (!$job_offer_id) {
            return response()->json(['message' => 'you should give me the id of the job offer in parameter(job_offer_id)','status' => false],404);
        }
        $validator = Validator::make($request->all(), [
            'offered_price' => 'required|numeric',
            'description' => 'required|max:1000',
            'type_of_pricing' => 'required',
        ],[
            'offered_price.required' => 'يجب أن تُرسل السعر المعروض',
            'offered_price.numeric' => 'يجب أن يكون السعر مكون من أرقام فقط',
            'description.required' => 'يجب أن تُرسل الوصف',
            'description.max:1000' => 'الوصف يجب أن يكون أقل من 1000 حرف',
            'type_of_pricing.required' => 'يجب أن تُرسل نوع التسعيرة',
        ]);
        if ($validator->fails()) {
            return response()->json([$validator->errors(),'status'=>false],401);
        }

        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message'=>'craftsman not found','status'=>false,],404);
        }

        $job_offer = JobsOffer::find($job_offer_id);
        if (!$job_offer) {
            return response()->json(['message'=>'the job offer not found','status'=>false,],404);
        }
        $oldReply = JobsOfferReply::where('job_offer_id',$job_offer_id)->where('craftsman_id',$craftsman_id)->first();
        if ($oldReply) {
            return response()->json([
            'message' => 'لقد قدمت رداً لهذا العرض بالفعل',
            'status' => false,
            ],400);
        }

        $job_offer_reply = new JobsOfferReply();

        $job_offer_reply->offered_price = $request->offered_price;
        $job_offer_reply->description = $request->description;
        $job_offer_reply->type_of_pricing = $request->type_of_pricing;
        $job_offer_reply->job_offer_id = $job_offer_id;
        $job_offer_reply->craftsman_id = $craftsman_id;
        $job_offer_reply->save();

        if ($job_offer_reply) {
            return response()->json([
            'message' => 'تم تسجيل ردك على هذا العرض بنجاح',
            'status' => true,
            ],201);
        }
        else
        {
            return response()->json([
                'message' => 'لم نستطع تسجيل ردك على هذا العرض',
                'status' => false,
            ],400);
        }
    }

    public function update_reply(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $reply_id = $request->reply_id;
        if (!$reply_id) {
            return response()->json(['message' => 'you should give me the id of the reply in parameter(reply_id)','status' => false],404);
        }
        $validator = Validator::make($request->all(), [
            'offered_price' => 'required|numeric',
            'description' => 'required|max:1000',
            'type_of_pricing' => 'required',
        ],[
            'offered_price.required' => 'يجب أن تُرسل السعر المعروض',
            'offered_price.numeric' => 'يجب أن يكون السعر مكون من أرقام فقط',
            'description.required' => 'يجب أن تُرسل الوصف',
            'description.max:1000' => 'الوصف يجب أن يكون أقل من 1000 حرف',
            'type_of_pricing.required' => 'يجب أن تُرسل نوع التسعيرة',
        ]);
        if ($validator->fails()) {
            return response()->json([$validator->errors(),'status'=>false],401);
        }

        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message'=>'craftsman not found','status'=>false,],404);
        }

        $job_offer_reply = JobsOfferReply::find($reply_id);
        if (!$job_offer_reply) {
            return response()->json(['message'=>'the job offer reply not found','status'=>false,],404);
        }

        if ($job_offer_reply->craftsman_id != $craftsman_id) {
            return response()->json(['message'=>'this reply is not for this craftsman','status'=>false,],404);
        }

        $job_offer_reply->fill($request->post())->update();

        if ($job_offer_reply) {
            return response()->json([
            'message' => 'تم تعديل ردك على هذا العرض بنجاح',
            'status' => true,
            ],201);
        }
        else
        {
            return response()->json([
                'message' => 'لم نستطع تعديل ردك على هذا العرض',
                'status' => false,
            ],400);
        }
    }

    public function get_replies(Request $request)
    {
        $job_offer_id = $request->job_offer_id;
        if (!$job_offer_id) {
            return response()->json(['message' => 'you should give me the id of the job offer in parameter(job_offer_id)','status' => false],404);
        }
        $pagination = $request->pagination;
        if (!$pagination) {
            return response()->json(['message' => 'you should give me the value of the pagination in parameter(pagination)','status' => false],404);
        }

        $job_offer = JobsOffer::find($job_offer_id);
        if (!$job_offer) {
            return response()->json(['message'=>'job offer not found','status'=>false,],404);
        }
        $replies =JobsOfferReply::select("id","offered_price","description","type_of_pricing","craftsman_id",'job_offer_id',"created_at")->orderBy('id', 'desc')->where('job_offer_id',$job_offer_id)->paginate($pagination)->through(function($rp) {
            $craftsman = Craftsman::find($rp->craftsman_id);
            if ($craftsman) {
                $rp['craftsman_name'] = $craftsman->name;
                $rp['craftsman_image'] = $craftsman->image;
                // ratings
                $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $rp->craftsman_id)->where('status','finished')->get();
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
                $rp['average_rating'] = $avgRating;
                $rp['number_of_ratings'] = $ratingValuesCount;
            }else{
                $rp['craftsman_name'] = null;
                $rp['craftsman_image'] = null;
                $rp['average_rating'] = null;
                $rp['number_of_ratings'] = null;
            }
            return $rp;
        });
        if ($replies) {
            return response()->json(['data' => ['job_offer_title' => $job_offer->title,'replies' => $replies],'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'لا يوجد ردود على هذا العرض','status' => false]);
        }
    }


    public function delete_reply(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $job_offer_id = $request->job_offer_id;
        if (!$job_offer_id) {
            return response()->json(['message' => 'you should give me the id of the job_offer in parameter(job_offer_id)','status' => false],404);
        }
        $oldReply = JobsOfferReply::where('craftsman_id',$craftsman_id)->where('job_offer_id',$job_offer_id)->first();
        if ($oldReply) {
            $oldReply->delete();
            return response()->json(['message' => 'تم إزالة ردك بنجاح','status' => true],200);
        }else {
            return response()->json(['message' => 'لم نستطع إزالة ردك','status' => false],400);
        }
    }

    public function add_inspection(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $job_offer_id = $request->job_offer_id;
        if (!$job_offer_id) {
            return response()->json(['message' => 'you should give me the id of the job offer in parameter(job_offer_id)','status' => false],404);
        }
        $validator = Validator::make($request->all(), [
            'offered_price' => 'required|numeric',
            'inspection_price' => 'required|numeric',
            'description' => 'required|max:1000',
            'start_date' => 'required',
            'end_date' => 'required',
            'type_of_pricing' => 'required',
        ],[
            'offered_price.required' => 'يجب أن تُرسل السعر المبدئي',
            'offered_price.numeric' => 'يجب أن يكون السعر المبدئي مكون من أرقام فقط',
            'inspection_price.required' => 'يجب أن تُرسل سعر المعاينة',
            'inspection_price.numeric' => 'يجب أن يكون سعر المعاينة مكون من أرقام فقط',
            'description.required' => 'يجب أن تُرسل الوصف',
            'description.max:1000' => 'الوصف يجب أن يكون أقل من 1000 حرف',
            'start_date.required' => 'يجب أن تُرسل تاريخ البداية',
            'end_date.required' => 'يجب أن تُرسل تاريخ الإنتهاء',
            'type_of_pricing.required' => 'يجب أن تُرسل نوع التسعيرة',
        ]);
        if ($validator->fails()) {
            return response()->json([$validator->errors(),'status'=>false],401);
        }

        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message'=>'craftsman not found','status'=>false,],404);
        }

        $job_offer = JobsOffer::find($job_offer_id);
        if (!$job_offer) {
            return response()->json(['message'=>'the job offer not found','status'=>false,],404);
        }
        $oldinspection = JobsOfferInspection::where('job_offer_id',$job_offer_id)->where('craftsman_id',$craftsman_id)->first();
        if ($oldinspection) {
            return response()->json([
            'message' => 'لقد قدمت طلب معاينة لهذا العرض بالفعل',
            'status' => false,
            ],400);
        }

        $job_offer_inspection = new JobsOfferInspection();

        $job_offer_inspection->offered_price = $request->offered_price;
        $job_offer_inspection->inspection_price = $request->inspection_price;
        $job_offer_inspection->description = $request->description;
        $job_offer_inspection->type_of_pricing = $request->type_of_pricing;
        $job_offer_inspection->start_date = $request->start_date;
        $job_offer_inspection->end_date = $request->end_date;
        $job_offer_inspection->job_offer_id = $job_offer_id;
        $job_offer_inspection->craftsman_id = $craftsman_id;
        $job_offer_inspection->save();

        if ($job_offer_inspection) {
            return response()->json([
            'message' => 'تم تسجيل طلب المعاينة على هذا العرض بنجاح',
            'status' => true,
            ],201);
        }
        else
        {
            return response()->json([
                'message' => 'لم نستطع تسجيل طلب المعاينة على هذا العرض',
                'status' => false,
            ],400);
        }
    }

    public function update_inspection(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $inspection_id = $request->inspection_id;
        if (!$inspection_id) {
            return response()->json(['message' => 'you should give me the id of the inspection in parameter(inspection_id)','status' => false],404);
        }
        $validator = Validator::make($request->all(), [
            'offered_price' => 'required|numeric',
            'inspection_price' => 'required|numeric',
            'description' => 'required|max:1000',
            'start_date' => 'required',
            'end_date' => 'required',
            'type_of_pricing' => 'required',
        ],[
            'offered_price.required' => 'يجب أن تُرسل السعر المبدئي',
            'offered_price.numeric' => 'يجب أن يكون السعر المبدئي مكون من أرقام فقط',
            'inspection_price.required' => 'يجب أن تُرسل سعر المعاينة',
            'inspection_price.numeric' => 'يجب أن يكون سعر المعاينة مكون من أرقام فقط',
            'description.required' => 'يجب أن تُرسل الوصف',
            'description.max:1000' => 'الوصف يجب أن يكون أقل من 1000 حرف',
            'start_date.required' => 'يجب أن تُرسل تاريخ البداية',
            'end_date.required' => 'يجب أن تُرسل تاريخ الإنتهاء',
            'type_of_pricing.required' => 'يجب أن تُرسل نوع التسعيرة',
        ]);
        if ($validator->fails()) {
            return response()->json([$validator->errors(),'status'=>false],401);
        }

        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message'=>'craftsman not found','status'=>false,],404);
        }

        $job_offer_inspection = JobsOfferInspection::find($inspection_id);
        if (!$job_offer_inspection) {
            return response()->json(['message'=>'the job offer inspection not found','status'=>false,],404);
        }

        if ($job_offer_inspection->craftsman_id != $craftsman_id) {
            return response()->json(['message'=>'this inspection is not for this craftsman','status'=>false,],404);
        }

        $job_offer_inspection->fill($request->post())->update();

        if ($job_offer_inspection) {
            return response()->json([
            'message' => 'تم تعديل طلب المعاينة على هذا العرض بنجاح',
            'status' => true,
            ],200);
        }
        else
        {
            return response()->json([
                'message' => 'لم نستطع تعديل طلب المعاينة على هذا العرض',
                'status' => false,
            ],400);
        }
    }

    public function get_inspections(Request $request)
    {
        $job_offer_id = $request->job_offer_id;
        if (!$job_offer_id) {
            return response()->json(['message' => 'you should give me the id of the job offer in parameter(job_offer_id)','status' => false],404);
        }
        $pagination = $request->pagination;
        if (!$pagination) {
            return response()->json(['message' => 'you should give me the value of the pagination in parameter(pagination)','status' => false],404);
        }

        $job_offer = JobsOffer::find($job_offer_id);
        if (!$job_offer) {
            return response()->json(['message'=>'job offer not found','status'=>false,],404);
        }
        $inspections =JobsOfferInspection::select()->orderBy('id', 'desc')->where('job_offer_id',$job_offer_id)->paginate($pagination)->through(function($rp) {
            $craftsman = Craftsman::find($rp->craftsman_id);
            if ($craftsman) {
                $rp['craftsman_name'] = $craftsman->name;
                $rp['craftsman_image'] = $craftsman->image;
                // ratings
                $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $rp->craftsman_id)->where('status','finished')->get();
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
                $rp['average_rating'] = $avgRating;
                $rp['number_of_ratings'] = $ratingValuesCount;
            }else{
                $rp['craftsman_name'] = null;
                $rp['craftsman_image'] = null;
                $rp['average_rating'] = null;
                $rp['number_of_ratings'] = null;
            }
            return $rp;
        });
        if ($inspections) {
            return response()->json(['data' => ['job_offer_title' => $job_offer->title,'inspections' => $inspections],'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'لا يوجد طلبات معاينة على هذا العرض','status' => false]);
        }
    }

    public function delete_inspection(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $job_offer_id = $request->job_offer_id;
        if (!$job_offer_id) {
            return response()->json(['message' => 'you should give me the id of the job_offer in parameter(job_offer_id)','status' => false],404);
        }
        $oldInspection = JobsOfferInspection::where('craftsman_id',$craftsman_id)->where('job_offer_id',$job_offer_id)->first();
        if ($oldInspection) {
            $oldInspection->delete();
            return response()->json(['message' => 'تم إزالة طلب المعاينة بنجاح','status' => true],200);
        }else {
            return response()->json(['message' => 'لم نجد طلب المعاينة','status' => false],400);
        }
    }


    public function get_pending_jobs(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $pagination = $request->pagination;
        if (!$pagination) {
            return response()->json(['message' => 'you should give me the pagination value in parameter(pagination)','status' => false],404);
        }
        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message'=>'craftsman not found','status'=>false,],404);
        }
        $replies =JobsOfferReply::select("job_offer_id as pending_job_id",'created_at as reply_created_at')->orderBy('reply_created_at', 'desc')->where('craftsman_id',$craftsman_id)->paginate($pagination)->through(function($rp){
            $rp['pending_job'] = JobsOffer::select('id','title','description','address','phone','city','start_date','end_date','client_price','client_id','craft_id','created_at')->where('id',$rp->pending_job_id)->get()->map(function($jb){
                $client = Client::where('id',$jb->client_id)->get()->map(function($cl){
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
                    $jb['client_data'] = $client;
                }else {
                    $jb['client_data'] = null;
                }
                if ($jb->job_offer_images()->get()) {
                    $jb['images'] = $jb->job_offer_images()->get('image');
                }else {
                    $jb['images'] = [];
                }
                return $jb;
            });
            return $rp;
        });
        if ($replies->count() > 0) {
            return response()->json(['data' => $replies,'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'لا يوجد أعمال قيد الإنتظار','status' => false]);
        }
    }


    public function get_one_pending_jobs(Request $request)
    {
        $pending_job_id = $request->pending_job_id;
        if (!$pending_job_id) {
            return response()->json(['message' => 'you should give me the id of the pending_job in parameter(pending_job_id)','status' => false],404);
        }
        $pending_job = JobsOffer::select('id','title','description','address','phone','city','start_date','end_date','client_price','client_id','craft_id','created_at')->where('id',$pending_job_id)->get()->map(function($jb){
            $client = Client::where('id',$jb->client_id)->get()->map(function($cl){
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
                $jb['client_data'] = $client;
            }else {
                $jb['client_data'] = null;
            }
            if ($jb->job_offer_images()->get()) {
                $jb['images'] = $jb->job_offer_images()->get('image');
            }else {
                $jb['images'] = [];
            }
            return $jb;
        });
        if ($pending_job->count() > 0) {
            return response()->json(['data' => $pending_job,'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'هذا العمل المعلق غير موجود','status' => false]);
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

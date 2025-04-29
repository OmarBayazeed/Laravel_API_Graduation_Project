<?php

namespace App\Http\Controllers\craftsman;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientJobCancel;
use App\Models\ClientNotification;
use App\Models\ClientsRating;
use App\Models\Craftsman;
use App\Models\CraftsmanDoneJobs;
use App\Models\CraftsmanDoneJobsimage;
use App\Models\CraftsmanDoneJobsRating;
use App\Models\CraftsmanJob;
use App\Models\CraftsmanJobCancel;
use App\Models\CraftsmanJobFinished;
use App\Models\CraftsmanJobImage;
use App\Models\CraftsmanNotification;
use App\Models\JobsOffer;
use App\Models\JobsOfferImage;
use App\Models\JobsOfferInspection;
use App\Models\JobsOfferReply;
use App\Models\Phone;
use App\Services\NotificationSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CraftsmanJobsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function get_jobs(Request $request)
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
        $jobs =CraftsmanJob::select("id","title","description","price","city","address","phone","type_of_pricing","start_date","end_date","client_id","craftsman_id","created_at",)->orderBy('id', 'desc')->where('craftsman_id',$craftsman_id)->paginate($pagination)->through(function($jb){
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
            $CheckFinishActiveJob = CraftsmanJobFinished::select()->where('active_job_id',$jb->id)->first();
            if ($CheckFinishActiveJob) {
                $jb['is_finished'] = 'yes';
            }else {
                $jb['is_finished'] = 'no';
            }
            if ($jb->job_images()->get()) {
                $jb['images'] = $jb->job_images()->get(['image']);
            }else{
                $jb['images'] = [];
            }
            return $jb;
        });

        if ($jobs) {
            return response()->json(['data' => $jobs,'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'لا يوجد أعمال نشطة','status' => false]);
        }
    }

    public function get_job(Request $request)
    {
        $job_id = $request->job_id;
        if (!$job_id) {
            return response()->json(['message' => 'you should give me the id of the job in parameter(job_id)','status' => false],404);
        }
        $jobs =CraftsmanJob::select("id","title","description","price","city","address","phone","type_of_pricing","start_date","end_date","client_id","craftsman_id","created_at",)->where('id',$job_id)->get()->map(function($jb){
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
            $finished = CraftsmanJobFinished::where('active_job_id',$jb->id)->first();
            if ($finished) {
                $jb['is_finished'] = 'yes';
            }else {
                $jb['is_finished'] = 'no';
            }
            if ($jb->job_images()->get()) {
                $jb['images'] = $jb->job_images()->get(['image']);
            }else{
                $jb['images'] = [];
            }
            return $jb;
        });

        if ($jobs->count() > 0) {
            return response()->json(['data' => $jobs,'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'هذا العمل النشط غير موجود','status' => false]);
        }
    }

    public function get_client_active_jobs(Request $request)
    {
        $client_id = $request->client_id;
        if (!$client_id) {
            return response()->json(['message' => 'you should give me the id of the client in parameter(client_id)','status' => false],404);
        }
        $pagination = $request->pagination;
        if (!$pagination) {
            return response()->json(['message' => 'you should give me the pagination value in parameter(pagination)','status' => false],404);
        }
        $client = Client::find($client_id);
        if (!$client) {
            return response()->json(['message'=>'client not found','status'=>false,],404);
        }
        $jobs =CraftsmanJob::select("id","title","description","price","city","address","type_of_pricing","start_date","end_date","client_id","craftsman_id","created_at")->orderBy('id', 'desc')->where('client_id',$client_id)->paginate($pagination)->through(function($jb){
            $finished = CraftsmanJobFinished::where('active_job_id',$jb->id)->first();
            if ($finished) {
                $jb['is_finished'] = 'yes';
            }else {
                $jb['is_finished'] = 'no';
            }
            if ($jb->job_images()->get()) {
                $jb['images'] = $jb->job_images()->get(['image']);
            }else{
                $jb['images'] = [];
            }
            return $jb;
        });

        if ($jobs) {
            return response()->json(['data' => $jobs,'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'لا يوجد أعمال نشطة','status' => false]);
        }
    }

    public function get_client_active_job(Request $request)
    {
        $job_id = $request->job_id;
        if (!$job_id) {
            return response()->json(['message' => 'you should give me the id of the job in parameter(job_id)','status' => false],404);
        }
        $jobs =CraftsmanJob::select("id","title","description","price","city","address","type_of_pricing","start_date","end_date","client_id","craftsman_id","created_at",)->where('id',$job_id)->get()->map(function($jb){
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
            $finished = CraftsmanJobFinished::where('active_job_id',$jb->id)->first();
            if ($finished) {
                $jb['is_finished'] = 'yes';
            }else {
                $jb['is_finished'] = 'no';
            }
            if ($jb->job_images()->get()) {
                $jb['images'] = $jb->job_images()->get(['image']);
            }else{
                $jb['images'] = [];
            }
            $craftsman = Craftsman::where('id',$jb->craftsman_id)->get()->map(function($cr){
                $phones = Phone::select('phone')->where('craftsman_id',$cr->id)->where('type','contact')->get();
                if ($phones) {
                    $cr['phones'] = $phones;
                }else {
                    $cr['phones'] = null;
                }
                $whatsapp = Phone::select('phone')->where('craftsman_id',$cr->id)->where('type','whatsapp')->get();
                if ($whatsapp) {
                    $cr['whatsapp'] = $whatsapp;
                }else {
                    $cr['whatsapp'] = null;
                }
                return $cr;
            });
            if ($craftsman) {
                $jb['craftsman_data'] = $craftsman;
            }else {
                $jb['craftsman_data'] = null;
            }
            return $jb;
        });

        if ($jobs->count() > 0) {
            return response()->json(['data' => $jobs,'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'هذا العمل النشط غير موجود','status' => false]);
        }
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function add_job(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $job_offer_id = $request->job_offer_id;
        if (!$job_offer_id) {
            return response()->json(['message' => 'you should give me the id of the job offer in parameter(job_offer_id)','status' => false],404);
        }

        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message'=>'craftsman not found','status'=>false,],404);
        }

        $job_offer = JobsOffer::find($job_offer_id);
        if (!$job_offer) {
            return response()->json(['message'=>'the job offer not found','status'=>false,],404);
        }
        $notificationVar = $job_offer->title;
        $job_offer_reply = JobsOfferReply::where('job_offer_id' , $job_offer->id)->where('craftsman_id',$craftsman_id)->first();
        if (!$job_offer_reply) {
            return response()->json(['message'=>'this craftsman did not apply for this job','status'=>false,],404);
        }
        $ActiveJob = new CraftsmanJob();
        $ActiveJob->title = $job_offer->title;
        $ActiveJob->description = $job_offer->description;
        $ActiveJob->price = $job_offer_reply->offered_price;
        $ActiveJob->type_of_pricing = $job_offer_reply->type_of_pricing;
        $ActiveJob->city = $job_offer->city;
        $ActiveJob->address = $job_offer->address;
        $ActiveJob->phone = $job_offer->phone;
        $ActiveJob->start_date = $job_offer->start_date;
        $ActiveJob->end_date = $job_offer->end_date;
        $ActiveJob->status = 'unfinished';
        $ActiveJob->client_id = $job_offer->client_id;
        $ActiveJob->craftsman_id = $craftsman_id;
        $ActiveJob->save();
        if ($ActiveJob) {
            $job_offer_images = JobsOfferImage::where('job_offer_id' , $job_offer->id)->get();
            if ($job_offer_images) {
                foreach ($job_offer_images as $img) {
                    $active_job_image = new CraftsmanJobImage();
                    $url = storage_path('' . 'app/public/images/' . $img->image . '');
                    $NameAfterDelete = str_replace("jobs_offers/", "",$img->image);
                    $extension = pathinfo($NameAfterDelete, PATHINFO_EXTENSION);
                    $imageName = 'active_jobs/'.Str::random().'.'.$extension;
                    $newUrl = storage_path('' . 'app/public/images/' . $imageName . '');
                    File::move($url, $newUrl);
                    $active_job_image->image = $imageName;
                    $active_job_image->job_id = $ActiveJob->id;
                    $active_job_image->save();
                }
                foreach ($job_offer_images as $job_offer_image) {
                    if ($job_offer_image->image) {
                        $exist = Storage::disk('public')->exists('images/'. $job_offer_image->image);
                        if ($exist) {
                            $exist = Storage::disk('public')->delete('images/'. $job_offer_image->image);
                        }
                    }
                }
                JobsOfferImage::where('job_offer_id' , $job_offer->id)->delete();
            }
            JobsOfferReply::where('job_offer_id' , $job_offer->id)->delete();
            $job_offer->delete();
            $craftsman->status = 'busy';
            $craftsman->save();
            // send notification
            $title = 'تم الموافقة عليك';
            $body = 'تم قبولك في العرض الذي قدمت عليه('.$notificationVar.')';
            $sender = app(NotificationSender::class);
            $result = $sender->send($craftsman->id, $title, $body, 'craftsman');
            // if you want to know the error of sending notification
            // if ($result['status'] === 'success') {
            //     return response()->json(['message' => 'message sent successfully with notification', 'status' => true], 200);
            // } elseif ($result['status'] === 'partial') {
            //     return response()->json(['message' => 'message sent successfully but without some notifications', 'status' => true], 200);
            // } else {
            //     return response()->json(['message' => 'message sent successfully but without notification', 'status' => true], 200);
            // }
            if ($job_offer) {
                return response()->json([
                    'message' => 'تم إضافة العرض الى قائمة الأعمال النشطة',
                    'message2' => 'تم إزالة العرض من قائمة العروض',
                    'status' => true,
                    ],201);
            }
            else
            {
                return response()->json(['message' => 'لم نستطع إزالة العرض من قائمة العروض', 'status' => false],401);
            }
        }
        else
        {
            return response()->json([
                'message' => 'لم نستطع إضافة هذا العرض الى قائمة الأعمال النشطة',
                'status' => false,
            ],401);
        }
    }

    public function add_job_from_inspection(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $job_offer_id = $request->job_offer_id;
        if (!$job_offer_id) {
            return response()->json(['message' => 'you should give me the id of the job offer in parameter(job_offer_id)','status' => false],404);
        }

        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message'=>'craftsman not found','status'=>false,],404);
        }

        $job_offer = JobsOffer::find($job_offer_id);
        if (!$job_offer) {
            return response()->json(['message'=>'the job offer not found','status'=>false,],404);
        }
        $notificationVar = $job_offer->title;
        $job_offer_inspection = JobsOfferInspection::where('job_offer_id' , $job_offer->id)->where('craftsman_id',$craftsman_id)->first();
        if (!$job_offer_inspection) {
            return response()->json(['message'=>'this craftsman did not apply for this job','status'=>false,],404);
        }
        $ActiveJob = new CraftsmanJob();
        $ActiveJob->title = $job_offer->title;
        $ActiveJob->description = $job_offer->description;
        $ActiveJob->price = $job_offer_inspection->offered_price;
        $ActiveJob->type_of_pricing = $job_offer_inspection->type_of_pricing;
        $ActiveJob->city = $job_offer->city;
        $ActiveJob->address = $job_offer->address;
        $ActiveJob->phone = $job_offer->phone;
        $ActiveJob->start_date = $job_offer_inspection->start_date;
        $ActiveJob->end_date = $job_offer_inspection->end_date;
        $ActiveJob->status = 'unfinished';
        $ActiveJob->client_id = $job_offer->client_id;
        $ActiveJob->craftsman_id = $craftsman_id;
        $ActiveJob->save();
        if ($ActiveJob) {
            $job_offer_images = JobsOfferImage::where('job_offer_id' , $job_offer->id)->get();
            if ($job_offer_images) {
                foreach ($job_offer_images as $img) {
                    $active_job_image = new CraftsmanJobImage();
                    $url = storage_path('' . 'app/public/images/' . $img->image . '');
                    $NameAfterDelete = str_replace("jobs_offers/", "",$img->image);
                    $extension = pathinfo($NameAfterDelete, PATHINFO_EXTENSION);
                    $imageName = 'active_jobs/'.Str::random().'.'.$extension;
                    $newUrl = storage_path('' . 'app/public/images/' . $imageName . '');
                    File::move($url, $newUrl);
                    $active_job_image->image = $imageName;
                    $active_job_image->job_id = $ActiveJob->id;
                    $active_job_image->save();
                }
                foreach ($job_offer_images as $job_offer_image) {
                    if ($job_offer_image->image) {
                        $exist = Storage::disk('public')->exists('images/'. $job_offer_image->image);
                        if ($exist) {
                            $exist = Storage::disk('public')->delete('images/'. $job_offer_image->image);
                        }
                    }
                }
                JobsOfferImage::where('job_offer_id' , $job_offer->id)->delete();
            }
            JobsOfferinspection::where('job_offer_id' , $job_offer->id)->delete();
            $job_offer->delete();
            $craftsman->status = 'busy';
            $craftsman->save();
            // send notification
            $title = 'تم الموافقة على طلب معاينتك';
            $body = 'تم قبولك في العرض الذي قدمت طلب المعاينة عليه('.$notificationVar.')';
            $sender = app(NotificationSender::class);
            $result = $sender->send($craftsman->id, $title, $body, 'craftsman');
            // if you want to know the error of sending notification
            // if ($result['status'] === 'success') {
            //     return response()->json(['message' => 'message sent successfully with notification', 'status' => true], 200);
            // } elseif ($result['status'] === 'partial') {
            //     return response()->json(['message' => 'message sent successfully but without some notifications', 'status' => true], 200);
            // } else {
            //     return response()->json(['message' => 'message sent successfully but without notification', 'status' => true], 200);
            // }
            if ($job_offer) {
                return response()->json([
                    'message' => 'تم إضافة العرض الى قائمة الأعمال النشطة',
                    'message2' => 'تم إزالة العرض من قائمة العروض',
                    'status' => true,
                    ],201);
            }
            else
            {
                return response()->json(['message' => 'لم نستطع إزالة العرض من قائمة العروض', 'status' => false],401);
            }
        }
        else
        {
            return response()->json([
                'message' => 'لم نستطع إضافة هذا العرض الى قائمة الأعمال النشطة',
                'status' => false,
            ],401);
        }


    }

    public function finish_job(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $active_job_id = $request->active_job_id;
        if (!$active_job_id) {
            return response()->json(['message' => 'you should give me the id of the active job in parameter(active_job_id)','status' => false],404);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|numeric|between:1,5',
            'comment' => 'required',
        ],[
            'rating.required' => 'يجب أن تُرسل التقييم',
            'rating.numeric' => 'التقييم يجب أن يتكون من أرقام فقط',
            'rating.between:1,5' => 'التقييم يجب أن يكون بين ال 1 و ال 5',
            'comment.required' => 'يجب أن تُرسل تعليقك',
        ]);
        if ($validator->fails()) {
            return response()->json(['message'=>$validator->errors(),'status'=>false],401);
        }

        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message'=>'craftsman not found','status'=>false,],404);
        }

        $active_job = CraftsmanJob::select()->where('id',$active_job_id)->where('craftsman_id',$craftsman_id)->first();
        if (!$active_job) {
            return response()->json(['message'=>'active job not found or this craftsman does not have an active job','status'=>false,],404);
        }
        $notificationVar = $active_job->title;
        $notificationVar2 = $active_job->client_id;
        $CheckFinishActiveJob = CraftsmanJobFinished::select()->where('active_job_id',$active_job_id)->first();
        if ($CheckFinishActiveJob) {
            return response()->json(['message'=>'يوجد طلب لإنهاء هذا العمل بالفعل','status'=>false,],400);
        }
        $client_rating = new ClientsRating();
        $client_rating->rating = $request->rating;
        $client_rating->comment = $request->comment;
        $client_rating->craftsman_id = $craftsman_id;
        $client_rating->client_id = $active_job->client_id;
        $client_rating->save();
        if ($client_rating) {
            $FinishActiveJob = new CraftsmanJobFinished();
            $FinishActiveJob->CraftsmanStatus = 'finished';
            $FinishActiveJob->craftsman_id = $craftsman_id;
            $FinishActiveJob->active_job_id = $active_job_id;
            $FinishActiveJob->save();
            if ($FinishActiveJob) {
                // send notification
                $title = 'لقد انتهى الصنايعي من عمله';
                $body = 'تستطيع إنهاء العمل في قائمة الأعمال النشطة الخاصة بك واسمه: ('.$notificationVar.')';
                $sender = app(NotificationSender::class);
                $result = $sender->send($notificationVar2, $title, $body, 'client');
                // if you want to know the error of sending notification
                // if ($result['status'] === 'success') {
                //     return response()->json(['message' => 'message sent successfully with notification', 'status' => true], 200);
                // } elseif ($result['status'] === 'partial') {
                //     return response()->json(['message' => 'message sent successfully but without some notifications', 'status' => true], 200);
                // } else {
                //     return response()->json(['message' => 'message sent successfully but without notification', 'status' => true], 200);
                // }
                return response()->json(['message'=>'المهمة تمت بنجاح','status'=>true,],200);
            }
            else {
                return response()->json(['message'=>'لم نستطع إتمام المهمة','status'=>false,],400);
            }
        }

    }

    public function client_finish_job(Request $request)
    {
        $client_id = $request->client_id;
        if (!$client_id) {
            return response()->json(['message' => 'you should give me the id of the client in parameter(client_id)','status' => false],404);
        }
        $active_job_id = $request->active_job_id;
        if (!$active_job_id) {
            return response()->json(['message' => 'you should give me the id of the active job in parameter(active_job_id)','status' => false],404);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'nullable|array',
            'image.*' => 'mimes:png,jpg,jpeg|max:50120',
            'rating' => 'required|numeric|between:1,5',
            'comment' => 'required',
        ],[
            'image.array' => 'يجب أن تُرسل الصورة على هيئة مصفوفة',
            'image.*.mimes:png,jpg,jpeg' => 'الإمتداد يجب أن يكون png أو jpg أو jpeg',
            'image.*.max:50120' => 'يجب أن يكون حجم الصورة أقل من 50 ميجا',
            'rating.required' => 'يجب أن تُرسل التقييم',
            'rating.numeric' => 'التقييم يجب أن يتكون من أرقام فقط',
            'rating.between:1,5' => 'التقييم يجب أن يكون بين ال 1 و ال 5',
            'comment.required' => 'يجب أن تُرسل تعليقك',
        ]);
        if ($validator->fails()) {
            return response()->json([$validator->errors(),'status'=>false],401);
        }

        $client = Client::find($client_id);
        if (!$client) {
            return response()->json(['message'=>'client not found','status'=>false,],404);
        }

        $active_job = CraftsmanJob::select()->where('id',$active_job_id)->where('client_id',$client_id)->first();
        if (!$active_job) {
            return response()->json(['message'=>'active job not found or this client does not have an active job','status'=>false,],404);
        }
        $notificationVar = $active_job->craftsman_id;
        $FinishActiveJob = CraftsmanJobFinished::select()->where('active_job_id',$active_job_id)->first();
        if (!$FinishActiveJob) {
            return response()->json(['message'=>'the craftsman does not finish the job yet','status'=>false,],400);
        }
        $status = 'finished';
        $FinishActiveJob->fill(['ClientStatus'=>$status,'client_id'=>$client_id])->update();

        if ($FinishActiveJob->CraftsmanStatus == 'finished' AND $FinishActiveJob->ClientStatus == 'finished') {
            $doneJob = new CraftsmanDoneJobs();
            $doneJob->title = $active_job->title;
            $doneJob->description = $active_job->description;
            $doneJob->price = $active_job->price;
            $doneJob->city = $active_job->city;
            $doneJob->address = $active_job->address;
            $doneJob->phone = $active_job->phone;
            $doneJob->status = 'finished';
            $doneJob->craftsman_id = $active_job->craftsman_id;
            $doneJob->client_id = $active_job->client_id;
            CraftsmanJobFinished::where('active_job_id' , $active_job->id)->delete();
            $active_jobs_images = CraftsmanJobImage::where('job_id' , $active_job->id)->get();
            if ($active_jobs_images) {
                foreach ($active_jobs_images as $active_job_image) {
                    if ($active_job_image->image) {
                        $exist = Storage::disk('public')->exists('images/'. $active_job_image->image);
                        if ($exist) {
                            $exist = Storage::disk('public')->delete('images/'. $active_job_image->image);
                        }
                    }
                }
                CraftsmanJobImage::where('job_id' , $active_job->id)->delete();
            }
            $active_job->delete();
            if ($active_job) {
                $doneJob->save();
                if ($request->hasFile('image')) {
                    foreach ($request->file('image') as $img) {
                        $done_job_image = new CraftsmanDoneJobsimage();
                        $imageName = 'done_jobs/'.Str::random().'.'.$img->getClientOriginalExtension();
                        Storage::disk('public')->putFileAs('images/', $img, $imageName);
                        $done_job_image->image = $imageName;
                        $done_job_image->craftsmanDoneJob_id = $doneJob->id;
                        $done_job_image->save();
                    }
                }
                $done_job_rating = new CraftsmanDoneJobsRating();
                $done_job_rating->rating = $request->rating;
                $done_job_rating->comment = $request->comment;
                $done_job_rating->craftsmanDoneJob_id = $doneJob->id;
                $done_job_rating->client_id = $client_id;
                $done_job_rating->save();

                $client_rating = ClientsRating::where('craftsman_id',$doneJob->craftsman_id)->where('client_id',$doneJob->client_id)->where('done_job_id',null)->first();
                $client_rating->done_job_id = $doneJob->id;
                $client_rating->save();

                $craftsman = Craftsman::where('id',$doneJob->craftsman_id)->first();
                $craftsman_active_jobs = CraftsmanJob::where('craftsman_id',$doneJob->craftsman_id)->get();
                if ($craftsman_active_jobs->count() == 0) {
                    $craftsman->status = 'free';
                    $craftsman->save();
                }
                if ($doneJob) {
                    // send notification
                    $title = 'لقد وافق العميل على إنهاء العمل';
                    $body = 'لقد وافق العميل على إنهاء العمل وقام بتقييمك وستجد هذا العمل في قائمة الأعمال المنتهية';
                    $sender = app(NotificationSender::class);
                    $result = $sender->send($notificationVar, $title, $body, 'craftsman');
                    // if you want to know the error of sending notification
                    // if ($result['status'] === 'success') {
                    //     return response()->json(['message' => 'message sent successfully with notification', 'status' => true], 200);
                    // } elseif ($result['status'] === 'partial') {
                    //     return response()->json(['message' => 'message sent successfully but without some notifications', 'status' => true], 200);
                    // } else {
                    //     return response()->json(['message' => 'message sent successfully but without notification', 'status' => true], 200);
                    // }
                    return response()->json([
                        'message' => 'تم إضافة هذا العمل الى قائمة الأعمال المنتهية',
                        'message2' => 'active job deleted successfully',
                        'status' => true,
                        ],200);
                }
                else
                {
                    return response()->json([
                        'message' => 'can not store this done job',
                        'status' => false,
                    ],401);
                }
            }
            else
            {
                return response()->json(['message' => 'can not delete this active job', 'status' => false],401);
            }
        }
    }


    public function client_cancel_job(Request $request)
    {
        $client_id = $request->client_id;
        if (!$client_id) {
            return response()->json(['message' => 'you should give me the id of the client in parameter(client_id)','status' => false],404);
        }
        $active_job_id = $request->active_job_id;
        if (!$active_job_id) {
            return response()->json(['message' => 'you should give me the id of the active job in parameter(active_job_id)','status' => false],404);
        }

        $client = Client::find($client_id);
        if (!$client) {
            return response()->json(['message'=>'client not found','status'=>false,],404);
        }

        $active_job = CraftsmanJob::select()->where('id',$active_job_id)->where('client_id',$client_id)->first();
        if (!$active_job) {
            return response()->json(['message'=>'active job not found or this client does not have this active job','status'=>false,],404);
        }
        $notificationVar = $active_job->craftsman_id;
        $CancelActiveJob = CraftsmanJobCancel::select()->where('active_job_id',$active_job_id)->first();
        if ($CancelActiveJob) {
            return response()->json(['message'=>'craftsman already requested cancellation for this active_job','status'=>false,],400);
        }
        $CancelActiveJob = ClientJobCancel::select()->where('active_job_id',$active_job_id)->where('client_id',$client_id)->first();
        if ($CancelActiveJob) {
            return response()->json(['message'=>'this client already requested cancellation for this active_job','status'=>false,],400);
        }
        $status = 'pending';
        $CancelActiveJob = new ClientJobCancel();
        $CancelActiveJob->status = $status;
        $CancelActiveJob->client_id = $client_id;
        $CancelActiveJob->active_job_id = $active_job_id;
        $CancelActiveJob->save();
        if ($CancelActiveJob) {
            return response()->json(['message' => 'request is sent successfully', 'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'can not send this request', 'status' => false],400);
        }
    }


    public function get_cancellation_requests_for_client(Request $request)
    {
        // Extract client_id from the request
        $client_id = $request->client_id;
        if (!$client_id) {
            return response()->json([
                'message' => 'You should provide the ID of the client in the parameter (client_id)',
                'status' => false
            ], 404);
        }

        // Extract pagination value from the request
        $pagination = $request->pagination;
        if (!$pagination) {
            return response()->json([
                'message' => 'You should provide the pagination value in the parameter (pagination)',
                'status' => false
            ], 404);
        }

        // Check if the client exists
        $client = Client::find($client_id);
        if (!$client) {
            return response()->json([
                'message' => 'Client not found',
                'status' => false
            ], 404);
        }

        // Query to fetch canceled jobs with pending cancellation requests
        $canceled_jobs = CraftsmanJobCancel::where('status', 'pending') // Filter by pending status
            ->whereHas('active_job', function ($query) use ($client_id) {
                $query->where('client_id', $client_id); // Ensure the job belongs to the given client
            })
            ->with(['active_job.craftsman', 'active_job.job_images']) // Eager load related data
            ->paginate($pagination); // Paginate the results

        // Return the paginated results as JSON
        return response()->json([
            'data' => $canceled_jobs,
            'status' => true,
        ], 200);
    }


    public function craftsman_cancel_job(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $active_job_id = $request->active_job_id;
        if (!$active_job_id) {
            return response()->json(['message' => 'you should give me the id of the active job in parameter(active_job_id)','status' => false],404);
        }

        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message'=>'craftsman not found','status'=>false,],404);
        }

        $active_job = CraftsmanJob::select()->where('id',$active_job_id)->where('craftsman_id',$craftsman_id)->first();
        if (!$active_job) {
            return response()->json(['message'=>'active job not found or this craftsman does not have this active job','status'=>false,],404);
        }
        $notificationVar = $active_job->craftsman_id;
        $CancelActiveJob = ClientJobCancel::select()->where('active_job_id',$active_job_id)->first();
        if ($CancelActiveJob) {
            return response()->json(['message'=>'client already requested cancellation for this active_job','status'=>false,],400);
        }
        $CancelActiveJob = CraftsmanJobCancel::select()->where('active_job_id',$active_job_id)->where('craftsman_id',$craftsman_id)->first();
        if ($CancelActiveJob) {
            return response()->json(['message'=>'this craftsman already requested cancellation for this active_job','status'=>false,],400);
        }
        $status = 'pending';
        $CancelActiveJob = new CraftsmanJobCancel();
        $CancelActiveJob->status = $status;
        $CancelActiveJob->craftsman_id = $craftsman_id;
        $CancelActiveJob->active_job_id = $active_job_id;
        $CancelActiveJob->save();
        if ($CancelActiveJob) {
            return response()->json(['message' => 'request is sent successfully', 'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'can not send this request', 'status' => false],400);
        }
    }


    public function get_cancellation_requests_for_craftsman(Request $request)
    {
        // Extract craftsman_id from the request
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json([
                'message' => 'You should provide the ID of the craftsman in the parameter (craftsman_id)',
                'status' => false
            ], 404);
        }

        // Extract pagination value from the request
        $pagination = $request->pagination;
        if (!$pagination) {
            return response()->json([
                'message' => 'You should provide the pagination value in the parameter (pagination)',
                'status' => false
            ], 404);
        }

        // Check if the craftsman exists
        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json([
                'message' => 'Craftsman not found',
                'status' => false
            ], 404);
        }

        // Query to fetch canceled jobs with pending cancellation requests
        $canceled_jobs = ClientJobCancel::where('status', 'pending') // Filter by pending status
            ->whereHas('active_job', function ($query) use ($craftsman_id) {
                $query->where('craftsman_id', $craftsman_id); // Ensure the job belongs to the given craftsman
            })
            ->with(['active_job.client', 'active_job.job_images']) // Eager load related data
            ->paginate($pagination); // Paginate the results

        // Return the paginated results as JSON
        return response()->json([
            'data' => $canceled_jobs,
            'status' => true,
        ], 200);
    }


}

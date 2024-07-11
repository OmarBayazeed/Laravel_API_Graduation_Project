<?php

namespace App\Http\Controllers;

use App\Http\Resources\paginationResource;
use App\Models\City;
use App\Models\Craft;
use App\Models\Craftsman;
use App\Models\CraftsmanDoneJobs;
use App\Models\CraftsmanDoneJobsRating;
use App\Models\CraftsmanJob;
use App\Models\SearchImages;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SearchController extends Controller
{

    public function search(Request $request)
    {
        $craft = $request->craft;
        if (!$craft) {
            return response()->json(['message' => 'you should give me the craft parameter(craft)','status' => false],404);
        }
        $sort = $request->sort;
        if (!$sort) {
            $sort = 'default';
        }
        if ($request->city) {
            foreach ($request->city as $city) {
                $cities = City::select('city')->where('city' , $city)->get();
            }
            $citiesCount = $cities->count();
            if ($citiesCount == 0) {
                return response()->json(['message' => 'هذه المدن غير موجودة','status' => false]);
            }
            $cities = $request->city;
        }else {
            $cities = false;
        }
        if ($request->ratingGTE) {
            $_SESSION['ratingGTE'] = $request->ratingGTE;
            $ratingGTE = $request->ratingGTE;
        }else {
            $ratingGTE = false;
        }
        if ($request->dateGTE) {
            $dateGTE = $request->dateGTE;
        }else {
            $dateGTE = false;
        }
        if ($request->done_jobs) {
            $_SESSION['done_jobs_num'] = $request->done_jobs;
            $done_jobs = $request->done_jobs;
        }else {
            $done_jobs = false;
        }
        $pagination = $request->pagination;
        if (!$pagination) {
            return response()->json(['message' => 'you should give me the pagination parameter(pagination)','status' => false],404);
        }

        if ($sort=='default') {
            if ($cities AND !$ratingGTE AND !$dateGTE AND !$done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->paginate($pagination)->through(function($ct){
                    if ($ct->cities()->get()) {
                        $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['done_jobs_num'] = $done_jobs->count();
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
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
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                    }
                    return $ct;
                });
            }
            elseif (!$cities AND $ratingGTE AND !$dateGTE AND !$done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE']) {
                        $ct['done_jobs_num'] = $done_jobs->count();
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        if ($ct != null) {
                            return $ct;
                        }
                    }
                });
            }
            elseif (!$cities AND !$ratingGTE AND $dateGTE AND !$done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    $ct['done_jobs_num'] = $done_jobs_num;
                    $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                    $ct['cities'] = $ct->cities()->get('city') or [];
                    $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                    $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                    $Ratings =false;
                    foreach ($done_jobs as $done) {
                        $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                    }
                    if ($Ratings) {
                        foreach ($Ratings as $rating) {
                            $totalRates = array_sum(array_map("count", [$rating]));
                            for ($j=0; $j < $totalRates; $j++) {
                                $ratings_values[] = $rating[$j]['rating'];
                            }
                        }
                        if (isset($ratings_values)) {
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
                    $ct['average_rating'] = $avgRating;
                    $ct['number_of_ratings'] = $ratingValuesCount;
                    return $ct;
                });
            }
            elseif (!$cities AND !$ratingGTE AND !$dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    if ($done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
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
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif ($cities AND $ratingGTE AND !$dateGTE AND !$done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE']) {
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['done_jobs_num'] = $done_jobs->count();
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif ($cities AND !$ratingGTE AND $dateGTE AND !$done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    $ct['done_jobs_num'] = $done_jobs_num;
                    $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                    $ct['cities'] = $ct->cities()->get('city') or [];
                    $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                    $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                    $Ratings =false;
                    foreach ($done_jobs as $done) {
                        $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                    }
                    if ($Ratings) {
                        foreach ($Ratings as $rating) {
                            $totalRates = array_sum(array_map("count", [$rating]));
                            for ($j=0; $j < $totalRates; $j++) {
                                $ratings_values[] = $rating[$j]['rating'];
                            }
                        }
                        if (isset($ratings_values)) {
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
                    $ct['average_rating'] = $avgRating;
                    $ct['number_of_ratings'] = $ratingValuesCount;
                    return $ct;
                });
            }
            elseif ($cities AND !$ratingGTE AND !$dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    if ($done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
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
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif (!$cities AND $ratingGTE AND $dateGTE AND !$done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE']) {
                        $ct['done_jobs_num'] = $done_jobs->count();
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif (!$cities AND $ratingGTE AND !$dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE'] AND $done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif (!$cities AND !$ratingGTE AND $dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    if ($done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
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
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif ($cities AND $ratingGTE AND $dateGTE AND !$done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE']) {
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['done_jobs_num'] = $done_jobs->count();
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif ($cities AND $ratingGTE AND !$dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE'] AND $done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif ($cities AND !$ratingGTE AND $dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    if ($done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
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
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif (!$cities AND $ratingGTE AND $dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE'] AND $done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif ($cities AND $ratingGTE AND $dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE'] AND $done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }else{
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    $ct['done_jobs_num'] = $done_jobs_num;
                    $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                    $ct['cities'] = $ct->cities()->get('city') or [];
                    $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                    $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                    $Ratings =false;
                    foreach ($done_jobs as $done) {
                        $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                    }
                    if ($Ratings) {
                        foreach ($Ratings as $rating) {
                            $totalRates = array_sum(array_map("count", [$rating]));
                            for ($j=0; $j < $totalRates; $j++) {
                                $ratings_values[] = $rating[$j]['rating'];
                            }
                        }
                        if (isset($ratings_values)) {
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
                    $ct['average_rating'] = $avgRating;
                    $ct['number_of_ratings'] = $ratingValuesCount;
                    return $ct;
                });
            }
            // Filter out null values
            $filteredCraftsmen = array_filter($craftsman->items(), function ($item) {
                return !is_null($item);
            });
            $totalCount = count($filteredCraftsmen);
            // Create a new paginator instance with filtered data
            $filteredPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $filteredCraftsmen,
                $totalCount,
                $craftsman->perPage(),
                $craftsman->currentPage(),
            );
            if ($filteredPaginator->count() == 0) {
                return response()->json(['message'=>'لا يوجد صنايعية','status'=>false]);
            }
            elseif ($filteredPaginator) {
                return response()->json(['data' => $filteredPaginator,'status'=>true],200);
            }
        }
        else if ($sort=='rating') {
            if ($cities AND !$ratingGTE AND !$dateGTE AND !$done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->paginate($pagination)->through(function($ct){
                    if ($ct->cities()->get()) {
                        $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['done_jobs_num'] = $done_jobs->count();
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
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
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                    }
                    return $ct;
                });
            }
            elseif (!$cities AND $ratingGTE AND !$dateGTE AND !$done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE']) {
                        $ct['done_jobs_num'] = $done_jobs->count();
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
                // $users = User::whereHas('ratings', function ($query) {
                //     $query->selectRaw('AVG(star) AS avg_rating')->havingRaw('AVG(star) >= ?', [$filters['ratings']]);
                // })->get();
            }
            elseif (!$cities AND !$ratingGTE AND $dateGTE AND !$done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    $ct['done_jobs_num'] = $done_jobs_num;
                    $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                    $ct['cities'] = $ct->cities()->get('city') or [];
                    $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                    $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                    $Ratings =false;
                    foreach ($done_jobs as $done) {
                        $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                    }
                    if ($Ratings) {
                        foreach ($Ratings as $rating) {
                            $totalRates = array_sum(array_map("count", [$rating]));
                            for ($j=0; $j < $totalRates; $j++) {
                                $ratings_values[] = $rating[$j]['rating'];
                            }
                        }
                        if (isset($ratings_values)) {
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
                    $ct['average_rating'] = $avgRating;
                    $ct['number_of_ratings'] = $ratingValuesCount;
                    return $ct;
                });
            }
            elseif (!$cities AND !$ratingGTE AND !$dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    if ($done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
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
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif ($cities AND $ratingGTE AND !$dateGTE AND !$done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE']) {
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['done_jobs_num'] = $done_jobs->count();
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif ($cities AND !$ratingGTE AND $dateGTE AND !$done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    $ct['done_jobs_num'] = $done_jobs_num;
                    $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                    $ct['cities'] = $ct->cities()->get('city') or [];
                    $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                    $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                    $Ratings =false;
                    foreach ($done_jobs as $done) {
                        $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                    }
                    if ($Ratings) {
                        foreach ($Ratings as $rating) {
                            $totalRates = array_sum(array_map("count", [$rating]));
                            for ($j=0; $j < $totalRates; $j++) {
                                $ratings_values[] = $rating[$j]['rating'];
                            }
                        }
                        if (isset($ratings_values)) {
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
                    $ct['average_rating'] = $avgRating;
                    $ct['number_of_ratings'] = $ratingValuesCount;
                    return $ct;
                });
            }
            elseif ($cities AND !$ratingGTE AND !$dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    if ($done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
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
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif (!$cities AND $ratingGTE AND $dateGTE AND !$done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE']) {
                        $ct['done_jobs_num'] = $done_jobs->count();
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif (!$cities AND $ratingGTE AND !$dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE'] AND $done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif (!$cities AND !$ratingGTE AND $dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    if ($done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
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
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif ($cities AND $ratingGTE AND $dateGTE AND !$done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE']) {
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['done_jobs_num'] = $done_jobs->count();
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif ($cities AND $ratingGTE AND !$dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE'] AND $done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif ($cities AND !$ratingGTE AND $dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    if ($done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
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
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif (!$cities AND $ratingGTE AND $dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE'] AND $done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif ($cities AND $ratingGTE AND $dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE'] AND $done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }else{
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    $ct['done_jobs_num'] = $done_jobs_num;
                    $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                    $ct['cities'] = $ct->cities()->get('city') or [];
                    $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                    $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                    $Ratings =false;
                    foreach ($done_jobs as $done) {
                        $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                    }
                    if ($Ratings) {
                        foreach ($Ratings as $rating) {
                            $totalRates = array_sum(array_map("count", [$rating]));
                            for ($j=0; $j < $totalRates; $j++) {
                                $ratings_values[] = $rating[$j]['rating'];
                            }
                        }
                        if (isset($ratings_values)) {
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
                    $ct['average_rating'] = $avgRating;
                    $ct['number_of_ratings'] = $ratingValuesCount;
                    return $ct;
                });
            }
            // Filter out null values
            $filteredCraftsmen = array_filter($craftsman->items(), function ($item) {
                return !is_null($item);
            });
            $totalCount = count($filteredCraftsmen);
            // Create a new paginator instance with filtered data
            $filteredPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $filteredCraftsmen,
                $totalCount,
                $craftsman->perPage(),
                $craftsman->currentPage(),
            );

            // Sort the craftsmen by average_rating in ascending order
            $sortedCraftsmen = collect($filteredPaginator->items())->sortBy('average_rating')->reverse()->values()->toArray();

            // Create a new paginator instance with sorted data
            $sortedPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $sortedCraftsmen,
                $totalCount,
                $craftsman->perPage(),
                $craftsman->currentPage(),
            );
            if ($sortedPaginator->count() == 0) {
                return response()->json(['message'=>'لا يوجد صنايعية','status'=>false]);
            }
            elseif ($sortedPaginator) {
                return response()->json(['data' => $sortedPaginator,'status'=>true],200);
            }
        }
        else if ($sort=='done_jobs') {
            if ($cities AND !$ratingGTE AND !$dateGTE AND !$done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->paginate($pagination)->through(function($ct){
                    if ($ct->cities()->get()) {
                        $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['done_jobs_num'] = $done_jobs->count();
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
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
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                    }
                    return $ct;
                });
            }
            elseif (!$cities AND $ratingGTE AND !$dateGTE AND !$done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE']) {
                        $ct['done_jobs_num'] = $done_jobs->count();
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
                // $users = User::whereHas('ratings', function ($query) {
                //     $query->selectRaw('AVG(star) AS avg_rating')->havingRaw('AVG(star) >= ?', [$filters['ratings']]);
                // })->get();
            }
            elseif (!$cities AND !$ratingGTE AND $dateGTE AND !$done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    $ct['done_jobs_num'] = $done_jobs_num;
                    $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                    $ct['cities'] = $ct->cities()->get('city') or [];
                    $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                    $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                    $Ratings =false;
                    foreach ($done_jobs as $done) {
                        $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                    }
                    if ($Ratings) {
                        foreach ($Ratings as $rating) {
                            $totalRates = array_sum(array_map("count", [$rating]));
                            for ($j=0; $j < $totalRates; $j++) {
                                $ratings_values[] = $rating[$j]['rating'];
                            }
                        }
                        if (isset($ratings_values)) {
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
                    $ct['average_rating'] = $avgRating;
                    $ct['number_of_ratings'] = $ratingValuesCount;
                    return $ct;
                });
            }
            elseif (!$cities AND !$ratingGTE AND !$dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    if ($done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
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
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif ($cities AND $ratingGTE AND !$dateGTE AND !$done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE']) {
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['done_jobs_num'] = $done_jobs->count();
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif ($cities AND !$ratingGTE AND $dateGTE AND !$done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    $ct['done_jobs_num'] = $done_jobs_num;
                    $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                    $ct['cities'] = $ct->cities()->get('city') or [];
                    $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                    $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                    $Ratings =false;
                    foreach ($done_jobs as $done) {
                        $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                    }
                    if ($Ratings) {
                        foreach ($Ratings as $rating) {
                            $totalRates = array_sum(array_map("count", [$rating]));
                            for ($j=0; $j < $totalRates; $j++) {
                                $ratings_values[] = $rating[$j]['rating'];
                            }
                        }
                        if (isset($ratings_values)) {
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
                    $ct['average_rating'] = $avgRating;
                    $ct['number_of_ratings'] = $ratingValuesCount;
                    return $ct;
                });
            }
            elseif ($cities AND !$ratingGTE AND !$dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    if ($done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
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
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif (!$cities AND $ratingGTE AND $dateGTE AND !$done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE']) {
                        $ct['done_jobs_num'] = $done_jobs->count();
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif (!$cities AND $ratingGTE AND !$dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE'] AND $done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif (!$cities AND !$ratingGTE AND $dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    if ($done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
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
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif ($cities AND $ratingGTE AND $dateGTE AND !$done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE']) {
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['done_jobs_num'] = $done_jobs->count();
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif ($cities AND $ratingGTE AND !$dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE'] AND $done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif ($cities AND !$ratingGTE AND $dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    if ($done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
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
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif (!$cities AND $ratingGTE AND $dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE'] AND $done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }
            elseif ($cities AND $ratingGTE AND $dateGTE AND $done_jobs) {
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                foreach ($cities as $city) {
                    $cityOwnerId = City::select('craftsman_id')->where('city' , $city)->get();
                    $totalId = array_sum(array_map("count", [$cityOwnerId]));
                    for ($j=0; $j < $totalId; $j++) {
                        $id_values[] = $cityOwnerId[$j]['craftsman_id'];
                    }
                }
                // Calculate the date x years ago
                $YearsAgo = Carbon::now()->subYears($dateGTE);
                // Retrieve users created in and before the calculated date
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->whereIn("id" , $id_values)->whereYear('created_at', '<=', $YearsAgo->year)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    // ratings
                    if ($done_jobs) {
                        $Ratings =false;
                        foreach ($done_jobs as $done) {
                            $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                        }
                        if ($Ratings) {
                            foreach ($Ratings as $rating) {
                                $totalRates = array_sum(array_map("count", [$rating]));
                                for ($j=0; $j < $totalRates; $j++) {
                                    $ratings_values[] = $rating[$j]['rating'];
                                }
                            }
                            if (isset($ratings_values)) {
                                $ratingValuesSum = array_sum($ratings_values);
                                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                                if (!$avgRating) {
                                    $avgRating = false;
                                    $ratingValuesCount = false;
                                }
                            }else {
                                $avgRating = false;
                                $ratingValuesCount = false;
                            }
                        }else {
                            $avgRating = false;
                            $ratingValuesCount = false;
                        }
                    }else {
                        $avgRating = false;
                        $ratingValuesCount = false;
                    }
                    if ($avgRating AND $avgRating >= $_SESSION['ratingGTE'] AND $done_jobs AND $done_jobs_num >= $_SESSION['done_jobs_num']) {
                        $ct['done_jobs_num'] = $done_jobs_num;
                        $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                        $ct['cities'] = $ct->cities()->get('city') or [];
                        $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                        $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                        $ct['average_rating'] = $avgRating;
                        $ct['number_of_ratings'] = $ratingValuesCount;
                        return $ct;
                    }
                });
            }else{
                $craftId[] = Craft::select('id')->where('name','LIKE', '%' . $craft . '%')->get();
                foreach ($craftId as $id) {
                    $totalCraftId = array_sum(array_map("count", [$craftId]));
                for ($j=0; $j < $totalCraftId; $j++) {
                    $craftId_values[] = $id[$j]['id'];
                }
                }
                $craftsman =  Craftsman::orderBy('created_at','ASC')->whereIn("craft_id" , $craftId_values)->paginate($pagination)->through(function($ct){
                    $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $ct->id)->where('status','finished')->get();
                    $done_jobs_num = $done_jobs->count();
                    $ct['done_jobs_num'] = $done_jobs_num;
                    $ct['active_jobs_num'] = CraftsmanJob::select('id')->where('craftsman_id' , $ct->id)->get()->count();
                    $ct['cities'] = $ct->cities()->get('city') or [];
                    $ct['craft'] = Craft::select('name')->where('id' , $ct->craft_id)->first() or [];
                    $ct['search_images'] = SearchImages::select('id','image')->where('craftsman_id' , $ct->id)->get() or [];
                    $Ratings =false;
                    foreach ($done_jobs as $done) {
                        $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                    }
                    if ($Ratings) {
                        foreach ($Ratings as $rating) {
                            $totalRates = array_sum(array_map("count", [$rating]));
                            for ($j=0; $j < $totalRates; $j++) {
                                $ratings_values[] = $rating[$j]['rating'];
                            }
                        }
                        if (isset($ratings_values)) {
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
                    $ct['average_rating'] = $avgRating;
                    $ct['number_of_ratings'] = $ratingValuesCount;
                    return $ct;
                });
            }
            // Filter out null values
            $filteredCraftsmen = array_filter($craftsman->items(), function ($item) {
                return !is_null($item);
            });
            $totalCount = count($filteredCraftsmen);
            // Create a new paginator instance with filtered data
            $filteredPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $filteredCraftsmen,
                $totalCount,
                $craftsman->perPage(),
                $craftsman->currentPage(),
            );

            // Sort the craftsmen by done_jobs_num in ascending order
            $sortedCraftsmen = collect($filteredPaginator->items())->sortBy('done_jobs_num')->reverse()->values()->toArray();

            // Create a new paginator instance with sorted data
            $sortedPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $sortedCraftsmen,
                $totalCount,
                $craftsman->perPage(),
                $craftsman->currentPage(),
            );
            if ($sortedPaginator->count() == 0) {
                return response()->json(['message'=>'لا يوجد صنايعية','status'=>false]);
            }
            elseif ($sortedPaginator) {
                return response()->json(['data' => $sortedPaginator,'status'=>true],200);
            }
        }

    }

}

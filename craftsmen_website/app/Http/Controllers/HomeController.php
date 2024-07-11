<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Craft;
use App\Models\Craftsman;
use App\Models\CraftsmanDoneJobs;
use App\Models\CraftsmanDoneJobsRating;
use App\Models\CraftsmanJob;
use App\Models\SearchImages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function get_homepage_data()
    {
        $craftsmenCount = Craftsman::all()->count();
        $clientsCount = Client::all()->count();
        $done_jobs = CraftsmanDoneJobs::where('status','finished')->get();
        $doneJobsCount = $done_jobs->count();
        $crafts = Craft::select('id','name','image')->get()->map(function($cf){
            if ($cf->craftsmen()) {
                $cf['num_of_craftsmen'] = $cf->craftsmen()->count();
            }else {
                $cf['num_of_craftsmen'] = 0;
            }
            return $cf;
        });
        $sortedCrafts = collect($crafts)->sortBy('num_of_craftsmen')->reverse()->values()->toArray();
        $totalProfit = $done_jobs->sum('price');
        $monthlyProfits = DB::table('craftsman_done_jobs')->where('status','finished')->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(price) as total_profit')->groupBy('year', 'month', 'craftsman_id')->get();
        // Calculate the average profit per month across all users
        $averageProfit = $monthlyProfits->avg('total_profit');
        $topCraftsmen = Craftsman::get()->filter(function($ct){
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
            if ($avgRating) {
                $ct['done_jobs_num'] = $done_jobs->count();
                $ct['average_rating'] = $avgRating;
                $ct['number_of_ratings'] = $ratingValuesCount;
                $ct['craft_name'] = $ct->craft()->get('name');
                return $ct;
            }
        });
        $sortedTopCraftsmen = collect($topCraftsmen)->sortBy('average_rating')->reverse()->values()->toArray();
        $top5Craftsmen = array_slice($sortedTopCraftsmen, 0, 5);

        return response()->json([
            'craftsmen_num' => $craftsmenCount,
            'client_num' => $clientsCount,
            'doneJobs_num' => $doneJobsCount,
            'crafts' => $sortedCrafts,
            'total_profit' => $totalProfit,
            'average_profit' => round($averageProfit),
            'top_craftsmen' => $top5Craftsmen,
        ],200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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

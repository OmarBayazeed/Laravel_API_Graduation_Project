<?php

use App\Http\Controllers\ChartController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\client\ClientAuthController;
use App\Http\Controllers\CraftsController;
use App\Http\Controllers\craftsman\CraftsmanAuthController;
use App\Http\Controllers\craftsman\CraftsmanDoneJobsController;
use App\Http\Controllers\craftsman\CraftsmanJobsController;
use App\Http\Controllers\craftsman\CraftsmanPhoneAndCityController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobOfferController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SearchImagesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });



Route::group([
    'middleware' => ['assign.guard:craftsman','jwt.auth'],
    'prefix' => 'craftsman'
], function ($router) {
    Route::post('/login', [CraftsmanAuthController::class, 'login'])->withoutMiddleware(['assign.guard:craftsman','jwt.auth']);
    Route::post('/register', [CraftsmanAuthController::class, 'register'])->withoutMiddleware(['assign.guard:craftsman','jwt.auth']);
    Route::post('/google', [CraftsmanAuthController::class, 'google'])->withoutMiddleware(['assign.guard:craftsman','jwt.auth']);
    Route::post('/facebook', [CraftsmanAuthController::class, 'facebook'])->withoutMiddleware(['assign.guard:craftsman','jwt.auth']);
    Route::post('/forgot_password', [ForgotPasswordController::class, 'craftsman_send_email'])->withoutMiddleware(['assign.guard:craftsman','jwt.auth']);
    Route::post('/reset_password', [ForgotPasswordController::class, 'craftsman_reset_password'])->withoutMiddleware(['assign.guard:craftsman','jwt.auth']);
    Route::post('/logout', [CraftsmanAuthController::class, 'logout']);
    Route::post('/refresh', [CraftsmanAuthController::class, 'refresh']);
    Route::get('/user-profile', [CraftsmanAuthController::class, 'userProfile']);
    Route::post('/complete-info', [CraftsmanAuthController::class, 'completeInfo']);
    Route::post('/update-info', [CraftsmanAuthController::class, 'updateInfo']);
    // Route::post('/add_city', [CraftsmanPhoneAndCityController::class, 'store_city']);
    Route::post('/update_city', [CraftsmanPhoneAndCityController::class, 'update_city']);
    // Route::post('/add_phone', [CraftsmanPhoneAndCityController::class, 'store_phone']);
    Route::post('/update_phone', [CraftsmanPhoneAndCityController::class, 'update_phone']);
    Route::post('/get_user', [CraftsmanAuthController::class, 'get_user'])->withoutMiddleware(['assign.guard:craftsman','jwt.auth']);
    Route::post('/add_job_offer_reply', [JobOfferController::class, 'add_reply']);
    Route::post('/add_job_offer_inspection', [JobOfferController::class, 'add_inspection']);
    Route::post('/update_job_offer_reply', [JobOfferController::class, 'update_reply']);
    Route::post('/update_job_offer_inspection', [JobOfferController::class, 'update_inspection']);
    Route::post('/delete_job_offer_reply', [JobOfferController::class, 'delete_reply']);
    Route::post('/delete_job_offer_inspection', [JobOfferController::class, 'delete_inspection']);
    Route::post('/add_job', [CraftsmanJobsController::class, 'add_job']);
    Route::post('/add_job_from_inspection', [CraftsmanJobsController::class, 'add_job_from_inspection']);
    Route::post('/finish_job', [CraftsmanJobsController::class, 'finish_job']);
    Route::post('/get_jobs', [CraftsmanJobsController::class, 'get_jobs']);
    Route::post('/get_job', [CraftsmanJobsController::class, 'get_job']);
    Route::post('/get_pending_jobs', [JobOfferController::class, 'get_pending_jobs']);
    Route::post('/get_one_pending_job', [JobOfferController::class, 'get_one_pending_jobs']);
    Route::post('/get_done_jobs', [CraftsmanDoneJobsController::class, 'get_done_jobs']);
    Route::post('/get_done_job', [CraftsmanDoneJobsController::class, 'get_done_job']);
    Route::post('/get_all_craftsman_ratings', [CraftsmanDoneJobsController::class, 'get_all_ratings']);
    Route::post('/portfolio', [CraftsmanDoneJobsController::class, 'portfolio']);
    Route::post('/get_portfolio', [CraftsmanDoneJobsController::class, 'get_portfolio']);
    Route::post('/get_one_portfolio', [CraftsmanDoneJobsController::class, 'get_one_portfolio']);
    Route::post('/update_portfolio', [CraftsmanDoneJobsController::class, 'update_portfolio']);
    Route::post('/delete_portfolio', [CraftsmanDoneJobsController::class, 'delete_portfolio']);
    Route::post('/delete_portfolio_image', [CraftsmanDoneJobsController::class, 'delete_portfolio_image']);
    Route::post('/delete_account', [CraftsmanAuthController::class, 'delete_account']);
    Route::post('/add_search_images', [SearchImagesController::class, 'add_search_images']);
    Route::post('/get_search_images', [SearchImagesController::class, 'get_search_images']);
    Route::post('/update_search_images', [SearchImagesController::class, 'update_search_images']);
    Route::post('/delete_search_image', [SearchImagesController::class, 'delete_search_image']);
    Route::post('/chart', [ChartController::class, 'chart']);
    Route::post('/get_notifications', [NotificationsController::class, 'get_craftsman_notifications']);
    Route::post('/active_job_cancellation', [CraftsmanJobsController::class, 'craftsman_cancel_job']);
    Route::post('/get_active_job_cancellation_for_craftsman', [CraftsmanJobsController::class, 'get_cancellation_requests_for_craftsman']);
    Route::post('/craftsman_response_job_cancellation', [CraftsmanJobsController::class, 'craftsman_response_job_cancellation']);
    Route::post('/create_chat', [ChatController::class, 'create_chat']);
    Route::post('/send_message', [ChatController::class, 'sendMessage']);
});


Route::group([
    'middleware' => ['assign.guard:client','jwt.auth'],
    'prefix' => 'client'
], function ($router) {
    Route::post('/login', [ClientAuthController::class, 'login'])->withoutMiddleware(['assign.guard:client','jwt.auth']);
    Route::post('/register', [ClientAuthController::class, 'register'])->withoutMiddleware(['assign.guard:client','jwt.auth']);
    Route::post('/google', [ClientAuthController::class, 'google'])->withoutMiddleware(['assign.guard:client','jwt.auth']);
    Route::post('/facebook', [ClientAuthController::class, 'facebook'])->withoutMiddleware(['assign.guard:client','jwt.auth']);
    Route::post('/forgot_password', [ForgotPasswordController::class, 'client_send_email'])->withoutMiddleware(['assign.guard:client','jwt.auth']);
    Route::post('/reset_password', [ForgotPasswordController::class, 'client_reset_password'])->withoutMiddleware(['assign.guard:client','jwt.auth']);
    Route::post('/logout', [ClientAuthController::class, 'logout']);
    Route::post('/refresh', [ClientAuthController::class, 'refresh']);
    Route::get('/user-profile', [ClientAuthController::class, 'userProfile']);
    Route::post('/update-info', [ClientAuthController::class, 'updateInfo']);
    Route::post('/get_user', [ClientAuthController::class, 'get_user'])->withoutMiddleware(['assign.guard:craftsman','jwt.auth']);
    Route::post('/add_job_offer', [JobOfferController::class, 'add_job_offer']);
    Route::post('/delete_job_offer', [JobOfferController::class, 'delete_job_offer']);
    Route::post('/get_job_offer', [JobOfferController::class, 'get_one_job_offer']);
    Route::post('/get_client_job_offers', [JobOfferController::class, 'get_client_job_offers']);
    Route::post('/get_job_offers_by_city_craft', [JobOfferController::class, 'get_city_job_offers']);
    Route::post('/get_job_offer_replies', [JobOfferController::class, 'get_replies']);
    Route::post('/get_job_offer_inspections', [JobOfferController::class, 'get_inspections']);
    Route::post('/finish_job', [CraftsmanJobsController::class, 'client_finish_job']);
    Route::post('/get_all_client_ratings', [ClientAuthController::class, 'get_all_ratings']);
    Route::post('/add_to_FavoriteList', [FavoriteController::class, 'add_to_FavoriteList']);
    Route::post('/get_FavoriteList', [FavoriteController::class, 'get_FavoriteList']);
    Route::post('/delete_from_FavoriteList', [FavoriteController::class, 'delete_from_FavoriteList']);
    Route::post('/add_a_favorite', [FavoriteController::class, 'add_a_favorite']);
    Route::post('/get_favorites', [FavoriteController::class, 'get_favorites']);
    Route::post('/delete_a_favorite', [FavoriteController::class, 'delete_a_favorite']);
    Route::post('/search', [SearchController::class, 'search']);
    Route::post('/delete_account', [ClientAuthController::class, 'delete_account']);
    Route::post('/get_active_jobs', [CraftsmanJobsController::class, 'get_client_active_jobs']);
    Route::post('/get_active_job', [CraftsmanJobsController::class, 'get_client_active_job']);
    Route::post('/get_notifications', [NotificationsController::class, 'get_client_notifications']);
    Route::post('/active_job_cancellation', [CraftsmanJobsController::class, 'client_cancel_job']);
    Route::post('/get_active_job_cancellation_for_client', [CraftsmanJobsController::class, 'get_cancellation_requests_for_client']);
    Route::post('/client_response_job_cancellation', [CraftsmanJobsController::class, 'client_response_job_cancellation']);
});

Route::post('/get_crafts', [CraftsController::class, 'index']);
Route::post('/get_craft', [CraftsController::class, 'get_one_craft']);
Route::post('/add_crafts', [CraftsController::class, 'store']);
Route::post('/update_crafts', [CraftsController::class, 'update']);

Route::post('/home', [HomeController::class, 'get_homepage_data']);

Route::get('/img/{path}/{name}', function(String $path, String $name){
    // Assuming $name contains the name of the uploaded file
    $filePath = storage_path('app/public/images/'. $path. '/' . $name);

    // Check if the file exists
    if (file_exists($filePath)) {
        return response()->file("$filePath");
    } else {
        return 'file not found';
    }
    // if (file_exists($path . '/' . $name)) {
    //     return response()->file("$path/$name");
    // }
});


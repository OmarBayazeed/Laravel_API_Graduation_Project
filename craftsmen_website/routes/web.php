<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get('/pusher', function () {
    return view('pusher');
});

Route::get('/clear-cache', function () {
    // Clear configuration cache
    Artisan::call('config:clear');

    // Clear application cache
    Artisan::call('cache:clear');

    // Clear application routes
    Artisan::call('route:clear');

    return "Configuration and cache and route cleared successfully!";
});

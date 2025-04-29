<?php

namespace App\Providers;

use App\Events\Notify;
use App\Events\ClientNotify;
use App\Models\ClientNotification;
use App\Models\CraftsmanNotification;
use Illuminate\Support\ServiceProvider;
use App\Services\FCMService;
use App\Services\NotificationSender;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(FCMService::class, function () {
            return new FCMService();
        });

        $this->app->when(NotificationSender::class)
            ->needs(FCMService::class)
            ->give(FCMService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // CraftsmanNotification::created(function($notify){
        //     broadcast(new Notify($notify->craftsman_id,$notify->title,$notify->msg,$notify->id));
        // });

        // ClientNotification::created(function($notify){
        //     broadcast(new ClientNotify($notify->client_id,$notify->title,$notify->msg,$notify->id));
        // });
    }
}

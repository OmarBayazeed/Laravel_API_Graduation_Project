<?php
namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

class FCMService
{
    private $firebase;

    public function __construct()
    {
        $this->firebase = (new Factory)
            ->withServiceAccount(storage_path('app/craftsman-7f136-firebase-adminsdk-fbsvc-d43dde50c5.json'))
            ->createMessaging();
    }

    public function sendNotification(array $message)
    {
        $response = $this->firebase->send($message);
        return $response;
    }
}

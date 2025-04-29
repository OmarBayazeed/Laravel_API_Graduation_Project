<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientNotification;
use App\Models\Craftsman;
use App\Models\CraftsmanNotification;
use App\Models\Notification;
use App\Models\User;
use Kreait\Firebase\Exception\Messaging\InvalidMessage;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Exception;

class NotificationSender
{
    protected $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Send notification to one or all users.
     *
     * @param string|int|null $userId 'all' to send to all users with tokens
     * @param string $title
     * @param string $body
     * @return array
     */
    public function send($userId, $title, $body, $type)
    {
        if ($userId === 'all') {
            return $this->sendToAllUsers($title, $body);
        } else {
            return $this->sendToSingleUser($userId, $title, $body, $type);
        }
    }

    /**
     * Send notification to all users who have mobile tokens.
     */
    private function sendToAllUsers($title, $body)
    {
        $users = Client::whereNotNull('mobile_token')->get();
        $users2 = Craftsman::whereNotNull('mobile_token')->get();
        if ($users->isEmpty() && $users2->isEmpty()) {
            return ['status' => 'error', 'message' => 'No users found with mobile tokens.'];
        }

        $failedTokens = [];

        foreach ($users as $user) {
            $result = $this->sendNotificationToUser($user, $title, $body, $type = 'client');
            if (!$result['success']) {
                $failedTokens[] = $user->mobile_token;
            }
        }
        foreach ($users2 as $user) {
            $result = $this->sendNotificationToUser($user, $title, $body, $type = 'craftsman');
            if (!$result['success']) {
                $failedTokens[] = $user->mobile_token;
            }
        }
        $allUsersCount = $users->count() + $users2->count();

        if (count($failedTokens) == $allUsersCount ) {
            return ['status' => 'error', 'message' => 'Failed to send notifications to all users.'];
        }

        if (!empty($failedTokens)) {
            return [
                'status' => 'partial',
                'message' => 'Some notifications failed.',
                'failed_tokens' => $failedTokens,
            ];
        }

        return ['status' => 'success', 'message' => 'Notifications sent successfully to all users.'];
    }

    /**
     * Send notification to a single user by ID.
     */
    private function sendToSingleUser($userId, $title, $body, $type)
    {
        if ($type == 'client') {
            $user = Client::find($userId);
        }
        if ($type == 'craftsman') {
            $user = Craftsman::find($userId);
        }

        if (!$user) {
            return ['status' => 'error', 'message' => 'User not found.'];
        }

        if (!$user->mobile_token) {
            return ['status' => 'error', 'message' => 'User does not have a mobile token.'];
        }

        return $this->sendNotificationToUser($user, $title, $body, $type);
    }

    /**
     * Core method to send notification to a single user object.
     */
    private function sendNotificationToUser($user, $title, $body, $type)
    {
        $message = [
            'token' => $user->mobile_token,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => [
                'customKey1' => 'value1',
                'customKey2' => 'value2',
            ],
        ];

        try {
            $this->fcmService->sendNotification($message);

            if ($type == 'client') {
                // Save notification only if FCM succeeds
                ClientNotification::create([
                    'client_id' => $user->id,
                    'title' => $title,
                    'msg' => $body,
                ]);
            }
            if ($type == 'craftsman') {
                // Save notification only if FCM succeeds
                CraftsmanNotification::create([
                    'craftsman_id' => $user->id,
                    'title' => $title,
                    'msg' => $body,
                ]);
            }

            return ['status' => 'success', 'message' => 'Notification sent successfully.'];
        } catch (InvalidMessage | NotFound | Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}

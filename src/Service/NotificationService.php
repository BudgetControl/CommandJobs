<?php

declare(strict_types=1);

namespace Budgetcontrol\jobs\Service;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\BadResponseException;
use Budgetcontrol\jobs\Facade\BudgetControlClient;
use Budgetcontrol\Connector\Client\PushNotificationClient;
use Budgetcontrol\Connector\Entities\Payloads\Notification\PushNotification;

class NotificationService
{

    private PushNotificationClient $notificationClient;

    public function __construct()
    {
        $this->notificationClient = BudgetControlClient::pushNotification();
    }

    public function sendPushNotificationToUser(string $userUuid, string $title, string $body): void
    {
        try {
            
            $payload = new PushNotification($title, $body);
            $this->notificationClient->notificationMessageToUser($userUuid, $payload);

        } catch (BadResponseException $e) {
            Log::error('Failed to send push notification', [
                'userUuid' => $userUuid,
                'title' => $title,
                'body' => $body,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

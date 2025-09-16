<?php declare(strict_types=1);
namespace Budgetcontrol\jobs\Service;

use Budgetcontrol\jobs\Domain\Entities\NotificationData;

class PushNotificationService extends HttpService {

    /**
     * Sends a push notification to a user.
     * 
     * @param array $data An associative array containing notification information
     * @return void
     */
    public function sendNotification(NotificationData $data): void
    {
        $url = '/notify/message';
        $this->invoke('POST', $url, $data->toArray());
    }

}
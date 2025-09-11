<?php
namespace Budgetcontrol\jobs\Facade;

use Illuminate\Support\Facades\Facade;
use Budgetcontrol\SdkMailer\Service\Mail as BaseMail;

/**
 * Class Mail
 *
 * This class is a facade for the Notification class.
 * @see \Budgetcontrol\jobs\Service\PushNotificationService
 *
 * @method static void sendNotification(\Budgetcontrol\jobs\Domain\Entities\NotificationData $data)
 */

class Notification extends Facade
{

    protected static function getFacadeAccessor(): string
    {
        return 'notification-http';
    }
}
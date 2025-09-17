<?php
declare(strict_types=1);

namespace Budgetcontrol\jobs\Traits;

use Budgetcontrol\jobs\Facade\Notification;
use Budgetcontrol\jobs\Domain\Entities\NotificationData;
use Illuminate\Support\Facades\Cache as SysCache;

trait Notify
{
    private string $cacheKey;
    private NotificationData $dataNotification;

    abstract public function setCacheKey(string $key): void;

    protected function notify(NotificationData $data, bool $force = false): void
    {
        $this->dataNotification = $data;
        $this->cacheKey = md5(serialize($data->toArray()));

        if( $force === false && $this->checkIfNotificationSent() === true) {
            return;
        }

        Notification::sendNotification($data);

        //thenn save in chache that we sent the notification
        $this->cacheNotificationFlag(null); 
    }

    /**
     * Caches the notification flag for a specific bill and user
     * 
     * @param int $ttl The time to live in days for the cache entry
     * @return void
     */
    protected function cacheNotificationFlag(?int $ttl): void
    {
        $ttl = is_null($ttl) ? null : $ttl * 60 * 60 * 24;
        SysCache::put($this->cacheKey, true, $ttl);
    }

    /**
     * Checks if a notification has already been sent for a specific bill and user.
     *
     * @return bool Returns true if notification was not sent yet, false otherwise
     */
    protected function checkIfNotificationSent(): bool
    {
        return SysCache::has($this->cacheKey);
    }

}
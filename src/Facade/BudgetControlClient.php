<?php

declare(strict_types=1);

namespace Budgetcontrol\jobs\Facade;


/**
 * @see \Budgetcontrol\Connector\Factory\MicroserviceClient
 * @method static \Budgetcontrol\Connector\Client\MailerClient mailer()
 * @method static \Budgetcontrol\Connector\Client\PushNotificationClient pushNotification()
 * @method static \Budgetcontrol\Connector\Client\AuthenticationClient authentication()
 * @method static \Budgetcontrol\Connector\Client\EntryClient entry()
 * @method static \Budgetcontrol\Connector\Client\StatsClient stats()
 */
final class BudgetControlClient
{
    protected static function getFacadeAccessor(): string
    {
        return 'client-http';
    }
}

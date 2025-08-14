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
 * @method static \Budgetcontrol\Connector\Client\CacheClient cache()
 * @method static \Budgetcontrol\Connector\Client\BudgetClient budget()
 * @method static \Budgetcontrol\Connector\Client\WorkspaceClient workspace()
 */
final class BudgetControlClient extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'client-http';
    }
}

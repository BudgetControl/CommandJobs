<?php

namespace Tests;

use Mockery;
use Illuminate\Support\Facades\Facade;
use Monolog\Logger;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private const DOMAIN_DUMMY =  [
        'notification' => 'http://budgetcontrol-ms-notifications',
        'mailer' => 'http://budgetcontrol-ms-notifications',
        'budget' => 'http://budgetcontrol-ms-budget',
    ];
    protected function tearDown(): void
    {
        if ($container = Mockery::getContainer()) {
            $container->mockery_close();
        }

        Mockery::close();
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Set up the Facade application
        Facade::setFacadeApplication([
                'log' => Mockery::mock(Logger::class),
                'date' => Mockery::mock(Date::class),
                'mail' => Mockery::mock(\Budgetcontrol\jobs\Service\MailerService::class),
                'http' => Mockery::mock(\GuzzleHttp\Client::class),
                'crypt' => Mockery::mock(CryptableService::class),
                'cache-http' => Mockery::mock(\Budgetcontrol\jobs\Service\CacheService::class),
                'client-http' => Mockery::mock(new \Budgetcontrol\Connector\Factory\MicroserviceClient(
                    self::DOMAIN_DUMMY,''
                )),
        ]);
    }
}
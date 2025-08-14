<?php

$mail = new \Budgetcontrol\jobs\Service\MailerService(
    baseUrl: env('NOTIFICATION_MS_URL'),
    apiKey: env('API_SECRET')
);

$cache = new \Budgetcontrol\jobs\Service\CacheService(
    baseUrl: env('CACHE_CLEAR_URL'),
    apiKey: env('API_SECRET')
);
$cache->addHeader('X-webhook-secret', env('WEBHOOK_SECRET', ''));


$domains_config = [
    'notification' => env('NOTIFICATION_MS_URL', 'http://budgetcontrol-ms-notifications'),
    'mailer' => env('NOTIFICATION_MS_URL', 'http://budgetcontrol-ms-notifications'),
    'budget' => env('STATS_MS_URL', 'http://budgetcontrol-ms-budget'),
];

$_client = new \Budgetcontrol\Connector\Factory\MicroserviceClient(
    $domains_config,
    env('API_SECRET', '')
);

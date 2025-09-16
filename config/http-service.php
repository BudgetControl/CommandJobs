<?php

$mail = new \Budgetcontrol\jobs\Service\MailerService(
    baseUrl: env('NOTIFICATION_MS_URL'),
    apiKey: env('API_SECRET')
);

$cache = new \Budgetcontrol\jobs\Service\CacheService(
    baseUrl: env('CACHE_CLEAR_URL'),
    apiKey: env('API_SECRET')
);

$notification = new \Budgetcontrol\jobs\Service\PushNotificationService(
    baseUrl: env('NOTIFICATION_MS_URL'),
    apiKey: env('API_SECRET')
);

$cache->addHeader('X-webhook-secret', env('WEBHOOK_SECRET', ''));
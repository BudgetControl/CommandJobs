<?php

$mail = new \Budgetcontrol\jobs\Service\MailerService(
    baseUrl: env('NOTIFICATION_MS_URL'),
    apiKey: env('API_SECRET')
);
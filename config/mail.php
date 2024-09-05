<?php


$mail = new \BudgetcontrolLibs\Mailer\Service\ClientMail(
    env('MAIL_HOST', 'mailhog'),
    env('MAIL_DRIVER', 'mailhog'),
    env('MAIL_PASSWORD', ''),
    env('MAIL_USER', ''),
    env('MAIL_FROM_ADDRESS')
);
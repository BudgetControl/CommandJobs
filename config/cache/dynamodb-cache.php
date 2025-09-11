<?php

$config = [
    'credentials' => [
        'key' => env('AWS_COGNITO_ACCESS_KEY_ID', ''),
        'secret' => env('AWS_COGNITO_SECRET_ACCESS_KEY', ''),
    ],
    'region' => env('AWS_COGNITO_REGION','us-east-1'),
    'version' => env('AWS_COGNITO_VERSION','latest'),

    'app_client_id' => env('AWS_COGNITO_CLIENT_ID', ''),
    'app_client_secret' => env('AWS_COGNITO_CLIENT_SECRET', ''),
    'user_pool_id' => env('AWS_COGNITO_USER_POOL_ID', ''),
    'redirect_uri' => env('AWS_COGNITO_REDIRECT_URI', ''),
];

$dynamoDbCacheCLient = new \Aws\DynamoDb\DynamoDbClient($config);
<?php

use Illuminate\Cache\FileStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository;
use Illuminate\Cache\DynamoDbStore;
use Illuminate\Filesystem\Filesystem;

$cacheDriver = env('CACHE_DRIVER', 'file');
// Configura il file store per il caching
if($cacheDriver === 'file') {
    $fs = new Filesystem();
    $store = new FileStore($fs, env('CACHE_PATH', __DIR__ . '/../storage/cache'));
}

if($cacheDriver === 'redis') {
    require_once __DIR__.'/cache/redis-cache.php';
    $store = new RedisStore(
        new RedisCache()
    );
}

if($cacheDriver === 'dynamodb') {
    require_once __DIR__.'/cache/dynamodb-cache.php';
    $store = new DynamoDbStore(
        $dynamoDbCacheCLient,
        env('DYNAMODB_CACHE_TABLE', 'cache'),
        env('DYNAMODB_CACHE_KEY', 'key'),
        env('DYNAMODB_CACHE_VALUE', 'value'),
        env('DYNAMODB_CACHE_EXPIRATION', 'expires_at'),
        env('DYNAMODB_CACHE_PREFIX', 'ms_authentication')
    );
}

// Crea un'istanza del Repository del cache utilizzando il file store
$cache = new Repository($store);
<?php
// Autoload Composer dependencies

use BudgetcontrolLibs\Crypt\Service\CryptableService;
use Monolog\Level;
use Illuminate\Support\Facades\Facade;
use \Illuminate\Support\Carbon as Date;

require_once __DIR__ . '/../vendor/autoload.php';

// Set up your application configuration
// Initialize slim application
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Crea un'istanza del gestore del database (Capsule)
$capsule = new \Illuminate\Database\Capsule\Manager();

// Aggiungi la configurazione del database al Capsule
$connections = require_once __DIR__.'/../config/database.php';

$connection = env('DB_CONNECTION');
$capsule->addConnection($connections[$connection]);

// Esegui il boot del Capsule
$capsule->bootEloquent();
$capsule->setAsGlobal();

// Set up the logger
require_once __DIR__ . '/../config/logger.php';

/** mail configuration */
require_once __DIR__ . '/../config/http-service.php';

// Set up the Facade application HTTP
$http = new \GuzzleHttp\Client();

// Set up the Facade crypt application
$crypt = new CryptableService(env('APP_KEY'));

// Set up the Facade application
Facade::setFacadeApplication([
    'log' => $logger,
    'date' => new Date(),
    'mail' => $mail,
    'http' => $http,
    'crypt' => $crypt,
    'cache-http' => $cache,
    'client-http' => $_client

]);
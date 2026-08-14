<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// PHP 8.5 deprecates the PDO::MYSQL_ATTR_* constants that Laravel 11's shipped
// config/database.php still uses. With the CLI's display_errors=STDOUT those
// notices get printed into the response body ahead of the JSON, which makes
// every API reply unparseable. Errors still go to storage/logs via the logger.
ini_set('display_errors', '0');

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());

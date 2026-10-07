<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Support both layouts with the same file:
// - Local dev: this file lives in public/, with vendor/bootstrap as siblings
//   of public/ (the standard Laravel layout).
// - Shared hosting: this file is copied to htdocs/ alongside the full project
//   uploaded to a sibling htdocs/kashafin/ folder.
$basePath = is_dir(__DIR__.'/kashafin') ? __DIR__.'/kashafin' : __DIR__.'/..';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $basePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $basePath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $basePath.'/bootstrap/app.php';

$app->handleRequest(Request::capture());

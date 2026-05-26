<?php
/**
 * Greenhouse Observation App — entry point
 * Bootstrap is implemented in src/bootstrap.php (Step 2).
 */
declare(strict_types=1);

// Disable error display to clients regardless of host php.ini (TDS-UI-090).
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

define('APP_ROOT', __DIR__);
define('APP_VERSION', trim(file_get_contents(APP_ROOT . '/VERSION')));

// Autoloader (installed via composer install).
$autoloader = APP_ROOT . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Dependencies not installed. Run: composer install';
    exit;
}
require $autoloader;

// Bootstrap — routing, config, DB init, request dispatch.
// Created in the next implementation step.
require APP_ROOT . '/src/bootstrap.php';

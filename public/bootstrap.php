<?php
/**
 * Bootstrap file for GitHub Smart Application
 * Handles autoloading and basic configuration
 */

// Define application root
define('APP_ROOT', dirname(__DIR__));

// Autoload Composer dependencies
require_once APP_ROOT . '/vendor/autoload.php';

// Load environment variables
require_once APP_ROOT . '/config/database.php';

// Load application configuration
require_once APP_ROOT . '/config/app.php';

// Set error reporting based on environment
if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Start session
session_start();

// Set security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Load security middleware if available
if (class_exists('App\Security\SecurityMiddleware')) {
    $security = new App\Security\SecurityMiddleware();
    $security->setSecurityHeaders();
} 
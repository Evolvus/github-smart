<?php

// Security: Environment-based error reporting
$isProduction = ($_ENV['APP_ENV'] ?? 'development') === 'production';

if ($isProduction) {
    // Production: Hide all errors
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/app.log');
} else {
    // Development: Show errors but log them
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/app.log');
}

// Custom error logging function
function logError($message, $type = 'ERROR', $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$type}: {$message}";
    
    if (!empty($context)) {
        $logMessage .= " | Context: " . json_encode($context);
    }
    
    file_put_contents(__DIR__ . '/app.log', $logMessage . PHP_EOL, FILE_APPEND);
}

// Custom success logging function
function logSuccess($message, $context = []) {
    logError($message, 'SUCCESS', $context);
}

// Custom info logging function
function logInfo($message, $context = []) {
    logError($message, 'INFO', $context);
}

// Custom debug logging function
function logDebug($message, $context = []) {
    global $isProduction;
    if (!$isProduction) {
        logError($message, 'DEBUG', $context);
    }
}

// Set up error handler to catch all errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $errorType = '';
    switch ($errno) {
        case E_ERROR:
            $errorType = 'E_ERROR';
            break;
        case E_WARNING:
            $errorType = 'E_WARNING';
            break;
        case E_PARSE:
            $errorType = 'E_PARSE';
            break;
        case E_NOTICE:
            $errorType = 'E_NOTICE';
            break;
        default:
            $errorType = 'UNKNOWN';
    }
    
    logError("{$errorType}: {$errstr} in {$errfile} on line {$errline}", 'PHP_ERROR');
    
    // Don't execute PHP internal error handler
    return true;
});

// Set up exception handler
set_exception_handler(function($exception) {
    logError("Uncaught Exception: " . $exception->getMessage() . 
             " in " . $exception->getFile() . 
             " on line " . $exception->getLine() . 
             "\nStack trace: " . $exception->getTraceAsString(), 'EXCEPTION');
});

// Log application startup
logInfo("Application started", [
    'php_version' => PHP_VERSION,
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'environment' => $isProduction ? 'production' : 'development'
]);

$GITHUB_API_TOKEN = $_ENV['GITHUB_TOKEN'] ?? null; 
$GITHUB_ORG = 'Syneca';

$APP_NAME = 'CRUX';

// Log configuration loaded
logInfo("Configuration loaded", [
    'github_org' => $GITHUB_ORG,
    'app_name' => $APP_NAME,
    'github_token_set' => !empty($GITHUB_API_TOKEN)
]);

?>

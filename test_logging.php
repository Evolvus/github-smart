<?php
// Test script to demonstrate logging functionality
require_once 'config.php';
require_once 'dbconn.php';

echo "<h2>Testing Logging Functionality</h2>";

// Test different log levels
logInfo("This is an info message", ['test' => 'info']);
logSuccess("This is a success message", ['test' => 'success']);
logError("This is an error message", ['test' => 'error']);
logDebug("This is a debug message", ['test' => 'debug']);

// Test database connection
echo "<h3>Testing Database Connection</h3>";
$con = getConnection();
if ($con) {
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    logSuccess("Database connection test passed");
} else {
    echo "<p style='color: red;'>✗ Database connection failed</p>";
    logError("Database connection test failed");
}

// Test PDO connection
echo "<h3>Testing PDO Connection</h3>";
$pdo = getPDOConnection();
if ($pdo) {
    echo "<p style='color: green;'>✓ PDO connection successful</p>";
    logSuccess("PDO connection test passed");
} else {
    echo "<p style='color: red;'>✗ PDO connection failed</p>";
    logError("PDO connection test failed");
}

// Test environment variables
echo "<h3>Environment Variables</h3>";
echo "<ul>";
echo "<li>DB_HOST: " . (isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : 'NOT SET') . "</li>";
echo "<li>DB_NAME: " . (isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : 'NOT SET') . "</li>";
echo "<li>DB_USER: " . (isset($_ENV['DB_USER']) ? $_ENV['DB_USER'] : 'NOT SET') . "</li>";
echo "<li>GITHUB_TOKEN: " . (isset($_ENV['GITHUB_TOKEN']) ? 'SET' : 'NOT SET') . "</li>";
echo "</ul>";

logInfo("Environment variables checked", [
    'db_host_set' => isset($_ENV['DB_HOST']),
    'db_name_set' => isset($_ENV['DB_NAME']),
    'db_user_set' => isset($_ENV['DB_USER']),
    'github_token_set' => isset($_ENV['GITHUB_TOKEN'])
]);

echo "<h3>Recent Log Entries</h3>";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ccc;'>";
if (file_exists('app.log')) {
    $logContent = file_get_contents('app.log');
    $lines = explode("\n", $logContent);
    $recentLines = array_slice($lines, -20); // Show last 20 lines
    echo htmlspecialchars(implode("\n", $recentLines));
} else {
    echo "No log file found.";
}
echo "</pre>";

echo "<p><a href='app.log' target='_blank'>View Full Log File</a></p>";
?> 
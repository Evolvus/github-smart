<?php
// Database setup script
require_once 'config.php';

echo "<h2>Database Setup and Configuration</h2>";

// Check if .env file exists
if (!file_exists('.env')) {
    echo "<p style='color: red;'>✗ .env file not found. Please create it with your database credentials.</p>";
    echo "<p>Example .env file:</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px;'>";
    echo "# Database Configuration\n";
    echo "DB_HOST=localhost\n";
    echo "DB_PORT=3306\n";
    echo "DB_NAME=project_management\n";
    echo "DB_USER=root\n";
    echo "DB_PASSWORD=your_password\n";
    echo "\n# GitHub API Configuration\n";
    echo "GITHUB_TOKEN=your_github_token_here\n";
    echo "</pre>";
    exit;
}

// Load environment variables
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "<h3>Environment Variables Check</h3>";
echo "<ul>";
echo "<li>DB_HOST: " . (isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : 'NOT SET') . "</li>";
echo "<li>DB_NAME: " . (isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : 'NOT SET') . "</li>";
echo "<li>DB_USER: " . (isset($_ENV['DB_USER']) ? $_ENV['DB_USER'] : 'NOT SET') . "</li>";
echo "<li>DB_PASSWORD: " . (isset($_ENV['DB_PASSWORD']) ? 'SET' : 'NOT SET') . "</li>";
echo "<li>GITHUB_TOKEN: " . (isset($_ENV['GITHUB_TOKEN']) ? 'SET' : 'NOT SET') . "</li>";
echo "</ul>";

// Test database connection
echo "<h3>Database Connection Test</h3>";
$con = getConnection();
if ($con) {
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    logSuccess("Database connection test passed");
    
    // Check if tables exist
    echo "<h3>Database Tables Check</h3>";
    $tables = ['gh_issues', 'gh_projects', 'gh_issue_tags', 'expense_perm_matrix', 'crux_auth'];
    
    foreach ($tables as $table) {
        $result = mysqli_query($con, "SHOW TABLES LIKE '$table'");
        if (mysqli_num_rows($result) > 0) {
            echo "<p style='color: green;'>✓ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>✗ Table '$table' does not exist</p>";
        }
    }
    
    // Create tables if they don't exist
    echo "<h3>Creating Missing Tables</h3>";
    $sqlFile = file_get_contents('create_tables.sql');
    $queries = explode(';', $sqlFile);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query) && !str_starts_with($query, '--')) {
            try {
                $result = mysqli_query($con, $query);
                if ($result) {
                    echo "<p style='color: green;'>✓ Executed: " . substr($query, 0, 50) . "...</p>";
                } else {
                    echo "<p style='color: orange;'>⚠ Query failed: " . mysqli_error($con) . "</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠ Query error: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    mysqli_close($con);
} else {
    echo "<p style='color: red;'>✗ Database connection failed</p>";
    logError("Database connection test failed");
}

echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>Update your .env file with correct database credentials</li>";
echo "<li>Add your GitHub API token to the .env file</li>";
echo "<li>Run this setup script again to verify everything works</li>";
echo "<li>Test the application by visiting <a href='index.php'>index.php</a></li>";
echo "</ol>";

echo "<h3>GitHub Token Setup</h3>";
echo "<p>To get a GitHub token:</p>";
echo "<ol>";
echo "<li>Go to GitHub.com → Settings → Developer settings → Personal access tokens</li>";
echo "<li>Generate a new token with 'repo' and 'read:org' permissions</li>";
echo "<li>Add the token to your .env file as GITHUB_TOKEN=your_token_here</li>";
echo "</ol>";

logInfo("Database setup script completed");
?> 
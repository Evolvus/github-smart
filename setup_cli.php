#!/usr/bin/env php
<?php
// Command-line database setup script
require_once 'config.php';

echo "=== GitHub Smart Database Setup ===\n\n";

// Check if .env file exists
if (!file_exists('.env')) {
    echo "❌ .env file not found. Please create it with your database credentials.\n";
    echo "Example .env file:\n";
    echo "# Database Configuration\n";
    echo "DB_HOST=localhost\n";
    echo "DB_PORT=3306\n";
    echo "DB_NAME=project_management\n";
    echo "DB_USER=root\n";
    echo "DB_PASSWORD=your_password\n";
    echo "\n# GitHub API Configuration\n";
    echo "GITHUB_TOKEN=your_github_token_here\n";
    exit(1);
}

// Load environment variables
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "Environment Variables Check:\n";
echo "- DB_HOST: " . (isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : 'NOT SET') . "\n";
echo "- DB_NAME: " . (isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : 'NOT SET') . "\n";
echo "- DB_USER: " . (isset($_ENV['DB_USER']) ? $_ENV['DB_USER'] : 'NOT SET') . "\n";
echo "- DB_PASSWORD: " . (isset($_ENV['DB_PASSWORD']) ? 'SET' : 'NOT SET') . "\n";
echo "- GITHUB_TOKEN: " . (isset($_ENV['GITHUB_TOKEN']) ? 'SET' : 'NOT SET') . "\n\n";

// Test database connection
echo "Testing Database Connection...\n";
$con = getConnection();
if ($con) {
    echo "✅ Database connection successful\n";
    logSuccess("Database connection test passed");
    
    // Check if tables exist
    echo "\nChecking Database Tables:\n";
    $tables = ['gh_issues', 'gh_projects', 'gh_issue_tags', 'expense_perm_matrix', 'crux_auth'];
    
    foreach ($tables as $table) {
        $result = mysqli_query($con, "SHOW TABLES LIKE '$table'");
        if (mysqli_num_rows($result) > 0) {
            echo "✅ Table '$table' exists\n";
        } else {
            echo "❌ Table '$table' does not exist\n";
        }
    }
    
    // Create tables if they don't exist
    echo "\nCreating Missing Tables...\n";
    $sqlFile = file_get_contents('create_tables.sql');
    $queries = explode(';', $sqlFile);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query) && !str_starts_with($query, '--')) {
            try {
                $result = mysqli_query($con, $query);
                if ($result) {
                    echo "✅ Executed: " . substr($query, 0, 50) . "...\n";
                } else {
                    echo "⚠️  Query failed: " . mysqli_error($con) . "\n";
                }
            } catch (Exception $e) {
                echo "⚠️  Query error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    mysqli_close($con);
    echo "\n✅ Database setup completed successfully!\n";
} else {
    echo "❌ Database connection failed\n";
    logError("Database connection test failed");
    echo "\nPlease check your database credentials in the .env file.\n";
    exit(1);
}

echo "\nNext Steps:\n";
echo "1. Update your .env file with correct database credentials\n";
echo "2. Add your GitHub API token to the .env file\n";
echo "3. Run this setup script again to verify everything works\n";
echo "4. Test the application by visiting http://localhost:8000\n\n";

echo "GitHub Token Setup:\n";
echo "1. Go to GitHub.com → Settings → Developer settings → Personal access tokens\n";
echo "2. Generate a new token with 'repo' and 'read:org' permissions\n";
echo "3. Add the token to your .env file as GITHUB_TOKEN=your_token_here\n\n";

logInfo("Database setup script completed");
?> 
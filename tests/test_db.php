<?php
// Simple database connection test
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "Testing database connection...\n";
echo "Host: " . $_ENV['DB_HOST'] . "\n";
echo "Database: " . $_ENV['DB_NAME'] . "\n";
echo "User: " . $_ENV['DB_USER'] . "\n";
echo "Password: " . (isset($_ENV['DB_PASSWORD']) ? 'SET' : 'NOT SET') . "\n";

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s',
        $_ENV['DB_HOST'],
        $_ENV['DB_PORT'],
        $_ENV['DB_NAME']
    );
    
    echo "DSN: $dsn\n";
    
    $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ PDO connection successful!\n";
    
    // Test creating database if it doesn't exist
    $pdoNoDb = new PDO(
        sprintf('mysql:host=%s;port=%s', $_ENV['DB_HOST'], $_ENV['DB_PORT']),
        $_ENV['DB_USER'],
        $_ENV['DB_PASSWORD']
    );
    
    $pdoNoDb->exec("CREATE DATABASE IF NOT EXISTS " . $_ENV['DB_NAME']);
    echo "✅ Database created/verified!\n";
    
    // Test connection to the specific database
    $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
    echo "✅ Connected to database: " . $_ENV['DB_NAME'] . "\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
?> 
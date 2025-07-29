<?php
// Test GitHub REST API (the one actually used by the application)
require_once 'config.php';
require_once 'dbconn.php';

echo "=== GitHub REST API Test ===\n\n";

// Load environment variables
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$GITHUB_ORG = 'Evolvus';
$GITHUB_API_TOKEN = $_ENV['GITHUB_TOKEN'];
$APP_NAME = 'CRUX';

echo "Testing GitHub REST API for issues...\n";

// Test the exact API call that the application uses
$api_url = "https://api.github.com/orgs/$GITHUB_ORG/issues?filter=all&state=all&per_page=10&page=1";

echo "URL: $api_url\n";

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $api_url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $GITHUB_API_TOKEN",
    "User-Agent: $APP_NAME",
    "Accept: application/vnd.github.v3+json"
]);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo "HTTP Status Code: $http_code\n";

if ($http_code === 200) {
    echo "✅ GitHub REST API connection successful!\n";
    $issues = json_decode($response, true);
    
    echo "Total issues found: " . count($issues) . "\n";
    
    if (!empty($issues)) {
        echo "\nFirst few issues:\n";
        foreach (array_slice($issues, 0, 3) as $issue) {
            echo "- Issue #" . $issue['number'] . ": " . $issue['title'] . "\n";
            echo "  State: " . $issue['state'] . "\n";
            echo "  Assignee: " . ($issue['assignee']['login'] ?? 'Unassigned') . "\n";
            echo "  Repository: " . $issue['repository']['name'] . "\n";
            echo "  Node ID: " . $issue['node_id'] . "\n\n";
        }
    } else {
        echo "No issues found.\n";
    }
} else {
    echo "❌ GitHub REST API connection failed!\n";
    echo "Response: $response\n";
}

logInfo("GitHub REST API test completed", [
    'github_org' => $GITHUB_ORG,
    'http_code' => $http_code,
    'issues_count' => isset($issues) ? count($issues) : 0
]);
?> 
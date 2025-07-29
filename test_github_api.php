<?php
// Test GitHub API connection
require_once 'config.php';
require_once 'dbconn.php';

echo "=== GitHub API Test ===\n\n";

// Load environment variables
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "Environment Check:\n";
echo "- GITHUB_ORG: " . $GITHUB_ORG . "\n";
echo "- GITHUB_TOKEN: " . (isset($_ENV['GITHUB_TOKEN']) ? 'SET' : 'NOT SET') . "\n";
echo "- APP_NAME: " . $APP_NAME . "\n\n";

// Test GitHub API connection
$api_url = "https://api.github.com/orgs/$GITHUB_ORG";
$token = $_ENV['GITHUB_TOKEN'];

echo "Testing GitHub API connection...\n";
echo "URL: $api_url\n";

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $api_url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "User-Agent: $APP_NAME",
    "Accept: application/vnd.github.v3+json"
]);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo "HTTP Status Code: $http_code\n";

if ($http_code === 200) {
    echo "✅ GitHub API connection successful!\n";
    $data = json_decode($response, true);
    echo "Organization: " . $data['login'] . "\n";
    echo "Name: " . $data['name'] . "\n";
    echo "Public Repos: " . $data['public_repos'] . "\n";
} else {
    echo "❌ GitHub API connection failed!\n";
    echo "Response: $response\n";
}

// Test GraphQL API (used by the application)
echo "\n=== Testing GraphQL API ===\n";

$graphql_url = 'https://api.github.com/graphql';
$query = <<<QUERY
{
    organization(login: "$GITHUB_ORG") {
        login
        name
        projectsV2(first: 5) {
            totalCount
            nodes {
                id 
                number
                title
                url
                closed
            }
        }
    }
}
QUERY;

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $graphql_url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "User-Agent: $APP_NAME",
    "Content-Type: application/json"
]);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo "GraphQL HTTP Status Code: $http_code\n";

if ($http_code === 200) {
    echo "✅ GraphQL API connection successful!\n";
    $data = json_decode($response, true);
    
    if (isset($data['data']['organization'])) {
        $org = $data['data']['organization'];
        echo "Organization: " . $org['login'] . "\n";
        echo "Name: " . $org['name'] . "\n";
        echo "Projects V2 Count: " . $org['projectsV2']['totalCount'] . "\n";
        
        if (!empty($org['projectsV2']['nodes'])) {
            echo "Projects:\n";
            foreach ($org['projectsV2']['nodes'] as $project) {
                echo "- " . $project['title'] . " (ID: " . $project['id'] . ")\n";
            }
        } else {
            echo "No projects found.\n";
        }
    } else {
        echo "❌ GraphQL response error: " . json_encode($data) . "\n";
    }
} else {
    echo "❌ GraphQL API connection failed!\n";
    echo "Response: $response\n";
}

logInfo("GitHub API test completed", [
    'github_org' => $GITHUB_ORG,
    'token_set' => isset($_ENV['GITHUB_TOKEN']),
    'http_code' => $http_code
]);
?> 
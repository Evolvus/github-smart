<?php
// Simple API test to debug the issue
require_once 'config.php';
require_once 'dbconn.php';

echo "=== Simple API Test ===\n\n";

// Load environment variables
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$GITHUB_ORG = 'Evolvus';
$GITHUB_API_TOKEN = $_ENV['GITHUB_TOKEN'];
$APP_NAME = 'CRUX';

echo "Testing basic API call...\n";

// Test the exact API call that the application uses
$api_url = "https://api.github.com/orgs/$GITHUB_ORG/issues?filter=all&state=all&per_page=5&page=1";

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
    echo "✅ GitHub API connection successful!\n";
    $issues = json_decode($response, true);
    
    echo "Total issues found: " . count($issues) . "\n";
    
    if (!empty($issues)) {
        echo "\nTesting first issue insertion...\n";
        
        // Test database connection
        $pdo = getPDOConnection();
        if ($pdo) {
            echo "✅ Database connection successful\n";
            
            // Test inserting the first issue manually
            $issue = $issues[0];
            echo "Issue #" . $issue['number'] . ": " . $issue['title'] . "\n";
            
            // Manually insert the issue
            try {
                $gh_id = $issue['number'] ?? 0;
                $gh_node_id = $issue['node_id'] ?? '';
                $gh_id_url = $issue['html_url'] ?? '';
                $repo = $issue['repository']['name'] ?? '';
                $repo_url = $issue['repository']['html_url'] ?? '';
                $issue_text = $issue['title'] ?? '';
                $assigned_date = isset($issue['created_at']) ? date('Y-m-d', strtotime($issue['created_at'])) : date('Y-m-d');
                $assignee = isset($issue['assignees'][0]['login']) ? $issue['assignees'][0]['login'] : 'UNASSIGNED';
                $gh_json = json_encode($issue);
                $updated_at = isset($issue['updated_at']) ? date('Y-m-d H:i:s', strtotime($issue['updated_at'])) : date('Y-m-d H:i:s');
                $closed_at = !empty($issue['closed_at']) ? date('Y-m-d', strtotime($issue['closed_at'])) : null;
                $state = $issue['state'] ?? 'open';
                
                $stmt = $pdo->prepare(
                    "INSERT INTO gh_issues (gh_id, gh_node_id, gh_id_url, repo, repo_url, gh_project_url, issue_text, client, assigned_date, target_date, 
                    gh_json, assignee, gh_project, gh_project_title, last_updated_at, closed_at, gh_state) VALUES 
                    (:gh_id, :gh_node_id, :gh_id_url, :repo, :repo_url, :gh_project_url, :issue_text, :client, :assigned_date, :target_date, :gh_json, :assignee, :gh_project, :gh_project_title, :updated_at, :closed_at, :gh_state)"
                );
                
                $stmt->bindParam(':gh_id', $gh_id);
                $stmt->bindParam(':gh_node_id', $gh_node_id);
                $stmt->bindParam(':gh_id_url', $gh_id_url);
                $stmt->bindParam(':repo', $repo);
                $stmt->bindParam(':repo_url', $repo_url);
                $gh_project_url = '';
                $client = '';
                $gh_project = '';
                $gh_project_title = '';
                $target_date = null;
                
                $stmt->bindParam(':gh_project_url', $gh_project_url);
                $stmt->bindParam(':issue_text', $issue_text);
                $stmt->bindParam(':client', $client);
                $stmt->bindParam(':assigned_date', $assigned_date);
                $stmt->bindParam(':target_date', $target_date, PDO::PARAM_NULL);
                $stmt->bindParam(':gh_json', $gh_json);
                $stmt->bindParam(':assignee', $assignee);
                $stmt->bindParam(':gh_project', $gh_project);
                $stmt->bindParam(':gh_project_title', $gh_project_title);
                $stmt->bindParam(':updated_at', $updated_at);
                $stmt->bindParam(':closed_at', $closed_at, PDO::PARAM_NULL);
                $stmt->bindParam(':gh_state', $state);
                
                $stmt->execute();
                echo "✅ Issue insertion successful!\n";
                
            } catch (Exception $e) {
                echo "❌ Issue insertion failed: " . $e->getMessage() . "\n";
            }
        } else {
            echo "❌ Database connection failed\n";
        }
    } else {
        echo "No issues found.\n";
    }
} else {
    echo "❌ GitHub API connection failed!\n";
    echo "Response: $response\n";
}

logInfo("Simple API test completed");
?> 
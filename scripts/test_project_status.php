<?php
/**
 * Test script for Project Board Status functionality
 * This script tests the GitHub ProjectsV2 API integration and database operations
 */

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../config/app.php');
require_once(__DIR__ . '/../api/utilities_project.php');

$pdo = getPDOConnection();
date_default_timezone_set("Asia/Kolkata");

echo "=== Project Board Status Test Script ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Test 1: Check if the new table exists
echo "1. Checking if gh_issue_project_status table exists...\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'gh_issue_project_status'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "   ✓ Table exists\n";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE gh_issue_project_status");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   ✓ Table has " . count($columns) . " columns\n";
    } else {
        echo "   ✗ Table does not exist\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "   ✗ Error checking table: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check if GitHub service can be loaded
echo "\n2. Testing GitHub service...\n";
try {
    require_once(__DIR__ . '/../vendor/autoload.php');
    $githubService = new \App\Services\GitHubService();
    echo "   ✓ GitHub service loaded successfully\n";
} catch (Exception $e) {
    echo "   ✗ Error loading GitHub service: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Test GraphQL client
echo "\n3. Testing GraphQL client...\n";
try {
    $graphqlClient = new \App\Services\GitHubGraphQLClient();
    echo "   ✓ GraphQL client loaded successfully\n";
} catch (Exception $e) {
    echo "   ✗ Error loading GraphQL client: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Check if there are any existing issues in the database
echo "\n4. Checking existing issues...\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM gh_issues");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $issueCount = $result['count'];
    
    echo "   Found {$issueCount} issues in database\n";
    
    if ($issueCount > 0) {
        // Get a sample issue
        $stmt = $pdo->query("SELECT gh_node_id, gh_id, issue_text FROM gh_issues LIMIT 1");
        $issue = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   Sample issue: #{$issue['gh_id']} - {$issue['issue_text']}\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error checking issues: " . $e->getMessage() . "\n";
}

// Test 5: Check existing project board status data
echo "\n5. Checking existing project board status data...\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM gh_issue_project_status");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $statusCount = $result['count'];
    
    echo "   Found {$statusCount} project board status records\n";
    
    if ($statusCount > 0) {
        // Get sample status data
        $stmt = $pdo->query("
            SELECT 
                ips.project_title,
                ips.status_field_name,
                ips.status_value,
                COUNT(*) as issue_count
            FROM gh_issue_project_status ips
            GROUP BY ips.project_title, ips.status_field_name, ips.status_value
            LIMIT 5
        ");
        $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   Sample status data:\n";
        foreach ($statuses as $status) {
            echo "     - {$status['project_title']}: {$status['status_field_name']} = {$status['status_value']} ({$status['issue_count']} issues)\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ Error checking project board status: " . $e->getMessage() . "\n";
}

// Test 6: Test API endpoint functionality
echo "\n6. Testing API endpoint functionality...\n";
try {
    // Test the getProjectStatus API
    $url = "http://localhost:8081/api/getProjectStatus.php?action=summary";
    $response = file_get_contents($url);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['status'])) {
            echo "   ✓ API endpoint accessible\n";
            echo "   Response status: {$data['status']}\n";
            echo "   Data count: {$data['count']}\n";
        } else {
            echo "   ⚠ API response format unexpected\n";
        }
    } else {
        echo "   ⚠ API endpoint not accessible (server may not be running)\n";
    }
} catch (Exception $e) {
    echo "   ⚠ Error testing API endpoint: " . $e->getMessage() . "\n";
}

// Test 7: Test project board status import (dry run)
echo "\n7. Testing project board status import (dry run)...\n";
try {
    // This would normally call the GitHub API, but we'll just test the function exists
    if (function_exists('fetchProjectBoardStatus')) {
        echo "   ✓ Import function exists\n";
    } else {
        echo "   ⚠ Import function not found (may need to include the file)\n";
    }
} catch (Exception $e) {
    echo "   ⚠ Error testing import: " . $e->getMessage() . "\n";
}

echo "\n=== Test Summary ===\n";
echo "All basic functionality tests completed.\n";
echo "To run a full import test, use:\n";
echo "  curl -X POST http://localhost:8081/api/getProjectBoardStatus.php\n";
echo "To view the dashboard, visit:\n";
echo "  http://localhost:8081/project-status.php\n";
echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";
?>

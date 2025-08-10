<?php
session_name("Project");
session_start();
require_once(__DIR__ . '/../../config/database.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/../utilities_project.php');

$pdo = getPDOConnection();
date_default_timezone_set("Asia/Kolkata");

/**
 * Insert or update project board status for an issue
 */
function insertProjectBoardStatus($pdo, $ghNodeId, $projectData, $statusFields) {
    try {
        // First, delete any existing status for this issue-project combination
        $deleteStmt = $pdo->prepare("DELETE FROM gh_issue_project_status WHERE gh_node_id = :gh_node_id AND project_id = :project_id");
        $deleteStmt->bindParam(':gh_node_id', $ghNodeId);
        $deleteStmt->bindParam(':project_id', $projectData['project_id']);
        $deleteStmt->execute();
        
        // Insert new status records for each status field
        foreach ($statusFields as $statusField) {
            $stmt = $pdo->prepare("
                INSERT INTO gh_issue_project_status 
                (gh_node_id, project_id, project_title, project_url, status_field_id, status_field_name, status_value, status_color, item_id) 
                VALUES 
                (:gh_node_id, :project_id, :project_title, :project_url, :status_field_id, :status_field_name, :status_value, :status_color, :item_id)
            ");
            
            $stmt->bindParam(':gh_node_id', $ghNodeId);
            $stmt->bindParam(':project_id', $projectData['project_id']);
            $stmt->bindParam(':project_title', $projectData['project_title']);
            $stmt->bindParam(':project_url', $projectData['project_url']);
            $stmt->bindParam(':status_field_id', $statusField['field_id']);
            $stmt->bindParam(':status_field_name', $statusField['field_name']);
            $stmt->bindParam(':status_value', $statusField['value']);
            $stmt->bindParam(':status_color', $statusField['color']);
            $stmt->bindParam(':item_id', $projectData['item_id']);
            
            $stmt->execute();
        }
        
        return true;
    } catch (PDOException $e) {
        write_log("Database Error inserting project board status: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetch project board status using GitHub ProjectsV2 API via GraphQL
 */
function fetchProjectBoardStatus($githubOrg, $githubToken, $appName) {
    // Use the GitHub service to fetch project board status
    require_once(__DIR__ . '/../../vendor/autoload.php');
    
    try {
        // Set up configuration
        $config = [
            'github' => [
                'token' => $githubToken,
                'org' => $githubOrg
            ],
            'app' => [
                'name' => $appName
            ],
            'logging' => [
                'file' => __DIR__ . '/../../logs/app.log'
            ]
        ];
        
        // Create GitHub service instance
        $githubService = new \App\Services\GitHubService();
        
        // Fetch project board status
        $projectStatuses = $githubService->getProjectBoardStatus();
        
        write_log("Fetched project board status for " . count($projectStatuses) . " issues");
        
        return $projectStatuses;
        
    } catch (Exception $e) {
        write_log("Error fetching project board status: " . $e->getMessage());
        return [];
    }
}

/**
 * Get issue node ID from GitHub issue ID
 */
function getIssueNodeId($pdo, $ghIssueId) {
    try {
        $stmt = $pdo->prepare("SELECT gh_node_id FROM gh_issues WHERE gh_id = :gh_id");
        $stmt->bindParam(':gh_id', $ghIssueId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['gh_node_id'] : null;
    } catch (PDOException $e) {
        write_log("Database Error getting issue node ID: " . $e->getMessage());
        return null;
    }
}

try {
    $startTime = date("Y-m-d H:i:s");
    write_log("Starting project board status import at {$startTime}");
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Clear existing project board status data
    $pdo->exec("DELETE FROM gh_issue_project_status");
    write_log("Cleared existing project board status data from database");
    
    // Fetch project board status from GitHub
    $projectStatuses = fetchProjectBoardStatus($GITHUB_ORG, $GITHUB_API_TOKEN, $APP_NAME);
    
    $processedCount = 0;
    $errorCount = 0;
    
    // Process each project status
    foreach ($projectStatuses as $issueId => $projectData) {
        try {
            // Get the issue node ID from our database
            $issueNodeId = getIssueNodeId($pdo, $issueId);
            
            if (!$issueNodeId) {
                write_log("Warning: Issue node ID not found for GitHub issue ID: {$issueId}");
                $errorCount++;
                continue;
            }
            
            // Insert project board status
            $success = insertProjectBoardStatus($pdo, $issueNodeId, $projectData, $projectData['status_fields']);
            
            if ($success) {
                $processedCount++;
                write_log("Processed project board status for issue node ID: {$issueNodeId} in project: {$projectData['project_title']}");
            } else {
                $errorCount++;
                write_log("Failed to process project board status for issue node ID: {$issueNodeId}");
            }
            
        } catch (Exception $e) {
            $errorCount++;
            write_log("Error processing project board status for issue ID {$issueId}: " . $e->getMessage());
        }
    }
    
    $endTime = date("Y-m-d H:i:s");
    write_log("Project board status import completed at {$endTime}");
    
    $responseArray = [
        "status" => "success",
        "message" => "Project board status imported successfully!",
        "timestamp" => $endTime,
        "issues_processed" => $processedCount,
        "errors" => $errorCount,
        "total_project_statuses" => count($projectStatuses)
    ];
    
    insertAuditData($pdo, "PROJECT_BOARD_STATUS_IMPORT", $startTime, $endTime);
    
} catch (PDOException $e) {
    write_log("Database Error: " . $e->getMessage());
    $responseArray = ["status" => "error", "message" => "Database Error: " . $e->getMessage()];
} catch (Exception $e) {
    write_log("Error: " . $e->getMessage());
    $responseArray = ["status" => "error", "message" => "Error: " . $e->getMessage()];
}

// Return JSON response for AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    write_log(json_encode($responseArray));
    echo json_encode($responseArray);
} else {
    write_log($responseArray['message']);
    echo $responseArray['message'];
}
?>

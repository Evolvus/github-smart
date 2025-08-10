<?php
session_name("Project");
session_start();
require_once(__DIR__ . '/../../config/database.php');
require_once(__DIR__ . '/../../config/app.php');
require_once(__DIR__ . '/../api/utilities_project.php');

$pdo = getPDOConnection();
date_default_timezone_set("Asia/Kolkata");

/**
 * Get project board status for all issues
 */
function getProjectBoardStatus($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                ips.gh_node_id,
                ips.project_id,
                ips.project_title,
                ips.project_url,
                ips.status_field_id,
                ips.status_field_name,
                ips.status_value,
                ips.status_color,
                ips.item_id,
                i.gh_id,
                i.issue_text,
                i.assignee,
                i.gh_state
            FROM gh_issue_project_status ips
            LEFT JOIN gh_issues i ON ips.gh_node_id = i.gh_node_id
            ORDER BY ips.project_title, ips.status_field_name, ips.status_value
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        write_log("Database Error getting project board status: " . $e->getMessage());
        return [];
    }
}

/**
 * Get project board status for a specific issue
 */
function getIssueProjectStatus($pdo, $ghNodeId) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                ips.gh_node_id,
                ips.project_id,
                ips.project_title,
                ips.project_url,
                ips.status_field_id,
                ips.status_field_name,
                ips.status_value,
                ips.status_color,
                ips.item_id,
                i.gh_id,
                i.issue_text,
                i.assignee,
                i.gh_state
            FROM gh_issue_project_status ips
            LEFT JOIN gh_issues i ON ips.gh_node_id = i.gh_node_id
            WHERE ips.gh_node_id = :gh_node_id
            ORDER BY ips.project_title, ips.status_field_name
        ");
        
        $stmt->bindParam(':gh_node_id', $ghNodeId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        write_log("Database Error getting issue project status: " . $e->getMessage());
        return [];
    }
}

/**
 * Get project board status summary by project
 */
function getProjectStatusSummary($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                ips.project_id,
                ips.project_title,
                ips.project_url,
                ips.status_field_name,
                ips.status_value,
                ips.status_color,
                COUNT(*) as issue_count
            FROM gh_issue_project_status ips
            GROUP BY ips.project_id, ips.status_field_name, ips.status_value
            ORDER BY ips.project_title, ips.status_field_name, ips.status_value
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        write_log("Database Error getting project status summary: " . $e->getMessage());
        return [];
    }
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get request parameters
    $action = $_GET['action'] ?? 'all';
    $ghNodeId = $_GET['gh_node_id'] ?? null;
    
    $data = [];
    
    switch ($action) {
        case 'issue':
            if ($ghNodeId) {
                $data = getIssueProjectStatus($pdo, $ghNodeId);
            } else {
                throw new Exception("gh_node_id parameter is required for 'issue' action");
            }
            break;
            
        case 'summary':
            $data = getProjectStatusSummary($pdo);
            break;
            
        case 'all':
        default:
            $data = getProjectBoardStatus($pdo);
            break;
    }
    
    $responseArray = [
        "status" => "success",
        "action" => $action,
        "data" => $data,
        "count" => count($data),
        "timestamp" => date("Y-m-d H:i:s")
    ];
    
} catch (PDOException $e) {
    write_log("Database Error: " . $e->getMessage());
    $responseArray = ["status" => "error", "message" => "Database Error: " . $e->getMessage()];
} catch (Exception $e) {
    write_log("Error: " . $e->getMessage());
    $responseArray = ["status" => "error", "message" => "Error: " . $e->getMessage()];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($responseArray, JSON_PRETTY_PRINT);
?> 
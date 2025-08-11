<?php
session_name("Project");
session_start();
require_once(__DIR__ . "/../../config/database.php");

require_once(__DIR__ . "/../../config/app.php");

$pdo = getPDOConnection();

try {
    // Set PDO to throw exceptions on error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if gh_issue_project_status table has data
    $statusTableCheck = $pdo->query("SELECT COUNT(*) as count FROM gh_issue_project_status");
    $statusTableCount = $statusTableCheck->fetch(PDO::FETCH_ASSOC)['count'];

    if ($statusTableCount > 0) {
        // Use gh_issue_project_status table if it has data
        $query = "
            SELECT 
                p.*,
                COALESCE(issue_counts.count_of_issues, 0) as count_of_issues
            FROM gh_projects p
            LEFT JOIN (
                SELECT 
                    project_id,
                    COUNT(DISTINCT gh_node_id) as count_of_issues
                FROM gh_issue_project_status 
                GROUP BY project_id
            ) issue_counts ON p.gh_id = issue_counts.project_id
            WHERE p.gh_id != 'UNASSIGNED'
            ORDER BY count_of_issues DESC
        ";
        
        // Add UNASSIGNED project with dynamic count (issues not in any project board)
        $unassignedQuery = "
            SELECT COUNT(*) as count_of_issues 
            FROM gh_issues 
            WHERE gh_node_id NOT IN (SELECT DISTINCT gh_node_id FROM gh_issue_project_status)
        ";
    } else {
        // Fallback to gh_issues table for project assignments
        $query = "
            SELECT 
                p.*,
                COALESCE(issue_counts.count_of_issues, 0) as count_of_issues
            FROM gh_projects p
            LEFT JOIN (
                SELECT 
                    gh_project,
                    COUNT(*) as count_of_issues
                FROM gh_issues 
                WHERE gh_project IS NOT NULL AND gh_project != ''
                GROUP BY gh_project
            ) issue_counts ON p.gh_id = issue_counts.gh_project
            WHERE p.gh_id != 'UNASSIGNED'
            ORDER BY count_of_issues DESC
        ";
        
        // Add UNASSIGNED project with dynamic count (issues not assigned to any project)
        $unassignedQuery = "
            SELECT COUNT(*) as count_of_issues 
            FROM gh_issues 
            WHERE gh_project IS NULL OR gh_project = ''
        ";
    }

    $result = $pdo->query($query);
    $projects = $result->fetchAll(PDO::FETCH_ASSOC);

    $unassignedResult = $pdo->query($unassignedQuery);
    $unassignedCount = $unassignedResult->fetch(PDO::FETCH_ASSOC)['count_of_issues'];

    // Always add UNASSIGNED project with dynamic count
    $unassignedProject = [
        'gh_id' => 'UNASSIGNED',
        'title' => 'UNASSIGNED',
        'url' => 'https://github.com/orgs/Syneca/',
        'closed' => '0',
        'count_of_issues' => $unassignedCount
    ];
    
    // Add UNASSIGNED project to the beginning of the array
    array_unshift($projects, $unassignedProject);

    echo json_encode($projects);

    } catch (PDOException $e) {
        // Handle database errors
        echo "Database Error: " . $e->getMessage();
    } catch (Exception $e) {
        // Handle other errors
        echo "Error: " . $e->getMessage();
    }


?>

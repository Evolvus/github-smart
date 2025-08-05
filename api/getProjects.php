<?php
session_name("Project");
session_start();
require_once(__DIR__ . "/../config/database.php");

require_once(__DIR__ . "/../config/app.php");

$pdo = getPDOConnection();

try {
    // Set PDO to throw exceptions on error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query to get all projects
    $query = "SELECT * FROM gh_projects where 1=1 order by count_of_issues desc";
    $result = $pdo->query($query);
    $projects = $result->fetchAll(PDO::FETCH_ASSOC);

    // Add UNASSIGNED project with dynamic count
    $unassignedQuery = "SELECT COUNT(*) as count_of_issues FROM gh_issues WHERE gh_project IS NULL OR gh_project = ''";
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
    
    // Remove any existing UNASSIGNED projects from the array
    $projects = array_filter($projects, function($project) {
        return $project['gh_id'] !== 'UNASSIGNED';
    });
    
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

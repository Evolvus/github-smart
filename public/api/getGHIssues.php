<?php
session_name("Project");
session_start();
require_once(__DIR__ . '/../../config/database.php');
require_once(__DIR__ . '/../../config/app.php');

require_once(__DIR__ . '/utilities_project.php');


error_log("getGHIssues.php started");

// Check if GitHub token is configured
if (empty($GITHUB_API_TOKEN) || $GITHUB_API_TOKEN === 'your_github_token_here') {
    $responseArray = [
        'status' => 'error',
        'message' => 'GitHub API token not configured. Please set GITHUB_TOKEN in docker.env file.',
        'requires_setup' => true
    ];
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($responseArray);
    } else {
        echo $responseArray['message'];
    }
    exit;
}


error_log("GitHub API Token: " . ($GITHUB_API_TOKEN ? 'SET' : 'NOT SET'));

// Initialize variables
$startTime = date("Y-m-d H:i:s");
$page = 1;





$pdo = getPDOConnection();
if (!$pdo) {
    $responseArray = "Database connection failed. Please check your database configuration.";
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($responseArray);
    } else {
        echo $responseArray;
    }
    exit;
}
date_default_timezone_set("Asia/Kolkata");



function insertProjectData($pdo, $gh_id, $title, $url,$closed = 'false', $count_of_issues = 0) {
    try {
        // Prepare the SQL statement for insertion
        $stmt = $pdo->prepare("INSERT INTO gh_projects (gh_id, title, closed, count_of_issues,url) VALUES (:gh_id, :title, :closed, :count_of_issues,:url)");

        // Bind the parameters
        $stmt->bindParam(':gh_id', $gh_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':closed', $closed);
        $stmt->bindParam(':count_of_issues', $count_of_issues);
        $stmt->bindParam(':url', $url);

        // Execute the statement to insert data
        $stmt->execute();

        return true; // Return true if insertion was successful
    } catch (PDOException $e) {
        // Handle any database errors
        write_log( "Database Error: " . $e->getMessage());
        return false; // Return false if there was an error
    }
}

function insertUnassignedProjectData($pdo) {
    try {
        // Prepare the SQL statement for insertion
        $sql = "INSERT INTO gh_projects (gh_id, title,  count_of_issues, url) select  'UNASSIGNED', 'UNASSIGNED',  count(*), 'https://github.com/orgs/paycorp-io/' from gh_issues where gh_project is null";
        $stmt = $pdo->prepare( $sql);

        
        // Execute the statement to insert data
        $stmt->execute();

        return true; // Return true if insertion was successful
    } catch (PDOException $e) {
        // Handle any database errors
        write_log( "Database Error: " . $e->getMessage());
        return false; // Return false if there was an error
    }
}


function insertIssueIntoDatabase($pdo, $issue, $proj) {
    // Add error handling for missing array keys
    if (!is_array($issue)) {
        logError("Invalid issue data received", 'API_ERROR', ['issue' => $issue]);
        return;
    }
    
    $labels = $issue['labels'] ?? [];
    
    if (isset($issue['pull_request'])) {
        return;
    }

    // Safely extract issue data with error handling
    $gh_id = $issue['number'] ?? 0;
    $gh_node_id = $issue['node_id'] ?? '';
    $gh_id_url = $issue['html_url'] ?? '';
    $repo = $issue['repository']['name'] ?? '';
    $repo_url = $issue['repository']['html_url'] ?? '';
    $gh_project_url = ''; // You may need to extract this information from your GitHub issue data
    $issue_text = $issue['title'] ?? '';
    $client = ''; // You may need to extract this information from your GitHub issue data
    $assigned_date = isset($issue['created_at']) ? date('Y-m-d', strtotime($issue['created_at'])) : date('Y-m-d');
    $target_date = !empty($issue['due_date']) ? date('Y-m-d', strtotime($issue['due_date'])) : null; // Ensure valid date or NULL
    $assignee = isset($issue['assignees'][0]['login']) ? $issue['assignees'][0]['login'] : 'UNASSIGNED';
    $gh_json = json_encode($issue);
    $updated_at = isset($issue['updated_at']) ? date('Y-m-d H:i:s', strtotime($issue['updated_at'])) : date('Y-m-d H:i:s');
    $closed_at = !empty($issue['closed_at']) ? date('Y-m-d', strtotime($issue['closed_at'])) : null;
    $state = $issue['state'] ?? 'open';

    if ($proj) {
        $gh_project = $proj['id'] ?? '';
        $gh_project_title = $proj['title'] ?? '';
    } else {
        $gh_project = '';
        $gh_project_title = '';
    }

    // Insert issue into the database
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
    $stmt->bindParam(':gh_project_url', $gh_project_url);
    $stmt->bindParam(':issue_text', $issue_text);
    $stmt->bindParam(':client', $client);
    $stmt->bindParam(':assigned_date', $assigned_date);
    $stmt->bindParam(':target_date', $target_date, PDO::PARAM_NULL); // Bind as NULL if it's empty
    $stmt->bindParam(':gh_json', $gh_json);
    $stmt->bindParam(':assignee', $assignee);
    $stmt->bindParam(':gh_project', $gh_project);
    $stmt->bindParam(':gh_project_title', $gh_project_title);
    $stmt->bindParam(':updated_at', $updated_at);
    $stmt->bindParam(':closed_at', $closed_at, PDO::PARAM_NULL);
    $stmt->bindParam(':gh_state', $state);

    try {
        $stmt->execute();
    } catch (PDOException $e) {
        write_log("Database Error: " . $e->getMessage());
        return;
    }

    if (is_array($labels)) {
        foreach ($labels as $label) {
            if (is_array($label) && isset($label['name']) && isset($label['color'])) {
                $tag = $label['name'];
                $color = $label['color'];
                $stmt = $pdo->prepare(
                    "INSERT INTO gh_issue_tags (gh_node_id, tag, color) VALUES (:gh_node_id, :tag, :color)"
                );
                
                $stmt->bindParam(':gh_node_id', $gh_node_id);
                $stmt->bindParam(':tag', $tag);
                $stmt->bindParam(':color', $color);

                try {
                    $stmt->execute();
                } catch (PDOException $e) {
                    write_log("Database Error: " . $e->getMessage());
                }
            }
        }
    }
}



try {
    // Set PDO to throw exceptions on error

   

    $startTime = date("Y-m-d H:i:s"); // Use the current timestamp

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
     // Delete existing data from the gh_issues table
     $deleteStmt = $pdo->prepare("DELETE FROM gh_issues");
     $deleteStmt->execute();

     $deleteStmt = $pdo->prepare("DELETE FROM gh_projects");
     $deleteStmt->execute();

     $deleteStmt = $pdo->prepare("DELETE FROM gh_issue_tags");
     $deleteStmt->execute();

    // Initialize the page counter
    $page = 1;

    $arr = getProjectIssues($GITHUB_ORG,$GITHUB_API_TOKEN);

    // Check if getProjectIssues returned an error string instead of an array
    if (!is_array($arr)) {
        logError("GraphQL API failed, falling back to REST API only", 'API_WARNING', [
            'error' => $arr,
            'github_org' => $GITHUB_ORG
        ]);
        $arr = array(); // Empty array for projects
    }

    $projects = array();
    if (is_array($arr) && !empty($arr)) {
        foreach($arr as $key => $value){
            $x = $value['id'];
            $project = $value;

            if(!$projects[$x]){
                $project = $value;
                $project['count_of_issues'] = 1;
                $projects[$x] = $project;
            } else{
                $projects[$x]['count_of_issues'] ++;
            }
        }

        foreach($projects as $key => $value){
            insertProjectData($pdo, $value['id'], $value['title'], $value['url'] , $value['closed'],  $value['count_of_issues']);
        }
    } else {
        logInfo("No projects found or GraphQL API unavailable", [
            'projects_count' => 0
        ]);
    }

    while (true) {
        $api_url = "https://api.github.com/orgs/$GITHUB_ORG/issues?filter=all&state=all&per_page=99&page=$page";

        // Fetch issues from GitHub API
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $api_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $GITHUB_API_TOKEN",
            "User-Agent: $APP_NAME "
        ]);
        $response = curl_exec($curl);
        curl_close($curl);

        $issues = json_decode($response, true);

        // If there are no more issues, break out of the loop
        if (empty($issues)) {
            break;
        }

        // Loop through GitHub issues and insert into MySQL database
        foreach ($issues as $issue) {
            // Check if we have project data for this issue
            $projectData = (is_array($arr) && isset($arr[$issue['node_id']])) ? $arr[$issue['node_id']] : null;
            insertIssueIntoDatabase($pdo, $issue, $projectData);
        }

        // Increment the page counter
        $page++;
    }

     //Insert issues unassigned to any projects
     insertUnassignedProjectData($pdo);

    $responseArray = [
        'status' => 'success',
        'message' => 'Issues inserted successfully!',
        'timestamp' => date("Y-m-d H:i:s")
    ];
    insertAuditData($pdo, "RETRIEVE FROM GITHUB", $startTime, date("Y-m-d H:i:s"));
} catch (PDOException $e) {
    write_log( "Database Error: " . $e->getMessage());
    $responseArray = [
        'status' => 'error',
        'message' => 'Database Error: ' . $e->getMessage()
    ];
} catch (Exception $e) {
    write_log( "Database Error: " . $e->getMessage());
    $responseArray = [
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ];
}

// if requested by AJAX request return JSON response
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($responseArray);
} else {
    echo $responseArray;
}
?>

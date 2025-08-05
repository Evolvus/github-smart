<?php
session_name("Project");
session_start();
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../config/app.php');
require_once(__DIR__ . '/utilities_project.php');

$pdo = getPDOConnection();
date_default_timezone_set("Asia/Kolkata");

function insertProjectData($pdo, $gh_id, $title, $url, $closed = 'false', $count_of_issues = 0) {
    try {
        $stmt = $pdo->prepare("INSERT INTO gh_projects (gh_id, title, closed, count_of_issues, url) VALUES (:gh_id, :title, :closed, :count_of_issues, :url)");
        $stmt->bindParam(':gh_id', $gh_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':closed', $closed);
        $stmt->bindParam(':count_of_issues', $count_of_issues);
        $stmt->bindParam(':url', $url);
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        write_log("Database Error: " . $e->getMessage());
        return false;
    }
}

function insertUnassignedProjectData($pdo) {
    try {
        $sql = "INSERT INTO gh_projects (gh_id, title, count_of_issues, url) SELECT 'UNASSIGNED', 'UNASSIGNED', COUNT(*), 'https://github.com/orgs/paycorp-io/' FROM gh_issues WHERE gh_project IS NULL OR gh_project = ''";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        write_log("Database Error: " . $e->getMessage());
        return false;
    }
}

function insertIssueIntoDatabase($pdo, $issue, $projectData = null) {
    if (!is_array($issue)) {
        write_log("Invalid issue data received");
        return;
    }
    
    if (isset($issue['pull_request'])) {
        return; // Skip pull requests
    }

    // Extract issue data
    $gh_id = $issue['number'] ?? 0;
    $gh_node_id = $issue['node_id'] ?? '';
    $gh_id_url = $issue['html_url'] ?? '';
    $repo = $issue['repository']['name'] ?? '';
    $repo_url = $issue['repository']['html_url'] ?? '';
    $issue_text = $issue['title'] ?? '';
    $client = '';
    $assigned_date = isset($issue['created_at']) ? date('Y-m-d', strtotime($issue['created_at'])) : date('Y-m-d');
    $target_date = !empty($issue['due_date']) ? date('Y-m-d', strtotime($issue['due_date'])) : null;
    $assignee = isset($issue['assignees'][0]['login']) ? $issue['assignees'][0]['login'] : 'UNASSIGNED';
    $gh_json = json_encode($issue);
    $updated_at = isset($issue['updated_at']) ? date('Y-m-d H:i:s', strtotime($issue['updated_at'])) : date('Y-m-d H:i:s');
    $closed_at = !empty($issue['closed_at']) ? date('Y-m-d H:i:s', strtotime($issue['closed_at'])) : null;
    $state = $issue['state'] ?? 'open';

    // Set project data
    if ($projectData && is_array($projectData)) {
        $gh_project = $projectData['id'] ?? '';
        $gh_project_title = $projectData['title'] ?? '';
    } else {
        $gh_project = '';
        $gh_project_title = '';
    }

    // Insert issue into database
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
    $stmt->bindParam(':gh_project_url', '');
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

    try {
        $stmt->execute();
        write_log("Inserted issue #{$gh_id}: {$issue_text} - Project: {$gh_project_title}");
    } catch (PDOException $e) {
        write_log("Database Error inserting issue #{$gh_id}: " . $e->getMessage());
        return;
    }

    // Insert issue tags
    $labels = $issue['labels'] ?? [];
    if (is_array($labels)) {
        foreach ($labels as $label) {
            if (is_array($label) && isset($label['name']) && isset($label['color'])) {
                $tag = $label['name'];
                $color = $label['color'];
                $stmt = $pdo->prepare("INSERT INTO gh_issue_tags (gh_node_id, tag, color) VALUES (:gh_node_id, :tag, :color)");
                $stmt->bindParam(':gh_node_id', $gh_node_id);
                $stmt->bindParam(':tag', $tag);
                $stmt->bindParam(':color', $color);

                try {
                    $stmt->execute();
                } catch (PDOException $e) {
                    write_log("Database Error inserting tag: " . $e->getMessage());
                }
            }
        }
    }
}

function fetchAllIssuesWithProjects($githubOrg, $githubToken, $appName) {
    $allIssues = [];
    $page = 1;
    $perPage = 100; // GitHub API max per page
    
    write_log("Starting to fetch issues from GitHub API...");
    
    while (true) {
        $apiUrl = "https://api.github.com/orgs/{$githubOrg}/issues?filter=all&state=open&per_page={$perPage}&page={$page}";
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $apiUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$githubToken}",
            "User-Agent: {$appName}",
            "Accept: application/vnd.github.v3+json"
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpCode !== 200) {
            write_log("GitHub API error: HTTP {$httpCode} for page {$page}");
            break;
        }
        
        $issues = json_decode($response, true);
        
        if (empty($issues)) {
            write_log("No more issues found on page {$page}");
            break;
        }
        
        write_log("Fetched " . count($issues) . " issues from page {$page}");
        
        foreach ($issues as $issue) {
            if (isset($issue['pull_request'])) {
                continue; // Skip pull requests
            }
            
            // For each issue, fetch its project assignments
            $projectData = fetchIssueProjects($githubOrg, $githubToken, $appName, $issue['repository']['name'], $issue['number']);
            $issue['project_data'] = $projectData;
            $allIssues[] = $issue;
        }
        
        $page++;
        
        // Safety check to prevent infinite loops
        if ($page > 50) {
            write_log("Reached maximum page limit (50), stopping pagination");
            break;
        }
    }
    
    write_log("Total issues fetched: " . count($allIssues));
    return $allIssues;
}

function fetchIssueProjects($githubOrg, $githubToken, $appName, $repoName, $issueNumber) {
    $apiUrl = "https://api.github.com/repos/{$githubOrg}/{$repoName}/issues/{$issueNumber}";
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $apiUrl);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$githubToken}",
        "User-Agent: {$appName}",
        "Accept: application/vnd.github.v3+json"
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($httpCode !== 200) {
        write_log("Error fetching issue #{$issueNumber} projects: HTTP {$httpCode}");
        return null;
    }
    
    $issueData = json_decode($response, true);
    
    // Check if the issue has any project assignments
    if (isset($issueData['project_cards']) && !empty($issueData['project_cards'])) {
        // This would require additional API calls to get project details
        // For now, we'll use the GraphQL approach for project data
        return null;
    }
    
    return null;
}

function fetchProjectsWithIssues($githubOrg, $githubToken, $appName) {
    // Since GraphQL is not working, let's use a simpler approach
    // We'll manually assign SYNECA issues to the SYNECA ROADMAP project based on the issue title
    
    $projects = [
        [
            'id' => 'PVT_kwDODTts384A-eBm',
            'title' => 'SYNECA ROADMAP',
            'closed' => false,
            'url' => 'https://github.com/orgs/Syneca/projects/1',
            'count_of_issues' => 0
        ]
    ];
    
    $projectIssues = [];
    
    // We'll populate this when we process issues
    // For now, return the project structure
    write_log("Using fallback project assignment for SYNECA issues");
    
    return ['projects' => $projects, 'project_issues' => $projectIssues];
}

try {
    $startTime = date("Y-m-d H:i:s");
    write_log("Starting GitHub issues import at {$startTime}");
    write_log("TEST: This is the updated version of the API");
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Clear existing data
    $pdo->exec("DELETE FROM gh_issues");
    $pdo->exec("DELETE FROM gh_projects");
    $pdo->exec("DELETE FROM gh_issue_tags");
    
    write_log("Cleared existing data from database");
    
    // Fetch projects and their issues using GraphQL
    $projectData = fetchProjectsWithIssues($GITHUB_ORG, $GITHUB_API_TOKEN, $APP_NAME);
    $projects = $projectData['projects'] ?? [];
    $projectIssues = $projectData['project_issues'] ?? [];
    
    // Insert projects into database
    foreach ($projects as $project) {
        insertProjectData($pdo, $project['id'], $project['title'], $project['url'], $project['closed'], $project['count_of_issues']);
    }
    
    write_log("Inserted " . count($projects) . " projects into database");
    
    // Fetch all issues with pagination
    $allIssues = fetchAllIssuesWithProjects($GITHUB_ORG, $GITHUB_API_TOKEN, $APP_NAME);
    
    // Process each issue
    foreach ($allIssues as $issue) {
        $issueNodeId = $issue['node_id'];
        $projectData = $projectIssues[$issueNodeId] ?? null;
        
        write_log("Processing issue #{$issue['number']}: {$issue['title']} - Project data: " . ($projectData ? 'found' : 'not found'));
        
        // For SYNECA issues, always assign to SYNECA ROADMAP project (override GraphQL data)
        if (strpos($issue['title'], '[SYNECA]:') === 0) {
            $projectData = [
                'id' => 'PVT_kwDODTts384A-eBm',
                'title' => 'SYNECA ROADMAP',
                'closed' => false,
                'url' => 'https://github.com/orgs/Syneca/projects/1'
            ];
            write_log("Force assigning issue #{$issue['number']} to SYNECA ROADMAP project based on title");
        } else {
            write_log("Issue #{$issue['number']} is not a SYNECA issue: {$issue['title']}");
        }
        
        insertIssueIntoDatabase($pdo, $issue, $projectData);
    }
    
    // Insert unassigned project data
    insertUnassignedProjectData($pdo);
    
    $endTime = date("Y-m-d H:i:s");
    write_log("GitHub issues import completed at {$endTime}");
    
    $responseArray = [
        "status" => "success",
        "message" => "Issues inserted successfully!",
        "timestamp" => $endTime,
        "issues_processed" => count($allIssues),
        "projects_found" => count($projects)
    ];
    
    insertAuditData($pdo, "RETRIEVE FROM GITHUB", $startTime, $endTime);
    
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

<?php
require_once(__DIR__ . '/../config/app.php');

header('Content-Type: application/json');

function write_log($message) {
    $log_file = __DIR__ . '/../logs/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] {$message}" . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

function fetchProjectMapping($org, $token, $appName) {
    $graphql_query = '
    query($org: String!) {
        organization(login: $org) {
            projectsV2(first: 100) {
                nodes {
                    id
                    number
                    title
                    url
                    closed
                }
            }
        }
    }';

    $variables = [
        'org' => $org
    ];

    $data = [
        'query' => $graphql_query,
        'variables' => $variables
    ];

    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'User-Agent: ' . $appName
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/graphql');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        throw new Exception("GitHub API returned HTTP code: {$http_code}");
    }

    $result = json_decode($response, true);
    
    if (isset($result['errors'])) {
        throw new Exception("GraphQL API errors: " . json_encode($result['errors']));
    }

    $projects = $result['data']['organization']['projectsV2']['nodes'] ?? [];
    
    // Create mapping from project ID to project number
    $projectMapping = [];
    foreach ($projects as $project) {
        if (!$project['closed']) {
            $projectMapping[$project['id']] = $project['number'];
        }
    }
    
    return $projectMapping;
}

function fetchProjectStatus($org, $token, $appName, $projectId) {
    // Extract project number from project ID
    $projectNumber = extractProjectNumber($projectId);
    
    if (!$projectNumber) {
        throw new Exception("Could not extract project number from project ID: {$projectId}");
    }
    
    write_log("Extracted project number: {$projectNumber} from project ID: {$projectId}");
    
    $graphql_query = '
    query($org: String!, $projectNumber: Int!) {
        organization(login: $org) {
            projectV2(number: $projectNumber) {
                id
                title
                number
                fields(first: 20) {
                    nodes {
                        ... on ProjectV2Field {
                            id
                            name
                            dataType
                        }
                        ... on ProjectV2SingleSelectField {
                            id
                            name
                            options {
                                id
                                name
                                color
                            }
                        }
                    }
                }
                items(first: 100) {
                    pageInfo {
                        hasNextPage
                        endCursor
                    }
                    nodes {
                        id
                        content {
                            ... on Issue {
                                id
                                number
                                title
                                state
                                assignees(first: 10) {
                                    nodes {
                                        login
                                    }
                                }
                                repository {
                                    name
                                    owner {
                                        login
                                    }
                                }
                            }
                        }
                        fieldValues(first: 20) {
                            nodes {
                                ... on ProjectV2ItemFieldTextValue {
                                    id
                                    text
                                    field {
                                        ... on ProjectV2Field {
                                            id
                                            name
                                        }
                                    }
                                }
                                ... on ProjectV2ItemFieldSingleSelectValue {
                                    id
                                    name
                                    optionId
                                    field {
                                        ... on ProjectV2SingleSelectField {
                                            id
                                            name
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }';

    $variables = [
        'org' => $org,
        'projectNumber' => $projectNumber
    ];

    $data = [
        'query' => $graphql_query,
        'variables' => $variables
    ];

    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'User-Agent: ' . $appName
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/graphql');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    write_log("Project Status API - HTTP Code: {$http_code}");
    write_log("Project Status API - Response: " . substr($response, 0, 500));

    if ($http_code !== 200) {
        throw new Exception("GitHub API returned HTTP code: {$http_code}");
    }

    $result = json_decode($response, true);
    
    if (isset($result['errors'])) {
        write_log("GraphQL Errors: " . json_encode($result['errors']));
        throw new Exception("GraphQL API errors: " . json_encode($result['errors']));
    }

    return $result['data'] ?? null;
}

function extractProjectNumber($projectId) {
    global $GITHUB_ORG, $GITHUB_API_TOKEN, $APP_NAME;
    
    // Pattern 1: Extract from URL like "https://github.com/orgs/Syneca/projects/1"
    if (preg_match('/projects\/(\d+)/', $projectId, $matches)) {
        return (int)$matches[1];
    }
    
    // Pattern 2: If it's already a number
    if (is_numeric($projectId)) {
        return (int)$projectId;
    }
    
    // Pattern 3: Try to extract any number from the string
    if (preg_match('/(\d+)/', $projectId, $matches)) {
        return (int)$matches[1];
    }
    
    // Pattern 4: For opaque project IDs, fetch the mapping from GitHub
    try {
        $mappingData = fetchProjectMapping($GITHUB_ORG, $GITHUB_API_TOKEN, $APP_NAME);
        if ($mappingData && isset($mappingData['projectMapping'][$projectId])) {
            return $mappingData['projectMapping'][$projectId];
        }
    } catch (Exception $e) {
        write_log("Failed to fetch project mapping: " . $e->getMessage());
    }
    
    return null;
}

try {
    $projectId = $_GET['projectId'] ?? null;
    
    if (!$projectId) {
        throw new Exception("Project ID is required");
    }

    write_log("Fetching project status for project ID: {$projectId}");

    $projectData = fetchProjectStatus($GITHUB_ORG, $GITHUB_API_TOKEN, $APP_NAME, $projectId);
    
    if (!$projectData) {
        throw new Exception("No project data returned from GitHub");
    }

    // Extract status information
    $project = $projectData['organization']['projectV2'] ?? null;
    
    if (!$project) {
        throw new Exception("Project not found");
    }

    write_log("Found project: {$project['title']} (Number: {$project['number']})");

    // Get status field options
    $statusOptions = [];
    $statusField = null;
    
    foreach ($project['fields']['nodes'] as $field) {
        if ($field['name'] === 'Status' || $field['name'] === 'status') {
            $statusField = $field;
            if (isset($field['options'])) {
                $statusOptions = $field['options'];
            }
            break;
        }
    }

    write_log("Status field found: " . ($statusField ? $statusField['name'] : 'No status field'));
    write_log("Status options: " . json_encode($statusOptions));

    // Process items and their status
    $itemsByStatus = [];
    $statusMapping = [
        'backlog' => ['Backlog', 'To Do', 'Todo', 'Triage'],
        'ready' => ['Ready', 'Ready for Development', 'Ready to Start'],
        'in-progress' => ['In Progress', 'In Development', 'Development', 'Working on it'],
        'review' => ['Review', 'In Review', 'Testing', 'QA'],
        'done' => ['Done', 'Complete', 'Completed', 'Closed']
    ];

    foreach ($project['items']['nodes'] as $item) {
        $issue = $item['content'] ?? null;
        if (!$issue || $issue['__typename'] !== 'Issue') {
            continue;
        }

        $issueStatus = 'backlog'; // default
        $issueId = $issue['id'];
        $issueNumber = $issue['number'];
        $issueTitle = $issue['title'];
        $issueState = $issue['state'];
        $repository = $issue['repository']['name'] ?? 'Unknown';
        $assignees = [];
        
        if (isset($issue['assignees']['nodes'])) {
            foreach ($issue['assignees']['nodes'] as $assignee) {
                $assignees[] = $assignee['login'];
            }
        }

        // Find status from field values
        foreach ($item['fieldValues']['nodes'] as $fieldValue) {
            if (isset($fieldValue['field']['name']) && 
                ($fieldValue['field']['name'] === 'Status' || $fieldValue['field']['name'] === 'status')) {
                
                $statusName = $fieldValue['name'] ?? '';
                write_log("Issue #{$issueNumber} has status: {$statusName}");
                
                // Map status to our categories
                foreach ($statusMapping as $category => $statusNames) {
                    if (in_array($statusName, $statusNames)) {
                        $issueStatus = $category;
                        break;
                    }
                }
                break;
            }
        }

        // If no status found, use issue state as fallback
        if ($issueStatus === 'backlog' && $issueState === 'CLOSED') {
            $issueStatus = 'done';
        }

        if (!isset($itemsByStatus[$issueStatus])) {
            $itemsByStatus[$issueStatus] = [];
        }

        $itemsByStatus[$issueStatus][] = [
            'id' => $issueId,
            'number' => $issueNumber,
            'title' => $issueTitle,
            'state' => $issueState,
            'status' => $issueStatus,
            'repository' => $repository,
            'assignees' => $assignees
        ];
    }

    // Log status distribution
    foreach ($itemsByStatus as $status => $items) {
        write_log("Status '{$status}': " . count($items) . " items");
    }

    $response = [
        'status' => 'success',
        'project' => [
            'id' => $project['id'],
            'title' => $project['title'],
            'number' => $project['number'],
            'statusOptions' => $statusOptions,
            'itemsByStatus' => $itemsByStatus
        ]
    ];

    write_log("Successfully processed project status data");

} catch (Exception $e) {
    write_log("Error in getProjectStatus.php: " . $e->getMessage());
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
?> 
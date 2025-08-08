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

    write_log("Project Mapping API - HTTP Code: {$http_code}");

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

try {
    write_log("Fetching project mapping for organization");

    $projectData = fetchProjectMapping($GITHUB_ORG, $GITHUB_API_TOKEN, $APP_NAME);
    
    if (!$projectData) {
        throw new Exception("No project data returned from GitHub");
    }

    $projects = $projectData['organization']['projectsV2']['nodes'] ?? [];
    
    // Filter out closed projects and create mapping
    $projectMapping = [];
    $activeProjects = [];
    
    foreach ($projects as $project) {
        if (!$project['closed']) {
            $projectMapping[$project['id']] = $project['number'];
            $activeProjects[] = [
                'id' => $project['id'],
                'number' => $project['number'],
                'title' => $project['title'],
                'url' => $project['url']
            ];
        }
    }

    write_log("Found " . count($activeProjects) . " active projects");

    $response = [
        'status' => 'success',
        'projectMapping' => $projectMapping,
        'activeProjects' => $activeProjects
    ];

} catch (Exception $e) {
    write_log("Error in getProjectMapping.php: " . $e->getMessage());
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>

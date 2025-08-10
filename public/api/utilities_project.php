
<?php

require_once(__DIR__ . '/../../vendor/autoload.php'); // Include Guzzle library
require_once(__DIR__ . '/../../config/app.php');

// Define write_log function if not already defined
if (!function_exists('write_log')) {
    function write_log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
        
        // Try to write to log file, but don't fail if we can't
        try {
            @file_put_contents(__DIR__ . '/../../config/app.log', $logMessage, FILE_APPEND);
        } catch (Exception $e) {
            // Silently fail if we can't write to log file
        }
    }
}



function getProjectIssues($org,$token){
    GLOBAL $APP_NAME;
    $api_url = 'https://api.github.com/graphql';
    
    $project_issue = array();
    
    // First, get all projects
    $projectsQuery = <<<QUERY
    {
        organization(login: "{$org}") {
            projectsV2(first: 80) {
                nodes {
                    id 
                    number
                    title
                    url
                    closed
                }
            }
        }
    }
    QUERY;

    try {
        // Initialize Guzzle client
        $client = new \GuzzleHttp\Client();
    
        // Make a POST request to get all projects
        $response = $client->post($api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'User-Agent' => $APP_NAME,
            ],
            'json' => ['query' => $projectsQuery],
        ]);

        // Check the HTTP status code
        if ($response->getStatusCode() === 200) {
            // Parse the JSON response
            $data = json_decode($response->getBody(), true);
            
            if (!isset($data['data']['organization']['projectsV2']['nodes'])) {
                return $project_issue;
            }

            // For each project, fetch all items using cursor-based pagination
            foreach($data['data']['organization']['projectsV2']['nodes'] as $proj){
                if(empty($proj) || $proj['closed']){
                    continue;
                }
                
                $projectId = $proj['id'];
                $hasNextPage = true;
                $endCursor = null;
                
                while ($hasNextPage) {
                    $afterClause = $endCursor ? '"' . $endCursor . '"' : 'null';
                    $itemsQuery = <<<QUERY
                    {
                        node(id: "{$projectId}") {
                            ... on ProjectV2 {
                                items(first: 100, after: {$afterClause}) {
                                    pageInfo {
                                        hasNextPage
                                        endCursor
                                    }
                                    nodes {
                                        content { 
                                            ... on Issue { 
                                                id
                                                title 
                                                closed
                                                updatedAt
                                                labels(first: 10) {
                                                    nodes {
                                                        name
                                                        color
                                                    }
                                                }
                                            } 
                                        }
                                    }
                                }
                            }
                        }
                    }
                    QUERY;
                    
                    $response = $client->post($api_url, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $token,
                            'User-Agent' => $APP_NAME,
                        ],
                        'json' => ['query' => $itemsQuery],
                    ]);
                    
                    if ($response->getStatusCode() === 200) {
                        $itemsData = json_decode($response->getBody(), true);
                        
                        if (isset($itemsData['data']['node']['items'])) {
                            $items = $itemsData['data']['node']['items'];
                            $pageInfo = $items['pageInfo'];
                            
                            foreach($items['nodes'] as $issues){
                                if(empty($issues) || empty($issues['content']) || $issues['content']['closed']){
                                    continue;
                                }
                                
                                $id = $issues['content']['id'];
                                $project['id'] = $proj['id'];
                                $project['title'] = $proj['title'];
                                $project['closed'] = $proj['closed'];
                                $project['url'] = $proj['url'];
                                $project_issue[$id] = $project;
                            }
                            
                            $hasNextPage = $pageInfo['hasNextPage'];
                            $endCursor = $pageInfo['endCursor'];
                        } else {
                            $hasNextPage = false;
                        }
                    } else {
                        $hasNextPage = false;
                    }
                }
            }

            return $project_issue;

        } else {
            return 'Error: Unable to fetch data from GitHub API.';
        }
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }

}

//PVT_kwDOBAc7t84AQnaC
function getProject($org,$token,$repo,$issue) {
  
    GLOBAL $APP_NAME;
    $api_url = 'https://api.github.com/graphql';

    $query = <<<QUERY
    {
        repository(owner: "{$org}", name: "{$repo}") {
            issue(number: {$issue}) {
                projectsV2(first: 20) {
                    nodes {
                        id
                        title
                    }
                }
            }
        }
    }
    QUERY;
    $client = new GuzzleHttp\Client();
    try {
        // Make a POST request to the GitHub GraphQL API
        $response = $client->post($api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'User-Agent' => $APP_NAME,
            ],
            'json' => ['query' => $query],
        ]);
    
        // Check the HTTP status code
        if ($response->getStatusCode() === 200) {
            // Parse and handle the response
            $data = json_decode($response->getBody(), true);

            return $data['data']['repository']['issue']['projectsV2']['nodes'][0];
            //return $data;
        } else {
            error_log('Error: Unable to fetch data from GitHub API.');
        }
    } catch (Exception $e) {
        error_log( 'Error: ' . $e->getMessage());
    }

}


function getProject1($repo) {
  

    $api_url = 'https://api.github.com/graphql';

    $query = <<<QUERY
    {
        node(id: "PVT_kwDOBAc7t84AQnaC") {
            ... on ProjectV2 {
              items(first: 99) {
                nodes{
                  id
                  content{              
                    ... on DraftIssue {
                      title
                      body
                    }
                    ...on Issue {
                      title
                      assignees(first: 99) {
                        nodes{
                          login
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
    QUERY;
    $client = new GuzzleHttp\Client();
    try {
        // Make a POST request to the GitHub GraphQL API
        global $GITHUB_API_TOKEN, $APP_NAME;
        $response = $client->post($api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $GITHUB_API_TOKEN,
                'User-Agent' => $APP_NAME,
            ],
            'json' => ['query' => $query],
        ]);
    
        // Check the HTTP status code
        if ($response->getStatusCode() === 200) {
            // Parse and handle the response
            $data = json_decode($response->getBody(), true);
            //return $data['data']['repository']['issue']['projectsV2']['nodes'][0];
            echo json_encode($data);
        } else {
            echo 'Error: Unable to fetch data from GitHub API.';
        }
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }

}




function insertAuditData($pdo, $action, $startTime, $endTime) {
    try {
     
        // Prepare the SQL statement for insertion
        $stmt = $pdo->prepare("INSERT INTO gh_audit (action, start_time, end_time) VALUES (:action, :start_time, :end_time)");

        // Bind the parameters
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':start_time',  $startTime);
        $stmt->bindParam(':end_time',  $endTime);

        // Execute the statement to insert data
        $stmt->execute();

        return true; // Return true if insertion was successful
    } catch (PDOException $e) {
        // Handle any database errors
        write_log("Database Error: " . $e->getMessage());
        return false; // Return false if there was an error
    }
}




?>
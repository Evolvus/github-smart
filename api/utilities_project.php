
<?php


//require '../vendor/autoload.php'; // Include Guzzle library
require_once('../config.php');



function getProjectIssues($org,$token){
    GLOBAL $APP_NAME;
    $api_url = 'https://api.github.com/graphql';
    $query = <<<QUERY
    {
        organization(login: "{$org}") {
            projectsV2(first: 80) {
                nodes {
                    id 
                    number
                    title
                    url
                    closed
                    items(first: 20) { 
                        nodes{
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
    }
    QUERY;

    try {
        // Initialize Guzzle client
        $client = new \GuzzleHttp\Client();
    
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

            // Parse the JSON response
            $data = json_decode($response->getBody(), true);

            $project_issue = array();

            foreach($data['data']['organization']['projectsV2']['nodes'] as $proj){
                if(empty($proj) || $proj['closed']){
                    
                    continue;
                }
                //echo $proj['title'].'<br>';
                foreach($proj['items']['nodes'] as $issues){

                    //echo json_encode($issues );
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
            }

            

            // Access the GraphQL response data

            return $project_issue ;

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
        echo "Database Error: " . $e->getMessage();
        return false; // Return false if there was an error
    }
}




?>
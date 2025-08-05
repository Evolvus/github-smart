<?php
require_once(__DIR__ . '/config/app.php');

$apiUrl = 'https://api.github.com/graphql';

// Query to get the SYNECA ROADMAP project and all its items
$query = <<<QUERY
{
    node(id: "PVT_kwDODTts384A-eBm") {
        ... on ProjectV2 {
            id
            title
            items(first: 100) {
                totalCount
                pageInfo {
                    hasNextPage
                    endCursor
                }
                nodes {
                    content { 
                        ... on Issue { 
                            id
                            number
                            title 
                            closed
                        } 
                    }
                }
            }
        }
    }
}
QUERY;

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $apiUrl);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$GITHUB_API_TOKEN}",
    "User-Agent: {$APP_NAME}",
    "Content-Type: application/json"
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo "HTTP Code: {$httpCode}\n";
echo "Response:\n";
$data = json_decode($response, true);
echo json_encode($data, JSON_PRETTY_PRINT);
?> 
<?php
require_once(__DIR__ . '/config/app.php');

$apiUrl = "https://api.github.com/orgs/{$GITHUB_ORG}/issues?filter=all&state=all&per_page=10&page=1";

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $apiUrl);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$GITHUB_API_TOKEN}",
    "User-Agent: {$APP_NAME}",
    "Accept: application/vnd.github.v3+json"
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo "HTTP Code: {$httpCode}\n";
echo "Response:\n";
$data = json_decode($response, true);
echo json_encode($data, JSON_PRETTY_PRINT);
?> 
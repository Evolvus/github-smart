<?php
use App\Utils\Database;
use App\Utils\Logger;

require_once(__DIR__ . "/../vendor/autoload.php");
require_once(__DIR__ . "/../config/database.php");

try {
    $pdo = Database::getPDOConnection();

    $bucketName = $_POST['name'];

    Logger::writeLog("POST NEW BUCKET " . $bucketName);

    // Insert the new bucket into the database
    $query = "INSERT INTO gh_buckets (name) VALUES (:name)";
    $stmt = $pdo->prepare($query);

    $response = ['success' => false];

    if ($stmt->execute(['name' => $bucketName])) {
        $response['success'] = true;
        $response['id'] = $pdo->lastInsertId();
        $response['name'] = $bucketName;
    }

    Logger::writeLog(json_encode($response));
    echo json_encode($response);

} catch (Exception $e) {
    Logger::writeLog("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

<?php
use App\Utils\Database;
use App\Utils\Logger;

require_once(__DIR__ . "/../../vendor/autoload.php");
require_once(__DIR__ . "/../../config/database.php");

try {
    $pdo = Database::getPDOConnection();

    $bucketId = $_POST['bucket_id'];

    Logger::writeLog("POST DELETE BUCKET: ID " . $bucketId);

    // Delete the bucket from the database
    $query = "DELETE FROM gh_buckets WHERE id = :id";
    $stmt = $pdo->prepare($query);

    $response = ['success' => false];

    if ($stmt->execute(['id' => $bucketId])) {
        $response['success'] = true;
    }

    Logger::writeLog(json_encode($response));
    echo json_encode($response);

} catch (Exception $e) {
    Logger::writeLog("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

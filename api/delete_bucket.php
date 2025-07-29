<?php
require_once('../dbconn.php');

try {
    $pdo = getPDOConnection();

    $bucketId = $_POST['bucket_id'];

    write_log("POST DELETE BUCKET: ID " . $bucketId);

    // Delete the bucket from the database
    $query = "DELETE FROM gh_buckets WHERE id = :id";
    $stmt = $pdo->prepare($query);

    $response = ['success' => false];

    if ($stmt->execute(['id' => $bucketId])) {
        $response['success'] = true;
    }

    write_log(json_encode($response));
    echo json_encode($response);

} catch (Exception $e) {
    write_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

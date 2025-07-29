<?php
require_once('../dbconn.php');

try {
    $pdo = getPDOConnection();

    $bucketId = $_POST['bucket_id'];
    $bucketName = $_POST['name'];

    write_log("POST UPDATE BUCKET NAME: ID " . $bucketId . ", Name " . $bucketName);

    // Update the bucket name in the database
    $query = "UPDATE gh_buckets SET name = :name WHERE id = :id";
    $stmt = $pdo->prepare($query);

    $response = ['success' => false];

    if ($stmt->execute(['name' => $bucketName, 'id' => $bucketId])) {
        $response['success'] = true;
        $response['id'] = $bucketId;
        $response['name'] = $bucketName;
    }

    write_log(json_encode($response));
    echo json_encode($response);

} catch (Exception $e) {
    write_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

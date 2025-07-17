<?php
require_once('../../dbconn.php');

try {
    $pdo = getPDOConnection();

    $bucketName = $_POST['name'];

    write_log("POST NEW BUCKET " . $bucketName);

    // Insert the new bucket into the database
    $query = "INSERT INTO gh_buckets (name) VALUES (:name)";
    $stmt = $pdo->prepare($query);

    $response = ['success' => false];

    if ($stmt->execute(['name' => $bucketName])) {
        $response['success'] = true;
        $response['id'] = $pdo->lastInsertId();
        $response['name'] = $bucketName;
    }

    write_log(json_encode($response));
    echo json_encode($response);

} catch (Exception $e) {
    write_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

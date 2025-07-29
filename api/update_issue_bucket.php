<?php
require_once('../dbconn.php');

try {
    $pdo = getPDOConnection();

    $issue_number = $_POST['issue_id'];
    $bucket = $_POST['bucket'];

    write_log("POST ISSUE " . $issue_number . " " . $bucket);

    // Update the issue bucket in the database
    $query = "UPDATE gh_pinned_issues SET bucket = :bucket WHERE gh_node_id = :issue_number AND is_deleted = 'NO'";
    $stmt = $pdo->prepare($query);

    $response = ['success' => false];

    if ($stmt->execute(['bucket' => $bucket, 'issue_number' => $issue_number])) {
        $response['success'] = true;
    }

    write_log(json_encode($response));
    echo json_encode($response);

} catch (Exception $e) {
    write_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

<?php
require_once('../dbconn.php');
require_once('../config.php');

$pdo = getPDOConnection();

try {
    // Fetch all buckets
    $stmt = $pdo->prepare('SELECT * FROM gh_buckets');
    $stmt->execute();
    $buckets = $stmt->fetchAll();

    // Fetch the default bucket
    $default_stmt = $pdo->prepare('SELECT * FROM gh_buckets WHERE is_default = TRUE LIMIT 1');
    $default_stmt->execute();
    $default_bucket = $default_stmt->fetch();

    echo json_encode(array('buckets' => $buckets, 'default' => $default_bucket));
} catch (Exception $e) {
    logError("Database error in get_buckets: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array('error' => 'Database error occurred'));
}
?>

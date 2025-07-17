<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../../dbconn.php');

require_once('../config.php');

$pdo = getPDOConnection();

try {
    // Fetch all buckets
    $stmt = $pdo->query('SELECT * FROM gh_buckets');
    $buckets = $stmt->fetchAll();

    // Fetch the default bucket
    $default_stmt = $pdo->query('SELECT * FROM gh_buckets WHERE is_default = TRUE LIMIT 1');
    $default_bucket = $default_stmt->fetch();

    echo json_encode(array('buckets' => $buckets, 'default' => $default_bucket));
} catch (Exception $e) {
    echo json_encode(array('error' => $e->getMessage()));
}
?>

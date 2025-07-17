<?php
session_name("Project");
session_start();
require_once('../../dbconn.php');
require_once('../config.php');

$pdo = getPDOConnection();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$tag = isset($_POST['customer']) ? trim($_POST['customer']) : '';

if (empty($tag)) {
    echo json_encode(['success' => false, 'error' => 'Customer tag is required.']);
    exit;
}

// Check if the customer tag already exists in gh_customer
$stmt = $pdo->prepare("SELECT COUNT(*) FROM gh_customer WHERE tag = :tag");
$stmt->bindValue(':tag', $tag, PDO::PARAM_STR);
$stmt->execute();

if ($stmt->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'error' => 'Customer tag already exists.']);
    exit;
}

// Insert the new customer tag into gh_customer
$stmt = $pdo->prepare("INSERT INTO gh_customer (tag) VALUES (:tag)");
$stmt->bindValue(':tag', $tag, PDO::PARAM_STR);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Unable to add customer tag.']);
}
?>
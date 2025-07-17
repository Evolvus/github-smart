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

$customer = isset($_POST['customer']) ? trim($_POST['customer']) : '';

if (empty($customer)) {
    echo json_encode(['success' => false, 'error' => 'Customer tag is required.']);
    exit;
}

// Delete the customer tag from gh_customer
$stmt = $pdo->prepare("DELETE FROM gh_customer WHERE tag = :tag");
$stmt->bindValue(':tag', $customer, PDO::PARAM_STR);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Unable to remove customer tag.']);
}
?>
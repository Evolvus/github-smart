<?php
require_once(__DIR__ . "/../../config/database.php");
require_once(__DIR__ . "/../../config/app.php");

// Security: CSRF Protection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        exit;
    }
}

// Input validation function
function validateInput($input, $type = 'string') {
    switch ($type) {
        case 'int':
            return filter_var($input, FILTER_VALIDATE_INT) !== false ? (int)$input : null;
        case 'string':
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

try {
    $pdo = getPDOConnection();

    // Validate and sanitize inputs
    $bucketId = validateInput($_POST['bucket_id'] ?? '', 'int');
    $bucketName = validateInput($_POST['name'] ?? '', 'string');

    // Additional validation
    if (!$bucketId || $bucketId <= 0) {
        throw new Exception('Invalid bucket ID');
    }

    if (empty($bucketName) || strlen($bucketName) > 255) {
        throw new Exception('Invalid bucket name');
    }

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
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>

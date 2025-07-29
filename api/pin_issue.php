<?php
require_once('../dbconn.php');
require_once('../config.php');

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

$pdo = getPDOConnection();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate and sanitize inputs
        $issue_number = validateInput($_POST['issue_number'] ?? '', 'string');
        $user_id = validateInput($_POST['user_id'] ?? '', 'int');

        write_log("POSTED ".$issue_number );

        if (!empty($issue_number) && !empty($user_id) && $user_id > 0) {
            // Check if the issue is already pinned
            $stmt = $pdo->prepare("SELECT * FROM gh_pinned_issues WHERE user_id = :user_id AND gh_node_id = :issue_number and is_deleted = 'NO'");
            $stmt->execute(['user_id' => $user_id, 'issue_number' => $issue_number]);
            $existing = $stmt->fetch();

            write_log($existing);

            if ($existing) {
                // Unpin the issue
                $stmt = $pdo->prepare("UPDATE gh_pinned_issues set is_deleted = 'YES'  WHERE user_id = :user_id AND gh_node_id = :issue_number");
                $stmt->execute(['user_id' => $user_id, 'issue_number' => $issue_number]);
                $success = $stmt->rowCount() > 0;
                echo json_encode(['success' => $success]);
            } else {
                // Pin the issue
                $stmt = $pdo->prepare('INSERT INTO gh_pinned_issues (user_id, gh_node_id) VALUES (:user_id, :issue_number)');
                $stmt->execute(['user_id' => $user_id, 'issue_number' => $issue_number]);
                $success = $stmt->rowCount() > 0;

                echo json_encode(['success' => $success]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid input.']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    }
} catch (PDOException $e) {
    // Handle PDOException
    write_log("PDO Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error.']);
} catch (Exception $e) {
    // Handle other exceptions
    write_log("Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An error occurred.']);
}
?>

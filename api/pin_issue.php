<?php
require_once('../dbconn.php');

$pdo = getPDOConnection();



try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $issue_number = $_POST['issue_number'];
        $user_id = $_POST['user_id'];
        //write_log("POSTED WITH {$issue_number} and ${user_id}");

        write_log("POSTED ".$issue_number );

        if (!empty($issue_number) && !empty($user_id)) {
            // Check if the issue is already pinned

            $stmt = $pdo->prepare("SELECT * FROM gh_pinned_issues WHERE user_id = :user_id AND gh_node_id = :issue_number and is_deleted = 'NO'  ");

            //write_log($stmt);


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
            
            echo json_encode(['success' => false, 'error' => 'Invalid input.']);
        }
    } else {

        echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    }
} catch (PDOException $e) {
    // Handle PDOException
    write_log("PDO Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error.']);
} catch (Exception $e) {
    // Handle other exceptions
    write_log("Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred.']);
}

?>

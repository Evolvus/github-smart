
<?php


//require '../vendor/autoload.php'; // Include Guzzle library
require_once(__DIR__ . '/../config/app.php');



function insertAuditData($pdo, $action, $startTime, $endTime) {
    try {
        $stmt = $pdo->prepare("INSERT INTO gh_audit (action, start_time, end_time) VALUES (:action, :start_time, :end_time)");
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':start_time', $startTime);
        $stmt->bindParam(':end_time', $endTime);
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        write_log("Database Error: " . $e->getMessage());
        return false;
    }
}
?>
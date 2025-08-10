
<?php


//require '../vendor/autoload.php'; // Include Guzzle library
require_once(__DIR__ . '/../config/app.php');

// Define write_log function if not already defined
if (!function_exists('write_log')) {
    function write_log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
        
        // Try to write to log file, but don't fail if we can't
        try {
            @file_put_contents(__DIR__ . '/../config/app.log', $logMessage, FILE_APPEND);
        } catch (Exception $e) {
            // Silently fail if we can't write to log file
        }
    }
}



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
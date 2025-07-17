<?php
session_name("Project");
session_start();
require_once('../../dbconn.php');
require_once('../config.php');

$pdo = getPDOConnection();

try {
    // Check if a term was provided
    $term = isset($_GET['term']) ? $_GET['term'] : '';

    if ($term !== '') {
        $query = "SELECT DISTINCT tag, color FROM gh_issue_tags WHERE tag LIKE :term";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':term', "%" . $term . "%", PDO::PARAM_STR);
    } else {
        $query = "SELECT DISTINCT tag, color FROM gh_issue_tags";
        $stmt = $pdo->prepare($query);
    }
    
    $stmt->execute();
    $labels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($labels);  
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>
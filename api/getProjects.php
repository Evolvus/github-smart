<?php
session_name("Project");
session_start();
require_once('../../dbconn.php');

require_once('../config.php');

$pdo = getPDOConnection();

try {
    // Set PDO to throw exceptions on error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

     // Query to get the top N latest issues
     $query = "SELECT * FROM gh_projects where 1=1 order by count_of_issues desc" ;

     $result = $pdo->query($query);
     $projects = $result->fetchAll(PDO::FETCH_ASSOC);

     echo json_encode($projects);

    } catch (PDOException $e) {
        // Handle database errors
        echo "Database Error: " . $e->getMessage();
    } catch (Exception $e) {
        // Handle other errors
        echo "Error: " . $e->getMessage();
    }


?>

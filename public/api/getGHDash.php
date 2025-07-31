<?php
use App\Utils\Database;
use App\Utils\Logger;

session_name("Project");
session_start();
require_once(__DIR__ . "/../../vendor/autoload.php");
require_once(__DIR__ . "/../../config/database.php");
require_once(__DIR__ . "/../../config/app.php");

$pdo = Database::getPDOConnection();

// Input validation function
function validateInput($input, $type = 'string') {
    switch ($type) {
        case 'int':
            return filter_var($input, FILTER_VALIDATE_INT) !== false ? (int)$input : null;
        case 'string':
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL);
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

try {
    // Set PDO to throw exceptions on error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check the GET parameter to determine the requested data
    $action = validateInput($_GET['action'] ?? '', 'string');

    switch ($action) {
        case 'total_count':
            handleTotalCount($pdo);
            break;
        case 'issues_over_time':
            handleIssuesOverTime($pdo);
            break;
        case 'unassigned_count':
            handleUnassignedCount($pdo);
            break;
        case 'latest_issues':
            handleLatestIssues($pdo);
            break;
        case 'by_project':
            handleIssuesByProject($pdo);
            break;
        case 'countbyasignee':
            handleCountByAssignee($pdo);
            break;
        case 'by_assignee':
            handleIssuesByAssignee($pdo);
            break;
        case 'by_and_tags':
            handleIssuesByAndTags($pdo);
            break;
        case 'by_or_tags':
            handleIssuesByOrTags($pdo);
            break;
        case 'assignee':
            handleAllAssignees($pdo);
            break;
        case 'last_retrieve':
            handleLastRetrieve($pdo);
            break;
        case 'by_tags':
            handleTags($pdo);
            break;
        case 'customer': // NEW CASE
            handleAllCustomers($pdo);
            break;
        case 'by_pins':
            handlePins($pdo);
            break;
        case 'by_customer': // NEW CASE for fetching issues by customer
            handleIssuesByCustomer($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(["error" => "Invalid action specified."]);
            exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    Logger::logError("Database Error: " . $e->getMessage());
    echo json_encode(["error" => "Database Error: " . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    Logger::logError("Error: " . $e->getMessage());
    echo json_encode(["error" => "Error: " . $e->getMessage()]);
}

function handleTotalCount($pdo) {
    $query = "SELECT COUNT(*) AS total_count FROM gh_issues";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($result);
}

function handleIssuesOverTime($pdo) {
    $query = "SELECT DATE_FORMAT(assigned_date, '%Y-%m') as labels, COUNT(*) as data
              FROM gh_issues
              GROUP BY DATE_FORMAT(assigned_date, '%Y-%m')
              ORDER BY labels";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $labels = array_column($result, 'labels');
    $data = array_column($result, 'data');
    
    echo json_encode(["labels" => $labels, "data" => $data]);
}

function handleUnassignedCount($pdo) {
    $query = "SELECT COUNT(*) AS total_count FROM gh_issues WHERE assignee = 'UNASSIGNED'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($result);
}

function handleLatestIssues($pdo) {
    $query = "SELECT * FROM gh_issues ORDER BY assigned_date DESC LIMIT 5";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result);
}

function handleIssuesByProject($pdo) {
    $project = validateInput($_GET['projectId'] ?? '', 'string');
    $query = "SELECT * FROM gh_issues WHERE 1=1";
    
    if ($project) {
        if ($project === "UNASSIGNED") {
            $query .= " AND gh_project IS NULL";
        } else {
            $query .= " AND gh_project = :project";
        }
    }
    
    $query .= " ORDER BY assigned_date DESC";
    $stmt = $pdo->prepare($query);
    
    if ($project && $project !== "UNASSIGNED") {
        $stmt->bindValue(':project', $project, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["data" => $result]);
}

function handleCountByAssignee($pdo) {
    $count = validateInput($_GET['count'] ?? 0, 'int');
    $query = "SELECT assignee, COUNT(*) AS issue_count FROM gh_issues WHERE assignee <> 'UNASSIGNED'
              GROUP BY assignee ORDER BY issue_count DESC";
    
    if ($count > 0) {
        $query .= " LIMIT :count";
    }
    
    $stmt = $pdo->prepare($query);
    
    if ($count > 0) {
        $stmt->bindValue(':count', $count, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result);
}

function handleIssuesByAssignee($pdo) {
    $assignee = validateInput($_GET['assignee'] ?? '', 'string');
    $query = "SELECT *, DATEDIFF(NOW(), assigned_date) AS aging FROM gh_issues WHERE gh_state = 'open'";
    
    if ($assignee) {
        $query .= " AND assignee = :assignee";
    }
    
    $query .= " ORDER BY assigned_date DESC";
    $stmt = $pdo->prepare($query);
    
    if ($assignee) {
        $stmt->bindValue(':assignee', $assignee, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["data" => $result]);
}

function handleIssuesByAndTags($pdo) {
    $tags = validateInput($_GET['tags'] ?? '', 'string');
    $tags = array_filter(explode(',', $tags));
    
    if (empty($tags)) {
        $query = "SELECT a.*, DATEDIFF(NOW(), assigned_date) AS aging,tag FROM gh_issues  a
            left join gh_issue_tags b on a.gh_id = b.gh_id
            ORDER BY assigned_date DESC";
        $stmt = $pdo->query($query);
    } else {
        $placeholders = implode(',', array_fill(0, count($tags), '?'));
        $query = "SELECT a.*, DATEDIFF(NOW(), a.assigned_date) AS aging,c.tag
                  FROM gh_issues a
                  INNER JOIN (
                      SELECT gh_id
                      FROM gh_issue_tags
                      WHERE tag IN ($placeholders)
                      GROUP BY gh_id
                      HAVING COUNT(DISTINCT tag) = ?
                  ) b ON a.gh_id = b.gh_id
                  left join gh_issue_tags c on a.gh_id = c.gh_id
                  ORDER BY a.assigned_date DESC";
        $stmt = $pdo->prepare($query);
        
        foreach ($tags as $index => $tag) {
            $stmt->bindValue($index + 1, $tag, PDO::PARAM_STR);
        }
        $stmt->bindValue(count($tags) + 1, count($tags), PDO::PARAM_INT);
        $stmt->execute();
    }
    
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["data" => $result]);
}

function handleAllAssignees($pdo) {
    $query = "SELECT assignee, COUNT(*) AS count_issue FROM gh_issues GROUP BY assignee ORDER BY count_issue DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result);
}

function handleLastRetrieve($pdo) {
    try {
        $query = "SELECT * FROM gh_audit WHERE action = 'RETRIEVE FROM GITHUB' ORDER BY end_time DESC LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result === false) {
            echo json_encode(null);
        } else {
            echo json_encode($result);
        }
    } catch (Exception $e) {
        Logger::logError("LastRetrieve Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "LastRetrieve Error: " . $e->getMessage()]);
    }
}

function handleIssuesByOrTags($pdo) {
    $tags = validateInput($_GET['tags'] ?? '', 'string');
    $tags = array_filter(explode(',', $tags));
    
    if (empty($tags)) {
        $query = "SELECT a.*, DATEDIFF(NOW(), assigned_date) AS aging,tag FROM gh_issues  a
            left join gh_issue_tags b on a.gh_node_id = b.gh_node_id
            ORDER BY assigned_date DESC";
        $stmt = $pdo->query($query);
    } else {
        $placeholders = implode(',', array_fill(0, count($tags), '?'));
        $query = "SELECT a.*, DATEDIFF(NOW(), a.assigned_date) AS aging,c.tag tag
                  FROM gh_issues a
                  left join gh_issue_tags c on a.gh_node_id = c.gh_node_id
                  where tag IN ($placeholders)
                  ORDER BY a.assigned_date DESC";


        $stmt = $pdo->prepare($query);
        
        foreach ($tags as $index => $tag) {
            $stmt->bindValue($index +1, $tag, PDO::PARAM_STR);
        }
        
        $stmt->execute();
    }
    
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["data" => $result]);
}
function handleTags($pdo) {
    $andTags = validateInput($_GET['and_tags'] ?? '', 'string');
    $andTags = explode(',', $andTags);
    $orTags = validateInput($_GET['or_tags'] ?? '', 'string');
    $orTags = explode(',', $orTags);
    $state = validateInput($_GET['state'] ?? 'all', 'string');
    $closedAtStart = validateInput($_GET['closed_at_start'] ?? null, 'string');
    $closedAtEnd = validateInput($_GET['closed_at_end'] ?? null, 'string');

    $whereClauses = [];
    $joinClauses = [];
    $params = [];

    if (!empty($andTags)) {
        $andPlaceholders = implode(',', array_fill(0, count($andTags), '?'));
        $joinClauses[] = "
            INNER JOIN (
                SELECT gh_node_id
                FROM gh_issue_tags
                WHERE tag IN ($andPlaceholders)
                GROUP BY gh_node_id
                HAVING COUNT(DISTINCT tag) = ?
            ) d ON a.gh_node_id = d.gh_node_id
        ";
        $params = array_merge($params, $andTags, [count($andTags)]);
    }

    if (!empty($orTags)) {
        $orPlaceholders = implode(',', array_fill(0, count($orTags), '?'));
        $joinClauses[] = "
            INNER JOIN (
                SELECT gh_node_id
                FROM gh_issue_tags
                WHERE tag IN ($orPlaceholders)
                GROUP BY gh_node_id
            ) e ON a.gh_node_id = e.gh_node_id
        ";
        $params = array_merge($params, $orTags);
    }

    if ($state !== 'all') {
        $whereClauses[] = "a.gh_state = ?";
        $params[] = $state;
    }

    if ($closedAtStart && $closedAtEnd) {
        $whereClauses[] = "((a.closed_at >= ? AND a.closed_at <= ?) OR a.closed_at IS NULL)";
        $params[] = $closedAtStart;
        $params[] = $closedAtEnd;
    }

    $whereClause = implode(' AND ', $whereClauses);
    if (empty($whereClause)) {
        $whereClause = '1=1'; // Handle case where no tags or state are selected
    }

    $joinClause = implode(' ', $joinClauses);

    $query = "
    SELECT 
        a.id, 
        a.gh_id_url, 
        a.gh_node_id, 
        a.gh_id, 
        a.issue_text, 
        a.assignee, 
        a.repo, 
        a.assigned_date, 
        DATEDIFF(NOW(), a.assigned_date) AS aging, 
        COALESCE(b.tags, '') AS tags, 
        CASE WHEN c.gh_node_id IS NULL THEN 'UNPIN' ELSE 'PIN' END AS pin_status, 
        a.last_updated_at, 
        a.gh_state state, 
        IFNULL(a.closed_at, 'NOT CLOSED') AS closed_at
    FROM 
        gh_issues a
    LEFT JOIN (
        SELECT 
            gh_node_id, 
            GROUP_CONCAT(tag ORDER BY tag SEPARATOR ', ') AS tags
        FROM 
            gh_issue_tags
        GROUP BY 
            gh_node_id
    ) b ON a.gh_node_id = b.gh_node_id
    LEFT JOIN (
        SELECT 
            gh_node_id
        FROM 
            gh_pinned_issues
        WHERE 
            is_deleted = 'NO'
    ) c ON a.gh_node_id = c.gh_node_id
    $joinClause
    WHERE $whereClause 
    ORDER BY 
        pin_status, a.assigned_date DESC;
    ";

    //write_log($query);
    $jsonData = json_encode($params);
    //write_log($jsonData);

    $stmt = $pdo->prepare($query);
    foreach ($params as $index => $param) {
        $stmt->bindValue($index + 1, $param, PDO::PARAM_STR);
    }

    $stmt->execute();
    $issues = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['data' => $issues]);
}

// Replace the handleAllCustomers function with the following:
function handleAllCustomers($pdo) {
    $query = "
        SELECT 
            c.tag AS customer, 
            (
                SELECT COUNT(*) 
                FROM gh_issue_tags it
                INNER JOIN gh_issues i ON i.gh_node_id = it.gh_node_id
                WHERE it.tag = c.tag AND i.gh_state = 'open'
            ) AS count_issue 
        FROM gh_customer c
        ORDER BY count_issue DESC";
        
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result);
}

function handlePins($pdo) {
    $query = "
        SELECT a.id,a.gh_id_url,a.gh_id, a.gh_node_id , a.issue_text, a.assignee, a.repo, a.assigned_date, DATEDIFF(NOW(), 
        a.assigned_date) AS aging, GROUP_CONCAT(b.tag ORDER BY b.tag SEPARATOR ', ') as tags, 
        case when c.gh_node_id is null then 'UNPIN' else 'PIN' end pin_status, gh_json->>'$.body' AS body,c.bucket
        FROM gh_issues a
        inner join gh_pinned_issues c on a.gh_node_id = c.gh_node_id and is_deleted = 'NO'
        LEFT JOIN gh_issue_tags b ON a.gh_node_id = b.gh_node_id
        GROUP BY a.id,a.gh_id_url,a.gh_id, a.issue_text, a.assignee, a.repo, a.assigned_date,
        case when c.gh_node_id is null then 'UNPIN' else 'PIN' end , gh_json->>'$.body',bucket
        ORDER BY pin_status , a.assigned_date DESC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $issues = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['data' => $issues]);
}

// Update handleIssuesByCustomer function to ensure it only fetches for known customer tags:
function handleIssuesByCustomer($pdo) {
    $customer = validateInput($_GET['customer'] ?? '', 'string');
    if (!$customer) {
        echo json_encode(["data" => []]);
        return;
    }
    $query = "SELECT i.*, DATEDIFF(NOW(), i.assigned_date) AS aging
              FROM gh_issues i
              INNER JOIN gh_issue_tags t ON i.gh_node_id = t.gh_node_id
              INNER JOIN gh_customer c ON t.tag = c.tag
              WHERE t.tag = :customer AND i.gh_state = 'open'
              ORDER BY i.assigned_date ASC";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':customer', $customer, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["data" => $result]);
}
?>

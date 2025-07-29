<?php
session_name("Project");
session_start();
require_once('../dbconn.php');
require_once('../config.php');

$pdo = getPDOConnection();

try {
    // Set PDO to throw exceptions on error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check the GET parameter to determine the requested data
    $action = isset($_GET['action']) ? $_GET['action'] : '';

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
            echo json_encode(["error" => "Invalid action specified."]);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => "Database Error: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["error" => "Error: " . $e->getMessage()]);
}

function handleTotalCount($pdo) {
    $query = "SELECT COUNT(*) AS total_count FROM gh_issues";
    $result = $pdo->query($query)->fetch(PDO::FETCH_ASSOC);
    echo json_encode($result);
}

function handleIssuesOverTime($pdo) {
    $query = "SELECT DATE_FORMAT(assigned_date, '%Y-%m') as labels, COUNT(*) as data
              FROM gh_issues
              GROUP BY DATE_FORMAT(assigned_date, '%Y-%m')
              ORDER BY labels";
    $result = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
    
    $labels = array_column($result, 'labels');
    $data = array_column($result, 'data');
    
    echo json_encode(["labels" => $labels, "data" => $data]);
}

function handleUnassignedCount($pdo) {
    $query = "SELECT COUNT(*) AS total_count FROM gh_issues WHERE assignee = 'UNASSIGNED'";
    $result = $pdo->query($query)->fetch(PDO::FETCH_ASSOC);
    echo json_encode($result);
}

function handleLatestIssues($pdo) {
    $query = "SELECT * FROM gh_issues ORDER BY assigned_date DESC LIMIT 5";
    $result = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result);
}

function handleIssuesByProject($pdo) {
    $project = isset($_GET['projectId']) ? $_GET['projectId'] : '';
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
    $count = isset($_GET['count']) ? (int)$_GET['count'] : 0;
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
    $assignee = isset($_GET['assignee']) ? $_GET['assignee'] : '';
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
    $tags = isset($_GET['tags']) ? array_filter(explode(',', $_GET['tags'])) : [];
    
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
    $result = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result);
}

function handleLastRetrieve($pdo) {
    $query = "SELECT * FROM gh_audit WHERE action = 'RETRIEVE FROM GITHUB' ORDER BY end_time DESC LIMIT 1";
    $result = $pdo->query($query)->fetch(PDO::FETCH_ASSOC);
    echo json_encode($result);
}

function handleIssuesByOrTags($pdo) {
    $tags = isset($_GET['tags']) ? array_filter(explode(',', $_GET['tags'])) : [];
    
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
    $andTags = isset($_GET['and_tags']) ? explode(',', $_GET['and_tags']) : [];
    $orTags = isset($_GET['or_tags']) ? explode(',', $_GET['or_tags']) : [];
    $state = isset($_GET['state']) ? $_GET['state'] : 'all';
    $closedAtStart = isset($_GET['closed_at_start']) ? $_GET['closed_at_start'] : null;
    $closedAtEnd = isset($_GET['closed_at_end']) ? $_GET['closed_at_end'] : null;

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
        
    $result = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
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
    $customer = isset($_GET['customer']) ? $_GET['customer'] : '';
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

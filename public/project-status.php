<?php
session_name("Project");
session_start();
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../config/app.php');
require_once(__DIR__ . '/utilities_project.php');

$pdo = getPDOConnection();
date_default_timezone_set("Asia/Kolkata");

// Get project status data
function getProjectStatusData($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                ips.project_id,
                ips.project_title,
                ips.project_url,
                ips.status_field_name,
                ips.status_value,
                ips.status_color,
                COUNT(*) as issue_count,
                GROUP_CONCAT(DISTINCT i.assignee) as assignees
            FROM gh_issue_project_status ips
            LEFT JOIN gh_issues i ON ips.gh_node_id = i.gh_node_id
            GROUP BY ips.project_id, ips.status_field_name, ips.status_value
            ORDER BY ips.project_title, ips.status_field_name, ips.status_value
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

$projectStatusData = getProjectStatusData($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Board Status - GitHub Smart</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-card {
            border-left: 4px solid #007bff;
            margin-bottom: 15px;
        }
        .status-value {
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 4px;
            color: white;
        }
        .project-section {
            background: #f8f9fa;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
        }
        .project-title {
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .issue-count {
            font-size: 1.2em;
            font-weight: bold;
            color: #007bff;
        }
        .assignee-list {
            font-size: 0.9em;
            color: #6c757d;
        }
        .refresh-btn {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Project Board Status Dashboard</h1>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <button class="btn btn-primary refresh-btn" onclick="refreshProjectStatus()">
                            <i class="fas fa-sync-alt"></i> Refresh Project Status
                        </button>
                        <button class="btn btn-success refresh-btn" onclick="importProjectStatus()">
                            <i class="fas fa-download"></i> Import from GitHub
                        </button>
                    </div>
                    <div class="col-md-6 text-right">
                        <span class="badge badge-info">Last Updated: <?php echo date('Y-m-d H:i:s'); ?></span>
                    </div>
                </div>

                <?php if (empty($projectStatusData)): ?>
                    <div class="alert alert-info">
                        <h4>No Project Board Status Data Available</h4>
                        <p>Click "Import from GitHub" to fetch the latest project board status for all issues.</p>
                    </div>
                <?php else: ?>
                    <?php
                    $projects = [];
                    foreach ($projectStatusData as $row) {
                        $projectId = $row['project_id'];
                        if (!isset($projects[$projectId])) {
                            $projects[$projectId] = [
                                'title' => $row['project_title'],
                                'url' => $row['project_url'],
                                'statuses' => []
                            ];
                        }
                        $projects[$projectId]['statuses'][] = $row;
                    }
                    ?>

                    <?php foreach ($projects as $projectId => $project): ?>
                        <div class="project-section">
                            <h3 class="project-title">
                                <a href="<?php echo htmlspecialchars($project['url']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($project['title']); ?>
                                </a>
                            </h3>
                            
                            <div class="row">
                                <?php foreach ($project['statuses'] as $status): ?>
                                    <div class="col-md-4 col-lg-3">
                                        <div class="card status-card">
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($status['status_field_name']); ?></h6>
                                                <div class="status-value" style="background-color: <?php echo $status['status_color'] ? '#' . $status['status_color'] : '#6c757d'; ?>">
                                                    <?php echo htmlspecialchars($status['status_value']); ?>
                                                </div>
                                                <div class="issue-count mt-2">
                                                    <?php echo $status['issue_count']; ?> issues
                                                </div>
                                                <?php if (!empty($status['assignees'])): ?>
                                                    <div class="assignee-list mt-1">
                                                        Assignees: <?php echo htmlspecialchars($status['assignees']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function refreshProjectStatus() {
            location.reload();
        }

        function importProjectStatus() {
            if (confirm('This will fetch the latest project board status from GitHub. Continue?')) {
                $.ajax({
                    url: 'api/getProjectBoardStatus.php',
                    method: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            alert('Project board status imported successfully!\n\n' +
                                  'Issues processed: ' + response.issues_processed + '\n' +
                                  'Errors: ' + response.errors);
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Failed to import project board status. Please try again.');
                    }
                });
            }
        }

        // Auto-refresh every 5 minutes
        setInterval(function() {
            // Only refresh if user is active
            if (!document.hidden) {
                refreshProjectStatus();
            }
        }, 300000); // 5 minutes
    </script>
</body>
</html>

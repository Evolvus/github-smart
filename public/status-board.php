<?php
$permission = "VIEW";
require_once('head.php');
?>

<style>
.status-board {
    display: flex;
    gap: 20px;
    padding: 20px;
    overflow-x: auto;
    min-height: calc(100vh - 200px);
}

.status-column {
    min-width: 300px;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.status-header {
    font-weight: bold;
    font-size: 18px;
    margin-bottom: 15px;
    padding: 10px;
    border-radius: 5px;
    text-align: center;
}

.status-backlog {
    background: #e2e3e5;
    color: #383d41;
    border-left: 4px solid #6c757d;
}

.status-ready {
    background: #d1ecf1;
    color: #0c5460;
    border-left: 4px solid #17a2b8;
}

.status-in-progress {
    background: #fff3cd;
    color: #856404;
    border-left: 4px solid #ffc107;
}

.status-review {
    background: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

.status-done {
    background: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
}

.issue-card {
    background: white;
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-left: 4px solid #007bff;
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
}

.issue-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.issue-title {
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
    line-height: 1.4;
}

.issue-meta {
    font-size: 12px;
    color: #666;
    margin-bottom: 8px;
}

.issue-assignee {
    font-size: 11px;
    color: #007bff;
    font-weight: 500;
}

.issue-repo {
    font-size: 11px;
    color: #6c757d;
    font-style: italic;
}

.issue-id {
    font-size: 11px;
    color: #999;
}

.project-selector {
    background: white;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.project-selector select {
    max-width: 300px;
}

.loading {
    text-align: center;
    padding: 40px;
    color: #666;
}

.empty-state {
    text-align: center;
    padding: 40px;
    color: #999;
    font-style: italic;
}

.card-count {
    background: rgba(0,0,0,0.1);
    border-radius: 12px;
    padding: 2px 8px;
    font-size: 12px;
    margin-left: 8px;
}

@media (max-width: 768px) {
    .status-board {
        flex-direction: column;
        gap: 15px;
    }
    
    .status-column {
        min-width: auto;
    }
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h4 class="my-4">Status Board - Project Issues</h4>
            
            <!-- Project Selector -->
            <div class="project-selector">
                <div class="row">
                    <div class="col-md-6">
                        <label for="project-select" class="form-label">Select Project:</label>
                        <select id="project-select" class="form-select">
                            <option value="">Loading projects...</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Actions:</label>
                        <button id="refresh-btn" class="btn btn-secondary">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                <div id="project-info" class="mt-3" style="display: none;">
                    <div class="alert alert-info">
                        <h6 class="alert-heading">Project Information</h6>
                        <div id="project-details"></div>
                    </div>
                </div>
            </div>
            
            <!-- Status Board -->
            <div id="status-board" class="status-board">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading status board...
                </div>
            </div>
            
            <!-- Status Summary -->
            <div id="status-summary" class="mt-3" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Status Distribution</h6>
                        <small id="status-note" class="text-muted"></small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2 text-center">
                                <div class="badge bg-secondary fs-6">Backlog</div>
                                <div id="backlog-count" class="fw-bold">0</div>
                            </div>
                            <div class="col-md-2 text-center">
                                <div class="badge bg-info fs-6">Ready</div>
                                <div id="ready-count" class="fw-bold">0</div>
                            </div>
                            <div class="col-md-2 text-center">
                                <div class="badge bg-warning fs-6">In Progress</div>
                                <div id="in-progress-count" class="fw-bold">0</div>
                            </div>
                            <div class="col-md-2 text-center">
                                <div class="badge bg-danger fs-6">Review</div>
                                <div id="review-count" class="fw-bold">0</div>
                            </div>
                            <div class="col-md-2 text-center">
                                <div class="badge bg-success fs-6">Done</div>
                                <div id="done-count" class="fw-bold">0</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentProject = '';
let allIssues = [];

document.addEventListener('DOMContentLoaded', function() {
    loadProjects();
    setupEventListeners();
});

function setupEventListeners() {
    // Project selector change
    document.getElementById('project-select').addEventListener('change', function() {
        currentProject = this.value;
        loadStatusBoard();
    });
    
    // Refresh button
    document.getElementById('refresh-btn').addEventListener('click', function() {
        if (currentProject) {
            loadStatusBoard();
        }
    });
}

function loadProjects() {
    fetch('api/getProjects.php')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('project-select');
            select.innerHTML = '<option value="">Select a project...</option>';
            
            data.forEach(project => {
                if (project.closed != 1) {
                    const option = document.createElement('option');
                    option.value = project.gh_id;
                    option.textContent = `${project.title} (${project.count_of_issues})`;
                    select.appendChild(option);
                }
            });
        })
        .catch(error => {
            console.error('Error loading projects:', error);
            document.getElementById('project-select').innerHTML = '<option value="">Error loading projects</option>';
        });
}

function loadStatusBoard() {
    if (!currentProject) {
        document.getElementById('status-board').innerHTML = '<div class="empty-state">Please select a project to view the status board.</div>';
        document.getElementById('project-info').style.display = 'none';
        return;
    }
    
    document.getElementById('status-board').innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading project status board...</div>';
    
    // Get project board status data
    fetch(`api/getProjectStatus.php?action=all`)
        .then(response => response.json())
        .then(response => {
            console.log('Project board status data:', response);
            
            // Extract the data array from the response
            const data = response.data || [];
            
            // Store all project data globally
            window.allProjectData = data;
            
            // Filter data for the selected project
            const projectData = data.filter(item => item.project_id === currentProject);
            
            if (projectData.length === 0) {
                document.getElementById('status-board').innerHTML = '<div class="empty-state">No project board status data available for this project. Please import data from GitHub first.</div>';
                return;
            }
            
            // Group issues by their actual project board status
            allIssues = groupIssuesByProjectBoardStatus(projectData);
            console.log('Processed issues:', allIssues.length);
            console.log('Sample issue:', allIssues[0]);
            
            // Show project information
            if (projectData.length > 0) {
                showProjectInfoFromStatusData(projectData[0]);
            }
            
            renderStatusBoard();
        })
        .catch(error => {
            console.error('Error loading status board:', error);
            document.getElementById('status-board').innerHTML = '<div class="empty-state">Error loading project status. Please try again.</div>';
        });
}

function renderStatusBoard() {
    const board = document.getElementById('status-board');
    
    // Get unique status values from the actual project board data
    const statusValues = [...new Set(allIssues.map(issue => issue.status_value))];
    
    // Create dynamic status columns based on actual data
    const statusColumns = statusValues.map(statusValue => {
        const statusId = statusValue.toLowerCase().replace(/\s+/g, '-');
        let statusClass = 'status-backlog'; // default
        let icon = 'fas fa-list'; // default
        
        // Map status values to CSS classes and icons
        switch (statusValue.toLowerCase()) {
            case 'backlog':
                statusClass = 'status-backlog';
                icon = 'fas fa-list';
                break;
            case 'ready':
                statusClass = 'status-ready';
                icon = 'fas fa-check-circle';
                break;
            case 'in progress':
                statusClass = 'status-in-progress';
                icon = 'fas fa-play-circle';
                break;
            case 'in review':
                statusClass = 'status-review';
                icon = 'fas fa-search';
                break;
            case 'done':
                statusClass = 'status-done';
                icon = 'fas fa-flag-checkered';
                break;
            default:
                statusClass = 'status-backlog';
                icon = 'fas fa-tag';
        }
        
        return {
            id: statusId,
            title: statusValue,
            class: statusClass,
            icon: icon
        };
    });
    
    // Group issues by their actual project board status
    const groupedIssues = {};
    allIssues.forEach(issue => {
        const statusId = issue.status_value.toLowerCase().replace(/\s+/g, '-');
        if (!groupedIssues[statusId]) {
            groupedIssues[statusId] = [];
        }
        groupedIssues[statusId].push(issue);
    });
    
    let boardHTML = '';
    
    statusColumns.forEach(column => {
        const issues = groupedIssues[column.id] || [];
        
        boardHTML += `
            <div class="status-column">
                <div class="status-header ${column.class}">
                    <i class="${column.icon}"></i> ${column.title} <span class="card-count">${issues.length}</span>
                </div>
                <div id="${column.id}-column">
                    ${issues.map(issue => createIssueCard(issue)).join('')}
                </div>
            </div>
        `;
    });
    
    board.innerHTML = boardHTML;
    
    // Update status summary
    updateStatusSummary(groupedIssues);
    
    // Add click handlers to cards
    document.querySelectorAll('.issue-card').forEach(card => {
        card.addEventListener('click', function() {
            const issueUrl = this.getAttribute('data-url');
            if (issueUrl) {
                window.open(issueUrl, '_blank');
            }
        });
    });
}

function mergeProjectStatusWithIssues(issues, projectStatus) {
    console.log('Merging project status with issues. Project status keys:', Object.keys(projectStatus));
    
    // If no project status data is available, use intelligent mapping
    if (Object.keys(projectStatus).length === 0) {
        console.log('No project status data available, using intelligent mapping');
        return intelligentStatusMapping(issues);
    }
    
    // Create a map of issue numbers to their project status
    const statusMap = {};
    
    // Process project status data
    Object.keys(projectStatus).forEach(status => {
        projectStatus[status].forEach(item => {
            statusMap[item.number] = status;
        });
    });
    
    console.log('Status map created:', statusMap);
    console.log('Available issues:', issues.map(i => i.gh_id));
    
    // Merge status with issues
    return issues.map(issue => {
        const projectStatus = statusMap[issue.gh_id] || 'backlog'; // default to backlog if no status found
        console.log(`Issue #${issue.gh_id} mapped to status: ${projectStatus}`);
        return {
            ...issue,
            projectStatus: projectStatus
        };
    });
}

function intelligentStatusMapping(issues) {
    console.log('Using intelligent status mapping for', issues.length, 'issues');
    
    const result = issues.map(issue => {
        let projectStatus = 'backlog'; // default
        
        // Map based on issue properties and GitHub project status options
        const hasAssignee = issue.assignee && issue.assignee !== 'Unassigned';
        const hasLabels = issue.labels && issue.labels.length > 0;
        const isHighPriority = hasLabels && issue.labels.some(label => 
            label.toLowerCase().includes('priority') || 
            label.toLowerCase().includes('urgent') ||
            label.toLowerCase().includes('critical')
        );
        
        if (issue.gh_state === 'closed') {
            projectStatus = 'done';
        } else if (hasAssignee) {
            // Assigned issues - prioritize based on labels and assignee
            if (isHighPriority) {
                projectStatus = 'in-progress';
            } else if (hasLabels) {
                projectStatus = 'ready';
            } else {
                projectStatus = 'in-progress';
            }
        } else {
            // Unassigned issues
            if (isHighPriority) {
                projectStatus = 'ready';
            } else {
                projectStatus = 'backlog';
            }
        }
        
        console.log(`Issue #${issue.gh_id} (${issue.issue_text.substring(0, 30)}...) -> ${projectStatus} (assignee: ${issue.assignee || 'none'}, labels: ${issue.labels?.join(', ') || 'none'})`);
        
        return {
            ...issue,
            projectStatus: projectStatus
        };
    });
    
    console.log('Intelligent mapping distribution:', {
        backlog: result.filter(i => i.projectStatus === 'backlog').length,
        ready: result.filter(i => i.projectStatus === 'ready').length,
        'in-progress': result.filter(i => i.projectStatus === 'in-progress').length,
        review: result.filter(i => i.projectStatus === 'review').length,
        done: result.filter(i => i.projectStatus === 'done').length
    });
    
    return result;
}

function updateStatusSummary(groupedIssues) {
    document.getElementById('status-summary').style.display = 'block';
    
    // Update the summary with actual status values
    const statusSummary = document.getElementById('status-summary');
    const summaryBody = statusSummary.querySelector('.card-body');
    
    let summaryHTML = '<div class="row">';
    let totalIssues = 0;
    
    Object.keys(groupedIssues).forEach(statusId => {
        const count = groupedIssues[statusId].length;
        totalIssues += count;
        const statusName = groupedIssues[statusId][0]?.status_value || statusId.replace(/-/g, ' ');
        
        summaryHTML += `
            <div class="col-md-2 text-center">
                <div class="badge bg-secondary fs-6">${statusName}</div>
                <div class="fw-bold">${count}</div>
            </div>
        `;
    });
    
    summaryHTML += '</div>';
    summaryBody.innerHTML = summaryHTML;
    
    // Show note about data source
    const note = `Using actual GitHub ProjectsV2 status data (${totalIssues} issues)`;
    document.getElementById('status-note').textContent = note;
}

function groupIssuesByProjectStatus(issues) {
    const grouped = {
        'backlog': [],
        'ready': [],
        'in-progress': [],
        'review': [],
        'done': []
    };
    
    issues.forEach(issue => {
        const status = issue.projectStatus || 'backlog';
        if (grouped[status]) {
            grouped[status].push(issue);
        } else {
            grouped.backlog.push(issue); // fallback
        }
    });
    
    return grouped;
}

function showProjectInfo(project) {
    const projectInfo = document.getElementById('project-info');
    const projectDetails = document.getElementById('project-details');
    
    let detailsHTML = `
        <div class="row">
            <div class="col-md-6">
                <strong>Project:</strong> ${project.title}<br>
                <strong>Project Number:</strong> ${project.number}<br>
                <strong>Status Options:</strong> ${project.statusOptions?.length || 0} available
            </div>
            <div class="col-md-6">
                <strong>Total Items:</strong> ${Object.values(project.itemsByStatus).flat().length}<br>
                <strong>Status Distribution:</strong><br>
    `;
    
    // Show status distribution
    Object.keys(project.itemsByStatus).forEach(status => {
        const count = project.itemsByStatus[status].length;
        if (count > 0) {
            detailsHTML += `&nbsp;&nbsp;&nbsp;&nbsp;• ${status}: ${count}<br>`;
        }
    });
    
    detailsHTML += `
            </div>
        </div>
    `;
    
    projectDetails.innerHTML = detailsHTML;
    projectInfo.style.display = 'block';
}

function groupIssuesByProjectBoardStatus(projectData) {
    const issues = [];
    
    projectData.forEach(item => {
        // Only process Status field items (not Title or other fields)
        if (item.status_field_name === 'Status') {
            const issue = {
                gh_id: item.gh_id,
                issue_text: item.issue_text || 'Unknown Issue',
                assignee: item.assignee || 'Unassigned',
                gh_state: item.gh_state || 'open',
                projectStatus: item.status_value.toLowerCase().replace(/\s+/g, '-'),
                status_value: item.status_value,
                status_color: item.status_color,
                project_title: item.project_title,
                project_url: item.project_url,
                gh_id_url: `https://github.com/issues/${item.gh_id}`
            };
            issues.push(issue);
        }
    });
    
    return issues;
}

function showProjectInfoFromStatusData(projectData) {
    const projectInfo = document.getElementById('project-info');
    const projectDetails = document.getElementById('project-details');
    
    // Group by status to get counts
    const statusCounts = {};
    const allProjectData = window.allProjectData || [];
    
    allProjectData.forEach(item => {
        if (item.status_field_name === 'Status') {
            const status = item.status_value;
            statusCounts[status] = (statusCounts[status] || 0) + 1;
        }
    });
    
    let detailsHTML = `
        <div class="row">
            <div class="col-md-6">
                <strong>Project:</strong> ${projectData.project_title}<br>
                <strong>Project URL:</strong> <a href="${projectData.project_url}" target="_blank">View on GitHub</a><br>
                <strong>Data Source:</strong> GitHub ProjectsV2
            </div>
            <div class="col-md-6">
                <strong>Total Items:</strong> ${Object.values(statusCounts).reduce((a, b) => a + b, 0)}<br>
                <strong>Status Distribution:</strong><br>
    `;
    
    // Show status distribution
    Object.keys(statusCounts).forEach(status => {
        const count = statusCounts[status];
        detailsHTML += `&nbsp;&nbsp;&nbsp;&nbsp;• ${status}: ${count}<br>`;
    });
    
    detailsHTML += `
            </div>
        </div>
    `;
    
    projectDetails.innerHTML = detailsHTML;
    projectInfo.style.display = 'block';
}

function createIssueCard(issue) {
    const assignee = issue.assignee || 'Unassigned';
    const statusClass = issue.gh_state === 'open' ? 'border-success' : 'border-danger';
    const statusColor = issue.status_color ? `#${issue.status_color}` : '#6c757d';
    
    return `
        <div class="issue-card ${statusClass}" data-url="${issue.gh_id_url}">
            <div class="issue-title">${issue.issue_text}</div>
            <div class="issue-meta">
                <div class="issue-assignee">
                    <i class="fas fa-user"></i> ${assignee}
                </div>
                <div class="issue-id">
                    <i class="fas fa-hashtag"></i> #${issue.gh_id}
                </div>
                <div class="issue-status" style="color: ${statusColor}; font-weight: bold;">
                    <i class="fas fa-tag"></i> ${issue.status_value}
                </div>
            </div>
        </div>
    `;
}

// Auto-refresh every 5 minutes
setInterval(() => {
    if (currentProject) {
        loadStatusBoard();
    }
}, 300000);
</script>

<?php
require_once('bodyend.php');
?> 
<?php
$permission = "VIEW";
require_once('head.php');
?>
<!-- Button and "Last retrieved" container -->
<div class="row mt-2 mb-2 clearfix">
    <!-- Button and spinner -->
    <div class="clearfix">
        <button id="retrieve" class="btn btn-primary float-end">
            <span id="spinner" class="spinner-grow spinner-grow-sm" role="status" style="display: none;"></span>
            <span id="button-text">Retrieve GitHub Issues</span>
        </button>
    </div>
</div>

<!-- Dashboard components -->
<div class="row">
    <div class="col">
        <div class="row mt-2 mb-2">
            <div class="col">
                <div class="card">
                    <div class="card-body shadow bg-white rounded">
                        <h5 class="card-title">Open</h5>
                        <p class="card-text">
                            <span id="total-issues-badge" class="badge bg-primary">0</span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card">
                    <div class="card-body shadow bg-white rounded">
                        <h5 class="card-title">Unassigned</h5>
                        <p class="card-text">
                            <span id="unassigned-issues-badge" class="badge bg-primary">0</span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card">
                    <div class="card-body shadow bg-white rounded">
                        <h5 class="card-title">Last Retrieved</h5>
                        <p class="card-text">
                            <span id="last-retrieved-badge" class="badge bg-primary">0</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-2 mb-2">
            <div class="col">
                <div class="card">
                    <div class="card-body shadow bg-white rounded">
                        <h5 class="card-title">Top 5 Counts by Assignee</h5>
                        <canvas id="assignee-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card">
            <div class="card-body shadow bg-white rounded">
                <h5 class="card-title">Latest Issues</h5>
                <ul class="list-group" id="latest-issues">
                    <!-- Latest issues will be displayed here -->
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4 mb-5 shadow bg-white rounded">
    <canvas id="issues-over-time-chart"></canvas>
</div>

</div> <!-- container end -->

<script>
    document.addEventListener("DOMContentLoaded", function () {
        try {
            retrieveData();
            const retrieveButton = document.getElementById('retrieve');
            if (retrieveButton) {
                retrieveButton.addEventListener("click", retrieveIssuesFromGithub);
            }
        } catch (error) {
            console.error('Error during initialization:', error);
        }
    });

    function retrieveData() {
        try {
            retrieveTotalIssuesCount();
            retrieveTop5ByAssignee();
            retrieveLatestIssues();
            retrieveIssuesOverTime();
            retrieveLastRetrieved();
        } catch (error) {
            console.error('Error in retrieveData:', error);
        }
    }

    function retrieveIssuesFromGithub() {
        const retrieveButton = document.getElementById('retrieve');
        const buttonText = document.getElementById('button-text');
        const spinner = document.getElementById('spinner');

        retrieveButton.disabled = true;
        spinner.style.display = 'inline-block';
        buttonText.textContent = 'Retrieving...';

        fetch('api/getGHIssues.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(''),
        })
        .then(function(response) {
            if (response.ok) {
                return response.json();
            } else {
                return Promise.reject('Error: Unable to make the API request.');
            }
        })
        .then(function(data) {
            spinner.style.display = 'none';
            buttonText.textContent = 'Retrieve GitHub Issues';
            retrieveButton.disabled = false;
            
            // Check if the response indicates an error
            if (data && data.status === 'error') {
                console.warn('GitHub API Error:', data.message);
                // Show a user-friendly message
                alert('GitHub Integration: ' + data.message);
                return;
            }
            
            // Check if the response indicates success
            if (data && data.status === 'success') {
                // Show a success message
                alert('GitHub Integration: ' + data.message);
            }
            
            // Refresh the data regardless of success/error
            retrieveData();
        })
        .catch(function(error) {
            console.error(error);
            spinner.style.display = 'none';
            buttonText.textContent = 'Retrieve GitHub Issues';
            retrieveButton.disabled = false;
        });
    }

    function retrieveTotalIssuesCount() {
        fetch('api/getGHDash.php?action=total_count', { method: 'GET', headers: { 'Accept': 'application/json' } })
        .then(function(response) {
            if (response.ok) {
                return response.json();
            } else {
                return Promise.reject('Error: Unable to retrieve "Total Issues" count.');
            }
        })
        .then(function(response) {
            document.getElementById('total-issues-badge').textContent = response.total_count;
        })
        .catch(console.error);

        fetch('api/getGHDash.php?action=unassigned_count', { method: 'GET', headers: { 'Accept': 'application/json' } })
        .then(function(response) {
            if (response.ok) {
                return response.json();
            } else {
                return Promise.reject('Error: Unable to retrieve "Total Issues" count.');
            }
        })
        .then(function(response) {
            document.getElementById('unassigned-issues-badge').textContent = response.total_count;
        })
        .catch(console.error);
    }

    function retrieveTop5ByAssignee() {
        fetch('api/getGHDash.php?action=countbyasignee&count=5', { method: 'GET', headers: { 'Accept': 'application/json' } })
        .then(function(response) {
            if (response.ok) {
                return response.json();
            } else {
                return Promise.reject('Error: Unable to retrieve data from "Top 5 by Assignee" API.');
            }
        })
        .then(function(data) {
            updateAssigneeChart(data);
        })
        .catch(console.error)
        .finally(function() {
            document.getElementById('spinner').style.display = 'none';
            document.getElementById('retrieve').disabled = false;
        });
    }

    function updateAssigneeChart(data) {
        const labels = data.map(item => item.assignee);
        const counts = data.map(item => item.issue_count);

        const ctx = document.getElementById('assignee-chart').getContext('2d');
        const chartStatus = Chart.getChart("assignee-chart");
        if (chartStatus) chartStatus.destroy();

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Count of Issues',
                    data: counts,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    function retrieveLatestIssues() {
        fetch('api/getGHDash.php?action=latest_issues')
        .then(function(response) {
            console.log('Latest Issues - Response OK, parsing JSON...');
            return response.json();
        })
        .then(function(issues) {
            updateLatestIssues(issues);
        })
        .catch(console.error);
    }

    function updateLatestIssues(issues) {
        const latestIssuesList = document.getElementById('latest-issues');
        latestIssuesList.innerHTML = '';

        if (Array.isArray(issues)) {
            const table = document.createElement('table');
            table.classList.add('table', 'table-striped', 'table-hover', 'table-sm');

            const thead = document.createElement('thead');
            const headerRow = document.createElement('tr');
            ['ID', 'Text', 'Repo', 'Assignee'].forEach(headerText => {
                const th = document.createElement('th');
                th.textContent = headerText;
                headerRow.appendChild(th);
            });

            thead.appendChild(headerRow);
            table.appendChild(thead);

            const tbody = document.createElement('tbody');
            issues.forEach(issue => {
                const row = document.createElement('tr');

                const idCell = document.createElement('td');
                idCell.textContent = issue.gh_id;

                const textCell = document.createElement('td');
                const textLink = document.createElement('a');
                textLink.href = issue.gh_id_url;
                textLink.textContent = issue.issue_text;
                textCell.appendChild(textLink);

                const repoCell = document.createElement('td');
                repoCell.textContent = issue.repo;

                const assigneeCell = document.createElement('td');
                assigneeCell.textContent = issue.assignee;

                row.append(idCell, textCell, repoCell, assigneeCell);
                tbody.appendChild(row);
            });

            table.appendChild(tbody);
            latestIssuesList.appendChild(table);
        }
    }

    function retrieveIssuesOverTime() {
        fetch('api/getGHDash.php?action=issues_over_time', { method: 'GET', headers: { 'Accept': 'application/json' } })
        .then(function(response) {
            if (response.ok) {
                return response.json();
            } else {
                return Promise.reject('Error: Unable to retrieve data for issues over time.');
            }
        })
        .then(function(data) {
            const ctx = document.getElementById('issues-over-time-chart').getContext('2d');
            const chartStatus = Chart.getChart("issues-over-time-chart");
            if (chartStatus) chartStatus.destroy();

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Issues Raised Over Time',
                        data: data.data,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                        fill: false
                    }]
                },
                options: {
                    scales: {
                        x: { type: 'time', time: { unit: 'month' } }
                    }
                }
            });
        })
        .catch(console.error);
    }

    function retrieveLastRetrieved() {
        fetch('api/getGHDash.php?action=last_retrieve', { method: 'GET', headers: { 'Accept': 'application/json' } })
        .then(function(response) {
            if (response.ok) {
                return response.json();
            } else {
                return Promise.reject('Error: Unable to retrieve "Last retrieved" information.');
            }
        })
        .then(function(response) {
            if (response.end_time) {
                const [year, month, day, hour, minute, second] = response.end_time.split(/[- :]/).map(Number);
                const dateObject = new Date(year, month - 1, day, hour, minute, second);
                const now = new Date();
                const indianDateTime = new Date(now.toLocaleString("en-US", { timeZone: 'Asia/Kolkata' }));
                const diffMinutes = Math.round((indianDateTime - dateObject) / (1000 * 60));

                if (diffMinutes < 100) {
                    document.getElementById('last-retrieved-badge').textContent = diffMinutes + ' Minute(s) ago';
                } else {
                    document.getElementById('last-retrieved-badge').textContent = Math.round(diffMinutes / 60) + ' Hour(s) ago';
                }
            } else {
                document.getElementById('last-retrieved-badge').textContent = 'Unknown';
            }
        })
        .catch(function(error) {
            document.getElementById('last-retrieved-badge').textContent = 'N/A';
            console.error(error);
        });
    }
</script>

<?php
require_once('bodyend.php');
?>

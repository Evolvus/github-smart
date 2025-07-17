<?php
$permission = "VIEW";
require_once('head.php');
?>

<h4 class="my-4">Pinned Issues - Paycorp</h4>

<div class="container mt-5 pad-background">
    <div class="row">
        <div class="col-md-6">
            <h5>Pinned Issues</h5>
            <div class="row dropzone" id="pinned-issues-container" ondrop="drop(event)" ondragover="allowDrop(event)">
                <!-- Pinned issues will be dynamically generated here -->
            </div>
        </div>
        <div class="col-md-6">
            <h5>Other Issues</h5>
            <div class="row dropzone" id="other-issues-container" ondrop="drop(event)" ondragover="allowDrop(event)">
                <!-- Other issues will be dynamically generated here -->
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 text-center">
            <div id="loading" style="display: none;"><img src="path/to/loading.gif" alt="Loading..."></div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="issueModal" tabindex="-1" aria-labelledby="issueModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="issueModalLabel">Issue Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="modal-body-text"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        
        let labels = [];
        let issues = [];
        const issuesPerPage = 10;
        let currentPage = 1;
        let isLoading = false;

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('view-details')) {
                const bodyText = e.target.dataset.body;
                const htmlContent = marked.parse(bodyText);
                document.getElementById('modal-body-text').innerHTML = htmlContent;
            }
        });

        window.addEventListener('scroll', handleScroll);

        fetchLabels();
        fetchAndDisplayPinnedIssues(currentPage, isLoading, issuesPerPage);

        const userId = 'SYSTEM';

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('clip-issue')) {
                const issueNumber = e.target.dataset.issueNumber;
                const button = e.target;

                fetch('api/pin_issue.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ issue_number: issueNumber, user_id: userId })
                }).then(response => response.json())
                .then(response => {
                    if (response.success) {
                        button.classList.toggle('btn-secondary');
                        button.classList.toggle('btn-primary');
                        button.innerHTML = button.innerHTML === 'Pin' ? '<i class="fa fa-paperclip"></i>' : '<i class="fa fa-paperclip"></i>';
                        // Reset current page and issues list, then fetch and display updated pinned issues
                        currentPage = 1;
                        issues = [];
                        fetchAndDisplayPinnedIssues(currentPage, isLoading, issuesPerPage);
                    } else {
                        alert('Failed to pin the issue.');
                    }
                }).catch(() => {
                    alert('Failed to pin the issue.');
                });
            }
        });
    });

    function handleScroll() {
        if (window.scrollY + window.innerHeight > document.body.scrollHeight - 100) {
            currentPage++;
            displayIssues(currentPage, isLoading, issuesPerPage);
        }
    }

    function fetchLabels() {
        fetch('api/get_tags.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                    return;
                }
                labels = data;
            })
            .catch(() => {
                console.error('Failed to fetch labels.');
            });
    }

    function fetchAndDisplayPinnedIssues(currentPage, isLoading, issuesPerPage) {
        if (isLoading) return;
        isLoading = true;
        document.getElementById('loading').style.display = 'block';

        fetch('api/getGHDash.php?action=by_pins')
            .then(response => response.json())
            .then(response => {
                document.getElementById('loading').style.display = 'none';
                if (!response || !response.data || response.data.length === 0) {
                    if (currentPage === 1) {
                        document.getElementById('pinned-issues-container').innerHTML = '<p class="text-center">Nothing to show</p>';
                    }
                    isLoading = false;
                    return;
                }

                issues = response.data;
                displayIssues(currentPage, isLoading, issuesPerPage);
            })
            .catch(error => {
                document.getElementById('loading').style.display = 'none';
                console.error('Fetch error:', error);
                if (currentPage === 1) {
                    document.getElementById('pinned-issues-container').innerHTML = '<p class="text-center">Nothing to show</p>';
                }
                isLoading = false;
            });
    }

    function displayIssues(currentPage, isLoading, issuesPerPage) {
        const start = (currentPage - 1) * issuesPerPage;
        const end = start + issuesPerPage;
        let cards = '';

        issues.slice(start, end).forEach(function(issue) {
            const tags = issue.tags ? issue.tags.split(', ').map(tag => {
                const label = labels.find(l => l.name === tag.trim());
                const color = label ? label.color : 'gray';
                return `<span class="badge badge-secondary mr-1" style="background-color: #${color}">${tag.trim()}</span>`;
            }).join(' ') : '';

            cards += `
                <div class="col-md-4 mb-4" draggable="true" ondragstart="drag(event)" id="issue-${issue.gh_node_id}">
                    <div class="card">
                        <div class="card-header">
                            ${issue.issue_text} <a href="${issue.gh_id_url}" target="_blank">#${issue.gh_id}</a>
                        </div>
                        <div class="card-body">
                            <p class="card-text"><strong>Assignee:</strong> ${issue.assignee}</p>
                            <p class="card-text"><strong>Repository:</strong> ${issue.repo}</p>
                            <p class="card-text"><strong>Assigned Date:</strong> ${issue.assigned_date}</p>
                            <p class="card-text"><strong>Aging:</strong> ${issue.aging}</p>
                            <p class="card-text"><strong>Tags:</strong> ${tags}</p>
                            <button class="btn btn-info view-details" data-body="${issue.body}" data-bs-toggle="modal" data-bs-target="#issueModal">Details</button>
                        </div>
                        <i class="fa fa-paperclip clip-issue" data-issue-number="${issue.gh_node_id}"></i>
                    </div>
                </div>`;
        });

        if (currentPage === 1) {
            document.getElementById('pinned-issues-container').innerHTML = cards;
        } else {
            document.getElementById('pinned-issues-container').insertAdjacentHTML('beforeend', cards);
        }

        isLoading = false;

        if (end >= issues.length) {
            window.removeEventListener('scroll', handleScroll);
        }
    }

    function drag(event) {
        event.dataTransfer.setData("text", event.target.id);
    }

    function allowDrop(event) {
        event.preventDefault();
    }

    function drop(event) {
        event.preventDefault();
        const id = event.dataTransfer.getData("text");
        const draggableElement = document.getElementById(id);
        const dropzone = event.target.closest('.dropzone');
        if (dropzone) {
            dropzone.appendChild(draggableElement);
        }
        event.dataTransfer.clearData();
    }
</script>

<?php
require_once('bodyend.php');
?>

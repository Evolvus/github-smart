<?php
$permission = "VIEW";
require_once('head.php');
?>

<h4 class="my-4">All Issues - Paycorp</h4>

<div class="container mt-5">
    <div class="row">
        <!-- Project Details and Issues (Right Pane) -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-body shadow-sm bg-white rounded">
                    <table id="issues-tbl" class="table table-striped table-hover shadow">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Assignee</th>
                                <th>Repo</th>
                                <th>Project</th>
                                <th>Assigned Date</th>
                                <th>Aging</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be dynamically inserted here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const apiUrl = 'api/getGHDash.php?action=by_assignee';

        // Fetch issues data from the API
        fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                console.log(data);
                populateTable(data.data);
            })
            .catch(error => console.error('Error fetching issues:', error));

        // Populate table with data
        function populateTable(data) {
            const tableBody = document.querySelector('#issues-tbl tbody');
            tableBody.innerHTML = ''; // Clear existing data

            data.forEach(issue => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${issue.gh_id}</td>
                    <td><a href="${issue.gh_id_url}" target="_blank">${issue.issue_text}</a></td>
                    <td>${issue.assignee}</td>
                    <td>${issue.repo}</td>
                    <td>${issue.gh_project_title}</td>
                    <td>${issue.assigned_date}</td>
                    <td>${issue.aging}</td>
                `;
                tableBody.appendChild(row);
            });

            initializeDataTable();
        }

        // Initialize DataTable
        function initializeDataTable() {
            const table = document.querySelector('#issues-tbl');
            new DataTable(table, {
                paging: true,
                searching: true,
                info: true,
                order: [[4, 'asc']],
                dom: 'PBflrtip',
                searchPanes: {
                    viewTotal: true,
                    columns: [0, 2, 3, 4, 5]
                },
                buttons: [
                    {
                        extend: 'excel',
                        text: 'Export to Excel',
                        titleAttr: 'Export Data to Excel',
                        footer: true,
                        title: 'Issues By Assignees',
                        exportOptions: {
                            orthogonal: 'export',
                            columns: [0, 1, 2, 3, 4, 5, 6]
                        }
                    }
                ],
                buttons: [
                    {
                        extend: 'excel',
                        footer: true,
                        title: 'Issues By Assignees',
                        text: 'Export to Excel',
                        titleAttr: 'Export Data to Excel',
                        exportOptions: {
                            orthogonal: 'export',
                            modifier: {
                                // DataTables core
                                order: 'index', // 'current', 'applied',
                                //'index', 'original'
                                page: 'all', // 'all', 'current'
                                search: 'applied' // 'none', 'applied', 'removed'
                            },
                            columns: [0, 1, 2, 3, 4, 5]
                        }
                    }
                ],
                drawCallback: function() {
                    const pagination = document.querySelector('.dataTables_paginate');
                    const info = document.querySelector('.dataTables_info');
                    const length = document.querySelector('.dataTables_length');

                    const api = this.api();
                    const shouldDisplay = api.page.info().pages > 1;
                    pagination.style.display = shouldDisplay ? 'block' : 'none';
                    info.style.display = shouldDisplay ? 'block' : 'none';
                    length.style.display = shouldDisplay ? 'block' : 'none';
                }
            });
        }
    });
</script>

<?php
require_once('bodyend.php');
?>

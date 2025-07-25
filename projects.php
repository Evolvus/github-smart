<?php
$permission = "VIEW";
require_once('head.php');



?>
<h4 class="my-4">Project Wise Issues - Paycorp</h4>
<div class="container mt-5">
    <div class="row">
        <!-- Project List (Left Pane) -->
        <div class="col-md-2 shadow-sm bg-white rounded"">
            <h4>Projects (<span id="project-count">0</span>)</h4>
            <!-- Add a search input field -->
            <input type="text" id="project-search" class="form-control mb-3" placeholder="Search projects">



            <div id="project-list-container">
                <ul id="project-list" class="list-group">
                    <!-- Project list will be displayed here -->
                </ul>
            </div>
        </div>

        <!-- Project Details and Issues (Right Pane) -->
        <div class="col-md-10">
            <div id="project-details">
                <!-- Project details will be displayed here -->
            </div>
            <div class="card">
                <div class="card-body shadow-sm bg-white rounded">
            <table id="project-issues-tbl" class="table table-striped table-hover shadow bg-white rounded"">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Repo</th>
                        <th>Assignee</th>
                    </tr>
                </thead>
                <tbody id="project-issues">
                    <!-- Project issues will be displayed here -->
                </tbody>
            </table>
        </div>
    </div>
</div>
    </div>
</div>

<script>

    document.addEventListener("DOMContentLoaded", function () {
        fetchAndDisplayProjects();
        document.getElementById('project-search').addEventListener('input', filterProjectList);

    });
    // Function to filter the project list based on user input and the Unassigned checkbox
    function filterProjectList() {
        var input, filter, ul, li, a, i, txtValue;
        input = document.getElementById('project-search');
        filter = input.value.toUpperCase();
        ul = document.getElementById('project-list');
        li = ul.getElementsByTagName('li');
        //var unassignedCheckbox = document.getElementById('unassigned-checkbox');
        //var showUnassigned = unassignedCheckbox.checked; // Get the checkbox state

        // Convert filter to a regular expression pattern with wildcards
        filter = filter.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&"); // Escape special characters
        filter = filter.replace(/\*/g, '.*'); // Replace '*' with '.*' for wildcard search
        var regex = new RegExp(filter, 'i'); // 'i' for case-insensitive search

        for (i = 0; i < li.length; i++) {
            txtValue = li[i].textContent || li[i].innerText;
            if (regex.test(txtValue.toUpperCase())) {
                li[i].style.display = '';
            } else {
                li[i].style.display = 'none';
            }
        }
    }



    // Add an event listener to the project search input


    // Function to fetch project list and populate the left pane
    // Function to fetch project list and populate the left pane
    function fetchAndDisplayProjects() {
        fetch('api/getProjects.php')
            .then(response => response.json())
            .then(data => {
                var projectList = document.getElementById('project-list');
                var projectCount = document.getElementById('project-count');

                dataFiltered = data.filter(function (project) {
                    return project.closed != 1;
                });



                projectCount.textContent = dataFiltered.length; // Update project count
                dataFiltered.forEach(function (project) {
                    var listItem = document.createElement('li');
                    listItem.className = 'list-group-item';
                    listItem.textContent = project.title + ' (' + project.count_of_issues + ')';
                    listItem.addEventListener('click', function () {
                        // Remove selection from other items
                        var allItems = projectList.children;
                        for (var i = 0; i < allItems.length; i++) {
                            allItems[i].classList.remove('active');
                        }
                        // Add selection class to the clicked item
                        listItem.classList.add('active');
                        // Display project details and issues for the selected project
                        displayProjectDetails(project);
                        fetchAndDisplayProjectIssues(project.gh_id);
                    });
                    projectList.appendChild(listItem);
                });
            })
            .catch(error => console.error('Error fetching project list:', error));
    }


    // Function to display project details in the right pane
    function displayProjectDetails(project) {
        var projectDetails = document.getElementById('project-details');


        projectDetails.innerHTML = `
               <h4> <a href = '`+ project.url + `'<p>${project.title}</p></a></h4>
            `;
    }

   


    function fetchAndDisplayProjectIssues(projectId) {


        if ($.fn.DataTable.isDataTable('#project-issues-tbl')) {
            $('#project-issues-tbl').DataTable().destroy();
        }
        // Initialize the DataTable with AJAX
        var table = $('#project-issues-tbl').DataTable({
            //processing: true, // Show loading indicator
            drawCallback: function (settings) {
                var pagination = $(this).closest('.dataTables_wrapper').find('.dataTables_paginate');
                pagination.toggle(this.api().page.info().pages > 1);

                var pagInfo = $(this).closest('.dataTables_wrapper').find('.dataTables_info');
                pagInfo.toggle(this.api().page.info().pages > 1);

                var pagLength = $(this).closest('.dataTables_wrapper').find('.dataTables_length');
                pagLength.toggle(this.api().page.info().pages > 1);

            },
            ajax: {
                url: 'api/getGHDash.php',
                type: "GET", // You may need to change the HTTP method if your API requires it
                data: function (d) {
                    d.action = 'by_project';
                    d.projectId = projectId;
                }
            },
            dom: 'Bflrtip',
            buttons: [
                {
                    extend: 'excel',
                    footer: true,
                    title: 'Issues By Project',
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
            columns: [
                { "data": "gh_id" }, // Replace with your actual data keys
                {
                    data: 'issue_text',
                    render: function (data, type, row) {
                        if (type === 'display') {
                            return '<a href="' + row.gh_id_url + '" target="_blank">' + data + '</a>';
                        }
                        return data;
                    }
                },
                { "data": "repo" },
                //{ "data": "gh_project_title" },
                //{ "data": "assignee" },
                { "data": "assignee" }
            ],
            order: [[1, "asc"]] // Initial sorting column and direction
        });
    }
    // Call the function to fetch and display projects
    
</script>



<?php
require_once('bodyend.php');



?>
<?php
$permission = "VIEW";
require_once('head.php');



?>
<h4 class="my-4">People Wise Issues - Paycorp</h4>
<div class="container mt-5">
    <div class="row">
        <!-- Project List (Left Pane) -->
        <div class="col-md-2 shadow-sm">
            <h2>Assignee (<span id="assignee-count">0</span>)</h2>
            <!-- Add a search input field with Bootstrap 5 classes -->
            <input type="text" id="assignee-search" class="form-control ustom-search-input  form-control-sm mb-3"
                placeholder="Search Assignee">

            <div id="assignee-list-container">
                <ul id="assignee-list" class="list-group">
                    <!-- assignee list will be displayed here -->
                </ul>
            </div>
        </div>

        <!-- Project Details and Issues (Right Pane) -->
        <div class="col-md-10">
            <div id="assignee-details">
                <!-- Assignee details will be displayed here -->
            </div>
            <div class="card">
                <div class="card-body shadow-sm bg-white rounded">
                    <table id="assignee-issues-tbl" class="table table-striped table-hover shadow">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Repo</th>
                                <th>Project</th>
                                <th>Assigned Date</th>
                                <th>Aging</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
     document.addEventListener("DOMContentLoaded", function () {
        // Test if jQuery and DataTables are loaded

        
        fetchAndDisplayAssignees();
        document.getElementById('assignee-search').addEventListener('input', filterAssigneeList);
     });

    // Function to filter the assignee list based on user input
    // Function to filter the assignee list based on user input
    // Function to filter the assignee list based on user input with wildcard search
    function filterAssigneeList() {
        var input, filter, ul, li, a, i, txtValue;
        input = document.getElementById('assignee-search');
        filter = input.value.toUpperCase();
        ul = document.getElementById('assignee-list');
        li = ul.getElementsByTagName('li');

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


    // Add an event listener to the assignee search input
   


    // Function to fetch assignee list and populate the left pane
    function fetchAndDisplayAssignees() {

        fetch('api/getGHDash.php?action=assignee')
            .then(response => {

                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {

                var assigneeList = document.getElementById('assignee-list');
                var assigneeCount = document.getElementById('assignee-count');

                dataFiltered = data.filter(function (assignee) {
                    return true;
                });
                assigneeCount.textContent = dataFiltered.length; // Update assignee count
                dataFiltered.forEach(function (assignee) {
                    var listItem = document.createElement('li');
                    listItem.className = 'list-group-item';
                    listItem.textContent = assignee.assignee + " (" + assignee.count_issue + ") ";
                    listItem.addEventListener('click', function () {
        
                        
                        // Remove selection from other items
                        var allItems = assigneeList.children;
                        for (var i = 0; i < allItems.length; i++) {
                            allItems[i].classList.remove('active');
                        }
                        // Add selection class to the clicked item
                        listItem.classList.add('active');
                        // Display assignee details and issues for the selected assignee
                        displayAssigneeDetails(assignee);
                        fetchAndDisplayAssigneeIssues(assignee);
                    });
                    assigneeList.appendChild(listItem);
                });
            })
            .catch(error => {
                console.error('Error fetching assignee list:', error);
                document.getElementById('assignee-list').innerHTML = '<li class="list-group-item text-danger">Error loading assignees</li>';
            });
    }

    // Function to display assignee details in the right pane
    function displayAssigneeDetails(assignee) {
        var assigneeDetails = document.getElementById('assignee-details');

        assigneeDetails.innerHTML = `
            <div class="alert alert-info">
                <h4>${assignee.assignee}</h4>
                <p>Total Issues: ${assignee.count_issue}</p>
            </div>
        `;
    }

    // Function to fetch and display issues for the selected assignee using DataTables
    function fetchAndDisplayAssigneeIssues(assignee) {
        

        // Check if DataTables is loaded
        if (typeof $.fn.DataTable === 'undefined') {
            console.error('DataTables is not loaded!');
            alert('DataTables library is not loaded. Please refresh the page.');
            return;
        }

        if ($.fn.DataTable.isDataTable('#assignee-issues-tbl')) {
            $('#assignee-issues-tbl').DataTable().destroy();
        }
        
        // Initialize the DataTable with AJAX
        var table = $('#assignee-issues-tbl').DataTable({
            processing: true, // Show loading indicator
            serverSide: false, // Client-side processing
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
                type: "GET",
                data: function (d) {
                    d.action = 'by_assignee';
                    d.assignee = assignee.assignee;
                },
                dataSrc: function(json) {
    
                    return json.data || [];
                }
            },
            dom: 'Bflrtip',
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
                            order: 'index',
                            page: 'all',
                            search: 'applied'
                        },
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                }
            ],
            columns: [
                { "data": "gh_id" },
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
                { "data": "gh_project_title" },
                { "data": "assigned_date" },
                { "data": "aging" }
            ],
            order: [[4, "asc"]], // Initial sorting column and direction
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            language: {
                processing: "Loading issues...",
                emptyTable: "No issues found for this assignee",
                zeroRecords: "No matching issues found"
            }
        });
    }




</script>



<?php
require_once('bodyend.php');



?>


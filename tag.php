<?php
$permission = "VIEW";
require_once('head.php');
?>

<style>
    .slider-container {
        margin-top: 50px; /* Adjust the value as needed */
        margin-left: 10px; /* Adjust the value as needed */
        margin-right: 50px; /* Adjust the value as needed */
    }
    #closed-at-slider {
        width: 100%;
    }
    #filter-section {
        display: none;
    }
</style>


<link href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.5.0/nouislider.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.5.0/nouislider.min.js"></script>


<h4 class="my-4">All Issues by Tags - Paycorp</h4>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Filter</span>
                    <button id="toggle-filter" class="btn btn-link"><i class="fa fa-chevron-down"></i></button>
                </div>
                <div id="filter-section" class="card-body">
                    <label for="and-tags">Select Tags (AND condition):</label>
                    <input id="and-tags" placeholder="Type to add tags" class="form-control mb-2" />
                    <label for="or-tags">Select Tags (OR condition):</label>
                    <input id="or-tags" placeholder="Type to add tags" class="form-control mb-2" />
                    <label>Select State:</label>
                    <div class="btn-group mb-2" role="group" aria-label="State filter">
                        <input type="radio" class="btn-check" name="state-filter" id="state-open" value="open" checked>
                        <label class="btn btn-outline-primary" for="state-open">Open</label>
                        <input type="radio" class="btn-check" name="state-filter" id="state-closed" value="closed">
                        <label class="btn btn-outline-primary" for="state-closed">Closed</label>
                        <input type="radio" class="btn-check" name="state-filter" id="state-all" value="all">
                        <label class="btn btn-outline-primary" for="state-all">All</label>
                    </div>
                    <div class="col-md-12 mb-3">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <label>Closed At Date Range:</label>
                            </div>
                            <div class="col slider-container"> <!-- Added a custom class for the slider container -->
                                <div id="closed-at-slider" class="mb-2"></div>
                                <input type="hidden" id="closed-at-start">
                                <input type="hidden" id="closed-at-end">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-auto">
                            <button id="filter" class="btn btn-primary">Filter</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card">
                <div class="card-body shadow-sm bg-white rounded">
                    <table id="issues-tbl" class="table table-striped table-hover shadow">
                        <thead>
                            <tr>
                                <th>Pin</th>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Assignee</th>
                                <th>Repo</th>
                                <th>Assigned Date</th>
                                <th>Aging</th>
                                <th>Last Updated</th>
                                <th>State</th>
                                <th>Closed At</th>
                                <th>Tags</th>
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
    document.addEventListener('DOMContentLoaded', function() {
        const inputAnd = document.querySelector('#and-tags');
        const inputOr = document.querySelector('#or-tags');
        const stateFilter = document.querySelectorAll('input[name="state-filter"]');
        const closedAtStartInput = document.querySelector('#closed-at-start');
        const closedAtEndInput = document.querySelector('#closed-at-end');
        const closedAtSlider = document.querySelector('#closed-at-slider');
        const filterSection = document.querySelector('#filter-section');
        const toggleFilterButton = document.querySelector('#toggle-filter');
        let tagifyAnd, tagifyOr;
        let labels = [];
        let dataTable;

        function fetchLabels() {
            fetch('api/get_tags.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error(data.error);
                        return;
                    }
                    labels = data;
                    const tagList = labels.map(tag => ({ value: tag.name }));
                    initializeTagify(tagList);
                })
                .catch(() => {
                    console.error('Failed to fetch labels.');
                });
        }

        function initializeTagify(tagList) {
            tagifyAnd = new Tagify(inputAnd, {
                enforceWhitelist: true,
                whitelist: tagList,
                dropdown: {
                    classname: 'color-blue',
                    enabled: 0,
                    maxItems: 5,
                    position: 'text',
                    closeOnSelect: false,
                    highlightFirst: true
                }
            });

            tagifyOr = new Tagify(inputOr, {
                enforceWhitelist: true,
                whitelist: tagList,
                dropdown: {
                    classname: 'color-blue',
                    enabled: 0,
                    maxItems: 5,
                    position: 'text',
                    closeOnSelect: false,
                    highlightFirst: true
                }
            });
        }

        function initializeSlider() {
            // Get today's date and calculate one year ago
            const today = new Date();
            const oneYearAgo = new Date();
            oneYearAgo.setFullYear(today.getFullYear() - 1);

            // Calculate tomorrow's date
            const tomorrow = new Date(today);
            tomorrow.setDate(today.getDate() + 1);

            noUiSlider.create(closedAtSlider, {
                start: [timestamp(oneYearAgo), timestamp(tomorrow)], // Set the start values dynamically
                range: {
                    min: timestamp(oneYearAgo),
                    max: timestamp(tomorrow) 
                },
                step: 24 * 60 * 60,
                connect: true,
                behaviour: 'tap-drag',
                tooltips: [true, true],
                format: {
                    to: (value) => formatDate(new Date(value * 1000)), // Convert seconds to milliseconds
                    from: (value) => value
                }
            });

            closedAtSlider.noUiSlider.on('update', function(values, handle) {
                if (handle) {
                    closedAtEndInput.value = values[handle];
                } else {
                    closedAtStartInput.value = values[handle];
                }
            });
        }

        function timestamp(date) {
            return new Date(date).getTime() / 1000;
        }

        function formatDate(date) {
            return date.toISOString().split('T')[0];
        }

        function fetchAndDisplayIssues(andTags, orTags, state, closedAtStart, closedAtEnd) {
            if (dataTable) {
                dataTable.destroy();
                document.querySelector('#issues-tbl tbody').innerHTML = ''; // Clear existing data
            }

            dataTable = new DataTable('#issues-tbl', {
                drawCallback: function(settings) {
                    const api = this.api();
                    const pagination = document.querySelector('.dataTables_paginate');
                    const pagInfo = document.querySelector('.dataTables_info');
                    const pagLength = document.querySelector('.dataTables_length');

                    pagination.style.display = api.page.info().pages > 1 ? 'block' : 'none';
                    pagInfo.style.display = api.page.info().pages > 1 ? 'block' : 'none';
                    pagLength.style.display = api.page.info().pages > 1 ? 'block' : 'none';
                },
                ajax: {
                    url: 'api/getGHDash.php',
                    type: "GET",
                    data: function(d) {
                        d.action = 'by_tags';
                        if (andTags.length > 0) d.and_tags = andTags.join(',');
                        if (orTags.length > 0) d.or_tags = orTags.join(',');
                        if (state !== 'all') d.state = state;
                        if (closedAtStart) d.closed_at_start = closedAtStart;
                        if (closedAtEnd) d.closed_at_end = closedAtEnd;
                    }
                },
                dom: 'Bflrtip',
                buttons: [
                    {
                        extend: 'excel',
                        footer: true,
                        title: 'Issues By Tags',
                        text: 'Export to Excel',
                        titleAttr: 'Export Data to Excel',
                        exportOptions: {
                            orthogonal: 'export',
                            modifier: {
                                order: 'index',
                                page: 'all',
                                search: 'applied'
                            },
                            columns: [1, 2, 3, 4, 5, 6, 7, 8] // Excluding the pin column
                        }
                    }
                ],
                columns: [
                    {
                        data: null,
                        orderable: false,
                        className: 'dt-center',
                        render: function(data, type, row) {
                            const iconClass = row.pin_status === "UNPIN" ? '' : ' fa-thumbtack-pinned';
                            return `<i class="fa fa-thumb-tack pin-issue${iconClass}" data-node-id="${row.gh_node_id}" style="cursor:pointer" aria-label="Pin Issue"></i>`;
                        }
                    },
                    { data: "gh_id" },
                    {
                        data: 'issue_text',
                        render: function(data, type, row) {
                            return type === 'display' ? `<a href="${row.gh_id_url}" target="_blank">${data}</a>` : data;
                        }
                    },
                    { data: "assignee" },
                    { data: "repo" },
                    { data: "assigned_date" },
                    { data: "aging" },
                    { data: "last_updated_at" },
                    { data: "state" },
                    { data: "closed_at" },
                    {
                        data: 'tags',
                        render: function(data, type, row) {
                            if (type === 'display' && data) {
                                const tags = data.split(', ').map(tag => {
                                    const label = labels.find(l => l.name === tag.trim());
                                    const color = label ? label.color : 'gray';
                                    return `<span class="badge" style="background-color: #${color}">${tag.trim()}</span>`;
                                }).join(' ');
                                return tags;
                            }
                            return data;
                        }
                    }
                ]
            });
        }

        // Function to handle pinning issues
        function handlePinIssue(event) {
            if (event.target.classList.contains('pin-issue')) {
                const issueNumber = event.target.dataset.nodeId;
                const icon = event.target;

                const formData = new FormData();
                formData.append('issue_number', issueNumber);
                formData.append('user_id', 'SYSTEM');

                fetch('api/pin_issue.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(response => {
                    if (response.success) {
                        icon.classList.toggle('fa-thumbtack-pinned');
                    } else {
                        alert('Failed to pin the issue.');
                    }
                })
                .catch(() => {
                    alert('Failed to pin the issue.');
                });
            }
        }

        // Add the event listener only once
        document.querySelector('#issues-tbl tbody').addEventListener('click', handlePinIssue);

        // Fetch labels and display issues initially
        fetchLabels();
        fetchAndDisplayIssues([], [], 'all', null, null);

        // Initialize slider
        initializeSlider();

        // Filter button click event
        document.getElementById('filter').addEventListener('click', function() {
            const selectedAndTags = tagifyAnd.value.map(tag => tag.value);
            const selectedOrTags = tagifyOr.value.map(tag => tag.value);
            const selectedState = document.querySelector('input[name="state-filter"]:checked').value;
            const selectedClosedAtStart = closedAtStartInput.value ? closedAtStartInput.value : null;
            const selectedClosedAtEnd = closedAtEndInput.value ? closedAtEndInput.value : null;
            fetchAndDisplayIssues(selectedAndTags, selectedOrTags, selectedState, selectedClosedAtStart, selectedClosedAtEnd);

            // Hide the filter section after filtering
            filterSection.style.display = 'none';
            toggleFilterButton.innerHTML = '<i class="fa fa-chevron-down"></i>';
        });

        // Toggle filter visibility
        toggleFilterButton.addEventListener('click', function() {
            if (filterSection.style.display === 'none') {
                filterSection.style.display = 'block';
                toggleFilterButton.innerHTML = '<i class="fa fa-chevron-up"></i>';
            } else {
                filterSection.style.display = 'none';
                toggleFilterButton.innerHTML = '<i class="fa fa-chevron-down"></i>';
            }
        });
    });
</script>

<?php
require_once('bodyend.php');
?>

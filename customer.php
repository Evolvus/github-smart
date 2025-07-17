<?php
$permission = "VIEW";
require_once('head.php');
?>
<h4 class="my-4">Customer Wise Issues - Paycorp</h4>

<!-- Add Customer Tag Button -->
<div class="container mb-3">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
        <i class="bi bi-plus-circle"></i> Add Customer Tag
    </button>
</div>

<!-- Modal for adding a customer tag -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addCustomerForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addCustomerModalLabel">Add New Customer Tag</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="customer-tag" class="form-label">Customer Tag</label>
            <input type="text" class="form-control" id="customer-tag" name="customer" placeholder="Enter customer tag" required list="tags-list">
            <!-- datalist to hold the tag suggestions -->
            <datalist id="tags-list"></datalist>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Add Tag</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="container mt-5">
    <div class="row">
        <!-- Customer List (Left Pane) -->
        <div class="col-md-2 shadow-sm">
            <h2>Customer (<span id="customer-count">0</span>)</h2>
            <!-- Search input for customer tags -->
            <input type="text" id="customer-search" class="form-control form-control-sm mb-3" placeholder="Search Customer">
            <div id="customer-list-container">
                <ul id="customer-list" class="list-group">
                    <!-- customer list will be displayed here -->
                </ul>
            </div>
        </div>

        <!-- Customer Details and Issues (Right Pane) -->
        <div class="col-md-10">
            <div id="customer-details">
                <!-- Customer details will be displayed here -->
            </div>
            <div class="card">
                <div class="card-body shadow-sm bg-white rounded">
                    <table id="customer-issues-tbl" class="table table-striped table-hover shadow">
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
// Wait for the DOM to load
document.addEventListener("DOMContentLoaded", function () {
    fetchAndDisplayCustomers();
    document.getElementById('customer-search').addEventListener('input', filterCustomerList);

    // Native autocomplete using datalist:
    const tagInput = document.getElementById('customer-tag');
    tagInput.addEventListener('input', function() {
        const term = tagInput.value.trim();
        if (term.length >= 1) {
            fetch('api/get_tags.php?action=tags&term=' + encodeURIComponent(term))
                .then(response => response.json())
                .then(data => {
                    const dataList = document.getElementById('tags-list');
                    dataList.innerHTML = '';
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.tag;
                        dataList.appendChild(option);
                    });
                })
                .catch(error => console.error('Error fetching tags:', error));
        }
    });

    // Handle Add Customer Tag form submission using AJAX
    document.getElementById('addCustomerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        let tag = tagInput.value.trim();
        if (!tag) return;
        fetch('api/addCustomer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'customer=' + encodeURIComponent(tag)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Refresh customer list and close the modal
                fetchAndDisplayCustomers();
                const modalEl = document.getElementById('addCustomerModal');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                modalInstance.hide();
                document.getElementById('addCustomerForm').reset();
            } else {
                alert("Error: " + data.error);
            }
        })
        .catch(error => console.error('Error:', error));
    });
});

// Function to remove a customer tag using the API
function removeCustomer(customerTag) {
    if(!confirm('Are you sure you want to delete customer tag "' + customerTag + '"?')) {
        return;
    }
    fetch('api/removeCustomer.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'customer=' + encodeURIComponent(customerTag)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fetchAndDisplayCustomers();
        } else {
            alert("Error: " + data.error);
        }
    })
    .catch(error => console.error('Error removing customer tag:', error));
}

// Function to filter the customer list based on user input with wildcard search
function filterCustomerList() {
    const searchValue = document.getElementById('customer-search').value.trim().toLowerCase();
    const customerList = document.getElementById('customer-list');
    const liItems = customerList.getElementsByTagName('li');

    Array.from(liItems).forEach(item => {
        const itemText = item.textContent.toLowerCase();
        item.style.display = itemText.includes(searchValue) ? '' : 'none';
    });
}

// Function to fetch customer list and populate the left pane
function fetchAndDisplayCustomers() {
    fetch('api/getGHDash.php?action=customer')
        .then(response => response.json())
        .then(data => {
            const customerList = document.getElementById('customer-list');
            const customerCount = document.getElementById('customer-count');
            customerList.innerHTML = '';
            customerCount.textContent = data.length;
            data.forEach(function (customer) {
                // Create list item with flex container
                const listItem = document.createElement('li');
                listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
                
                // Span for displaying customer tag and issue count
                const textSpan = document.createElement('span');
                textSpan.textContent = customer.customer + " (" + customer.count_issue + ")";
                listItem.appendChild(textSpan);

                // Remove button with trash icon
                const removeBtn = document.createElement('button');
                removeBtn.className = 'btn btn-danger btn-sm';
                removeBtn.innerHTML = '<i class="bi bi-trash"></i>';
                removeBtn.addEventListener('click', function(event) {
                    event.stopPropagation(); // Prevent parent's click event
                    removeCustomer(customer.customer);
                });
                listItem.appendChild(removeBtn);

                // When clicking the list item (excluding the Remove button)
                listItem.addEventListener('click', function () {
                    Array.from(customerList.children).forEach(item => item.classList.remove('active'));
                    listItem.classList.add('active');
                    displayCustomerDetails(customer);
                    fetchAndDisplayCustomerIssues(customer);
                });
                
                customerList.appendChild(listItem);
            });
        })
        .catch(error => console.error('Error fetching customer list:', error));
}

// Function to display customer details in the right pane
function displayCustomerDetails(customer) {
    document.getElementById('customer-details').innerHTML = `<h4>${customer.customer}</h4>`;
}

// Function to fetch and display issues for the selected customer using DataTables
function fetchAndDisplayCustomerIssues(customer) {
    if ($.fn.DataTable.isDataTable('#customer-issues-tbl')) {
        $('#customer-issues-tbl').DataTable().destroy();
    }
    $('#customer-issues-tbl').DataTable({
        ajax: {
            url: 'api/getGHDash.php',
            type: "GET",
            data: function (d) {
                d.action = 'by_customer';
                d.customer = customer.customer;
            }
        },
        dom: 'Bflrtip',
        buttons: [
            {
                extend: 'excel',
                footer: true,
                title: 'Issues By Customer',
                text: 'Export to Excel',
                titleAttr: 'Export Data to Excel',
                exportOptions: {
                    orthogonal: 'export',
                    modifier: { order: 'index', page: 'all', search: 'applied' },
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
                        return `<a href="${row.gh_id_url}" target="_blank">${data}</a>`;
                    }
                    return data;
                }
            },
            { "data": "repo" },
            { "data": "gh_project_title" },
            { "data": "assigned_date" },
            { "data": "aging" }
        ],
        order: [[4, "asc"]]
    });
}
</script>

<?php
require_once('bodyend.php');
?>

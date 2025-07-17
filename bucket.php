<?php
$permission = "VIEW";
require_once('head.php');
?>

<style>
#container {
    display: flex;
    gap: 10px;
    flex-wrap: wrap; /* Allow buckets to wrap to the next line */
    transition: transform 0.5s ease;
}

.cover {
    background-color: #FAFAD2;
    position: relative; /* Make the cover position relative */
}

.bucket {
    width: 400px; /* Increased width */
    min-height: 300px;
    padding: 10px;
    border: 2px solid #ccc;
    border-radius: 5px;
    background-color: #f9f9f9;
    position: relative;
    margin-top: 20px;
    margin-bottom: 20px;
    margin-left: 20px;
}

.clip-issue {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 1.5em;
    cursor: grab;
}

.clip-issue:active {
    cursor: grabbing;
    opacity: 0.5;
}

.hide {
    display: none;
}

.drag-over {
    border: 2px dashed #000;
}

.card-container {
    width: 350px; /* Set custom width for the card container */
    margin-bottom: 20px;
}

.pagination {
    z-index: 1000; /* Ensure it is above other elements */
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 100%;
    display: flex;
    justify-content: space-between; /* Distribute buttons to left and right */
}

.pagination button {
    padding: 10px;
    font-size: 16px;
    background-color: var(--bs-primary); /* Bootstrap primary color */
    border: none;
    border-radius: 50%;
    color: #fff;
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    transition: background-color 0.3s ease;
}

.pagination button:hover {
    background-color: #0056b3; /* Darker shade for hover effect */
}

.pagination button:disabled {
    cursor: not-allowed;
    background-color: #ccc;
}

.pagination button i {
    font-size: 18px; /* Adjust the icon size */
}

.move-left {
    transform: translateX(-100%);
}

.move-right {
    transform: translateX(100%);
}

.container-wrapper {
    position: relative;
}

/* CSS for Add Bucket Button */
#addBucket {
    position: absolute;
    top: 10px;
    right: 60px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    transition: width 0.3s, background-color 0.3s;
    background-color: var(--bs-primary);
    color: #fff;
    border: none;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    cursor: pointer;
}

#addBucket:hover {
    width: 200px;
    background-color: #0056b3; /* Darker shade for hover effect */
}

#addBucket:hover::after {
    content: " Add Bucket";
    font-size: 16px;
    margin-left: 5px;
}

/* CSS for Toggle View Button */
#toggleView {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    background-color: var(--bs-secondary);
    color: #fff;
    border: none;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    cursor: pointer;
    transition: width 0.3s, background-color 0.3s;
}

#toggleView:hover {
    background-color: #0056b3; /* Darker shade for hover effect */
    width: 200px;
}

#toggleView:hover::after {
    content: attr(data-title);
    font-size: 16px;
    color: #fff; /* Ensure the text color is white */
    margin-left: 5px;
    white-space: nowrap; /* Prevent text wrapping */
}



.header-container {
    position: relative;
}

.bucket h3 {
    cursor: pointer;
}

.bucket input[type="text"] {
    display: none;
    width: calc(100% - 20px); /* Adjust to fit within the bucket */
    padding: 5px;
    font-size: 1.2em;
}

.bucket.editing h3 {
    display: none;
}

.bucket.editing input[type="text"] {
    display: block;
}

.bucket .delete-bucket {
    display: none;
    position: absolute;
    top: 10px;
    right: 10px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: red;
    color: white;
    border: none;
    font-size: 1.5em;
    line-height: 30px;
    text-align: center;
    cursor: pointer;
}

.bucket.empty .delete-bucket {
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Compact view styles */
.compact .bucket {
    width: 180px;
    min-height: 150px;
}

.compact .card-container {
    width: 150px;
    margin-bottom: 10px;
}

.compact .card-container .issue-number,
.compact .card-container .issue-description {
    font-size: 0.8em;
}

.compact .issue-description {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    width: 100%;
    display: block;
}
</style>

<div class="header-container d-flex justify-content-between align-items-center">
    <h4 class="my-4">Pinned Issues - Paycorp</h4>
    <button id="addBucket" class="btn btn-primary" data-title="Add Bucket">
        <i class="fas fa-plus"></i>
    </button> <!-- Add Bucket Button -->
    <button id="toggleView" class="btn btn-secondary" data-title="Compact View">
        <i class="fas fa-compress"></i>
    </button> <!-- Toggle View Button -->
</div>


<div class="cover">
    <div class="pagination">
        <button id="prevPage" disabled><i class="fas fa-chevron-left"></i></button>
        <button id="nextPage"><i class="fas fa-chevron-right"></i></button>
    </div>
    <div class="container-wrapper">
        <div id="container"></div>
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
    let bucketsPerPage = 3; // Number of buckets to show per page in expanded view
    let currentPage = 1;
    let totalPages = 1;
    let allBuckets = [];
    let allIssues = [];
    let draggedIssue = null; // Store the currently dragged issue
    let currentView = 'EXPAND';

    document.addEventListener('DOMContentLoaded', () => {
        fetch('./api/get_buckets.php')
            .then(response => response.json())
            .then(bucketData => {
                allBuckets = bucketData.buckets;
                totalPages = Math.ceil(allBuckets.length / bucketsPerPage);
                displayBuckets();
                return fetch('./api/getGHDash.php?action=by_pins');
            })
            .then(response => response.json())
            .then(issueData => {
                allIssues = issueData.data;
                createIssues();
            })
            .catch(error => console.error('Error fetching data:', error));

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('view-details')) {
                const bodyText = e.target.dataset.body;
                const htmlContent = marked.parse(bodyText);
                document.getElementById('modal-body-text').innerHTML = htmlContent;
            }
        });

        const prevPageButton = document.getElementById('prevPage');
        const nextPageButton = document.getElementById('nextPage');
        const addBucketButton = document.getElementById('addBucket');
        const toggleViewButton = document.getElementById('toggleView');

        prevPageButton.addEventListener('click', () => {
            if (currentPage > 1) {
                animateBuckets('right');
                setTimeout(() => {
                    currentPage--;
                    displayBuckets();
                }, 500); // Match this duration with the CSS transition duration
            }
        });

        nextPageButton.addEventListener('click', () => {
            if (currentPage < totalPages) {
                animateBuckets('left');
                setTimeout(() => {
                    currentPage++;
                    displayBuckets();
                }, 500); // Match this duration with the CSS transition duration
            }
        });

        // Add dragover event listeners to invoke page change when dragged over buttons
        prevPageButton.addEventListener('dragover', (e) => {
            e.preventDefault();
            if (currentPage > 1 && !draggedIssue.pageChanged) {
                draggedIssue.pageChanged = true;
                animateBuckets('right');
                setTimeout(() => {
                    currentPage--;
                    displayBuckets();
                    draggedIssue.pageChanged = false;
                    appendDraggedIssue();
                }, 500);
            }
        });

        nextPageButton.addEventListener('dragover', (e) => {
            e.preventDefault();
            if (currentPage < totalPages && !draggedIssue.pageChanged) {
                draggedIssue.pageChanged = true;
                animateBuckets('left');
                setTimeout(() => {
                    currentPage++;
                    displayBuckets();
                    draggedIssue.pageChanged = false;
                    appendDraggedIssue();
                }, 500);
            }
        });

        addBucketButton.addEventListener('click', () => {
            addNewBucket();
        });

        toggleViewButton.addEventListener('click', () => {
            toggleViewMode();
        });
    });

    function displayBuckets() {
        const container = document.getElementById('container');
        container.innerHTML = ''; // Clear previous buckets

        const start = (currentPage - 1) * bucketsPerPage;
        const end = start + bucketsPerPage;
        const bucketsToShow = allBuckets.slice(start, end);

        bucketsToShow.forEach(bucket => {
            console.log(bucket);
            const bucketDiv = document.createElement('div');
            bucketDiv.classList.add('bucket', 'empty'); // Initially mark as empty
            bucketDiv.id = `bucket${bucket.id}`;
            bucketDiv.innerHTML = `<h3>${bucket.name}</h3><input type="text" value="${bucket.name}" />`;

            // Add delete button only if it's not the default bucket
            if (bucket.id != 1) {
                bucketDiv.innerHTML += `<button class="delete-bucket">&times;</button>`;
            }

            container.appendChild(bucketDiv);

            const h3 = bucketDiv.querySelector('h3');
            const input = bucketDiv.querySelector('input');
            const deleteButton = bucketDiv.querySelector('.delete-bucket');

            h3.addEventListener('click', () => {
                bucketDiv.classList.add('editing');
                input.focus();
            });

            input.addEventListener('blur', () => {
                bucketDiv.classList.remove('editing');
                updateBucketName(bucket.id, input.value);
                h3.textContent = input.value;
            });

            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    input.blur();
                }
            });

            if (deleteButton) {
                deleteButton.addEventListener('click', () => {
                    deleteBucket(bucket.id, bucketDiv);
                });
            }
        });

        createIssues(); // Recreate issues for the current page
        updatePaginationButtons();
    }


function createIssues() {
    // Clear all issues from current buckets
    allBuckets.forEach(bucket => {
        const bucketDiv = document.getElementById(`bucket${bucket.id}`);
        if (bucketDiv) {
            if(bucket.id != 1){
                bucketDiv.innerHTML = `<h3>${bucket.name}</h3><input type="text" value="${bucket.name}" /><button class="delete-bucket">&times;</button>`;
            }
            else{
                bucketDiv.innerHTML = `<h3>${bucket.name}</h3><input type="text" value="${bucket.name}" />`;
            }
            
            const h3 = bucketDiv.querySelector('h3');
            const input = bucketDiv.querySelector('input');
            const deleteButton = bucketDiv.querySelector('.delete-bucket');

            h3.addEventListener('click', () => {
                bucketDiv.classList.add('editing');
                input.focus();
            });

            input.addEventListener('blur', () => {
                bucketDiv.classList.remove('editing');
                updateBucketName(bucket.id, input.value);
                h3.textContent = input.value;
            });

            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    input.blur();
                }
            });
            if(bucket.id != 1){
                deleteButton.addEventListener('click', () => {
                    deleteBucket(bucket.id, bucketDiv);
                });
            }
            
        }
    });

    allIssues.forEach(issue => {
        const bucketId = issue.bucket ? `bucket${issue.bucket}` : 'bucket1'; // Default to bucket1 if no bucket is assigned
        const bucketDiv = document.getElementById(bucketId);
        if (bucketDiv) {
            bucketDiv.classList.remove('empty'); // Mark bucket as not empty
            const issueCard = createCard(issue);
            bucketDiv.appendChild(issueCard);
        }
    });
    addDragAndDropFunctionality();
}



    function updatePaginationButtons() {
        document.getElementById('prevPage').disabled = currentPage === 1;
        document.getElementById('nextPage').disabled = currentPage === totalPages;
    }

  


    function createCard(issue) {
        const card = document.createElement('div');
        card.classList.add('card-container');
        card.draggable = true;
        card.ondragstart = dragStart;
        card.ondragend = dragEnd;
        card.id = `issue-${issue.gh_node_id}`;
        if(currentView == 'EXPAND'){
            card.innerHTML = `
                <div class="card">
                    <div class="card-header">
                        ${issue.issue_text} <a href="${issue.gh_id_url}" target="_blank">#${issue.gh_id}</a>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><strong>Assignee:</strong> ${issue.assignee}</p>
                        <button class="btn btn-info view-details" data-body="${issue.body}" data-bs-toggle="modal" data-bs-target="#issueModal">Details</button>
                    </div>
                    <i class="fa fa-paperclip clip-issue" data-issue-number="${issue.gh_node_id}"></i>
                </div>
            `;
        }
        else {
            card.innerHTML = `
                <div class="card">
                    <div class="card-header">
                        ${issue.issue_text} <a href="${issue.gh_id_url}" target="_blank">#${issue.gh_id}</a>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-info view-details" data-body="${issue.body}" data-bs-toggle="modal" data-bs-target="#issueModal">Details</button>
                    </div>
                    <i class="fa fa-paperclip clip-issue" data-issue-number="${issue.gh_node_id}"></i>
                </div>
            `;
        }
        
        return card;
    }

    function addDragAndDropFunctionality() {
        const dragIcons = document.querySelectorAll('.clip-issue');
        const buckets = document.querySelectorAll('.bucket');

        dragIcons.forEach(icon => {
            icon.addEventListener('dragstart', dragStart);
            icon.addEventListener('dragend', dragEnd);
        });

        buckets.forEach(bucket => {
            bucket.addEventListener('dragover', dragOver);
            bucket.addEventListener('dragenter', dragEnter);
            bucket.addEventListener('dragleave', dragLeave);
            bucket.addEventListener('drop', drop);
        });
    }

    function dragStart(e) {
    const cardContainer = e.target.closest('.card-container');
    e.dataTransfer.setData('text/plain', cardContainer.id);
    draggedIssue = {
        element: cardContainer,
        bucketId: cardContainer.closest('.bucket').id.replace('bucket', ''),
        pageChanged: false // Track if the page has already been changed
    };
    setTimeout(() => {
        cardContainer.classList.add('hide');
    }, 0);
}

function dragEnd(e) {
    if (draggedIssue) {
        const previousBucket = document.getElementById(`bucket${draggedIssue.bucketId}`);
        if (!previousBucket.querySelector('.card-container')) {
            previousBucket.classList.add('empty'); // Mark previous bucket as empty if no issues remain
        }
        draggedIssue.element.classList.remove('hide');
        draggedIssue = null;
    }
}


    function dragOver(e) {
        e.preventDefault();
    }

    function dragEnter(e) {
        e.preventDefault();
        e.target.classList.add('drag-over');
    }

    function dragLeave(e) {
        e.target.classList.remove('drag-over');
    }

    function drop(e) {
    e.preventDefault();
    e.target.classList.remove('drag-over');

    const id = e.dataTransfer.getData('text/plain');
    const draggable = document.getElementById(id);

    if (draggable && e.target.classList.contains('bucket')) {
        e.target.appendChild(draggable);
        draggable.classList.remove('hide'); // Ensure the card is visible
        updateIssueBucket(id.replace('issue-', ''), e.target.id.replace('bucket', ''));
        e.target.classList.remove('empty'); // Mark bucket as not empty
        draggedIssue = null;
    } else {
        const bucket = e.target.closest('.bucket');
        if (bucket && draggable) {
            bucket.appendChild(draggable);
            draggable.classList.remove('hide'); // Ensure the card is visible
            updateIssueBucket(id.replace('issue-', ''), bucket.id.replace('bucket', ''));
            bucket.classList.remove('empty'); // Mark bucket as not empty
            draggedIssue = null;
        }
    }
}


    function updateIssueBucket(issueId, newBucketId) {
    const formData = new FormData();
    formData.append('issue_id', issueId);
    formData.append('bucket', newBucketId);

    fetch('api/update_issue_bucket.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the issue in the allIssues array
            const issueIndex = allIssues.findIndex(issue => issue.gh_node_id === issueId);
            if (issueIndex !== -1) {
                allIssues[issueIndex].bucket = newBucketId;
            }
            console.log('Bucket updated:', data);
        } else {
            console.error('Error updating bucket:', data);
        }
    })
    .catch(error => console.error('Error updating bucket:', error));
}


    function updateBucketName(bucketId, newName) {
        const formData = new FormData();
        formData.append('bucket_id', bucketId);
        formData.append('name', newName);

        fetch('api/update_bucket_name.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Bucket name updated:', data);
        })
        .catch(error => console.error('Error updating bucket name:', error));
    }

    function deleteBucket(bucketId, bucketElement) {
    // Check if the bucket is the default bucket
    if (bucketId === 1) {
        alert('The default bucket cannot be deleted.');
        return;
    }

    console.log('about to delete:', bucketId);
    if (!confirm('Are you sure you want to delete this bucket?')) {
        return;
    }

    const formData = new FormData();
    formData.append('bucket_id', bucketId);

    fetch('api/delete_bucket.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the bucket from the local allBuckets array
            allBuckets = allBuckets.filter(bucket => bucket.id !== bucketId);
            totalPages = Math.ceil(allBuckets.length / bucketsPerPage);
            displayBuckets(); // Refresh buckets after deletion
            console.log('Bucket deleted:', data);
        } else {
            console.error('Error deleting bucket:', data);
        }
    })
    .catch(error => console.error('Error deleting bucket:', error));
}



    function animateBuckets(direction) {
        const container = document.getElementById('container');
        if (direction === 'left') {
            container.classList.add('move-left');
        } else if (direction === 'right') {
            container.classList.add('move-right');
        }

        setTimeout(() => {
            container.classList.remove('move-left', 'move-right');
        }, 500); // Match this duration with the CSS transition duration
    }

    function appendDraggedIssue() {
        if (draggedIssue) {
            const newBucketDiv = document.querySelector('.bucket');
            if (newBucketDiv) {
                newBucketDiv.appendChild(draggedIssue.element);
                newBucketDiv.classList.remove('empty'); // Mark new bucket as not empty
                draggedIssue.element.classList.remove('hide');
                draggedIssue = null;
            }
        }
    }

    function addNewBucket() {
        const bucketName = `New Bucket ${allBuckets.length + 1}`;
        const formData = new FormData();
        formData.append('name', bucketName);

        fetch('api/add_bucket.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const newBucket = {
                    id: data.id,
                    name: data.name
                };
                allBuckets.push(newBucket);
                totalPages = Math.ceil(allBuckets.length / bucketsPerPage);
                displayBuckets();
            }
        })
        .catch(error => console.error('Error adding bucket:', error));
    }

    function toggleViewMode() {
    const container = document.getElementById('container');
    const button = document.getElementById('toggleView');
    const icon = button.querySelector('i');
    if(currentView == 'EXPAND'){
        currentView = 'COMPACT'
    }
    else{
        currentView = 'EXPAND'
    }
    
    if (container.classList.contains('compact')) {
        container.classList.remove('compact');
        icon.classList.remove('fa-expand');
        icon.classList.add('fa-compress');
        button.setAttribute('data-title', 'Compact View');
        bucketsPerPage = 3;
    } else {
        container.classList.add('compact');
        icon.classList.remove('fa-compress');
        icon.classList.add('fa-expand');
        button.setAttribute('data-title', 'Expand View');
        bucketsPerPage = 6;
    }

    totalPages = Math.ceil(allBuckets.length / bucketsPerPage);
    currentPage = 1;
    displayBuckets();
}


</script>

<?php
require_once('bodyend.php');
?>

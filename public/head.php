<?php
require_once 'bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHub Issue Management</title>
    <link rel="shortcut icon" href="favicon.ico">

    <!-- Include Bootstrap 5 CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- Include DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">

    <!-- Include Tagify CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css" />

    <!-- Include Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" referrerpolicy="no-referrer" />

    <!-- Include Bootcomplete CSS -->
    <!-- <link href="script/bootcomplete.css" rel="stylesheet" type="text/css" /> -->
    
    <style>
        /* Custom CSS */
        #project-list-container {
            max-height: 600px;
            overflow-y: auto;
        }

        #assignee-list-container {
            max-height: 600px;
            max-width: 300px;
            overflow-y: auto;
        }

        .custom-search-input {
            width: 80%;
            max-width: 300px;
        }

        .tagify__dropdown {
            z-index: 10000;
        }

        .dt-center {
            text-align: center;
        }

        .fa-thumb-tack {
            color: #007bff;
        }

        .fa-thumb-tack:hover {
            color: blue;
        }

        .fa-thumbtack-pinned {
            color: red;
            transform: rotate(45deg);
        }

        .fa-thumbtack-pinned:hover {
            color: orange;
        }
        .pad-background {
        background: #fffbe8;
        border: 1px solid #ccc;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    }

    .pad-background:before {
        content: '';
        display: block;
        position: absolute;
        top: 0;
        left: 20px;
        right: 20px;
        height: 10px;
        border-top: 1px solid #ccc;
        background: repeating-linear-gradient(
            to bottom,
            #fffbe8,
            #fffbe8 29px,
            #ccc 30px,
            #ccc 30px
        );
    }

  


    .modal-body {
        white-space: pre-wrap; /* Ensure new lines are respected */
        max-height: calc(100vh - 210px);
        overflow-y: auto;
    }


    
    </style>
</head>

<body>
    <div class="container">
        <nav class="navbar navbar-expand-lg bg-primary rounded">
            <div class="container">
                <a class="navbar-brand" href="/">CRUX</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">GitHub</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="projects.php">Projects</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="people.php">People</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="issues.php">Issues</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="tag.php">Tags</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="status-board.php">Status Board</a>
                        </li>
                                    <li class="nav-item">
                <a class="nav-link" href="customer.php">Customers</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="project-status.php">Project Status</a>
            </li>
            <!-- <li class="nav-item">
                <a class="nav-link" href="bucket.php">Bucket</a>
            </li> -->
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Scripts -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/date-fns/1.30.1/date_fns.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
        <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.polyfills.min.js"></script>
  
        <!-- <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script> -->

        <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

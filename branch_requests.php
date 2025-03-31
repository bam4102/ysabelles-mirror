<?php
    include 'assets/controllers/branch_requests/branch_requests_controller.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Requests</title>
    <link rel="icon" type="image/png" href="./assets/img/sitelogo.png">
    <!-- Include necessary CSS -->
    <link rel="stylesheet" href="assets/css/branch_requests.css">
    <link rel="stylesheet" href="assets/css/global.css">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Select2 for better dropdowns -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>

<body>
    <?php include 'assets/nav/nav.php'; ?>

    <div class="container" style="max-width: 100%; padding-right: 15px; padding-left: 15px;">
        <!-- Title with Navigation Pills -->
        <div class="title-container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="title">Product Request</h1>
                <!-- Navigation Pills -->
                <ul class="nav nav-pills" id="requestTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="request-tab" data-bs-toggle="tab" data-bs-target="#request-pane" type="button" role="tab" aria-controls="request-pane" aria-selected="true" href="#request-pane">Product Request</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="myRequest-tab" data-bs-toggle="tab" data-bs-target="#myRequest-pane" type="button" role="tab" aria-controls="myRequest-pane" aria-selected="false" href="#myRequest-pane">My Request</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="incoming-tab" data-bs-toggle="tab" data-bs-target="#incoming-pane" type="button" role="tab" aria-controls="incoming-pane" aria-selected="false" href="#incoming-pane">Incoming Request</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="requestTabsContent">
            <?php 
            include 'assets/views/branch_requests/product_request_tab.php';
            include 'assets/views/branch_requests/my_requests_tab.php';
            include 'assets/views/branch_requests/incoming_requests_tab.php';
            ?>
        </div>
    </div>

    <!-- Selected Products Modal -->
    <div class="modal fade" id="selectedProductsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Selected Products</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul id="selectedProductsList"></ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Details Modal -->
    <div class="modal fade" id="requestDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="requestDetailsContent"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery and other scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/scripts/branch_requests.js"></script>
</body>

</html> 
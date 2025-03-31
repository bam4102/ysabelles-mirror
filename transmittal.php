<?php
include 'auth.php';
require_once 'assets/controllers/db.php';

// Check if the user session exists and is not empty
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header("Location: login_page.php");
    exit;
}

// Allow both ADMIN and INVENTORY positions to access this page
$allowedPositions = ['ADMIN', 'INVENTORY', 'SUPERADMIN'];
if (!in_array(strtoupper($_SESSION['user']['positionEmployee']), $allowedPositions)) {
    header("Location: credential_error.php");
    exit;
}

// Get the name of the currently logged-in user from the session.
$currentName = $_SESSION['user']['nameEmployee'];
$userPosition = $_SESSION['user']['positionEmployee'];
$userLocation = $_SESSION['user']['locationEmployee'];
$isSuperAdmin = ($userPosition === 'SUPERADMIN');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transmittal</title>
    <link rel="icon" type="image/png" href="./assets/img/sitelogo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="./assets/css/transmittal.css" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/global.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>

<body>
    <!-- Include Navigation -->
    <?php include 'assets/nav/nav.php'; ?>

    <div class="container mt-4">
        <!-- Add title container with tabs styling similar to utilities page -->
        <div class="title-container">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Transmittal</h1>
                <!-- Navigation Pills -->
                <ul class="nav nav-pills" id="transmittalTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="new-tab" data-bs-toggle="tab" data-bs-target="#new-pane" type="button" role="tab" aria-controls="new-pane" aria-selected="true">New Transmittal</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending-pane" type="button" role="tab" aria-controls="pending-pane" aria-selected="false">Pending Transmittals</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="receive-tab" data-bs-toggle="tab" data-bs-target="#receive-pane" type="button" role="tab" aria-controls="receive-pane" aria-selected="false">Receive Products</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-pane" type="button" role="tab" aria-controls="history-pane" aria-selected="false">Transmittal History</button>
                    </li>
                </ul>
            </div>
        </div>

        <div class="tab-content" id="transmittalTabsContent">
            <!-- Include the separate view files -->
            <?php include 'assets/views/transmittal/new_transmittal.php'; ?>
            <?php include 'assets/views/transmittal/pending_transmittals.php'; ?>
            <?php include 'assets/views/transmittal/receive_products.php'; ?>
            <?php include 'assets/views/transmittal/history.php'; ?>
        </div>
    </div>

    <!-- Include your JavaScript files here -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Transmittal module scripts -->
    <script src="./assets/scripts/transmittal/tables.js"></script>
    <script src="./assets/scripts/transmittal/tab-handling.js"></script>
    <script src="./assets/scripts/transmittal/product-selection.js"></script>
    <script src="./assets/scripts/transmittal/api-calls.js"></script>
    <script src="./assets/scripts/transmittal/product-scan.js"></script>
    <script src="./assets/scripts/transmittal/event-listeners.js"></script>
    <script src="./assets/scripts/transmittal/index.js"></script>
</body>

</html>
<?php
session_start();

// Check if the user session exists and is not empty
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header("Location: login_page.php");
    exit;
}

// If there is no logged-in user or the user's position is neither "ADMIN" nor "SUPERADMIN", redirect to credential_error.php.
if (!isset($_SESSION['user']) || (strtoupper($_SESSION['user']['positionEmployee']) !== 'ADMIN' && strtoupper($_SESSION['user']['positionEmployee']) !== 'SUPERADMIN')) {
    header("Location: credential_error.php");
    exit;
}

// Get the name of the currently logged-in user from the session.
$currentName = $_SESSION['user']['nameEmployee'];

include 'auth.php';
include './assets/controllers/db.php';
include './assets/controllers/reports/reports_controller.php';

// If user is ADMIN, force location to be their logged-in location
if (strtoupper($_SESSION['user']['positionEmployee']) === 'ADMIN') {
    $selectedLocation = $_SESSION['locationEmployee'];
} else {
    // For SUPERADMIN, use the selected location or default to null
    $selectedLocation = isset($_GET['location']) ? $_GET['location'] : null;
}

$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Fetch all report data at once to minimize database connections
$reports = [
    'daily' => [
        'sales' => getDailySalesReport($pdo, $selectedDate, $selectedLocation),
        'bonds' => getDailyBondReport($pdo, $selectedDate, $selectedLocation)
    ],
    'alltime' => [
        'report' => getAllTimeSalesReport($pdo, $selectedLocation),
        'sales' => getAllTimeSalesSummary($pdo, $selectedLocation),
        'bonds' => getAllTimeBondSummary($pdo, $selectedLocation)
    ],
    'unreturned' => getUnreturnedItems($pdo, $selectedLocation),
    'releasing' => getDueForRelease($pdo, $selectedLocation),
    'employees' => getEmployeeTransactionStats($pdo, $selectedLocation),
    'new_products' => getNewProductsReport($pdo, $selectedLocation)
];

// Add this after the h2 element and before the form
$locations = ['BACOLOD CITY', 'DUMAGUETE CITY', 'ILOILO CITY', 'SAN CARLOS CITY', 'CEBU CITY'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link rel="icon" type="image/png" href="./assets/img/sitelogo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="./assets/css/reports.css">
    <link rel="stylesheet" href="./assets/css/global.css">
</head>

<body>
    <?php include './assets/nav/nav.php'; ?>

    <div class="container mt-4">
        <!-- Title container with nav pills -->
        <div class="title-container">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Reports</h1>
                <!-- Navigation Pills -->
                <ul class="nav nav-pills" id="reportTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="daily-tab" data-bs-toggle="tab" data-bs-target="#daily" type="button" role="tab" aria-controls="daily" aria-selected="true">
                            Daily Sales
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="alltime-tab" data-bs-toggle="tab" data-bs-target="#alltime" type="button" role="tab" aria-controls="alltime" aria-selected="false">
                            All-Time Sales
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="new-products-tab" data-bs-toggle="tab" data-bs-target="#new-products" type="button" role="tab" aria-controls="new-products" aria-selected="false">
                            First-use Products
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="unreturned-tab" data-bs-toggle="tab" data-bs-target="#unreturned" type="button" role="tab" aria-controls="unreturned" aria-selected="false">
                            Unreturned Products
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="releasing-tab" data-bs-toggle="tab" data-bs-target="#releasing" type="button" role="tab" aria-controls="releasing" aria-selected="false">
                            Due for Releasing
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="employees-tab" data-bs-toggle="tab" data-bs-target="#employees" type="button" role="tab" aria-controls="employees" aria-selected="false">
                            Employee Stats
                        </button>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Date picker -->
        <div class="date-filter-container mb-3">
            <form id="reportFilterForm" class="d-flex justify-content-start gap-2" data-current-tab="daily">
                <input type="date" class="form-control date-picker" id="dateSelect" name="date" value="<?= $selectedDate ?>">
                <?php if (strtoupper($_SESSION['user']['positionEmployee']) === 'ADMIN'): ?>
                    <input type="hidden" name="location" value="<?= $_SESSION['locationEmployee'] ?>">
                <?php endif; ?>
                <select class="form-control" id="locationSelect" name="location" <?= strtoupper($_SESSION['user']['positionEmployee']) === 'ADMIN' ? 'disabled' : '' ?>>
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $location): ?>
                        <option value="<?= $location ?>" <?= $selectedLocation === $location ? 'selected' : '' ?>>
                            <?= $location ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="filterButton" class="btn btn-primary">
                    <i class="fas fa-search"></i> View Report
                </button>
            </form>
        </div>
        
        <!-- Tab Content -->
        <div class="tab-content animate-fade" id="reportTabContent">
            <?php
            // Include tab views
            include 'assets/views/reports/daily_tab.php';
            include 'assets/views/reports/alltime_tab.php';
            include 'assets/views/reports/unreturned_tab.php';
            include 'assets/views/reports/releasing_tab.php';
            include 'assets/views/reports/employees_tab.php';
            include 'assets/views/reports/new_products_tab.php';
            ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/scripts/reports.js"></script>
</body>
</html>
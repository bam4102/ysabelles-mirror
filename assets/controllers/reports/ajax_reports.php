<?php
session_start();

// Check if the user session exists and is not empty
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// If there is no logged-in user or the user's position is neither "ADMIN" nor "SUPERADMIN", return error
if (!isset($_SESSION['user']) || (strtoupper($_SESSION['user']['positionEmployee']) !== 'ADMIN' && strtoupper($_SESSION['user']['positionEmployee']) !== 'SUPERADMIN')) {
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

// Include database and report controllers
require_once '../../controllers/db.php';
require_once 'reports_controller.php';

$selectedDate = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
$selectedLocation = isset($_POST['location']) ? $_POST['location'] : null;
$tabType = isset($_POST['tab']) ? $_POST['tab'] : 'daily';

// Determine which data to fetch based on requested tab
$response = ['success' => true];

switch ($tabType) {
    case 'daily':
        $response['data'] = [
            'sales' => getDailySalesReport($pdo, $selectedDate, $selectedLocation),
            'bonds' => getDailyBondReport($pdo, $selectedDate, $selectedLocation)
        ];
        break;
    case 'alltime':
        $response['data'] = [
            'report' => getAllTimeSalesReport($pdo, $selectedLocation),
            'sales' => getAllTimeSalesSummary($pdo, $selectedLocation),
            'bonds' => getAllTimeBondSummary($pdo, $selectedLocation)
        ];
        break;
    case 'unreturned':
        $response['data'] = getUnreturnedItems($pdo, $selectedLocation);
        break;
    case 'releasing':
        $response['data'] = getDueForRelease($pdo, $selectedLocation);
        $response['metadata'] = [
            'tab' => 'releasing',
            'location' => $selectedLocation,
            'date' => $selectedDate
        ];
        break;
    case 'employees':
        $response['data'] = getEmployeeTransactionStats($pdo, $selectedLocation);
        break;
    case 'new_products':
        $response['data'] = getNewProductsReport($pdo, $selectedLocation);
        $response['metadata'] = [
            'tab' => 'new_products',
            'location' => $selectedLocation,
            'date' => $selectedDate
        ];
        break;
    default:
        $response = ['error' => 'Invalid tab type'];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response); 
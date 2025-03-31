<?php
/**
 * Product Availability Checker API
 * Checks if products are available for rental between specified dates
 */

// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");

// Include required files
require_once '../../assets/controllers/db.php';
require_once '../../assets/models/ProductModel.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    // Validate inputs
    if (!isset($_GET['start_date']) || !isset($_GET['end_date'])) {
        throw new Exception("Start date and end date are required");
    }
    
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];
    
    // Validate date format (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        throw new Exception("Invalid date format. Use YYYY-MM-DD");
    }
    
    // Make sure end date is not before start date
    if (strtotime($endDate) < strtotime($startDate)) {
        throw new Exception("End date cannot be before start date");
    }
    
    // Initialize product model
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception("Database connection not established");
    }
    
    $productModel = new ProductModel($pdo);
    
    // Get reservations for the date range
    $reservations = $productModel->getProductReservations($startDate, $endDate);
    
    // Get all products that are not sold
    $allProducts = $productModel->getProducts(['onlyActive' => true]);
    
    // Create a map of reserved product IDs
    $reservedProductIds = [];
    foreach ($reservations as $reservation) {
        $reservedProductIds[$reservation['productID']] = $reservation;
    }
    
    // Determine available products
    $availableProducts = [];
    $unavailableProducts = [];
    
    foreach ($allProducts as $product) {
        // Skip duplicate entries (due to images or variations)
        if (isset($availableProducts[$product['productID']]) || 
            isset($unavailableProducts[$product['productID']])) {
            continue;
        }
        
        if (isset($reservedProductIds[$product['productID']])) {
            // Product is reserved
            $unavailableProducts[$product['productID']] = [
                'productID' => $product['productID'],
                'nameProduct' => $product['nameProduct'],
                'locationProduct' => $product['locationProduct'],
                'reservation' => [
                    'transactionID' => $reservedProductIds[$product['productID']]['transactionID'],
                    'datePickUp' => $reservedProductIds[$product['productID']]['datePickUp'],
                    'dateReturn' => $reservedProductIds[$product['productID']]['dateReturn'],
                    'clientName' => $reservedProductIds[$product['productID']]['clientName']
                ]
            ];
        } else {
            // Product is available
            $availableProducts[$product['productID']] = [
                'productID' => $product['productID'],
                'nameProduct' => $product['nameProduct'],
                'locationProduct' => $product['locationProduct']
            ];
        }
    }
    
    // Prepare response
    $response['success'] = true;
    $response['message'] = 'Availability check completed';
    $response['data'] = [
        'start_date' => $startDate,
        'end_date' => $endDate,
        'availableCount' => count($availableProducts),
        'unavailableCount' => count($unavailableProducts),
        'totalCount' => count($allProducts),
        'availableProducts' => array_values($availableProducts),
        'unavailableProducts' => array_values($unavailableProducts)
    ];
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
} finally {
    // Return JSON response
    echo json_encode($response);
}
?> 
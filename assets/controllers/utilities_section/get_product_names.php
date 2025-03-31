<?php
// Include database connection
require_once '../db.php';

// Enable error logging
error_log("Get product names request received for category: " . $_GET['category'] ?? 'none');

// Set header to return JSON
header('Content-Type: application/json');

try {
    if (!isset($_GET['category']) || empty($_GET['category'])) {
        throw new Exception('Category code is required');
    }

    $categoryCode = $_GET['category'];
    
    // Simplified query - get names directly from product table
    $stmt = $pdo->prepare("
        SELECT DISTINCT nameProduct 
        FROM product
        WHERE typeProduct = :categoryCode
        ORDER BY productID DESC
        LIMIT 50
    ");
    
    $stmt->execute([':categoryCode' => $categoryCode]);
    
    $names = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $names[] = $row['nameProduct'];
    }
    
    error_log("Found " . count($names) . " product names for category: $categoryCode");
    
    // Return the names as JSON
    echo json_encode(['success' => true, 'names' => $names]);
    
} catch (Exception $e) {
    error_log("Error in get_product_names.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

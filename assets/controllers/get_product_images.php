<?php
/**
 * Controller to fetch product images
 * Returns all images associated with a specific product ID
 */

// Include database connection
require_once __DIR__ . '/db.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Check if product ID is provided
    if (!isset($_GET['id'])) {
        throw new Exception("Product ID is required");
    }
    
    $productID = (int)$_GET['id'];
    if ($productID <= 0) {
        throw new Exception("Invalid product ID");
    }
    
    // Prepare the query to fetch product images
    $stmt = $pdo->prepare("
        SELECT 
            pictureID, 
            pictureLocation, 
            isPrimary,
            isActive,
            fileType,
            fileSize,
            dateAdded
        FROM 
            picture
        WHERE 
            productID = :productID 
            AND isActive = 1
        ORDER BY 
            isPrimary DESC, 
            dateAdded DESC
    ");
    
    $stmt->execute(['productID' => $productID]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the images
    echo json_encode([
        'success' => true,
        'productID' => $productID,
        'imageCount' => count($images),
        'images' => $images
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in get_product_images.php: " . $e->getMessage());
    
    // Return error message
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 
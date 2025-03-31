<?php
require_once '../../controllers/db.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if product ID is provided
if (!isset($_GET['product_id']) || empty($_GET['product_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Product ID is required'
    ]);
    exit;
}

$productId = $_GET['product_id'];

try {
    // Fetch product details
    $query = "SELECT p.*, pc.productCategory 
              FROM product p 
              LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID 
              WHERE p.productID = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'Product not found'
        ]);
        exit;
    }
    
    // Fetch product images
    $imageQuery = "SELECT pictureID, pictureLocation, isPrimary 
                   FROM picture 
                   WHERE productID = ? 
                   AND isActive = 1 
                   ORDER BY isPrimary DESC, dateAdded ASC";
    $imageStmt = $pdo->prepare($imageQuery);
    $imageStmt->execute([$productId]);
    $images = $imageStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no images found, add a placeholder
    if (empty($images)) {
        $images[] = [
            'pictureID' => 0,
            'pictureLocation' => './assets/img/placeholder.jpg',
            'isPrimary' => 1
        ];
    }
    
    // Add images to product
    $product['images'] = $images;
    
    // Get product status
    $product['status'] = [
        'damaged' => (bool)$product['damageProduct'],
        'sold' => (bool)$product['soldProduct'],
        'inUse' => (bool)$product['useProduct'],
        'returned' => (bool)$product['returnedProduct']
    ];
    
    // Return success response with product data
    echo json_encode([
        'success' => true,
        'product' => $product
    ]);
    
} catch (PDOException $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 
<?php
require_once '../../controllers/db.php';

// Set headers for JSON response
header('Content-Type: application/json');

try {
    // Fetch all products with their categories
    $query = "SELECT p.*, pc.productCategory, e.nameEntourage 
              FROM product p 
              LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID 
              LEFT JOIN entourage e ON p.entourageID = e.entourageID
              ORDER BY p.productID DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch product images for all products
    $productIDs = array_column($products, 'productID');
    $images = [];
    $allProductImages = [];
    
    if (!empty($productIDs)) {
        $placeholders = implode(',', array_fill(0, count($productIDs), '?'));
        $imageQuery = "SELECT productID, pictureID, pictureLocation, isPrimary 
                       FROM picture 
                       WHERE productID IN ($placeholders) 
                       AND isActive = 1 
                       ORDER BY isPrimary DESC, dateAdded ASC";
                       
        $imageStmt = $pdo->prepare($imageQuery);
        $imageStmt->execute($productIDs);
        
        while ($row = $imageStmt->fetch(PDO::FETCH_ASSOC)) {
            // Normalize image path
            $imagePath = $row['pictureLocation'];
            
            // For the primary/first image to display in table
            if (!isset($images[$row['productID']])) {
                $images[$row['productID']] = $imagePath;
            }
            
            // Store all images for each product
            if (!isset($allProductImages[$row['productID']])) {
                $allProductImages[$row['productID']] = [];
            }
            
            $allProductImages[$row['productID']][] = [
                'id' => $row['pictureID'],
                'url' => $imagePath,
                'isPrimary' => $row['isPrimary']
            ];
        }
    }
    
    // Add image data to products
    foreach ($products as $key => $product) {
        $productId = $product['productID'];
        
        // Set primary image
        $products[$key]['imageUrl'] = isset($images[$productId]) 
            ? $images[$productId] 
            : './assets/img/placeholder.jpg';
        
        // Set all images
        $products[$key]['images'] = isset($allProductImages[$productId]) 
            ? $allProductImages[$productId] 
            : [['id' => 0, 'url' => './assets/img/placeholder.jpg', 'isPrimary' => 1]];
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'products' => $products
    ]);

} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 
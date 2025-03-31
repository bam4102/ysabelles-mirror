<?php
header('Content-Type: application/json');
require_once '../../controllers/db.php';

try {
    // Fetch all products with their categories
    $query = "SELECT p.*, pc.productCategory 
                FROM product p 
                LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID 
                ORDER BY p.productID DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch product images for all products
    $productIDs = array_column($allProducts, 'productID');
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
            // For the primary/first image to display in table
            if (!isset($images[$row['productID']])) {
                $images[$row['productID']] = $row['pictureLocation'];
            }
            
            // Store all images for each product
            if (!isset($allProductImages[$row['productID']])) {
                $allProductImages[$row['productID']] = [];
            }
            
            $allProductImages[$row['productID']][] = [
                'id' => $row['pictureID'],
                'url' => $row['pictureLocation'],
                'isPrimary' => $row['isPrimary']
            ];
        }
    }
    
    // Fetch product size variations
    $variationGroups = [];
    $variationQuery = "SELECT psv.id, psv.group_id, psv.product_id, psv.nameProduct, p.sizeProduct  
                        FROM product_size_variations psv
                        LEFT JOIN product p ON psv.product_id = p.productID
                        ORDER BY psv.group_id, p.sizeProduct";
    $variationStmt = $pdo->prepare($variationQuery);
    $variationStmt->execute();
    
    while ($row = $variationStmt->fetch(PDO::FETCH_ASSOC)) {
        $groupId = $row['group_id'];
        $productId = $row['product_id'];
        
        if (!isset($variationGroups[$groupId])) {
            $variationGroups[$groupId] = [];
        }
        
        // Store basic variation info
        $variationGroups[$groupId][] = [
            'id' => $row['id'],
            'product_id' => $productId,
            'nameProduct' => $row['nameProduct'] ?: null,
            'sizeProduct' => $row['sizeProduct'] ?: null
        ];
    }
    
    // Add image data and variation groups to products
    foreach ($allProducts as $key => $product) {
        $productId = $product['productID'];
        
        // Set primary image
        $allProducts[$key]['imageUrl'] = isset($images[$productId]) 
            ? $images[$productId] 
            : './assets/img/placeholder.jpg';
        
        // Set all images
        $allProducts[$key]['images'] = isset($allProductImages[$productId]) 
            ? $allProductImages[$productId] 
            : [['id' => 0, 'url' => './assets/img/placeholder.jpg', 'isPrimary' => 1]];
        
        // Set variation group
        $allProducts[$key]['variationGroupId'] = null;
        $allProducts[$key]['variations'] = [];
        
        // Find if this product belongs to a variation group
        foreach ($variationGroups as $groupId => $variations) {
            foreach ($variations as $variation) {
                if ($variation['product_id'] == $productId) {
                    $allProducts[$key]['variationGroupId'] = $groupId;
                    $allProducts[$key]['variations'] = $variations;
                    break 2; // Break out of both loops
                }
            }
        }
    }
    
    // Return JSON response
    echo json_encode($allProducts);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 
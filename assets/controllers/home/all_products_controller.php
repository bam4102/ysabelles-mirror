<?php
require_once __DIR__ . '/../db.php';

/**
 * Get all products for the product grid
 * 
 * @param int $limit Optional limit of products to fetch
 * @param int $offset Optional offset for pagination
 * @return array Array of products with their images
 */
function getAllProducts($limit = 0, $offset = 0) {
    global $pdo;
    
    try {
        // Prepare the query with or without limit
        $sql = "
            SELECT p.productID, p.nameProduct, p.priceProduct, p.colorProduct, 
                p.typeProduct, pc.productCategory, p.genderProduct, p.descProduct,
                p.soldProduct, p.damageProduct, p.counterProduct, pic.pictureLocation, pic.isPrimary
            FROM product p
            LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID
            LEFT JOIN picture pic ON p.productID = pic.productID
            WHERE p.soldProduct = 0 
            AND p.damageProduct = 0
            AND pic.isActive = 1
            ORDER BY p.nameProduct ASC
        ";
        
        // Add limit clause if requested
        if ($limit > 0) {
            $sql .= " LIMIT ?, ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$offset, $limit]);
        } else {
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
        }
        
        $allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Log the product count
        error_log("Found " . count($allProducts) . " products for grid");
        
        return $allProducts;
    } catch (PDOException $e) {
        error_log("Error fetching all products: " . $e->getMessage());
        return [];
    }
}

/**
 * Get total count of available products
 * 
 * @return int Total number of products
 */
function getTotalProductCount() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT p.productID) as total
            FROM product p
            WHERE p.soldProduct = 0 
            AND p.damageProduct = 0
        ");
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$result['total'];
    } catch (PDOException $e) {
        error_log("Error fetching product count: " . $e->getMessage());
        return 0;
    }
} 
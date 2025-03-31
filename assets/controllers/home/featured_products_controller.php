<?php
require_once __DIR__ . '/../db.php';

/**
 * Get featured products for the carousel
 * 
 * @return array Array of products with their images, sorted by view count
 */
function getFeaturedProducts() {
    global $pdo;
    
    try {
        // Get products with their images in a single query, sorted by view count
        $stmt = $pdo->prepare("
            SELECT p.productID, p.nameProduct, p.priceProduct, p.colorProduct, 
                p.typeProduct, pc.productCategory, p.genderProduct, p.descProduct,
                p.soldProduct, p.damageProduct, p.counterProduct, pic.pictureLocation, pic.isPrimary
            FROM product p
            LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID
            LEFT JOIN picture pic ON p.productID = pic.productID
            WHERE p.soldProduct = 0 
            AND p.damageProduct = 0
            AND pic.isActive = 1
            ORDER BY p.counterProduct DESC
        ");
        
        $stmt->execute();
        $allProductData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Log the product count
        error_log("Found " . count($allProductData) . " products for carousel");
        
        return $allProductData;
    } catch (PDOException $e) {
        error_log("Error fetching featured products: " . $e->getMessage());
        return [];
    }
} 
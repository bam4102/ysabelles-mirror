<?php
/**
 * Test script to directly check entourage data in the database
 * This file is for debugging purposes only and should be removed in production
 */

// Include database connection
require_once __DIR__ . '/../controllers/db.php';

// Set header to JSON
header('Content-Type: application/json');

try {
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception("Database connection not established");
    }
    
    $result = [
        'database_connected' => true,
        'tables' => []
    ];
    
    // Test 1: Check if entourage table exists and get count
    $entourageTest = $pdo->query("SHOW TABLES LIKE 'entourage'");
    $entourageTableExists = $entourageTest->rowCount() > 0;
    
    if ($entourageTableExists) {
        $countQuery = $pdo->query("SELECT COUNT(*) as count FROM entourage");
        $entourageCount = $countQuery->fetch(PDO::FETCH_ASSOC)['count'];
        
        $result['tables']['entourage'] = [
            'exists' => true,
            'count' => $entourageCount
        ];
        
        // If there are records, get sample data
        if ($entourageCount > 0) {
            $sampleQuery = $pdo->query("SELECT * FROM entourage LIMIT 5");
            $result['tables']['entourage']['sample_data'] = $sampleQuery->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        $result['tables']['entourage'] = [
            'exists' => false
        ];
    }
    
    // Test 2: Check product table for entourage associations
    $productQuery = $pdo->query("SHOW COLUMNS FROM product LIKE 'entourageID'");
    $entourageColumnExists = $productQuery->rowCount() > 0;
    
    if ($entourageColumnExists) {
        $productCountQuery = $pdo->query("SELECT COUNT(*) as count FROM product WHERE entourageID IS NOT NULL");
        $productCount = $productCountQuery->fetch(PDO::FETCH_ASSOC)['count'];
        
        $result['tables']['product'] = [
            'entourage_column_exists' => true,
            'products_with_entourage' => $productCount
        ];
        
        // If there are records, get sample data
        if ($productCount > 0) {
            $sampleProductQuery = $pdo->query("SELECT productID, nameProduct, entourageID FROM product WHERE entourageID IS NOT NULL LIMIT 5");
            $result['tables']['product']['sample_data'] = $sampleProductQuery->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        $result['tables']['product'] = [
            'entourage_column_exists' => false
        ];
    }
    
    // Test 3: Check picture table for entourage associations
    $pictureQuery = $pdo->query("SHOW COLUMNS FROM picture LIKE 'entourageID'");
    $pictureColumnExists = $pictureQuery->rowCount() > 0;
    
    if ($pictureColumnExists) {
        $pictureCountQuery = $pdo->query("SELECT COUNT(*) as count FROM picture WHERE entourageID IS NOT NULL");
        $pictureCount = $pictureCountQuery->fetch(PDO::FETCH_ASSOC)['count'];
        
        $result['tables']['picture'] = [
            'entourage_column_exists' => true,
            'pictures_with_entourage' => $pictureCount
        ];
        
        // If there are records, get sample data
        if ($pictureCount > 0) {
            $samplePictureQuery = $pdo->query("SELECT pictureID, pictureLocation, entourageID FROM picture WHERE entourageID IS NOT NULL LIMIT 5");
            $result['tables']['picture']['sample_data'] = $samplePictureQuery->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        $result['tables']['picture'] = [
            'entourage_column_exists' => false
        ];
    }
    
    // Test 4: Try the entourage query directly
    try {
        $directQuery = "
            SELECT 
                e.entourageID,
                e.nameEntourage,
                p.productID,
                p.nameProduct,
                p.priceProduct
            FROM entourage e
            LEFT JOIN product p ON e.entourageID = p.entourageID
            LIMIT 10
        ";
        
        $directStmt = $pdo->query($directQuery);
        $directResults = $directStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result['direct_query'] = [
            'success' => true,
            'count' => count($directResults),
            'sample_data' => $directResults
        ];
    } catch (PDOException $e) {
        $result['direct_query'] = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
    
    // Test 5: Try the exact query from getProductEntourage method
    try {
        $detailedQuery = "
            SELECT 
                e.entourageID,
                e.nameEntourage,
                p.productID,
                p.nameProduct,
                p.priceProduct,
                p.colorProduct,
                p.typeProduct,
                pc.productCategory,
                p.genderProduct,
                p.descProduct,
                p.soldProduct,
                p.damageProduct,
                pic.pictureLocation,
                pic.isPrimary,
                pic.pictureID
            FROM entourage e
            LEFT JOIN product p ON e.entourageID = p.entourageID
            LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID
            LEFT JOIN picture pic ON (
                (e.entourageID = pic.entourageID OR pic.productID = p.productID) 
                AND pic.isActive = 1
            )
            WHERE 1=1
            AND (p.soldProduct = 0 OR p.soldProduct IS NULL)
            ORDER BY e.nameEntourage ASC
            LIMIT 10
        ";
        
        $detailedStmt = $pdo->query($detailedQuery);
        $detailedResults = $detailedStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result['detailed_query'] = [
            'success' => true,
            'count' => count($detailedResults),
            'sample_data' => array_slice($detailedResults, 0, 5)
        ];
        
        // Process the data as the model would
        $entourageSets = [];
        foreach ($detailedResults as $row) {
            $entourageID = $row['entourageID'];
            
            if (!isset($entourageSets[$entourageID])) {
                $entourageSets[$entourageID] = [
                    'entourageID' => $entourageID,
                    'nameEntourage' => $row['nameEntourage'],
                    'products' => [],
                    'pictures' => []
                ];
            }
            
            // Add product if it exists
            if ($row['productID']) {
                $product = [
                    'productID' => $row['productID'],
                    'nameProduct' => $row['nameProduct'],
                    'priceProduct' => $row['priceProduct']
                ];
                
                // Only add if not already added
                if (!in_array($product['productID'], array_column($entourageSets[$entourageID]['products'] ?? [], 'productID'))) {
                    $entourageSets[$entourageID]['products'][] = $product;
                }
            }
            
            // Add picture if it exists
            if ($row['pictureLocation']) {
                $picture = [
                    'pictureID' => $row['pictureID'],
                    'pictureLocation' => $row['pictureLocation'],
                    'isPrimary' => $row['isPrimary']
                ];
                
                // Only add if not already added
                if (!in_array($picture['pictureID'], array_column($entourageSets[$entourageID]['pictures'] ?? [], 'pictureID'))) {
                    $entourageSets[$entourageID]['pictures'][] = $picture;
                }
            }
        }
        
        $result['processed_data'] = [
            'entourage_sets_count' => count($entourageSets),
            'data' => array_values($entourageSets)
        ];
        
    } catch (PDOException $e) {
        $result['detailed_query'] = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
    
    // Output the result
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'database_connected' => false,
        'error' => $e->getMessage()
    ]);
} 
<?php
/**
 * Direct test script for entourage retrieval
 * Bypasses the normal controller flow to test the database directly
 */

// Include database connection
require_once __DIR__ . '/../controllers/db.php';

// Set header to JSON
header('Content-Type: application/json');

try {
    // Check connection
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception("Database connection not established");
    }

    // Get all entourage sets directly
    $query = "SELECT * FROM entourage ORDER BY nameEntourage";
    $stmt = $pdo->query($query);
    $entourageSets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get products for each entourage
    $result = [];
    foreach ($entourageSets as $set) {
        $entourageID = $set['entourageID'];
        
        // Get products
        $productQuery = "SELECT * FROM product WHERE entourageID = ?";
        $productStmt = $pdo->prepare($productQuery);
        $productStmt->execute([$entourageID]);
        $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get pictures
        $pictureQuery = "
            SELECT * FROM picture 
            WHERE entourageID = ? 
            OR productID IN (SELECT productID FROM product WHERE entourageID = ?)
        ";
        $pictureStmt = $pdo->prepare($pictureQuery);
        $pictureStmt->execute([$entourageID, $entourageID]);
        $pictures = $pictureStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Build result
        $result[] = [
            'entourageID' => $entourageID,
            'nameEntourage' => $set['nameEntourage'],
            'productCount' => count($products),
            'pictureCount' => count($pictures),
            'products' => $products,
            'pictures' => $pictures
        ];
    }
    
    // Output the result
    echo json_encode([
        'success' => true,
        'count' => count($result),
        'data' => $result
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 
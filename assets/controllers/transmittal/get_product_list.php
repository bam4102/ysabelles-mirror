<?php
session_start();
require_once '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userLocation = $_SESSION['user']['locationEmployee'];

try {
    $sql = "SELECT p.*, pc.productCategory 
    FROM product p 
    LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID 
    LEFT JOIN transmittal t ON p.productID = t.productID AND t.statusTransmittal IN ('PENDING', 'IN_TRANSIT')
    WHERE p.soldProduct = 0 
    AND p.locationProduct = ?
    AND t.transmittalID IS NULL
    AND (p.isNew IS NULL OR p.isNew = 0)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userLocation]);
    
    $products = [];
    while ($row = $stmt->fetch()) {
        $products[] = [
            'id' => $row['productID'],
            'name' => $row['nameProduct'],
            'type' => $row['productCategory'] ?? $row['typeProduct'],
            'location' => $row['locationProduct'],
            'status' => $row['useProduct'] ? 'In Use' : 'Available'
        ];
    }
    
    echo json_encode(['status' => 'success', 'data' => $products]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 
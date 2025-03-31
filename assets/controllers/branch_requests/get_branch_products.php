<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['employeeID'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Include database connection
require_once '../../controllers/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['branch']) && isset($_POST['requiredDate'])) {
    $branch = $_POST['branch'];
    $requiredDate = $_POST['requiredDate'];
    $sourceBranch = $_SESSION['user']['locationEmployee']; // Current user's branch
    
    try {
        // First, get all products from the branch
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.productID, p.nameProduct, p.typeProduct, p.damageProduct, p.descProduct
            FROM product p
            LEFT JOIN purchase pu ON p.productID = pu.productID
            LEFT JOIN transaction t ON pu.transactionID = t.transactionID
            WHERE p.locationProduct = ? 
            AND p.soldProduct = 0
            AND (
                pu.productID IS NULL 
                OR t.dateReturn > ?
                OR (
                    t.dateReturn <= ?
                    AND t.bondStatus != 1 
                    AND NOT (t.bondStatus = 0 AND t.balanceTransaction != t.chargeTransaction)
                )
            )
            ORDER BY p.nameProduct ASC
        ");
        $stmt->execute([$branch, $requiredDate, $requiredDate]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Then, get all product IDs that are in pending requests from the user's branch
        $pendingStmt = $pdo->prepare("
            SELECT products
            FROM branch_requests 
            WHERE status = 'PENDING' AND sourceBranch = ?
        ");
        $pendingStmt->execute([$sourceBranch]);
        $pendingRequests = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create a set of product IDs that are in pending requests
        $pendingProductIds = [];
        foreach ($pendingRequests as $request) {
            $requestProducts = json_decode($request['products'], true);
            foreach ($requestProducts as $product) {
                $pendingProductIds[] = $product['id'];
            }
        }
        
        // Filter out products that are in pending requests
        $filteredProducts = array_filter($products, function($product) use ($pendingProductIds) {
            return !in_array($product['productID'], $pendingProductIds);
        });
        
        header('Content-Type: application/json');
        echo json_encode(array_values($filteredProducts)); // reset array keys
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request']);
    exit;
}
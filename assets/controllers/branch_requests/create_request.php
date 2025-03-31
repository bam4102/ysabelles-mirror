<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['employeeID'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Include database connection
require_once '../../controllers/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    if (empty($_POST['sourceBranch']) || empty($_POST['destinationBranch']) || 
        empty($_POST['products']) || empty($_POST['requiredDate'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $sourceBranch = $_POST['sourceBranch'];
    $destinationBranch = $_POST['destinationBranch'];
    $productIds = $_POST['products']; // Array of product IDs
    $notes = $_POST['notes'] ?? '';
    $requiredDate = $_POST['requiredDate'];
    $employeeId = $_SESSION['employeeID'];

    try {
        // Start a transaction
        $pdo->beginTransaction();

        // Retrieve product details for each selected product
        $productData = [];
        foreach ($productIds as $productId) {
            $stmt = $pdo->prepare("
                SELECT productID, nameProduct, codeProduct, priceProduct, typeProduct 
                FROM product 
                WHERE productID = ?
            ");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                $productData[] = [
                    'id' => $product['productID'],
                    'name' => $product['nameProduct'],
                    'code' => $product['codeProduct'],
                    'price' => $product['priceProduct'],
                    'typeProduct' => $product['typeProduct']
                ];
            }
        }

        // Convert product data to JSON
        $productsJson = json_encode($productData);

        // Insert into branch_requests table
        $stmt = $pdo->prepare("
            INSERT INTO branch_requests 
            (sourceBranch, destinationBranch, products, notes, requiredDate, requestedBy) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$sourceBranch, $destinationBranch, $productsJson, $notes, $requiredDate, $employeeId]);

        // Commit the transaction
        $pdo->commit();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Request created successfully']);
    } catch (PDOException $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

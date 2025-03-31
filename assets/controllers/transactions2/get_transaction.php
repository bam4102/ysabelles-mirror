<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Transaction ID is required']);
    exit;
}

try {
    $transactionId = intval($_GET['id']);
    
    $sql = "SELECT t.*, 
            CONCAT('[', GROUP_CONCAT(
                JSON_OBJECT(
                    'productID', p.productID,
                    'nameProduct', p.nameProduct,
                    'priceProduct', p.priceProduct,
                    'priceSold', p.priceSold,
                    'soldPProduct', pu.soldPProduct,
                    'is_confirmed_sold', (
                        SELECT COUNT(*) > 0 
                        FROM product_history 
                        WHERE productID = p.productID 
                        AND action_type = 'SOLD'
                    )
                )
            ), ']') as products
            FROM transaction t
            LEFT JOIN purchase pu ON t.transactionID = pu.transactionID
            LEFT JOIN product p ON pu.productID = p.productID
            WHERE t.transactionID = ?
            GROUP BY t.transactionID";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$transactionId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        throw new Exception('Transaction not found');
    }
    
    echo json_encode($transaction);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

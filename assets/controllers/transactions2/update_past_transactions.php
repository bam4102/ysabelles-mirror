<?php
session_start();
require_once '../db.php';

// Check user permissions
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userPosition = strtoupper($_SESSION['user']['positionEmployee']);
if (!in_array($userPosition, ['ADMIN', 'SUPERADMIN'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - Insufficient permissions']);
    exit;
}

header('Content-Type: application/json');

try {
    // Get current date
    $currentDate = date('Y-m-d');
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Find transactions with no payment records and bondStatus = 0
    $stmt = $pdo->prepare("
        SELECT t.transactionID 
        FROM transaction t
        LEFT JOIN payment p ON t.transactionID = p.transactionID
        WHERE t.bondStatus = 0
        AND p.paymentID IS NULL
        GROUP BY t.transactionID
    ");
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $updatedCount = 0;
    
    if (count($transactions) > 0) {
        // Update these transactions to bondStatus = 3
        $placeholders = implode(',', array_fill(0, count($transactions), '?'));
        $stmt = $pdo->prepare("
            UPDATE transaction 
            SET bondStatus = 3
            WHERE transactionID IN ($placeholders)
        ");
        $stmt->execute($transactions);
        $updatedCount = $stmt->rowCount();
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "$updatedCount transactions with no payments marked as inactive",
        'count' => $updatedCount
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 
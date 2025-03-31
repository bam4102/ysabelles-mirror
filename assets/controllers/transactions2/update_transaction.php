<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $transactionId = $_POST['transactionId'] ?? null;
    $clientName = $_POST['clientName'] ?? null;
    $clientAddress = $_POST['clientAddress'] ?? null;
    $clientContact = $_POST['clientContact'] ?? null;
    $location = $_POST['location'] ?? null;
    $pickupDate = $_POST['pickupDate'] ?? null;
    $returnDate = $_POST['returnDate'] ?? null;
    $discount = $_POST['discount'] ?? 0;
    $charge = $_POST['charge'] ?? 0;
    $bondRequired = $_POST['bondRequired'] ?? 0;
    $bondStatus = $_POST['bondStatus'] ?? 0;
    
    if (!$transactionId || !$clientName || !$clientAddress || !$clientContact || 
        !$location || !$pickupDate || !$returnDate) {
        throw new Exception('Missing required fields');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Get current values for fields that are disabled in the form
    $stmt = $pdo->prepare("
        SELECT dateTransaction, balanceTransaction, bondBalance 
        FROM transaction 
        WHERE transactionID = ?
    ");
    $stmt->execute([$transactionId]);
    $currentValues = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentValues) {
        throw new Exception('Transaction not found');
    }
    
    // Use current values for disabled fields
    $transactionDate = $currentValues['dateTransaction'];
    $balance = $currentValues['balanceTransaction'];
    $bondBalance = $currentValues['bondBalance'];
    
    // Update transaction with new fields
    $stmt = $pdo->prepare("
        UPDATE transaction 
        SET clientName = ?,
            clientAddress = ?,
            clientContact = ?,
            locationTransaction = ?,
            datePickUp = ?,
            dateReturn = ?,
            discountTransaction = ?,
            chargeTransaction = ?,
            bondTransaction = ?,
            bondStatus = ?
        WHERE transactionID = ?
    ");
    
    $stmt->execute([
        $clientName,
        $clientAddress,
        $clientContact,
        $location,
        $pickupDate,
        $returnDate,
        $discount,
        $charge,
        $bondRequired,
        $bondStatus,
        $transactionId
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Transaction updated successfully'
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

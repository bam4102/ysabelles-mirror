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
    $date = $_POST['date'] ?? null;
    $mode = $_POST['mode'] ?? null;
    $amount = $_POST['amount'] ?? null;
    $note = $_POST['note'] ?? '';
    
    if (!$transactionId || !$date || !$mode || !$amount) {
        throw new Exception('Missing required fields');
    }

    // Start transaction
    $pdo->beginTransaction();
    
    // Get current transaction details
    $stmt = $pdo->prepare("SELECT balanceTransaction, bondTransaction, bondStatus FROM transaction WHERE transactionID = ?");
    $stmt->execute([$transactionId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        throw new Exception('Transaction not found');
    }
    
    $currentBalance = $transaction['balanceTransaction'];
    $newBalance = $currentBalance - $amount;
    
    if ($newBalance < 0) {
        throw new Exception('Payment amount exceeds remaining balance');
    }
    
    // Insert payment record
    $stmt = $pdo->prepare("
        INSERT INTO payment (
            transactionID, employeeID, datePayment, 
            kindPayment, amountPayment, paymentCurrentBalance, 
            notePayment
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $transactionId,
        $_SESSION['user_id'] ?? 1, // Use logged-in user ID
        $date,
        $mode,
        $amount,
        $newBalance,
        $note
    ]);
    
    $paymentId = $pdo->lastInsertId();
    
    // Update transaction balance and bondStatus if needed
    if ($newBalance == 0 && $transaction['bondTransaction'] == 0 && $transaction['bondStatus'] == 0) {
        // If fully paid and no bond required, set bondStatus to 1 (complete)
        $stmt = $pdo->prepare("
            UPDATE transaction 
            SET balanceTransaction = ?, bondStatus = 1
            WHERE transactionID = ?
        ");
    } else {
        // Normal update
        $stmt = $pdo->prepare("
            UPDATE transaction 
            SET balanceTransaction = ? 
            WHERE transactionID = ?
        ");
    }
    
    $stmt->execute([$newBalance, $transactionId]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'paymentId' => $paymentId,
        'newBalance' => $newBalance
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

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
    $amount = $_POST['amount'] ?? null;
    $note = $_POST['note'] ?? '';
    
    if (!$transactionId || !$date || !$amount) {
        throw new Exception('Missing required fields');
    }

    // Start transaction
    $pdo->beginTransaction();
    
    // Get current transaction details
    $stmt = $pdo->prepare("SELECT bondBalance FROM transaction WHERE transactionID = ?");
    $stmt->execute([$transactionId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        throw new Exception('Transaction not found');
    }
    
    $currentBondBalance = $transaction['bondBalance'];
    
    if ($currentBondBalance < $amount) {
        throw new Exception('Release amount exceeds available bond balance');
    }
    
    $newBondBalance = $currentBondBalance - $amount;
    
    // Insert bond release record
    $stmt = $pdo->prepare("
        INSERT INTO bond (
            transactionID, employeeID, dateBond, 
            releaseBond, bondCurrentBalance, noteBond
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $transactionId,
        $_SESSION['user_id'] ?? 1, // Use logged-in user ID or default to 1 if not logged in
        $date,
        $amount,
        $newBondBalance,
        $note
    ]);
    
    $bondId = $pdo->lastInsertId();
    
    // Update transaction bond balance
    // If balance is 0, set bondStatus to 2 (completed)
    if ($newBondBalance == 0) {
        $stmt = $pdo->prepare("
            UPDATE transaction 
            SET bondBalance = ?, bondStatus = 2
            WHERE transactionID = ?
        ");
    } else {
        $stmt = $pdo->prepare("
            UPDATE transaction 
            SET bondBalance = ?
            WHERE transactionID = ?
        ");
    }
    
    $stmt->execute([$newBondBalance, $transactionId]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'bondId' => $bondId,
        'newBalance' => $newBondBalance
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

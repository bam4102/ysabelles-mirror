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
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['payment'])) {
        throw new Exception('Payment data is required');
    }

    $pdo->beginTransaction();

    // Process payment
    $payment = $data['payment'];
    $stmt = $pdo->prepare("
        SELECT balanceTransaction, bondTransaction, bondStatus
        FROM transaction 
        WHERE transactionID = ?
    ");
    $stmt->execute([$payment['transactionId']]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        throw new Exception('Transaction not found');
    }

    $newBalance = $transaction['balanceTransaction'] - $payment['amount'];
    
    // Insert payment
    $stmt = $pdo->prepare("
        INSERT INTO payment (
            transactionID, employeeID, datePayment, 
            kindPayment, amountPayment, paymentCurrentBalance, 
            notePayment
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $payment['transactionId'],
        $_SESSION['user_id'] ?? 1,
        $payment['date'],
        $payment['mode'],
        $payment['amount'],
        $newBalance,
        $payment['note']
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
        $stmt->execute([$newBalance, $payment['transactionId']]);
    } else {
        // Normal update
        $stmt = $pdo->prepare("
            UPDATE transaction 
            SET balanceTransaction = ?
            WHERE transactionID = ?
        ");
        $stmt->execute([$newBalance, $payment['transactionId']]);
    }

    $bondId = null;
    // Process bond if provided
    if (isset($data['bond']) && $data['bond']) {
        $bond = $data['bond'];
        $newBondBalance = $transaction['bondTransaction'];
        
        // Insert bond
        $stmt = $pdo->prepare("
            INSERT INTO bond (
                transactionID, employeeID, dateBond, 
                depositBond, bondCurrentBalance, noteBond
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $bond['transactionId'],
            $_SESSION['user_id'] ?? 1,
            $bond['date'],
            $bond['amount'],
            $bond['amount'],
            $bond['note']
        ]);
        
        $bondId = $pdo->lastInsertId();

        // Update transaction bond status
        $stmt = $pdo->prepare("
            UPDATE transaction 
            SET bondBalance = ?, bondStatus = 1
            WHERE transactionID = ?
        ");
        $stmt->execute([$bond['amount'], $bond['transactionId']]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'paymentId' => $paymentId,
        'bondId' => $bondId
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

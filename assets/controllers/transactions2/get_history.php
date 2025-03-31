<?php
include '../db.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Transaction ID is required']);
    exit;
}

$transactionId = intval($_GET['id']);

try {
    // Get payment history
    $paymentSql = "SELECT p.*, e.nameEmployee
                   FROM payment p
                   LEFT JOIN employee e ON p.employeeID = e.employeeID
                   WHERE p.transactionID = ? 
                   ORDER BY p.datePayment ASC";
    $stmt = $pdo->prepare($paymentSql);
    $stmt->execute([$transactionId]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get bond history
    $bondSql = "SELECT b.*, e.nameEmployee
                FROM bond b
                LEFT JOIN employee e ON b.employeeID = e.employeeID
                WHERE b.transactionID = ?
                ORDER BY b.dateBond ASC";
    $stmt = $pdo->prepare($bondSql);
    $stmt->execute([$transactionId]);
    $bonds = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Separate bonds into deposits and returns
    $bondDeposits = array_filter($bonds, fn($bond) => !is_null($bond['depositBond']));
    $bondReturns = array_filter($bonds, fn($bond) => !is_null($bond['releaseBond']));

    echo json_encode([
        'payments' => array_values($payments),
        'bondDeposits' => array_values($bondDeposits),
        'bondReturns' => array_values($bondReturns)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

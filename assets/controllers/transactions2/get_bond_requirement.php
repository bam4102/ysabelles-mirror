<?php
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Transaction ID required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT bondTransaction FROM transaction WHERE transactionID = ?");
    $stmt->execute([$_GET['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'bondRequired' => floatval($result['bondTransaction'] ?? 0)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

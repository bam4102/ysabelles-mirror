<?php
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

try {
    // Set timezone to Philippines
    date_default_timezone_set('Asia/Manila');
    
    // Get parameters
    $requestId = isset($_POST['requestId']) ? $_POST['requestId'] : null;
    $userLocation = $_SESSION['user']['locationEmployee'];
    $employeeId = $_SESSION['user']['employeeID'];
    
    if (!$requestId) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
        exit;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // First verify that the user is authorized to receive these transmittals
    $checkSql = "SELECT COUNT(*) as count 
                FROM transmittal 
                WHERE requestID = ? 
                AND toLocation = ? 
                AND statusTransmittal = 'IN_TRANSIT'";
    $stmt = $pdo->prepare($checkSql);
    $stmt->execute([$requestId, $userLocation]);
    $result = $stmt->fetch();
    
    if ($result['count'] <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'No transmittals found or not authorized to receive']);
        $pdo->rollBack();
        exit;
    }
    
    // Update all transmittals for this request to DELIVERED status
    $timeZoneSql = "SET time_zone = '+08:00'";
    $pdo->exec($timeZoneSql);
    
    $updateSql = "UPDATE transmittal 
                SET statusTransmittal = 'DELIVERED',
                    dateDelivered = NOW(),
                    receivedBy = ?
                WHERE requestID = ?
                AND toLocation = ?
                AND statusTransmittal = 'IN_TRANSIT'";
    $stmt = $pdo->prepare($updateSql);
    $status = $stmt->execute([$employeeId, $requestId, $userLocation]);
    
    if (!$status) {
        throw new Exception("Database error while updating transmittals");
    }
    
    // Get the number of affected rows
    $affectedRows = $stmt->rowCount();
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Successfully received ' . $affectedRows . ' transmittals',
        'count' => $affectedRows
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 
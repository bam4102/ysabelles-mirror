<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['employeeID'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Include database connection
require_once '../../controllers/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['requestId'])) {
    $requestId = $_POST['requestId'];
    
    try {
        // Get request details
        $stmt = $pdo->prepare("
            SELECT r.*, 
                   DATE_FORMAT(r.dateRequested, '%Y-%m-%d') as dateRequested,
                   DATE_FORMAT(r.completedDate, '%Y-%m-%d') as completedDate,
                   req.nameEmployee as requestedByName,
                   resp.nameEmployee as respondedByName,
                   comp.nameEmployee as completedByName
            FROM branch_requests r
            LEFT JOIN employee req ON req.employeeID = r.requestedBy
            LEFT JOIN employee resp ON resp.employeeID = r.respondedBy
            LEFT JOIN employee comp ON comp.employeeID = r.completedBy
            WHERE r.requestID = ?
        ");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Request not found']);
            exit;
        }
        
        header('Content-Type: application/json');
        echo json_encode($request);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

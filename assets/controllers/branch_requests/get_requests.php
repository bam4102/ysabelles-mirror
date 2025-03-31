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

// Get current user's branch location
$employeeId = $_SESSION['employeeID'];
$stmt = $pdo->prepare("SELECT locationEmployee FROM employee WHERE employeeID = ?");
$stmt->execute([$employeeId]);
$userBranch = $stmt->fetch(PDO::FETCH_ASSOC)['locationEmployee'];

try {
    // Get all requests initiated by this branch
    $stmt = $pdo->prepare("
        SELECT requestID, sourceBranch, destinationBranch, products, notes, 
               requiredDate, DATE_FORMAT(dateRequested, '%Y-%m-%d') as dateRequested, 
               status, requestedBy, respondedBy, completedBy, completedDate
        FROM branch_requests 
        WHERE sourceBranch = ?
        ORDER BY dateRequested DESC
    ");
    $stmt->execute([$userBranch]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($requests);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

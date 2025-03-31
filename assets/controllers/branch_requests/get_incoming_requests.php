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
    // Get all incoming requests to this branch
    $stmt = $pdo->prepare("
        SELECT requestID, sourceBranch, destinationBranch, products, notes, 
               requiredDate, DATE_FORMAT(dateRequested, '%Y-%m-%d') as dateRequested, 
               status, requestedBy, respondedBy, completedBy, completedDate,
               (status = 'APPROVED' AND completedDate IS NULL) as needsCompletion
        FROM branch_requests 
        WHERE destinationBranch = ?
        ORDER BY dateRequested DESC
    ");
    $stmt->execute([$userBranch]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add a "completed" flag for frontend convenience
    foreach ($requests as &$request) {
        $request['completed'] = !empty($request['completedDate']);
    }
    
    header('Content-Type: application/json');
    echo json_encode($requests);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

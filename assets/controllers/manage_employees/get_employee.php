<?php
session_start();
include '../db.php';
header('Content-Type: application/json');

// Check if user is logged in and has proper permissions
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Get user position and location
$position = strtoupper($_SESSION['user']['positionEmployee']);
$userLocation = $_SESSION['user']['locationEmployee'];

if (!isset($_GET['employeeID']) || empty($_GET['employeeID'])) {
    echo json_encode(['success' => false, 'error' => 'Employee ID is required']);
    exit;
}

$employeeID = $_GET['employeeID'];

try {
    $stmt = $pdo->prepare("SELECT * FROM employee WHERE employeeID = ?");
    $stmt->execute([$employeeID]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        echo json_encode(['success' => false, 'error' => 'Employee not found']);
        exit;
    }
    
    // For ADMIN users, check if the employee is from their location
    if ($position === 'ADMIN' && $employee['locationEmployee'] !== $userLocation) {
        echo json_encode(['success' => false, 'error' => 'You can only view employees from your location']);
        exit;
    }
    
    echo json_encode(['success' => true, 'employee' => $employee]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

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

if (!isset($_POST['employeeID']) || empty($_POST['employeeID'])) {
    echo json_encode(['success' => false, 'error' => 'Employee ID is required']);
    exit;
}

$employeeID = $_POST['employeeID'];

// If ADMIN, verify this employee belongs to their location
if ($position === 'ADMIN') {
    $stmt = $pdo->prepare("SELECT locationEmployee FROM employee WHERE employeeID = ?");
    $stmt->execute([$employeeID]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        echo json_encode(['success' => false, 'error' => 'Employee not found.']);
        exit;
    }
    
    // Check if they're trying to delete an employee from another location
    if ($employee['locationEmployee'] !== $userLocation) {
        echo json_encode(['success' => false, 'error' => 'You can only delete employees from your location.']);
        exit;
    }
}

try {
    $stmt = $pdo->prepare("DELETE FROM employee WHERE employeeID = ?");
    $stmt->execute([$employeeID]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Employee not found or already deleted.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

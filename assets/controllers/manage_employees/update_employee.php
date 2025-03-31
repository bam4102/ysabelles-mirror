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

// Check required fields except password (making password optional).
$required = ['employeeID', 'nameEmployee', 'usernameEmployee', 'dateStart', 'locationEmployee', 'positionEmployee'];
foreach ($required as $field) {
    if (!isset($_POST[$field]) || $_POST[$field] === '') {
        echo json_encode(['success' => false, 'error' => "$field is required."]);
        exit;
    }
}

$employeeID       = $_POST['employeeID'];
$nameEmployee     = $_POST['nameEmployee'];
$usernameEmployee = $_POST['usernameEmployee'];
$dateStart        = $_POST['dateStart'];
$dateEnd          = (isset($_POST['dateEnd']) && $_POST['dateEnd'] !== '') ? $_POST['dateEnd'] : null;
$locationEmployee = $_POST['locationEmployee'];
$positionEmployee = strtoupper($_POST['positionEmployee']); // Ensure position is uppercase for consistency
$employedEmployee = isset($_POST['employedEmployee']) ? 1 : 0;

// If ADMIN, first verify this employee belongs to their location
if ($position === 'ADMIN') {
    $stmt = $pdo->prepare("SELECT locationEmployee, positionEmployee FROM employee WHERE employeeID = ?");
    $stmt->execute([$employeeID]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        echo json_encode(['success' => false, 'error' => 'Employee not found.']);
        exit;
    }
    
    // Check if they're trying to edit an employee from another location
    if ($employee['locationEmployee'] !== $userLocation) {
        echo json_encode(['success' => false, 'error' => 'You can only edit employees from your location.']);
        exit;
    }
    
    // Check if they're trying to change the employee's location
    if ($locationEmployee !== $userLocation) {
        echo json_encode(['success' => false, 'error' => 'You cannot change an employee to a different location.']);
        exit;
    }
    
    // Prevent ADMIN users from changing position to ADMIN or SUPERADMIN
    if ($positionEmployee === 'ADMIN' || $positionEmployee === 'SUPERADMIN') {
        echo json_encode(['success' => false, 'error' => 'You do not have permission to create or modify administrator accounts.']);
        exit;
    }
}

// Get the password field (it may be empty)
$passwordEmployee = isset($_POST['passwordEmployee']) ? $_POST['passwordEmployee'] : '';

if ($passwordEmployee !== '') {
    // A new password is provided, so hash it and update the column.
    $hashedPassword = password_hash($passwordEmployee, PASSWORD_DEFAULT);
    $query = "UPDATE employee 
              SET nameEmployee = ?, 
                  usernameEmployee = ?, 
                  passwordEmployee = ?, 
                  dateStart = ?, 
                  dateEnd = ?, 
                  employedEmployee = ?, 
                  locationEmployee = ?, 
                  positionEmployee = ? 
              WHERE employeeID = ?";
    $params = [
        $nameEmployee,
        $usernameEmployee,
        $hashedPassword,
        $dateStart,
        $dateEnd,
        $employedEmployee,
        $locationEmployee,
        $positionEmployee,
        $employeeID
    ];
} else {
    // No new password provided; leave the password column unchanged.
    $query = "UPDATE employee 
              SET nameEmployee = ?, 
                  usernameEmployee = ?, 
                  dateStart = ?, 
                  dateEnd = ?, 
                  employedEmployee = ?, 
                  locationEmployee = ?, 
                  positionEmployee = ? 
              WHERE employeeID = ?";
    $params = [
        $nameEmployee,
        $usernameEmployee,
        $dateStart,
        $dateEnd,
        $employedEmployee,
        $locationEmployee,
        $positionEmployee,
        $employeeID
    ];
}

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

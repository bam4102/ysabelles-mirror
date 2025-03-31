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

// Check required fields.
$required = ['nameEmployee', 'usernameEmployee', 'passwordEmployee', 'dateStart', 'locationEmployee', 'positionEmployee'];
foreach ($required as $field) {
    if (!isset($_POST[$field]) || $_POST[$field] === '') {
        echo json_encode(['success' => false, 'error' => "$field is required."]);
        exit;
    }
}

$nameEmployee     = $_POST['nameEmployee'];
$usernameEmployee = $_POST['usernameEmployee'];
$passwordEmployee = $_POST['passwordEmployee'];
$dateStart        = $_POST['dateStart'];
$dateEnd          = (isset($_POST['dateEnd']) && $_POST['dateEnd'] !== '') ? $_POST['dateEnd'] : null;
$locationEmployee = $_POST['locationEmployee'];
$positionEmployee = strtoupper($_POST['positionEmployee']); // Ensure position is uppercase for consistency
$employedEmployee = isset($_POST['employedEmployee']) ? 1 : 0;

// For ADMIN users, restrict creating accounts to their own location
if ($position === 'ADMIN' && $locationEmployee !== $userLocation) {
    echo json_encode(['success' => false, 'error' => 'You can only create accounts for your own location.']);
    exit;
}

// Prevent ADMIN users from creating ADMIN or SUPERADMIN accounts
if ($position === 'ADMIN' && ($positionEmployee === 'ADMIN' || $positionEmployee === 'SUPERADMIN')) {
    echo json_encode(['success' => false, 'error' => 'You do not have permission to create administrator accounts.']);
    exit;
}

// Hash the password.
$hashedPassword = password_hash($passwordEmployee, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO employee (nameEmployee, usernameEmployee, passwordEmployee, dateStart, dateEnd, employedEmployee, locationEmployee, positionEmployee) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
      $nameEmployee,
      $usernameEmployee,
      $hashedPassword,
      $dateStart,
      $dateEnd,
      $employedEmployee,
      $locationEmployee,
      $positionEmployee
    ]);
    $newEmployeeID = $pdo->lastInsertId();
    echo json_encode(['success' => true, 'employeeID' => $newEmployeeID]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

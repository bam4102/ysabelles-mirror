<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['employeeID'])) {
    header("Location: login_page.php");
    exit;
}

// If the user's position is not ADMIN or CASHIER, redirect to credential_error.php
$userPosition = strtoupper($_SESSION['user']['positionEmployee']);
if (!in_array($userPosition, ['ADMIN', 'INVENTORY', 'SUPERADMIN'])) {
    header("Location: credential_error.php");
    exit;
}

include 'auth.php';
// Include database connection
require_once 'assets/controllers/db.php';

// Get all branch locations for dropdown
$stmt = $pdo->prepare("SELECT DISTINCT locationProduct FROM product WHERE locationProduct != '' GROUP BY locationProduct");
$stmt->execute();
$branches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get the name of the currently logged-in user from the session.
$currentName = $_SESSION['user']['nameEmployee'];

// Get current user's branch location
$employeeId = $_SESSION['employeeID'];
$stmt = $pdo->prepare("SELECT locationEmployee FROM employee WHERE employeeID = ?");
$stmt->execute([$employeeId]);
$userBranch = $stmt->fetch(PDO::FETCH_ASSOC)['locationEmployee'];

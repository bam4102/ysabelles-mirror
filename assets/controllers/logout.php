<?php
session_start();
include './db.php';

// Clear the session token in the database if user is logged in
if (isset($_SESSION['employeeID'])) {
    try {
        $stmt = $pdo->prepare("UPDATE employee SET session_token = NULL WHERE employeeID = ?");
        $stmt->execute([$_SESSION['employeeID']]);
        error_log("Session token cleared for user ID: " . $_SESSION['employeeID']);
    } catch (PDOException $e) {
        error_log("Error clearing session token: " . $e->getMessage());
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: ../../login_page.php");
exit;

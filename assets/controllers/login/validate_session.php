<?php
session_start();
include '../db.php';

function validateSession() {
    global $pdo;
    
    if (!isset($_SESSION['employeeID']) || !isset($_SESSION['session_token'])) {
        return false;
    }

    $stmt = $pdo->prepare("SELECT session_token FROM employee WHERE employeeID = ?");
    $stmt->execute([$_SESSION['employeeID']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result || $result['session_token'] !== $_SESSION['session_token']) {
        // Session is invalid, destroy it
        session_destroy();
        return false;
    }

    return true;
}

// If this file is accessed directly, check the session
if (basename($_SERVER['PHP_SELF']) == 'validate_session.php') {
    if (!validateSession()) {
        header("Location: ../../../login_page.php");
        exit;
    }
}
?> 
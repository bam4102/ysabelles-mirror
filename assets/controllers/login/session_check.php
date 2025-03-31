<?php
session_start();
include '../db.php';

// Skip session validation for login page and login process
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page === 'login_page.php' || $current_page === 'login_process.php') {
    return;
}

// Debug information
error_log("Session Check - Current Page: " . $current_page);
error_log("Session Data: " . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['employeeID']) || !isset($_SESSION['session_token'])) {
    error_log("Session Check Failed - Missing session data");
    session_destroy();
    header("Location: ../../../login_page.php");
    exit;
}

try {
    // Validate session token
    $stmt = $pdo->prepare("SELECT session_token, lastLogin FROM employee WHERE employeeID = ?");
    $stmt->execute([$_SESSION['employeeID']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Database Token: " . ($result ? $result['session_token'] : 'null'));
    error_log("Session Token: " . $_SESSION['session_token']);

    // Check if session token exists and matches
    if (!$result || empty($result['session_token']) || $result['session_token'] !== $_SESSION['session_token']) {
        error_log("Session Check Failed - Token mismatch or empty");
        session_destroy();
        header("Location: ../../../login_page.php");
        exit;
    }

    // Check if session is too old (optional, uncomment if you want to enforce session timeout)
    // $session_timeout = 3600; // 1 hour
    // if (strtotime($result['lastLogin']) < time() - $session_timeout) {
    //     error_log("Session Check Failed - Session timeout");
    //     session_destroy();
    //     header("Location: ../../../login_page.php");
    //     exit;
    // }

    error_log("Session Check Passed");
} catch (PDOException $e) {
    error_log("Database Error in Session Check: " . $e->getMessage());
    session_destroy();
    header("Location: ../../../login_page.php");
    exit;
}
?> 
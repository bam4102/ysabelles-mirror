<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include './assets/controllers/db.php';

// Skip session validation for login page and login process
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page === 'login_page.php' || $current_page === 'login_process.php' || $current_page === 'auth.php' || $current_page === 'concurrent_login.php') {
    return;
}

// Debug information
error_log("Session Check - Current Page: " . $current_page);
error_log("Session Data: " . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['employeeID']) || !isset($_SESSION['session_token'])) {
    error_log("Session Check Failed - Missing session data");
    $_SESSION['error'] = "Please log in to access this page.";
    session_destroy();
    header("Location: ./login_page.php");
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
        // Redirect to concurrent login page
        header("Location: ./concurrent_login.php");
        exit;
    }

    error_log("Session Check Passed");
} catch (PDOException $e) {
    error_log("Database Error in Session Check: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred. Please try logging in again.";
    session_destroy();
    header("Location: ./login_page.php");
    exit;
}
?> 
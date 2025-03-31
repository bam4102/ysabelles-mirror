<?php
session_start();
include '../db.php'; // db.php is in the same folder

if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Retrieve the user with the provided username.
    $stmt = $pdo->prepare("SELECT * FROM employee WHERE usernameEmployee = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Verify the password (assuming passwords are hashed) 
        if (password_verify($password, $user['passwordEmployee'])) {
            // Check if the account is employed 
            if ($user['employedEmployee'] != 1) {
                // If not employed, set error message for account termination
                $_SESSION['error'] = "Account terminated.";
                header("Location: ../../../login_page.php");
                exit;
            }

            // Generate a unique session token
            $session_token = bin2hex(random_bytes(32));
            
            // First, clear any existing session token
            $clearStmt = $pdo->prepare("UPDATE employee SET session_token = NULL WHERE employeeID = ?");
            $clearStmt->execute([$user['employeeID']]);
            
            // Then update with new session token and lastLogin
            $updateStmt = $pdo->prepare("UPDATE employee SET lastLogin = NOW(), session_token = ? WHERE employeeID = ?");
            $updateStmt->execute([$session_token, $user['employeeID']]);

            // Store user data in session
            $_SESSION['user'] = $user;
            $_SESSION['employeeID'] = $user['employeeID'];
            $_SESSION['positionEmployee'] = $user['positionEmployee'];
            $_SESSION['locationEmployee'] = $user['locationEmployee'];
            $_SESSION['session_token'] = $session_token;

            // Redirect based on the user's position.
            if (strtoupper($user['positionEmployee']) === 'ADMIN' || strtoupper($user['positionEmployee']) === 'SUPERADMIN') {
                header("Location: ../../../manage_employees.php");
            } elseif (strtoupper($user['positionEmployee']) === 'CASHIER') {
                header("Location: ../../../transactions2.php");
            } elseif (strtoupper($user['positionEmployee']) === 'INVENTORY') {
                header("Location: ../../../products.php");
            } else {
                header("Location: ../../../home.php");
            }
            exit;
        } else {
            $_SESSION['error'] = "Invalid username or password.";
            header("Location: ../../../login_page.php");
            exit;
        }
    } else {
        $_SESSION['error'] = "Invalid username or password.";
        header("Location: ../../../login_page.php");
        exit;
    }
} else {
    $_SESSION['error'] = "Please fill in all fields.";
    header("Location: ../../../login_page.php");
    exit;
}

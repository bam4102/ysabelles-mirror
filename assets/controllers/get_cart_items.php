<?php
// Disable error output to prevent HTML in response
error_reporting(0);
ini_set('display_errors', 0);

// Set header before any output
header('Content-Type: application/json');

try {
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    include 'db.php';
    include '../../cart_functions.php';

    // Get cart items using the existing function
    $cart = getCartItems($pdo);
    
    // Return as JSON
    echo json_encode($cart);
} catch (Exception $e) {
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
exit; // Ensure no extra whitespace is added after the response
?>

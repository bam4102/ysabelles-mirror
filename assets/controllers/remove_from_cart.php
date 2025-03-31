<?php
// Disable error output to prevent HTML in response
error_reporting(0);
ini_set('display_errors', 0);

// Set header before any output
header('Content-Type: text/plain');

try {
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    include 'db.php';
    include '../../cart_functions.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $index = isset($_POST['index']) ? $_POST['index'] : null;
        
        if ($index !== null) {
            $result = removeFromCart($index);
            echo $result;
        } else {
            echo "Error: No item index provided";
        }
    } else {
        echo "Error: Invalid request method";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
exit; // Ensure no extra whitespace is added after the response
?>

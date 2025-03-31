<?php
// Remove any BOM or extra whitespace before this line.
session_start();
header('Content-Type: application/json');

// Optionally disable error display for JSON output.
error_reporting(0);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['index'])) {
    $index = (int)$_POST['index'];
    
    if (isset($_SESSION['cart']) && isset($_SESSION['cart'][$index])) {
        // Remove the specific item and re-index the array.
        array_splice($_SESSION['cart'], $index, 1);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Item not found']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>

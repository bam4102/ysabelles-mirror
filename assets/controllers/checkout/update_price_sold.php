<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['index']) && isset($_POST['priceSold'])) {
    $index = (int)$_POST['index'];
    $priceSold = (float)$_POST['priceSold'];
    
    if ($priceSold < 0) {
        $priceSold = 0;
    }
    
    if (isset($_SESSION['cart']) && isset($_SESSION['cart'][$index])) {
        // Update the priceSold value in the session
        $_SESSION['cart'][$index]['priceSold'] = $priceSold;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Item not found']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>

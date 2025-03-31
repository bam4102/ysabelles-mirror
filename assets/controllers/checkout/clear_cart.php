<?php
include '../db.php';

// Clear the session cart
$_SESSION['cart'] = array();
echo "success";
?>

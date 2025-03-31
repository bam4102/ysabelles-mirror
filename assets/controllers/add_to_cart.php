<?php
session_start();
include 'db.php';

// Remove any whitespace or HTML comments before headers
header('Content-Type: text/plain'); // Ensure plain text response

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['productID'])) {
    $productID = filter_var($_POST['productID'], FILTER_SANITIZE_NUMBER_INT);
    // Get toBuy flag
    $toBuy = isset($_POST['toBuy']) ? (int)$_POST['toBuy'] : 0;
    
    if (!$productID) {
        echo "Invalid product ID";
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT p.*, pc.productCategory 
            FROM product p 
            LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID 
            WHERE p.productID = ?");
        $stmt->execute([$productID]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = array();
            }
            
            // Add toBuy flag to the product in cart
            $product['toBuy'] = $toBuy ? true : false;
            
            // If it's a "to buy" item, initialize priceSold with product price
            if ($toBuy) {
                $product['priceSold'] = $product['priceProduct'];
            }
            
            $_SESSION['cart'][] = $product;
            echo "success";
        } else {
            echo "Product not found";
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
} else {
    echo "Invalid request";
}
exit; // Ensure no extra whitespace is added after the response
?>

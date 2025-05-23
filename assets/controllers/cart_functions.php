<?php
include 'db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to format product ID according to the specification
function formatProductId($product) {
    // If product is part of an entourage
    if (!empty($product['entourageID'])) {
        return "EN" . $product['entourageID'] . $product['typeProduct'] . $product['productID'];
    }
    // Standard product
    else {
        return $product['categoryID'] . $product['productID'];
    }
}

// Function to add a product to the cart
function addToCart($pdo, $productID, $quantity = 1) {
    // Initialize the cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Get product info with image in a single query - use complete picture location
    $stmt = $pdo->prepare("
        SELECT p.*, pc.productCategory, pc.categoryCode, pi.pictureLocation,
               p.sizeProduct, p.locationProduct, p.bustProduct, p.waistProduct, p.lengthProduct
        FROM product p
        LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID
        LEFT JOIN (
            SELECT productID, pictureLocation 
            FROM picture 
            WHERE isActive = 1 
            GROUP BY productID
        ) pi ON p.productID = pi.productID
        WHERE p.productID = ?
    ");
    $stmt->execute([$productID]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        return "Product not found";
    }

    // Format display ID for the product
    $formattedId = formatProductId($product);
    
    // Add to cart with all details needed
    $cartItem = [
        'productID' => $product['productID'],
        'categoryID' => $product['categoryID'],
        'entourageID' => $product['entourageID'],
        'typeProduct' => $product['typeProduct'],
        'nameProduct' => $product['nameProduct'],
        'productCategory' => $product['productCategory'] ?? 'Uncategorized',
        'categoryCode' => $product['categoryCode'] ?? '',
        'priceProduct' => $product['priceProduct'],
        'quantity' => $quantity,
        'formattedId' => $formattedId,
        'pictureLocation' => $product['pictureLocation'], // Use the complete path from database
        'sizeProduct' => $product['sizeProduct'] ?? '',
        'locationProduct' => $product['locationProduct'] ?? '',
        'bustProduct' => $product['bustProduct'] ?? '',
        'waistProduct' => $product['waistProduct'] ?? '',
        'lengthProduct' => $product['lengthProduct'] ?? ''
    ];
    
    $_SESSION['cart'][] = $cartItem;
    
    return "success";
}

// Function to get all items in the cart
function getCartItems($pdo) {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return [];
    }
    
    $cart = $_SESSION['cart'];
    
    // Add index to each item for removal reference and fetch missing images
    foreach ($cart as $index => &$item) {
        $item['index'] = $index;
        
        // Fetch missing image if needed
        if (!isset($item['pictureLocation']) || empty($item['pictureLocation'])) {
            $image_query = "SELECT pictureLocation FROM picture WHERE productID = ? AND isActive = 1 LIMIT 1";
            $image_stmt = $pdo->prepare($image_query);
            $image_stmt->execute([$item['productID']]);
            $image = $image_stmt->fetch(PDO::FETCH_ASSOC);
            $item['pictureLocation'] = $image['pictureLocation'] ?? ''; // Use complete path from database
        }
        
        // Ensure formattedId exists (for backward compatibility)
        if (!isset($item['formattedId'])) {
            // Fetch missing data if needed
            if (!isset($item['categoryID']) || !isset($item['entourageID']) || !isset($item['typeProduct'])) {
                $stmt = $pdo->prepare("SELECT categoryID, entourageID, typeProduct FROM product WHERE productID = ?");
                $stmt->execute([$item['productID']]);
                $productData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($productData) {
                    $item['categoryID'] = $productData['categoryID'];
                    $item['entourageID'] = $productData['entourageID'];
                    $item['typeProduct'] = $productData['typeProduct'];
                }
            }
            
            // Create formatted ID with updated pattern
            if (!empty($item['entourageID'])) {
                $item['formattedId'] = "EN" . $item['entourageID'] . $item['typeProduct'] . $item['productID'];
            } else {
                $item['formattedId'] = $item['categoryID'] . $item['productID'];
            }
        }
    }
    
    return $cart;
}

// Function to remove an item from the cart
function removeFromCart($index) {
    if (!isset($_SESSION['cart']) || !isset($_SESSION['cart'][$index])) {
        return "Item not found in cart";
    }
    
    unset($_SESSION['cart'][$index]);
    $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index the array
    
    return "success";
}

// Function to clear the entire cart
function clearCart() {
    $_SESSION['cart'] = [];
    return "success";
}
?>

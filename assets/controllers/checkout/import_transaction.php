<?php
session_start();
include '../db.php'; // Assumes $pdo is defined here

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transactionID'])) {
    $transactionID = $_POST['transactionID'];

    // Fetch the transaction details.
    $stmtTrans = $pdo->prepare("SELECT * FROM transaction WHERE transactionID = ?");
    $stmtTrans->execute([$transactionID]);
    $transactionDetails = $stmtTrans->fetch(PDO::FETCH_ASSOC);
    if (!$transactionDetails) {
         echo json_encode(['success' => false, 'message' => 'Transaction not found.']);
         exit;
    }

    // Query all purchase records for this transaction.
    $stmtPurchases = $pdo->prepare("SELECT * FROM purchase WHERE transactionID = ?");
    $stmtPurchases->execute([$transactionID]);
    $purchases = $stmtPurchases->fetchAll(PDO::FETCH_ASSOC);
    
    // Ensure the session cart exists.
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }
    
    // Merge imported purchases with existing cart.
    foreach ($purchases as $purchase) {
        $productID = $purchase['productID'];
        // Make sure packagePurchase is an integer: 1 = Package A, 2 = Package B, 0 = none.
        $packagePurchase = (int)$purchase['packagePurchase']; 
        
        // Query product details (joining with productcategory for additional info)
        $stmtProduct = $pdo->prepare("
            SELECT p.*, pc.productCategory 
            FROM product p 
            LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID 
            WHERE p.productID = ?
        ");
        $stmtProduct->execute([$productID]);
        $product = $stmtProduct->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            // Build the imported cart item.
            $importedItem = [
                'productID'       => $product['productID'],
                'categoryID'      => $product['categoryID'],
                'nameProduct'     => $product['nameProduct'],
                'priceProduct'    => $product['priceProduct'],
                'productCategory' => $product['productCategory'] ?? '',
                'packagePurchase' => $packagePurchase  // Stored as an integer
            ];

            // Remove duplicate by productID.
            foreach ($_SESSION['cart'] as $key => $cartItem) {
                if ($cartItem['productID'] == $importedItem['productID']) {
                    unset($_SESSION['cart'][$key]);
                }
            }
            // Add the imported item.
            $_SESSION['cart'][] = $importedItem;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Imported " . count($purchases) . " purchase(s) successfully.",
        'transactionDetails' => $transactionDetails
    ]);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}
?>

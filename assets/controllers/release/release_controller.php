<?php

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

$message = "";
$messageType = "info";
$transactionDetails = [];
$productList = [];
$product = [];

/**
 * Helper function to get transaction details
 */
function getTransactionDetails($pdo, $transactionID) {
    // Get transaction details
    $sql = "SELECT t.transactionID, t.clientName, t.clientAddress, t.clientContact, 
            t.dateTransaction, t.datePickUp, t.dateReturn, t.bondStatus, 
            t.balanceTransaction, t.bondTransaction
            FROM transaction t 
            WHERE t.transactionID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$transactionID]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Helper function to get products in a transaction
 */
function getTransactionProducts($pdo, $transactionID) {
    // Get products in this transaction
    $sql = "SELECT p.productID, p.nameProduct, p.codeProduct, p.returnedProduct, p.soldProduct, 
            pur.packagePurchase, pur.soldPProduct,
            (SELECT MAX(CASE WHEN ph.action_type IN ('RELEASE', 'SOLD') THEN 1 ELSE 0 END)
             FROM product_history ph 
             WHERE ph.productID = p.productID AND ph.transactionID = pur.transactionID) as is_released,
            (SELECT MAX(CASE WHEN ph.action_type = 'RETURN' THEN 1 ELSE 0 END)
             FROM product_history ph 
             WHERE ph.productID = p.productID AND ph.transactionID = pur.transactionID) as is_returned
            FROM purchase pur
            JOIN product p ON pur.productID = p.productID
            WHERE pur.transactionID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$transactionID]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle transaction search
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'searchTransaction') {
    $searchQuery = isset($_POST['searchQuery']) ? trim($_POST['searchQuery']) : "";
    $searchType = isset($_POST['searchType']) ? trim($_POST['searchType']) : "clientName";
    
    if (empty($searchQuery)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please enter a search term.'
        ]);
        exit;
    }
    
    try {
        $sql = "SELECT t.transactionID, t.clientName, t.clientContact, t.dateTransaction, 
                t.bondStatus, t.balanceTransaction, t.bondTransaction, t.datePickUp, t.dateReturn
                FROM transaction t 
                WHERE t.bondStatus = 1 AND ";
                
        if ($searchType == 'clientName') {
            $sql .= "t.clientName LIKE ?";
            $params = ['%' . $searchQuery . '%'];
        } else {
            $sql .= "t.transactionID = ?";
            $params = [$searchQuery];
        }
        
        $sql .= " ORDER BY t.dateTransaction DESC LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => count($transactions) > 0 ? 'success' : 'error',
            'data' => $transactions,
            'message' => count($transactions) == 0 ? 'No active transactions found.' : ''
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error searching transactions: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Handle transaction detail request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'getTransactionDetails') {
    $transactionID = isset($_POST['transactionID']) ? trim($_POST['transactionID']) : "";
    
    if (empty($transactionID)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Transaction ID is required.'
        ]);
        exit;
    }
    
    try {
        // Get transaction details with bond check
        $sql = "SELECT t.* FROM transaction t WHERE t.transactionID = ? AND t.bondStatus = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$transactionID]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Transaction not found or not active.'
            ]);
            exit;
        }
        
        // Get all products in the transaction
        $products = getTransactionProducts($pdo, $transactionID);
        
        echo json_encode([
            'status' => 'success',
            'transaction' => $transaction,
            'products' => $products
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error retrieving transaction details: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Handle product release
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['codeProduct']) && isset($_POST['transactionID'])) {
    $codeProduct = trim($_POST['codeProduct']);
    $transactionID = trim($_POST['transactionID']);

    if (empty($codeProduct)) {
        $message = "Please scan a product code.";
    } else if (empty($transactionID)) {
        $message = "No transaction selected.";
    } else {
        try {
            // First, verify the transaction exists and is active
            $sql = "SELECT t.bondStatus, t.balanceTransaction FROM transaction t WHERE t.transactionID = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$transactionID]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                $message = "Transaction not found.";
            } else if ((int)$transaction['bondStatus'] !== 1) {
                $message = "Transaction is not active. Bond status must be 1.";
            } else if ((float)$transaction['balanceTransaction'] > 0) {
                $message = "Transaction has outstanding balance. Cannot release products.";
            } else {
                // Look up the product
                $sql = "SELECT p.productID, p.returnedProduct, p.codeProduct, p.isNew, p.nameProduct,
                        (SELECT COUNT(*) FROM purchase pur WHERE pur.productID = p.productID AND pur.transactionID = ?) as in_this_transaction
                        FROM product p WHERE p.codeProduct = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$transactionID, $codeProduct]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$product) {
                    $message = "Product not found.";
                } else if ((int)$product['isNew'] === 1) {
                    $message = "This is a new product and cannot be released. Please update its status first.";
                } else if ((int)$product['returnedProduct'] === 1) {
                    $message = "This product is already released.";
                } else if ((int)$product['in_this_transaction'] === 0) {
                    $message = "This product is not part of the selected transaction.";
                } else {
                    $productID = $product['productID'];
                    
                    // Check if the product has already been released in this transaction
                    $sql = "SELECT COUNT(*) as already_released
                            FROM product_history ph
                            WHERE ph.productID = ? AND ph.transactionID = ? AND ph.action_type IN ('RELEASE', 'SOLD')";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$productID, $transactionID]);
                    $historyCheck = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ((int)$historyCheck['already_released'] > 0) {
                        $message = "This product has already been released for this transaction.";
                    } else {
                        // Get the purchase record to determine if it's a sale or rental
                        $sql = "SELECT soldPProduct FROM purchase WHERE productID = ? AND transactionID = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$productID, $transactionID]);
                        $purchaseData = $stmt->fetch(PDO::FETCH_ASSOC);
                        $isSold = (int)$purchaseData['soldPProduct'] === 1;
                        
                        try {
                            $pdo->beginTransaction();

                            // Update product status
                            $sql = "UPDATE product SET returnedProduct = 1" . 
                                   ($isSold ? ", soldProduct = 1" : "") . 
                                   " WHERE productID = ?";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$productID]);

                            // Record in history
                            $action_type = $isSold ? 'SOLD' : 'RELEASE';
                            $sql = "INSERT INTO product_history (productID, transactionID, action_type, employeeID, action_date) 
                                    VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP(6))";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$productID, $transactionID, $action_type, $_SESSION['employeeID']]);
                            
                            // For sold products, check if we need to update transaction status
                            if ($isSold) {
                                // Check if all products in this transaction are sold products
                                $sql = "SELECT 
                                    COUNT(*) as total_products,
                                    SUM(CASE WHEN pur.soldPProduct = 1 THEN 1 ELSE 0 END) as sold_products,
                                    SUM(CASE WHEN pur.soldPProduct = 1 AND 
                                             (p.returnedProduct = 1 OR p.productID = ?) THEN 1 ELSE 0 END) as released_sold_products,
                                    SUM(CASE WHEN pur.soldPProduct = 0 THEN 1 ELSE 0 END) as rented_products
                                    FROM purchase pur
                                    JOIN product p ON pur.productID = p.productID 
                                    WHERE pur.transactionID = ?";
                                $stmt = $pdo->prepare($sql);
                                $stmt->execute([$productID, $transactionID]);
                                $transactionStats = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                // Only mark as completed if all products are sales and all have been released
                                $isAllSold = (int)$transactionStats['total_products'] === (int)$transactionStats['sold_products'];
                                $allSoldReleased = (int)$transactionStats['sold_products'] === (int)$transactionStats['released_sold_products'];
                                
                                if ($isAllSold && $allSoldReleased) {
                                    $sql = "UPDATE transaction SET bondStatus = 2 WHERE transactionID = ?";
                                    $stmt = $pdo->prepare($sql);
                                    $stmt->execute([$transactionID]);
                                }
                            }

                            $pdo->commit();
                            
                            $message = $isSold ? 
                                "Product sold successfully for code '$codeProduct'." : 
                                "Product released successfully for code '$codeProduct'.";
                            $messageType = "success";

                            // Get updated transaction data for display
                            $transactionDetails = getTransactionDetails($pdo, $transactionID);
                            $productList = getTransactionProducts($pdo, $transactionID);
                            
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            $message = "Error processing release: " . $e->getMessage();
                            $messageType = "danger";
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $message = "System error: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// For AJAX requests, ensure we only return the necessary content
if ($isAjax && !empty($message)) {
    // Just return the alert message for AJAX requests
    echo '<div class="alert alert-' . $messageType . '">' . htmlspecialchars($message) . '</div>';
    
    // Only include transaction details if needed and successful
    if (!empty($transactionDetails) && $messageType === 'success') {
        // This part is optional and can be removed if you don't need it
        // in the response, since we're doing a separate call to refresh
        // transaction details after a successful operation
    }
    
    // Exit to prevent any additional output
    exit;
}
?>

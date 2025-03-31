<?php

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

$message = "";
$messageType = "info";
$showConfirmation = false;
$transactionDetails = [];
$productList = [];
$targetProduct = [];
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
            (SELECT MAX(CASE WHEN ph.action_type = 'RELEASE' THEN 1 ELSE 0 END)
             FROM product_history ph 
             WHERE ph.productID = p.productID AND ph.transactionID = pur.transactionID) as is_released,
            (SELECT MAX(CASE WHEN ph.action_type = 'RETURN' THEN 1 ELSE 0 END)
             FROM product_history ph 
             WHERE ph.productID = p.productID AND ph.transactionID = pur.transactionID) as is_returned,
            (SELECT MAX(CASE WHEN ph.action_type = 'SOLD' THEN 1 ELSE 0 END)
             FROM product_history ph 
             WHERE ph.productID = p.productID AND ph.transactionID = pur.transactionID) as is_sold
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

// Handle product return process
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['codeProduct']) && !isset($_POST['action'])) {
    $codeProduct = isset($_POST['codeProduct']) ? trim($_POST['codeProduct']) : "";
    $transactionID = isset($_POST['transactionID']) ? trim($_POST['transactionID']) : "";
    $isDamaged = isset($_POST['isDamaged']) ? (int)$_POST['isDamaged'] : null;
    $damageDesc = isset($_POST['damageDesc']) ? trim($_POST['damageDesc']) : "";

    // Reset showConfirmation flag to avoid unintended UI parts
    $showConfirmation = false;

    if (empty($codeProduct)) {
        $message = "Please enter a product code.";
    } else if (empty($transactionID)) {
        $message = "No transaction selected.";
    } else {
        // Get basic product info along with transaction check in a single query
        $sql = "SELECT p.productID, p.soldProduct, p.codeProduct, p.nameProduct,
                (SELECT COUNT(*) FROM purchase WHERE productID = p.productID AND transactionID = ?) as in_transaction
                FROM product p 
                WHERE p.codeProduct = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$transactionID, $codeProduct]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $message = "Product not found.";
            $messageType = "danger";
        } else if ($product['in_transaction'] == 0) {
            $message = "This product is not part of the selected transaction.";
            $messageType = "danger";
        } else if ($product['soldProduct'] == 1) {
            $message = "This product has been sold and cannot be returned. Its status is marked as SOLD in the system.";
            $messageType = "danger";
        } else {
            $productID = $product['productID'];
            
            // Get product status and transaction details in a single optimized query
            $sql = "SELECT 
                    t.bondStatus, t.bondTransaction,
                    MAX(CASE WHEN ph.action_type = 'RELEASE' THEN 1 ELSE 0 END) AS is_released,
                    MAX(CASE WHEN ph.action_type = 'RETURN' THEN 1 ELSE 0 END) AS is_returned
                    FROM transaction t
                    JOIN purchase pur ON t.transactionID = pur.transactionID AND pur.productID = ?
                    LEFT JOIN product_history ph ON ph.productID = ? AND ph.transactionID = t.transactionID
                    WHERE t.transactionID = ?
                    GROUP BY t.transactionID";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$productID, $productID, $transactionID]);
            $status = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$status) {
                $message = "Transaction not found.";
                $messageType = "danger";
            } else {
                $isReleased = $status['is_released'];
                $isReturned = $status['is_returned'];
                $bondStatus = $status['bondStatus'];
                $bondAmount = $status['bondTransaction'];
                
                // Decision logic based on status
                    if ($bondStatus == 0) {
                    $message = "This transaction's bond is still not deposited. Cannot process return.";
                    $messageType = "danger";
                } elseif ($bondStatus == 2) {
                    $message = "This transaction is already completed (bond returned).";
                    $messageType = "info";
                    } elseif (!$isReleased) {
                        $message = "This product has not been released in the transaction yet and cannot be returned.";
                    $messageType = "warning";
                    } elseif ($isReturned) {
                        $message = "This product has already been returned in this transaction.";
                    $messageType = "info";
                    } elseif ($bondStatus == 1) {
                    // Process return if confirmation received
                        if (isset($isDamaged)) {
                            try {
                                $pdo->beginTransaction();

                            // Update product in a single optimized query
                                $sql = "UPDATE product 
                                        SET returnedProduct = 0,
                                            damageProduct = ?,
                                            descProduct = IF(? = 1, ?, descProduct),
                                        counterProduct = COALESCE(counterProduct, 0) + 1
                                        WHERE productID = ?";
                                $stmt = $pdo->prepare($sql);
                                $stmt->execute([$isDamaged, $isDamaged, $damageDesc, $productID]);

                            // Record history without timezone manipulation
                                $sql = "INSERT INTO product_history 
                                        (productID, transactionID, action_type, employeeID, damage_status, damage_description, action_date) 
                                    VALUES (?, ?, 'RETURN', ?, ?, ?, CURRENT_TIMESTAMP(6))";
                                $stmt = $pdo->prepare($sql);
                                $stmt->execute([$productID, $transactionID, $_SESSION['user']['employeeID'], $isDamaged, $isDamaged ? $damageDesc : null]);

                            // Optimized query to check if all products have been returned
                            $sql = "SELECT 
                                    (NOT EXISTS(
                                        SELECT 1 FROM purchase pur
                                        JOIN product p ON pur.productID = p.productID
                                        JOIN product_history ph_release ON p.productID = ph_release.productID 
                                            AND pur.transactionID = ph_release.transactionID
                                            AND ph_release.action_type = 'RELEASE'
                                        LEFT JOIN product_history ph_return ON p.productID = ph_return.productID 
                                            AND pur.transactionID = ph_return.transactionID
                                            AND ph_return.action_type = 'RETURN'
                                        WHERE pur.transactionID = ?
                                            AND pur.soldPProduct = 0
                                            AND p.soldProduct = 0
                                            AND ph_return.productID IS NULL
                                            AND p.productID != ? -- Exclude current product
                                    )) as all_returned";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$transactionID, $productID]);
                            $returnStatus = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            // If all products have been returned and bond is zero, update bond status
                            if ($returnStatus['all_returned'] && $bondAmount == 0) {
                                    $sql = "UPDATE transaction SET bondStatus = 2 WHERE transactionID = ?";
                                    $stmt = $pdo->prepare($sql);
                                    $stmt->execute([$transactionID]);
                                    $bondStatusUpdated = true;
                                }

                                $pdo->commit();
                            $message = "Product returned successfully" . ($isDamaged ? " with damage report" : "") . ".";
                            $messageType = "success";
                                
                                if (isset($bondStatusUpdated) && $bondStatusUpdated) {
                                $message .= " All items returned. Bond status updated to completed.";
                                }
                            } catch (Exception $e) {
                                $pdo->rollBack();
                            $message = "Error: " . $e->getMessage();
                            $messageType = "danger";
                            }
                        } else {
                        // Get transaction details for display
                        $tDetails = getTransactionDetails($pdo, $transactionID);
                        
                        // Show confirmation form for damage check
                            $showConfirmation = true;
                            $showDamageCheck = true;
                            $targetProduct = $product;
                        $transactionDetails = $tDetails;
                        }
                    } else {
                        $message = "This product cannot be returned with the current transaction status.";
                    $messageType = "warning";
                }
            }
        }
    }
}

// If this is an AJAX request, return only the snippet.
if ($isAjax) {
    // Start output buffering to capture all output
    ob_start();
?>
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($showConfirmation): ?>
        <div class="card mb-4" style="border: none; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <div class="card-header" style="background-color: #FFC107; color: #212529; border: none; border-radius: 5px 5px 0 0; padding: 15px;">
                <h5 class="mb-0">Return Confirmation - <?= htmlspecialchars($targetProduct['nameProduct']) ?> (<?= htmlspecialchars($targetProduct['codeProduct']) ?>)</h5>
            </div>
            <div class="card-body" style="padding: 20px;">
                <?php if ($showDamageCheck): ?>
                    <form id="damageCheckForm" action="return.php" method="POST">
                        <input type="hidden" name="codeProduct" value="<?= htmlspecialchars($targetProduct['codeProduct']) ?>">
                        <input type="hidden" name="transactionID" value="<?= htmlspecialchars($transactionDetails['transactionID']) ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Is the product damaged?</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="isDamaged" id="notDamaged" value="0" checked>
                                <label class="form-check-label" for="notDamaged">
                                    No, product is in good condition
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="isDamaged" id="isDamaged" value="1">
                                <label class="form-check-label" for="isDamaged">
                                    Yes, product is damaged
                                </label>
                            </div>
                        </div>
                        
                        <div id="damageDescContainer" class="mb-3" style="display: none;">
                            <label for="damageDesc" class="form-label">Damage Description</label>
                            <textarea class="form-control" name="damageDesc" id="damageDesc" rows="3"></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-warning" style="background-color: #FFC107; border-color: #FFC107; font-weight: 500;">Confirm Return</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
<?php
    // Get the output buffer content
    $output = ob_get_clean();
    
    // Remove any potential duplicate "Return Product" sections
    $output = preg_replace('/<div class="product-header">[\s\S]*?<\/div>/m', '', $output);
    $output = preg_replace('/<h1>Return Product<\/h1>/m', '', $output);
    $output = preg_replace('/<div[^>]*>Return Product<\/div>/m', '', $output);
    
    // Return the cleaned output
    echo $output;
    exit;
} // End of AJAX response block
?>

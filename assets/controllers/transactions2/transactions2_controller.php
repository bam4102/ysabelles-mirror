<?php
/**
 * Transaction Controller for transactions2.php
 * Handles data retrieval and processing for transactions
 */

// Format money amount helper function
function formatMoney($amount)
{
    return '₱ ' . number_format($amount, 2);
}

// Get bond status text helper function
function getBondStatusText($status, $non_sold_count = 0) {
    switch ($status) {
        case 0:
            return '<span class="badge bg-warning">Unpaid</span>';
        case 1:
            return '<span class="badge bg-primary">Active</span>';
        case 2:
            return $non_sold_count == 0 ? 
                '<span class="badge bg-info">Completed</span>' : 
                '<span class="badge bg-success">Returned</span>';
        case 3:
            return '<span class="badge bg-secondary">Inactive (No Payment)</span>';
        default:
            return '<span class="badge bg-secondary">Unknown</span>';
    }
}

/**
 * Function to retrieve transaction list with filters
 * Centralizes database access to minimize connections
 */
function getTransactions($pdo, $filters = []) {
    // Get the user's location and position from the session
    $userLocation = $_SESSION['user']['locationEmployee'] ?? null;
    $userPosition = strtoupper($_SESSION['user']['positionEmployee'] ?? '');
    
    // Build the SQL WHERE condition based on user role
    $locationCondition = '';
    if (in_array($userPosition, ['ADMIN', 'CASHIER']) && $userLocation) {
        $locationCondition = " WHERE t.locationTransaction = '$userLocation'";
    }
    
    // Apply additional filters if provided
    if (!empty($filters)) {
        // Add filter logic here if needed
        // For example: date ranges, location filters, etc.
    }

    // Modified SQL query to include information about sold products
    $sql = "SELECT t.*, e.nameEmployee, 
            (SELECT COUNT(*) 
             FROM purchase pu2 
             LEFT JOIN product_history ph ON pu2.productID = ph.productID 
             WHERE pu2.transactionID = t.transactionID 
             AND ph.action_type != 'SOLD') as non_sold_count,
            CONCAT('[', GROUP_CONCAT(
                JSON_OBJECT(
                    'productID', p.productID,
                    'nameProduct', p.nameProduct,
                    'priceProduct', p.priceProduct,
                    'priceSold', p.priceSold,
                    'soldPProduct', pu.soldPProduct,
                    'packagePurchase', pu.packagePurchase,
                    'isNew', p.isNew,
                    'is_confirmed_sold', (
                        SELECT COUNT(*) > 0 
                        FROM product_history 
                        WHERE productID = p.productID 
                        AND action_type = 'SOLD'
                    ),
                    'sold_date', (
                        SELECT action_date 
                        FROM product_history 
                        WHERE productID = p.productID 
                        AND action_type = 'SOLD'
                        LIMIT 1
                    ),
                    'release_date', (
                        SELECT action_date 
                        FROM product_history 
                        WHERE productID = p.productID 
                        AND transactionID = t.transactionID 
                        AND action_type = 'RELEASE'
                        LIMIT 1
                    ),
                    'return_date', (
                        SELECT action_date 
                        FROM product_history 
                        WHERE productID = p.productID 
                        AND transactionID = t.transactionID 
                        AND action_type = 'RETURN'
                        LIMIT 1
                    )
                )
            ), ']') as products
            FROM transaction t 
            LEFT JOIN employee e ON t.employeeID = e.employeeID
            LEFT JOIN purchase pu ON t.transactionID = pu.transactionID
            LEFT JOIN product p ON pu.productID = p.productID
            $locationCondition
            GROUP BY t.transactionID
            ORDER BY t.dateTransaction DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Function to retrieve transaction history data
 * Used for AJAX calls to get payment and bond history
 */
function getTransactionHistory($pdo, $transactionId) {
    // Validate input
    $transactionId = intval($transactionId);
    
    // Get payment history
    $paymentSql = "SELECT datePayment, amountPayment, kindPayment, paymentCurrentBalance, notePayment 
                   FROM payment 
                   WHERE transactionID = ? 
                   ORDER BY datePayment ASC";
    $stmt = $pdo->prepare($paymentSql);
    $stmt->execute([$transactionId]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get bond deposits
    $bondDepositSql = "SELECT dateBond, depositBond, bondCurrentBalance, noteBond 
                       FROM bond 
                       WHERE transactionID = ? AND depositBond IS NOT NULL
                       ORDER BY dateBond ASC";
    $stmt = $pdo->prepare($bondDepositSql);
    $stmt->execute([$transactionId]);
    $bondDeposits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get bond returns
    $bondReturnSql = "SELECT dateBond, releaseBond as amount, bondCurrentBalance, noteBond 
                      FROM bond 
                      WHERE transactionID = ? AND releaseBond IS NOT NULL
                      ORDER BY dateBond ASC";
    $stmt = $pdo->prepare($bondReturnSql);
    $stmt->execute([$transactionId]);
    $bondReturns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'payments' => $payments ?: [],
        'bondDeposits' => $bondDeposits ?: [],
        'bondReturns' => $bondReturns ?: []
    ];
}

// Handle AJAX requests for transaction history if this file is accessed directly
if (basename($_SERVER['PHP_SELF']) === 'transactions2_controller.php' && 
    isset($_GET['action']) && $_GET['action'] === 'getHistory') {
    
    require_once('../db.php'); // Include database connection
    
    header('Content-Type: application/json');
    
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Transaction ID is required']);
        exit;
    }

    $transactionId = intval($_GET['id']);
    
    try {
        $historyData = getTransactionHistory($pdo, $transactionId);
        echo json_encode($historyData);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

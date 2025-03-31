<?php
include 'db.php';

// Get product ID from request
$productID = isset($_GET['productID']) ? $_GET['productID'] : null;
$result = ['status' => false, 'message' => '', 'transactions' => []];

// Check if we should return all bookings regardless of date
$checkAll = isset($_GET['check_all']) && $_GET['check_all'] == '1';

if (!$productID) {
    $result['message'] = 'Product ID is required';
    echo json_encode($result);
    exit;
}

try {
    // Get date range parameters to check against
    function getDateRangeParams() {
        $dateFrom = !empty($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d');
        $dateTo = !empty($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d', strtotime('+30 days'));
        
        // Apply date allowance if option is checked
        if (isset($_GET['date_allowance']) && $_GET['date_allowance'] == '1') {
            $dateFrom = date('Y-m-d', strtotime($dateFrom . ' -7 days'));
            $dateTo = date('Y-m-d', strtotime($dateTo . ' +7 days'));
        }
        
        return [
            'from' => $dateFrom,
            'to' => $dateTo
        ];
    }
    
    $dateParams = getDateRangeParams();
    
    // First, always check if the product exists in any purchase record
    $checkPurchaseStmt = $pdo->prepare("
        SELECT 1 FROM purchase WHERE productID = ? LIMIT 1
    ");
    $checkPurchaseStmt->execute([$productID]);
    $inPurchase = $checkPurchaseStmt->fetchColumn() !== false;
    
    // If the product is in purchase table, we set status to true immediately
    if ($inPurchase) {
        $result['status'] = true;
        $result['in_purchase'] = true;
        $result['message'] = 'Product exists in purchase records';
    }
    
    // Build the base query for transactions
    $query = "
        SELECT 
            t.transactionID, 
            t.customerName,
            t.bondStatus,
            t.datePickUp,
            t.dateReturn,
            ph.action_type,
            ph.action_date
        FROM 
            purchase p
        JOIN 
            transaction t ON p.transactionID = t.transactionID
        LEFT JOIN 
            product_history ph ON p.productID = ph.productID AND p.transactionID = ph.transactionID
        WHERE 
            p.productID = ? AND
            t.bondStatus IN (0, 1) AND
            NOT EXISTS (
                SELECT 1 
                FROM product_history ph_return 
                WHERE ph_return.productID = p.productID 
                AND ph_return.transactionID = t.transactionID 
                AND ph_return.action_type = 'RETURN'
            )";
    
    // Only add date filtering if not checking all transactions
    if (!$checkAll) {
        $query .= " AND (
            (? <= t.dateReturn AND ? >= t.datePickUp) OR
            (t.datePickUp <= ? AND t.dateReturn >= ?)
        )";
    }
    
    $query .= " ORDER BY t.datePickUp";
    
    $stmt = $pdo->prepare($query);
    
    if ($checkAll) {
        // If checking all transactions, we only need the product ID
        $stmt->execute([$productID]);
    } else {
        // If filtering by date, add all parameters
        $stmt->execute([
            $productID, 
            $dateParams['from'], $dateParams['to'],
            $dateParams['from'], $dateParams['to']
        ]);
    }
    
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($transactions)) {
        $result['status'] = true;
        $result['message'] = $inPurchase ? 'Product is in purchase records and has transactions' : 
            ($checkAll ? 'Product has active transactions' : 'Product is booked during the selected dates');
        $result['transactions'] = $transactions;
    } 
    
    echo json_encode($result);
} catch (PDOException $e) {
    $result['message'] = 'Database error: ' . $e->getMessage();
    echo json_encode($result);
}
?>

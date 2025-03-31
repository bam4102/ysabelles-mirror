<?php

function getTransactionsForDateRange($pdo, $startDate, $endDate) {
    $sql = "SELECT * FROM transaction 
            WHERE (datePickUp BETWEEN :start AND :end 
            OR dateReturn BETWEEN :start AND :end)
            AND (bondStatus = 0 OR bondStatus = 1)
            ORDER BY datePickUp, dateReturn";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':start' => $startDate,
        ':end' => $endDate
    ]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function checkAllProductsStatus($pdo, $transactionID, $type) {
    if ($type === 'pickup') {
        // Check if all products in this transaction have been released or sold
        $sql = "SELECT 
                COUNT(*) as total_products,
                SUM(CASE WHEN ph.action_type IN ('RELEASE', 'SOLD') THEN 1 ELSE 0 END) as released_products
                FROM purchase pur
                JOIN product p ON pur.productID = p.productID
                LEFT JOIN (
                    SELECT productID, transactionID, action_type,
                           ROW_NUMBER() OVER (PARTITION BY productID, transactionID ORDER BY action_date DESC) as rn
                    FROM product_history
                    WHERE transactionID = ? AND action_type IN ('RELEASE', 'SOLD')
                ) ph ON p.productID = ph.productID AND ph.transactionID = pur.transactionID AND ph.rn = 1
                WHERE pur.transactionID = ?";
    } else {
        // Check if all products in this transaction have been returned or sold
        $sql = "SELECT 
                COUNT(*) as total_products,
                SUM(CASE 
                    WHEN (ph_release.action_type IN ('RELEASE', 'SOLD') AND ph_return.action_type = 'RETURN')
                    OR ph_release.action_type = 'SOLD'
                    THEN 1 ELSE 0 END) as returned_products
                FROM purchase pur
                JOIN product p ON pur.productID = p.productID
                LEFT JOIN (
                    SELECT productID, transactionID, action_type,
                           ROW_NUMBER() OVER (PARTITION BY productID, transactionID ORDER BY action_date DESC) as rn
                    FROM product_history
                    WHERE transactionID = ? AND action_type IN ('RELEASE', 'SOLD')
                ) ph_release ON p.productID = ph_release.productID AND ph_release.transactionID = pur.transactionID AND ph_release.rn = 1
                LEFT JOIN (
                    SELECT productID, transactionID, action_type,
                           ROW_NUMBER() OVER (PARTITION BY productID, transactionID ORDER BY action_date DESC) as rn
                    FROM product_history
                    WHERE transactionID = ? AND action_type = 'RETURN'
                ) ph_return ON p.productID = ph_return.productID AND ph_return.transactionID = pur.transactionID AND ph_return.rn = 1
                WHERE pur.transactionID = ?";
    }
                
    $stmt = $pdo->prepare($sql);
    
    if ($type === 'pickup') {
        $stmt->execute([$transactionID, $transactionID]);
    } else {
        $stmt->execute([$transactionID, $transactionID, $transactionID]);
    }
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ((int)$stats['total_products'] > 0) {
        if ($type === 'pickup') {
            return (int)$stats['total_products'] === (int)$stats['released_products'];
        } else {
            return (int)$stats['total_products'] === (int)$stats['returned_products'];
        }
    }
    
    return false;
}

function organizeTransactionsByDate($transactions, $pdo = null) {
    $organized = [];
    
    foreach ($transactions as $trans) {
        $pickupDate = $trans['datePickUp'];
        $returnDate = $trans['dateReturn'];
        $transactionID = $trans['transactionID'];
        
        // Only add to pickup dates if not all products are released yet
        if ($pdo && checkAllProductsStatus($pdo, $transactionID, 'pickup')) {
            // Skip adding to pickup as all products are already released
        } else {
            // Add to pickup dates
            if (!isset($organized[$pickupDate]['pickup'])) {
                $organized[$pickupDate]['pickup'] = [];
            }
            $organized[$pickupDate]['pickup'][] = $trans;
        }
        
        // Only add to return dates if not all products are returned yet
        if ($pdo && checkAllProductsStatus($pdo, $transactionID, 'return')) {
            // Skip adding to return as all products are already returned
        } else {
            // Add to return dates
            if (!isset($organized[$returnDate]['return'])) {
                $organized[$returnDate]['return'] = [];
            }
            $organized[$returnDate]['return'][] = $trans;
        }
    }
    
    return $organized;
}

function getCalendarNavigation($month, $year) {
    $prevMonth = $month - 1;
    $prevYear = $year;
    if ($prevMonth < 1) {
        $prevMonth = 12;
        $prevYear--;
    }
    
    $nextMonth = $month + 1;
    $nextYear = $year;
    if ($nextMonth > 12) {
        $nextMonth = 1;
        $nextYear++;
    }
    
    return [
        'prev' => ['month' => $prevMonth, 'year' => $prevYear],
        'next' => ['month' => $nextMonth, 'year' => $nextYear]
    ];
}
?>

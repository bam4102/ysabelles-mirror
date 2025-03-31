<?php

function getDailySalesReport($pdo, $date, $location = null)
{
    $report = [
        'totalSales' => 0,
        'totalDiscounts' => 0,
        'totalIncome' => 0,
        'payments' => [],
        'cashOnHand' => 0
    ];

    // Get total sales and discounts from transactions for the day
    $sql = "
        SELECT 
            SUM(chargeTransaction) as totalSales,
            SUM(discountTransaction) as totalDiscounts
        FROM transaction 
        WHERE DATE(dateTransaction) = ?";
    $params = [$date];

    if ($location) {
        $sql .= " AND locationTransaction = ?";
        $params[] = $location;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    $report['totalSales'] = $result['totalSales'] ?: 0;
    $report['totalDiscounts'] = $result['totalDiscounts'] ?: 0;
    $report['totalIncome'] = $report['totalSales'] - $report['totalDiscounts'];

    // Get payments for the day
    $sql = "
        SELECT p.*, t.clientName, t.chargeTransaction 
        FROM payment p 
        JOIN transaction t ON p.transactionID = t.transactionID 
        WHERE DATE(p.datePayment) = ?";

    if ($location) {
        $sql .= " AND t.locationTransaction = ?";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $report['payments'] = $stmt->fetchAll();

    // Calculate cash on hand as sum of all payments
    $cashOnHand = 0;
    foreach ($report['payments'] as $payment) {
        $cashOnHand += $payment['amountPayment'];
    }
    $report['cashOnHand'] = $cashOnHand;

    return $report;
}

function getDailyBondReport($pdo, $date, $location = null)
{
    $report = [
        'beginningBalance' => 0,
        'bondIncome' => 0,
        'deposits' => [],
        'bondRefund' => 0,
        'refunds' => [],
        'endingBalance' => 0
    ];

    // Get beginning balance (total bonds before this date)
    $sql = "
        SELECT COALESCE(SUM(depositBond), 0) - COALESCE(SUM(releaseBond), 0) as balance 
        FROM bond b
        JOIN transaction t ON b.transactionID = t.transactionID 
        WHERE dateBond < ?";
    $params = [$date];

    if ($location) {
        $sql .= " AND t.locationTransaction = ?";
        $params[] = $location;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $report['beginningBalance'] = $stmt->fetchColumn();

    // Get bond transactions for the day
    $sql = "
        SELECT b.*, t.clientName 
        FROM bond b 
        JOIN transaction t ON b.transactionID = t.transactionID 
        WHERE DATE(b.dateBond) = ?";
    $params = [$date];

    if ($location) {
        $sql .= " AND t.locationTransaction = ?";
        $params[] = $location;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bonds = $stmt->fetchAll();

    foreach ($bonds as $bond) {
        if ($bond['depositBond']) {
            $report['bondIncome'] += $bond['depositBond'];
            $report['deposits'][] = [
                'clientName' => $bond['clientName'],
                'amount' => $bond['depositBond']
            ];
        }
        if ($bond['releaseBond']) {
            $report['bondRefund'] += $bond['releaseBond'];
            $report['refunds'][] = [
                'clientName' => $bond['clientName'],
                'amount' => $bond['releaseBond']
            ];
        }
    }

    $report['endingBalance'] = $report['beginningBalance'] + $report['bondIncome'] - $report['bondRefund'];

    return $report;
}

function getUnreturnedItems($pdo, $location = null)
{
    $sql = "
        SELECT 
            t.clientName,
            t.clientContact,
            GROUP_CONCAT(CONCAT('(', p.productID, ') ', p.nameProduct) SEPARATOR ', ') as items,
            t.dateReturn,
            t.bondTransaction as bondPaid
        FROM transaction t
        JOIN purchase pu ON t.transactionID = pu.transactionID
        JOIN product p ON pu.productID = p.productID
        WHERE p.returnedProduct = 1 
        AND t.bondStatus != 2";
    $params = [];

    if ($location) {
        $sql .= " AND t.locationTransaction = ?";
        $params[] = $location;
    }

    $sql .= " GROUP BY t.transactionID HAVING COUNT(*) > 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getDueForRelease($pdo, $location = null)
{
    $sql = "
        SELECT 
            t.transactionID,
            t.clientName,
            t.clientContact,
            GROUP_CONCAT(CONCAT('(', p.productID, ') ', p.nameProduct) SEPARATOR ', ') as items,
            t.datePickUp,
            CASE 
                WHEN t.balanceTransaction > 0 THEN 'Pending Payment'
                ELSE 'Ready for Release'
            END as status
        FROM transaction t
        JOIN purchase pu ON t.transactionID = pu.transactionID
        JOIN product p ON pu.productID = p.productID
        WHERE t.datePickUp >= CURRENT_DATE
        AND p.returnedProduct = 0
        AND t.bondStatus != 2";
    $params = [];

    if ($location) {
        $sql .= " AND t.locationTransaction = ?";
        $params[] = $location;
    }

    $sql .= " GROUP BY t.transactionID HAVING COUNT(*) > 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getAllTimeSalesReport($pdo, $location = null)
{
    $report = [
        'totalSales' => 0,
        'totalDiscounts' => 0,
        'totalBonds' => 0,
        'totalTransactions' => 0,
        'paymentsByType' => [],
        'topProducts' => []
    ];

    // Get total sales, discounts and transaction count
    $sql = "
        SELECT 
            COUNT(DISTINCT t.transactionID) as transactionCount,
            SUM(t.chargeTransaction) as totalSales,
            SUM(t.discountTransaction) as totalDiscounts,
            SUM(t.bondTransaction) as totalBonds
        FROM transaction t";
    $params = [];

    if ($location) {
        $sql .= " WHERE t.locationTransaction = ?";
        $params[] = $location;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();

    $report['totalTransactions'] = $result['transactionCount'];
    $report['totalSales'] = $result['totalSales'];
    $report['totalDiscounts'] = $result['totalDiscounts'];
    $report['totalBonds'] = $result['totalBonds'];

    // Fix the payments by type query to join with transaction table
    $sql = "
        SELECT 
            p.kindPayment,
            COUNT(*) as count,
            SUM(p.amountPayment) as total
        FROM payment p
        JOIN transaction t ON p.transactionID = t.transactionID";
    $params = [];

    if ($location) {
        $sql .= " WHERE t.locationTransaction = ?";
        $params[] = $location;
    }

    $sql .= " GROUP BY p.kindPayment";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $report['paymentsByType'] = $stmt->fetchAll();

    // Get top products by rental frequency
    $sql = "
        SELECT 
            p.productID,
            p.nameProduct,
            p.typeProduct,
            COUNT(*) as rentCount,
            SUM(t.chargeTransaction) as totalRevenue
        FROM product p
        JOIN purchase pu ON p.productID = pu.productID
        JOIN transaction t ON pu.transactionID = t.transactionID";
    $params = [];

    if ($location) {
        $sql .= " WHERE t.locationTransaction = ?";
        $params[] = $location;
    }

    $sql .= " GROUP BY p.productID, p.nameProduct, p.typeProduct
        ORDER BY rentCount DESC
        LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $report['topProducts'] = $stmt->fetchAll();

    return $report;
}

function getAllTimeSalesSummary($pdo, $location = null)
{
    $summary = [
        'totalSales' => 0,
        'payments' => [],
        'cashOnHand' => 0
    ];

    // Get total sales from all transactions
    $sql = "
        SELECT SUM(chargeTransaction) as totalSales 
        FROM transaction";
    $params = [];

    if ($location) {
        $sql .= " WHERE locationTransaction = ?";
        $params[] = $location;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $summary['totalSales'] = $stmt->fetchColumn() ?: 0;

    // Get all payments and calculate total cash on hand
    $sql = "
        SELECT p.*, t.clientName, t.chargeTransaction 
        FROM payment p 
        JOIN transaction t ON p.transactionID = t.transactionID";

    if ($location) {
        $sql .= " WHERE t.locationTransaction = ?";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $summary['payments'] = $stmt->fetchAll();

    // Calculate cash on hand as sum of all payment amounts
    $summary['cashOnHand'] = array_sum(array_column($summary['payments'], 'amountPayment'));

    return $summary;
}

function getAllTimeBondSummary($pdo, $location = null)
{
    $summary = [
        'totalDeposits' => 0,
        'totalRefunds' => 0,
        'deposits' => [],
        'refunds' => [],
        'currentBalance' => 0
    ];

    // Get all bond transactions
    $sql = "
        SELECT b.*, t.clientName 
        FROM bond b 
        JOIN transaction t ON b.transactionID = t.transactionID";
    $params = [];

    if ($location) {
        $sql .= " WHERE t.locationTransaction = ?";
        $params[] = $location;
    }

    $sql .= " ORDER BY b.dateBond";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bonds = $stmt->fetchAll();

    foreach ($bonds as $bond) {
        if ($bond['depositBond']) {
            $summary['totalDeposits'] += $bond['depositBond'];
            $summary['deposits'][] = [
                'clientName' => $bond['clientName'],
                'amount' => $bond['depositBond'],
                'date' => $bond['dateBond']
            ];
        }
        if ($bond['releaseBond']) {
            $summary['totalRefunds'] += $bond['releaseBond'];
            $summary['refunds'][] = [
                'clientName' => $bond['clientName'],
                'amount' => $bond['releaseBond'],
                'date' => $bond['dateBond']
            ];
        }
    }

    $summary['currentBalance'] = $summary['totalDeposits'] - $summary['totalRefunds'];

    return $summary;
}

function getEmployeeTransactionStats($pdo, $location = null)
{
    $sql = "
        SELECT 
            e.employeeID,
            e.nameEmployee,
            e.positionEmployee,
            COUNT(DISTINCT t.transactionID) as transactionCount,
            COALESCE(SUM(t.chargeTransaction), 0) as totalSales,
            (SELECT COUNT(*) FROM payment p WHERE p.employeeID = e.employeeID) as paymentCount,
            (SELECT COUNT(*) FROM bond b WHERE b.employeeID = e.employeeID) as bondCount
        FROM employee e
        LEFT JOIN transaction t ON e.employeeID = t.employeeID
        WHERE e.employedEmployee = 1";
    $params = [];

    if ($location) {
        $sql .= " AND e.locationEmployee = ?";
        $params[] = $location;
    }

    $sql .= " GROUP BY e.employeeID, e.nameEmployee, e.positionEmployee
        ORDER BY transactionCount DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getNewProductsReport($pdo, $location = null) {
    $sql = "
        SELECT 
            p.productID,
            p.nameProduct,
            p.typeProduct,
            p.locationProduct,
            t.transactionID,
            t.clientName,
            t.datePickUp,
            t.dateReturn,
            t.bondStatus,
            CASE 
                WHEN t.bondStatus = 0 THEN 'Pending Bond'
                WHEN t.bondStatus = 1 THEN 'Bond Posted'
                ELSE 'Unknown'
            END as bondStatusText
        FROM product p
        LEFT JOIN purchase pu ON p.productID = pu.productID
        LEFT JOIN transaction t ON pu.transactionID = t.transactionID
        WHERE p.isNew = 1
        AND (t.bondStatus IS NULL OR t.bondStatus IN (0, 1))";
    
    $params = [];
    if ($location) {
        $sql .= " AND p.locationProduct = ?";
        $params[] = $location;
    }

    $sql .= " ORDER BY p.productID ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

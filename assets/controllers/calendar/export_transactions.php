<?php
require_once '../db.php';

// Get query parameters
$filter = $_GET['filter'] ?? 'all';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$type = $_GET['type'] ?? 'month';

if (!$startDate || !$endDate) {
    die('Export parameters not found');
}

// Generate filename based on export type and filter
$filterText = ($filter !== 'all') ? "_" . $filter : "";
$filename = $type === 'range' 
    ? "transactions{$filterText}_{$startDate}_to_{$endDate}.csv"
    : "transactions{$filterText}_" . date('Y-m', strtotime($startDate)) . ".csv";

// Fetch transactions with products
$sql = "SELECT 
            t.transactionID,
            t.locationTransaction,
            t.clientName,
            t.clientContact,
            t.datePickUp,
            t.dateReturn,
            t.balanceTransaction,
            t.bondStatus,
            t.bondBalance,
            t.chargeTransaction,
            t.discountTransaction,
            e.nameEmployee,
            GROUP_CONCAT(
                CONCAT(
                    p.productID, ':', 
                    p.nameProduct, ':', 
                    CASE 
                        WHEN pur.soldPProduct = 1 AND p.priceSold IS NOT NULL THEN p.priceSold
                        ELSE p.priceProduct 
                    END, ':',
                    CASE 
                        WHEN ph.action_type = 'SOLD' THEN 'Sold'
                        WHEN ph.action_type = 'RETURN' AND ph.damage_status = 1 THEN 'Damaged'
                        WHEN ph.action_type = 'RETURN' THEN 'Returned'
                        WHEN ph.action_type = 'RELEASE' THEN 'Released'
                        ELSE 'Pending'
                    END
                ) SEPARATOR '|'
            ) as products
        FROM transaction t
        LEFT JOIN employee e ON t.employeeID = e.employeeID
        LEFT JOIN purchase pur ON t.transactionID = pur.transactionID
        LEFT JOIN product p ON pur.productID = p.productID
        LEFT JOIN (
            SELECT 
                productID,
                transactionID,
                action_type,
                damage_status,
                ROW_NUMBER() OVER (PARTITION BY productID, transactionID ORDER BY action_date DESC) as rn
            FROM product_history
        ) ph ON pur.productID = ph.productID AND t.transactionID = ph.transactionID AND ph.rn = 1
        WHERE ";

// Apply filter based on selection
if ($filter === 'pickup') {
    $sql .= "(t.datePickUp BETWEEN :start AND :end)";
} else if ($filter === 'return') {
    $sql .= "(t.dateReturn BETWEEN :start AND :end)";
} else {
    $sql .= "(t.datePickUp BETWEEN :start AND :end OR t.dateReturn BETWEEN :start AND :end)";
}

$sql .= " AND (t.bondStatus = 0 OR t.bondStatus = 1)
        GROUP BY t.transactionID
        ORDER BY ";

// Order by relevant date based on filter
if ($filter === 'return') {
    $sql .= "t.dateReturn, t.datePickUp";
} else {
    $sql .= "t.datePickUp, t.dateReturn";
}

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':start' => $startDate,
    ':end' => $endDate
]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fputs($output, "\xEF\xBB\xBF");

// Add export information to the first rows
$exportInfo = [
    ["Export Type: " . strtoupper($type)],
    ["Date Range: " . date('F j, Y', strtotime($startDate)) . " to " . date('F j, Y', strtotime($endDate))],
    ["Transaction Filter: " . strtoupper($filter)],
    [] // Empty row for spacing
];
foreach ($exportInfo as $info) {
    fputcsv($output, $info);
}

// Add headers
fputcsv($output, [
    'Transaction ID',
    'Location',
    'Client Name',
    'Contact Number',
    'Pickup Date',
    'Return Date',
    'Total Charge',
    'Discount',
    'Balance',
    'Bond Balance',
    'Bond Status',
    'Employee',
    'Products'
]);

// Add data with products
foreach ($transactions as $transaction) {
    // Format products string
    $productsStr = '';
    if (!empty($transaction['products'])) {
        $productsList = [];
        foreach (explode('|', $transaction['products']) as $product) {
            list($id, $name, $price, $status) = explode(':', $product);
            $formattedPrice = 'P' . number_format((float)$price, 2);
            $productsList[] = "$name (ID:$id) - $formattedPrice - $status";
        }
        $productsStr = implode('; ', $productsList);
    }

    fputcsv($output, [
        $transaction['transactionID'],
        $transaction['locationTransaction'],
        $transaction['clientName'],
        $transaction['clientContact'],
        $transaction['datePickUp'],
        $transaction['dateReturn'],
        $transaction['chargeTransaction'],
        $transaction['discountTransaction'],
        $transaction['balanceTransaction'],
        $transaction['bondBalance'],
        $transaction['bondStatus'] ? 'Bond Not Released' : 'No Bond Deposit',
        $transaction['nameEmployee'],
        $productsStr
    ]);
}

fclose($output);
exit;

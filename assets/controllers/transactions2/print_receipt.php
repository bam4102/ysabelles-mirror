<?php
// Prevent direct access
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(dirname(dirname(__FILE__))));
}
require_once '../db.php';

// Helper functions
function formatMoney($amount) {
    return '₱' . number_format($amount, 2);
}

function calculateVat($amount) {
    $netOfVat = $amount / 1.12;
    return [
        'netOfVat' => $netOfVat,
        'addedVat' => $amount - $netOfVat
    ];
}

// Validate request
if (!isset($_GET['type']) || !isset($_GET['id'])) {
    http_response_code(400);
    die('Invalid request parameters');
}

$type = $_GET['type'];
$id = intval($_GET['id']);

try {
    // Base SQL for both payment and bond receipts
    $baseSql = "
        SELECT t.*, e.nameEmployee, p.*, 
            (SELECT bondCurrentBalance FROM bond 
             WHERE transactionID = t.transactionID 
             ORDER BY bondID DESC LIMIT 1) as current_bond_balance,
            (SELECT CONCAT('[', GROUP_CONCAT(
                JSON_OBJECT(
                    'nameProduct', pr.nameProduct,
                    'soldPProduct', pu.soldPProduct
                )
            ), ']')
            FROM purchase pu
            JOIN product pr ON pu.productID = pr.productID
            WHERE pu.transactionID = t.transactionID) as products
        FROM transaction t
        LEFT JOIN employee e ON e.employeeID = e.employeeID
        LEFT JOIN %s p ON t.transactionID = p.transactionID
        WHERE p.%s = ?";

    $table = $type === 'payment' ? 'payment' : 'bond';
    $idField = $type === 'payment' ? 'paymentID' : 'bondID';
    
    $sql = sprintf($baseSql, $table, $idField);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $receipt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receipt) {
        throw new Exception('Receipt not found');
    }

    // Get amount and current balance based on type
    $amount = $type === 'payment' ? 
        $receipt['amountPayment'] : 
        ($receipt['depositBond'] ?? $receipt['releaseBond']);
    $date = $type === 'payment' ? 
        $receipt['datePayment'] : 
        $receipt['dateBond'];
    $balance = $type === 'payment' ? 
        $receipt['paymentCurrentBalance'] : 
        $receipt['bondCurrentBalance'];

    // Calculate VAT components
    $vatComponents = calculateVat($amount);
    $products = json_decode($receipt['products'], true) ?: [];

    // Determine if bond disclaimer should be shown
    $showBondDisclaimer = $type === 'payment' && 
                          $balance === 0 && 
                          $receipt['bondTransaction'] > 0 && 
                          $receipt['current_bond_balance'] > 0;

} catch (Exception $e) {
    http_response_code(500);
    die('Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Receipt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
            body { padding: 0; margin: 0; }
            .receipt-container { box-shadow: none; }
        }
        .receipt-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .company-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .product-table {
            margin-bottom: 20px;
        }
        .totals-section {
            margin-top: 20px;
        }
        .disclaimer {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="company-header">
            <h2>YSABELLES</h2>
            <p>Payment Receipt</p>
        </div>

        <div class="info-section">
            <div class="row">
                <div class="col-6">
                    <p><strong>Date:</strong> <?= date('M d, Y', strtotime($date)) ?></p>
                    <p><strong>Transaction ID:</strong> <?= $receipt['transactionID'] ?></p>
                    <p><strong>Payment Mode:</strong> <?= $type === 'payment' ? $receipt['kindPayment'] : 'Bond' ?></p>
                </div>
                <div class="col-6">
                    <p><strong>Client:</strong> <?= htmlspecialchars($receipt['clientName']) ?></p>
                    <p><strong>Address:</strong> <?= htmlspecialchars($receipt['clientAddress']) ?></p>
                    <p><strong>Contact:</strong> <?= htmlspecialchars($receipt['clientContact']) ?></p>
                </div>
            </div>
        </div>

        <div class="product-table">
            <h5>Products</h5>
            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= htmlspecialchars($product['nameProduct']) ?></td>
                        <td><?= $product['soldPProduct'] ? '<span class="badge bg-primary">SOLD</span>' : '' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="totals-section">
            <div class="row">
                <div class="col-md-6">
                    <table class="table">
                        <tr>
                            <td><strong>Total Charge:</strong></td>
                            <td><?= formatMoney($receipt['chargeTransaction']) ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php echo ($type === 'bond' || $showBondDisclaimer) ? 'Bond Deposited' : 'Bond Required'; ?>:</strong></td>
                            <td><?= formatMoney($receipt['bondTransaction']) ?></td>
                        </tr>
                        <tr class="table-active">
                            <td><strong>Remaining Balance:</strong></td>
                            <td><?= formatMoney($balance) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table">
                        <tr>
                            <td><strong>Net of VAT:</strong></td>
                            <td><?= formatMoney($vatComponents['netOfVat']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Added VAT (12%):</strong></td>
                            <td><?= formatMoney($vatComponents['addedVat']) ?></td>
                        </tr>
                        <tr class="table-active">
                            <td><strong>Total Amount:</strong></td>
                            <td><?= formatMoney($amount) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($type === 'bond' || $showBondDisclaimer): ?>
        <div class="disclaimer">
            Keep this receipt as your claim stub for bond release.
        </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <p><strong>Processed by:</strong> <?= htmlspecialchars($receipt['nameEmployee']) ?></p>
            <button class="btn btn-primary no-print" onclick="window.print()">Print Receipt</button>
            <button class="btn btn-secondary no-print" onclick="window.close()">Close</button>
        </div>
    </div>
</body>
</html>

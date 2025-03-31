<?php
require_once '../db.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    die('Bond ID is required');
}

$bondId = intval($_GET['id']);

try {
    // Get bond and transaction details with products
    $sql = "SELECT b.*, t.*, e.nameEmployee,
            (SELECT CONCAT('[', GROUP_CONCAT(
                JSON_OBJECT(
                    'nameProduct', p.nameProduct,
                    'typeProduct', p.typeProduct,
                    'priceProduct', p.priceProduct,
                    'priceSold', p.priceSold,
                    'soldPProduct', pu.soldPProduct,
                    'packagePurchase', pu.packagePurchase
                )
            ), ']')
            FROM purchase pu
            JOIN product p ON pu.productID = p.productID
            WHERE pu.transactionID = t.transactionID) as products
            FROM bond b
            JOIN transaction t ON b.transactionID = t.transactionID 
            JOIN employee e ON b.employeeID = e.employeeID
            WHERE b.bondID = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$bondId]);
    $receipt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receipt || !$receipt['depositBond']) {
        throw new Exception('Bond receipt not found');
    }

    $products = json_decode($receipt['products'], true) ?: [];

} catch (Exception $e) {
    http_response_code(500);
    die('Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Bond Receipt</title>
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
            <p>Bond Deposit Receipt</p>
        </div>

        <div class="info-section">
            <div class="row">
                <div class="col-6">
                    <p><strong>Date:</strong> <?= date('M d, Y', strtotime($receipt['dateBond'])) ?></p>
                    <p><strong>Transaction ID:</strong> <?= $receipt['transactionID'] ?></p>
                    <p><strong>Bond Required:</strong> ₱<?= number_format($receipt['bondTransaction'], 2) ?></p>
                </div>
                <div class="col-6">
                    <p><strong>Client:</strong> <?= htmlspecialchars($receipt['clientName']) ?></p>
                    <p><strong>Address:</strong> <?= htmlspecialchars($receipt['clientAddress']) ?></p>
                    <p><strong>Contact:</strong> <?= htmlspecialchars($receipt['clientContact']) ?></p>
                </div>
            </div>
        </div>

        <div class="product-table">
            <h5>Products List</h5>
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): 
                        $displayPrice = $product['soldPProduct'] ? $product['priceSold'] : $product['priceProduct'];
                    ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($product['nameProduct']) ?>
                            <?php if ($product['soldPProduct']): ?>
                                <span class="badge bg-primary">SOLD</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($product['typeProduct']) ?></td>
                        <td>₱<?= number_format($displayPrice, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="totals-section">
            <div class="row">
                <div class="col-md-6 offset-md-6">
                    <table class="table">
                        <tr>
                            <td><strong>Required Bond:</strong></td>
                            <td>₱<?= number_format($receipt['bondTransaction'], 2) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Current Balance:</strong></td>
                            <td>₱<?= number_format($receipt['bondCurrentBalance'], 2) ?></td>
                        </tr>
                        <tr class="table-active">
                            <td><strong>Amount Deposited:</strong></td>
                            <td>₱<?= number_format($receipt['depositBond'], 2) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($receipt['noteBond']): ?>
        <div class="mt-4">
            <p><strong>Note:</strong> <?= htmlspecialchars($receipt['noteBond']) ?></p>
        </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <p><strong>Processed by:</strong> <?= htmlspecialchars($receipt['nameEmployee']) ?></p>
            <p class="mb-4">Keep this receipt as your claim stub for bond release.</p>
            <button class="btn btn-primary no-print" onclick="window.print()">Print Receipt</button>
            <button class="btn btn-secondary no-print" onclick="window.close()">Close</button>
        </div>
    </div>
</body>
</html>

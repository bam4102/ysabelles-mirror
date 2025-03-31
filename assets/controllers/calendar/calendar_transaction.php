<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_GET['transactionID'])) {
    echo json_encode(['success' => false, 'error' => 'Transaction ID is required']);
    exit;
}

$transactionID = $_GET['transactionID'];

try {
    // Get transaction details
    $sql = "SELECT t.*, e.nameEmployee 
            FROM transaction t
            LEFT JOIN employee e ON t.employeeID = e.employeeID 
            WHERE t.transactionID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$transactionID]);
    $trans = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($trans) {
        // Start building HTML response
        $html = '<div class="transaction-details">';
        
        // Basic transaction info
        $html .= '<div class="mb-3">';
        $html .= '<h5>Transaction Information</h5>';
        $html .= '<table class="table table-bordered">';
        $html .= '<tr><th>ID:</th><td>' . htmlspecialchars($trans['transactionID']) . '</td></tr>';
        $html .= '<tr><th>Client:</th><td>' . htmlspecialchars($trans['clientName']) . '</td></tr>';
        $html .= '<tr><th>Location:</th><td>' . htmlspecialchars($trans['locationTransaction']) . '</td></tr>';
        $html .= '<tr><th>Pickup:</th><td>' . htmlspecialchars($trans['datePickUp']) . '</td></tr>';
        $html .= '<tr><th>Return:</th><td>' . htmlspecialchars($trans['dateReturn']) . '</td></tr>';
        $html .= '<tr><th>Balance:</th><td>₱' . number_format($trans['balanceTransaction'], 2) . '</td></tr>';
        $html .= '<tr><th>Bond Balance:</th><td>₱' . number_format($trans['bondBalance'], 2) . '</td></tr>';
        $html .= '<tr><th>Employee:</th><td>' . htmlspecialchars($trans['nameEmployee']) . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';

        // Fetch related products with their history
        $sqlProducts = "SELECT 
                        p.productID, 
                        p.nameProduct, 
                        p.priceProduct, 
                        p.priceSold,
                        p.isNew,
                        pur.soldPProduct,
                        ph.action_type,
                        ph.damage_status,
                        ph.damage_description,
                        ph.action_date
                    FROM purchase pur
                    JOIN product p ON pur.productID = p.productID
                    LEFT JOIN (
                        SELECT 
                            productID,
                            action_type,
                            damage_status,
                            damage_description,
                            action_date,
                            ROW_NUMBER() OVER (PARTITION BY productID ORDER BY action_date DESC) as rn
                        FROM product_history
                        WHERE transactionID = ?
                    ) ph ON p.productID = ph.productID AND ph.rn = 1
                    WHERE pur.transactionID = ?
                    ORDER BY ph.action_date DESC";
        $stmtProducts = $pdo->prepare($sqlProducts);
        $stmtProducts->execute([$transactionID, $transactionID]);
        $products = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);

        if ($products) {
            $html .= '<div class="mb-3">';
            $html .= '<h5>Products in this Transaction</h5>';
            $html .= '<table class="table table-bordered">';
            $html .= '<thead><tr>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Price</th>
                        <th>Status</th>
                      </tr></thead><tbody>';
            
            foreach ($products as $prod) {
                // Determine status based on product_history
                if ($prod['action_type'] === 'SOLD') {
                    $status = '<span class="badge bg-info">Sold</span>';
                    $price = $prod['priceSold'] ? '₱' . number_format($prod['priceSold'], 2) : '₱' . number_format($prod['priceProduct'], 2);
                } else if ($prod['action_type'] === 'RETURN') {
                    if ($prod['damage_status'] == 1) {
                        $status = '<span class="badge bg-danger" title="' . htmlspecialchars($prod['damage_description']) . '">Damaged</span>';
                    } else {
                        $status = '<span class="badge bg-success">Returned</span>';
                    }
                    $price = '₱' . number_format($prod['priceProduct'], 2);
                } else if ($prod['action_type'] === 'RELEASE') {
                    $status = '<span class="badge bg-warning">Released</span>';
                    $price = '₱' . number_format($prod['priceProduct'], 2);
                } else {
                    $status = '<span class="badge bg-secondary">Pending</span>';
                    $price = '₱' . number_format($prod['priceProduct'], 2);
                }

                // Create badges for new and sold products
                $featureBadges = '';
                
                // Add New badge
                if ($prod['isNew'] == 1) {
                    $featureBadges .= ' <span class="badge status-new ms-2">New</span>';
                }
                
                // Add To Be Sold badge for products marked for sale but not yet processed
                if ($prod['soldPProduct'] == 1 && $prod['action_type'] !== 'SOLD') {
                    $featureBadges .= ' <span class="badge status-to-be-sold ms-1">To Be Sold</span>';
                }
                
                // Add Sold badge if the product was sold
                if ($prod['action_type'] === 'SOLD') {
                    $featureBadges .= ' <span class="badge status-sold ms-1">Sold</span>';
                }
                
                $html .= "<tr>";
                $html .= "<td>" . htmlspecialchars($prod['productID']) . "</td>";
                $html .= "<td><a href='#' class='product-link' data-productid='" . htmlspecialchars($prod['productID']) . "'>" . 
                        htmlspecialchars($prod['nameProduct']) . "</a>" . $featureBadges . "</td>";
                $html .= "<td>" . $price . "</td>";
                $html .= "<td>{$status}</td>";
                $html .= "</tr>";
            }
            $html .= "</tbody></table>";
            $html .= '</div>';
        } else {
            $html .= '<div class="alert alert-info">No products found for this transaction.</div>';
        }

        $html .= '</div>'; // Close transaction-details div

        echo json_encode([
            'success' => true,
            'html' => $html
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Transaction not found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

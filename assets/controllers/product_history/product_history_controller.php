<?php
// Modify the main SQL query to get all transactions where bondStatus=0 for each product
$sql = "SELECT p.*, 
        COUNT(DISTINCT ph.historyID) as total_uses,
        pc.productCategory,
        pc.categoryCode,
        (SELECT 
            GROUP_CONCAT(
                JSON_OBJECT(
                    'transactionID', t2.transactionID,
                    'clientName', t2.clientName,
                    'datePickUp', t2.datePickUp,
                    'dateReturn', t2.dateReturn,
                    'daysUntilReturn', DATEDIFF(t2.dateReturn, CURRENT_DATE)
                )
            )
            FROM transaction t2
            JOIN purchase pu ON t2.transactionID = pu.transactionID
            WHERE pu.productID = p.productID 
            AND t2.bondStatus IN (0, 1)
            ORDER BY t2.dateTransaction DESC
        ) as active_transactions
        FROM product p
        LEFT JOIN product_history ph ON ph.productID = p.productID
        LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID
        GROUP BY p.productID
        ORDER BY p.counterProduct DESC, total_uses DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Replace the history details SQL query
if (isset($_GET['id'])) {
    $sql = "SELECT 
            ph.historyID,
            ph.action_type,
            ph.action_date,
            ph.damage_status,
            ph.damage_description,
            t.transactionID,
            t.clientName,
            t.datePickUp,
            t.dateReturn,
            e.nameEmployee as handled_by
            FROM product_history ph
            JOIN transaction t ON ph.transactionID = t.transactionID
            JOIN employee e ON ph.employeeID = e.employeeID
            WHERE ph.productID = ?
            ORDER BY ph.action_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_GET['id']]);
    $historyDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Modify the AJAX response section (where isset($_GET['id']))
if ($isAjax && isset($_GET['id'])) {
    // Get product details
    $sql = "SELECT p.*, pc.productCategory, pc.categoryCode 
            FROM product p 
            LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID 
            WHERE p.productID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_GET['id']]);
    $productDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    // Updated transmittal history query to match database schema
    $transmittalSql = "SELECT t.*, 
                       e_sent.nameEmployee as sent_by,
                       e_received.nameEmployee as received_by,
                       DATE_FORMAT(t.dateTransmittal, '%M %d, %Y %h:%i %p') as formatted_date,
                       DATE_FORMAT(t.dateDelivered, '%M %d, %Y %h:%i %p') as formatted_delivered
                       FROM transmittal t
                       INNER JOIN employee e_sent ON t.employeeID = e_sent.employeeID
                       LEFT JOIN employee e_received ON t.receivedBy = e_received.employeeID
                       WHERE t.productID = ?
                       ORDER BY t.dateTransmittal DESC";
    $stmt = $pdo->prepare($transmittalSql);
    $stmt->execute([$_GET['id']]);
    $transmittals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
    <!-- Product Info Section -->
    <div class="product-info mb-4">
        <h6>Product Details</h6>
        <table class="table table-sm">
            <tr>
                <th>Code</th>
                <td><?= htmlspecialchars($productDetails['codeProduct']) ?></td>
                <th>Name</th>
                <td><?= htmlspecialchars($productDetails['nameProduct']) ?></td>
            </tr>
            <tr>
                <th>Category</th>
                <td><?= htmlspecialchars($productDetails['categoryCode']) ?></td>
                <th>Times Used</th>
                <td><span class="badge bg-info"><?= (int)$productDetails['counterProduct'] ?> times</span></td>
            </tr>
            <tr>
                <th>Status</th>
                <td colspan="3">
                    <?php if ($productDetails['returnedProduct'] == 1): ?>
                        <span class="badge bg-warning">Released</span>
                    <?php else: ?>
                        <span class="badge bg-success">Available</span>
                    <?php endif; ?>
                    <?php if ($productDetails['damageProduct'] == 1): ?>
                        <span class="badge bg-danger">Damaged</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- Collapsible History Sections -->
    <div class="accordion mb-4" id="historyAccordion">
        <!-- Transmittal History Section -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#transmittalHistory">
                    Transmittal History
                    <?php if (!empty($transmittals)): ?>
                        <span class="badge bg-secondary ms-2"><?= count($transmittals) ?></span>
                    <?php endif; ?>
                </button>
            </h2>
            <div id="transmittalHistory" class="accordion-collapse collapse show" data-bs-parent="#historyAccordion">
                <div class="accordion-body">
                    <?php if (!empty($transmittals)): ?>
                        <div class="transmittal-timeline">
                            <?php foreach ($transmittals as $transmittal): ?>
                                <div class="transmittal-item">
                                    <div class="timeline-connector">
                                        <div class="timeline-dot"></div>
                                    </div>
                                    <div class="transmittal-card">
                                        <div class="transmittal-header">
                                            <?php
                                            $statusClass = match ($transmittal['statusTransmittal']) {
                                                'PENDING' => 'status-pending',
                                                'IN_TRANSIT' => 'status-transit',
                                                'DELIVERED' => 'status-delivered',
                                                'CANCELLED' => 'status-cancelled',
                                                default => 'status-unknown'
                                            };
                                            ?>
                                            <div class="transmittal-status <?= $statusClass ?>">
                                                <?= htmlspecialchars($transmittal['statusTransmittal']) ?>
                                            </div>
                                            <div class="transmittal-date">
                                                <?= $transmittal['formatted_date'] ?>
                                            </div>
                                        </div>
                                        <div class="transmittal-body">
                                            <div class="location-flow">
                                                <div class="from-location">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <?= htmlspecialchars($transmittal['fromLocation']) ?>
                                                </div>
                                                <div class="flow-arrow">
                                                    <i class="fas fa-long-arrow-alt-right"></i>
                                                </div>
                                                <div class="to-location">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <?= htmlspecialchars($transmittal['toLocation']) ?>
                                                </div>
                                            </div>
                                            <div class="transmittal-details">
                                                <div class="personnel-info">
                                                    <div class="sent-by">
                                                        <i class="fas fa-paper-plane"></i>
                                                        Sent by <?= htmlspecialchars($transmittal['sent_by']) ?>
                                                    </div>
                                                    <?php if ($transmittal['receivedBy']): ?>
                                                        <div class="received-by">
                                                            <i class="fas fa-check-circle"></i>
                                                            Received by <?= htmlspecialchars($transmittal['received_by']) ?>
                                                            on <?= $transmittal['formatted_delivered'] ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($transmittal['noteTransmittal']): ?>
                                                    <div class="transmittal-note">
                                                        <i class="fas fa-sticky-note"></i>
                                                        <?= htmlspecialchars($transmittal['noteTransmittal']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No transmittal history available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Product Usage History Section -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#usageHistory">
                    Product Usage History
                    <?php if (!empty($historyDetails)): ?>
                        <span class="badge bg-secondary ms-2"><?= count($historyDetails) ?></span>
                    <?php endif; ?>
                </button>
            </h2>
            <div id="usageHistory" class="accordion-collapse collapse" data-bs-parent="#historyAccordion">
                <div class="accordion-body">
                    <?php if (!empty($historyDetails)): ?>
                        <div class="timeline">
                            <?php
                            $currentTransaction = null;
                            foreach ($historyDetails as $record):
                                if ($currentTransaction !== $record['transactionID']):
                                    if ($currentTransaction !== null): ?>
                        </div>
                </div>
            <?php endif;
                                    $currentTransaction = $record['transactionID'];
            ?>
            <div class="transaction-group mb-3">
                <div class="transaction-header">
                    <h6>Transaction #<?= $record['transactionID'] ?> - <?= htmlspecialchars($record['clientName']) ?></h6>
                    <div class="transaction-dates">
                        <span><i class="fas fa-calendar-check"></i> Pickup: <?= date('M d, Y', strtotime($record['datePickUp'])) ?></span>
                        <span><i class="fas fa-calendar-times"></i> Return: <?= date('M d, Y', strtotime($record['dateReturn'])) ?></span>
                    </div>
                </div>
                <div class="action-list">
                <?php endif; ?>
                <div class="action-item <?= strtolower($record['action_type']) ?>">
                    <div class="action-info">
                        <div class="action-icon">
                            <?= $record['action_type'] === 'RELEASE' ? '📤' : '📥' ?>
                        </div>
                        <div class="action-details">
                            <strong><?= $record['action_type'] ?></strong><br>
                            <small>by <?= htmlspecialchars($record['handled_by']) ?></small>
                            <?php if ($record['damage_status']): ?>
                                <div class="damage-alert">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span><?= htmlspecialchars($record['damage_description']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="action-date">
                        <?= date('M d, Y h:i A', strtotime($record['action_date'])) ?>
                    </div>
                </div>
            <?php endforeach;
                            if ($currentTransaction !== null): ?>
                </div>
            </div>
        <?php endif; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">No usage history available.</p>
        <?php endif; ?>
        </div>
    </div>
    </div>
    </div>

    <?php
    exit;
}

// Add this new AJAX handler before the closing PHP tag
if ($isAjax && isset($_GET['transactionId'])) {
    $sql = "SELECT t.*, 
            GROUP_CONCAT(DISTINCT CONCAT(p.nameProduct, ' (', IFNULL(p.codeProduct, p.productID), ')') SEPARATOR ', ') as products,
            e.nameEmployee as handledBy,
            GROUP_CONCAT(DISTINCT ph.action_type ORDER BY ph.action_date DESC) as actions,
            GROUP_CONCAT(DISTINCT py.kindPayment ORDER BY py.datePayment DESC) as payment_methods,
            GROUP_CONCAT(DISTINCT py.amountPayment ORDER BY py.datePayment DESC) as payment_amounts
            FROM transaction t
            LEFT JOIN purchase pu ON t.transactionID = pu.transactionID
            LEFT JOIN product p ON pu.productID = p.productID
            LEFT JOIN product_history ph ON t.transactionID = ph.transactionID
            LEFT JOIN employee e ON t.employeeID = e.employeeID
            LEFT JOIN payment py ON t.transactionID = py.transactionID
            WHERE t.transactionID = ?
            GROUP BY t.transactionID";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_GET['transactionId']]);
    $transactionDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($transactionDetails): ?>
        <div class="transaction-details">
            <h5>Transaction #<?= htmlspecialchars($transactionDetails['transactionID']) ?></h5>
            <table class="table table-sm">
                <tr>
                    <th>Client:</th>
                    <td><?= htmlspecialchars($transactionDetails['clientName']) ?></td>
                </tr>
                <tr>
                    <th>Contact:</th>
                    <td><?= htmlspecialchars($transactionDetails['clientContact']) ?></td>
                </tr>
                <tr>
                    <th>Pickup Date:</th>
                    <td><?= date('M d, Y', strtotime($transactionDetails['datePickUp'])) ?></td>
                </tr>
                <tr>
                    <th>Return Date:</th>
                    <td><?= date('M d, Y', strtotime($transactionDetails['dateReturn'])) ?></td>
                </tr>
                <tr>
                    <th>Products:</th>
                    <td>
                        <?php if ($transactionDetails['products']): ?>
                            <?= htmlspecialchars($transactionDetails['products']) ?>
                        <?php else: ?>
                            <em>No products listed</em>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Status:</th>
                    <td>
                        <?php
                        $actions = explode(',', $transactionDetails['actions'] ?? '');
                        $lastAction = end($actions);
                        echo $lastAction ? "<span class='badge bg-" .
                            ($lastAction === 'RETURN' ? 'success">Returned' : 'warning">Released') .
                            "</span>" : "<span class='badge bg-secondary'>Pending</span>";
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>Payment Methods:</th>
                    <td>
                        <?php
                        if ($transactionDetails['payment_methods']) {
                            $methods = explode(',', $transactionDetails['payment_methods']);
                            $amounts = explode(',', $transactionDetails['payment_amounts']);
                            foreach ($methods as $i => $method) {
                                echo htmlspecialchars($method) . ': ₱' . number_format($amounts[$i], 2) . '<br>';
                            }
                        } else {
                            echo '<em>No payments recorded</em>';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>Handled By:</th>
                    <td><?= htmlspecialchars($transactionDetails['handledBy']) ?></td>
                </tr>
            </table>
        </div>
<?php
    else:
        echo '<div class="alert alert-danger">Transaction not found.</div>';
    endif;
    exit;
}
?>
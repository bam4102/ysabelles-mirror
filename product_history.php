<?php
session_start();
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header("Location: login_page.php");
    exit;
}

$currentName = $_SESSION['user']['nameEmployee'];
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

include 'auth.php';
include './assets/controllers/db.php';
include './assets/controllers/product_history/product_history_controller.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Product History</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="./assets/img/sitelogo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="./assets/css/product_history.css" rel="stylesheet">
    <link href="./assets/css/global.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="product-history">
    <!-- Include Navigation -->
    <?php include 'assets/nav/nav.php'; ?>

    <div class="container-fluid">
        <div class="title-container">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Product History</h1>
                <div class="nav nav-pills">
                    <button type="button" class="nav-link active filter-btn" data-filter="all">All</button>
                    <button type="button" class="nav-link filter-btn" data-filter="available">Available</button>
                    <button type="button" class="nav-link filter-btn" data-filter="released">Released</button>
                    <button type="button" class="nav-link filter-btn" data-filter="damaged">Damaged</button>
                    <button type="button" class="nav-link filter-btn" data-filter="overdue">7+ Days Not Returned</button>
                    <button type="button" class="nav-link filter-btn" data-filter="new">New Products</button>
                    <button type="button" class="nav-link filter-btn" data-filter="sold">Sold Items</button>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">All Products</h5>
                    </div>
                    <div class="card-body">
                        <table id="productsTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Times Used</th>
                                    <th>Status</th>
                                    <th>Current</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr class="<?= $product['isNew'] == 1 ? 'new-product' : '' ?>">
                                        <td><?= htmlspecialchars($product['productID']) ?></td>
                                        <td><?= htmlspecialchars($product['nameProduct']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($product['categoryCode'] ?? '') ?>
                                            (<?= htmlspecialchars($product['productCategory'] ?? '') ?>)
                                        </td>
                                        <td>
                                            <?= (int)$product['counterProduct'] ?? 0 ?>
                                        </td>
                                        <td class="status-cell">
                                            <?php if ($product['soldProduct'] == 1): ?>
                                                <span class="badge bg-info">Sold</span>
                                            <?php elseif ($product['returnedProduct'] == 1): ?>
                                                <span class="badge bg-warning">Released</span>
                                                <?php
                                                if ($product['active_transactions']) {
                                                    $transactions = array_filter(json_decode('[' . $product['active_transactions'] . ']', true));
                                                    if (!empty($transactions)) {
                                                        foreach ($transactions as $trans) {
                                                            $daysUntilReturn = (int)$trans['daysUntilReturn'];
                                                            if ($daysUntilReturn < -7) {
                                                                echo '<span class="badge bg-danger delay-badge">Delayed (' . abs($daysUntilReturn) . ' days)</span>';
                                                            }
                                                        }
                                                    }
                                                }
                                                ?>
                                            <?php else: ?>
                                                <span class="badge bg-success">Available</span>
                                            <?php endif; ?>
                                            <?php if ($product['damageProduct'] == 1): ?>
                                                <span class="badge bg-danger">Damaged</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            if ($product['active_transactions']):
                                                $transactions = array_filter(json_decode('[' . $product['active_transactions'] . ']', true));
                                                if (!empty($transactions)):
                                                    foreach ($transactions as $index => $trans):
                                                        $daysUntilReturn = (int)$trans['daysUntilReturn'];
                                                        // Set button color based on status:
                                                        // Red if delayed more than 7 days
                                                        // Blue for most recent transaction
                                                        // Gray for other active transactions
                                                        $btnClass = $daysUntilReturn < -7 ? 'btn-danger' : ($index === 0 ? 'btn-info' : 'btn-secondary');
                                            ?>
                                                        <button type="button"
                                                            class="btn btn-sm <?= $btnClass ?> view-transaction mb-1"
                                                            data-transaction-id="<?= $trans['transactionID'] ?>"
                                                            title="Transaction #<?= $trans['transactionID'] ?> - <?= htmlspecialchars($trans['clientName']) ?>">
                                                            <i class="fas fa-info-circle"></i>
                                                            <?php if ($daysUntilReturn < -7): ?>
                                                                <i class="fas fa-exclamation-triangle"></i>
                                                            <?php endif; ?>
                                                            <?= $index + 1 ?>
                                                        </button>
                                                <?php
                                                    endforeach;
                                                endif;
                                            else:
                                                ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button"
                                                class="btn btn-sm btn-primary view-history"
                                                data-product-id="<?= $product['productID'] ?>">
                                                View History
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- History Modal -->
        <div class="modal fade" id="ph_historyModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Product History Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="ph_historyModalContent">
                        <!-- History content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
        <!-- Transaction Details Modal -->
        <div class="modal fade" id="ph_transactionModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Transaction Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="ph_transactionModalContent">
                        <!-- Transaction content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Bootstrap bundle before other scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="./assets/scripts/product_history.js"></script>
</body>

</html>
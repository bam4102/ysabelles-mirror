<?php
session_start();
include 'auth.php';
include 'assets/controllers/db.php';
include 'assets/controllers/transactions2/transactions2_controller.php';

// Check if the user session exists and is not empty
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header("Location: login_page.php");
    exit;
}

// If the user's position is not ADMIN or CASHIER, redirect to credential_error.php
$userPosition = strtoupper($_SESSION['user']['positionEmployee']);
if (!in_array($userPosition, ['ADMIN', 'CASHIER', 'SUPERADMIN'])) {
    header("Location: credential_error.php");
    exit;
}

// Get the name and location of the currently logged-in user from the session.
$currentName = $_SESSION['user']['nameEmployee'];
$userLocation = $_SESSION['user']['locationEmployee'];

// Check if user can modify location (only SUPERADMIN)
$canModifyLocation = ($userPosition === 'SUPERADMIN');

// Make a single database call to get transactions with appropriate filters
$transactions = getTransactions($pdo);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions</title>
    <link rel="icon" type="image/png" href="./assets/img/sitelogo.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/global.css">
    <link rel="stylesheet" href="./assets/css/transactions2.css">
</head>

<body>
    <!-- Include Navigation -->
    <?php include 'assets/nav/nav.php'; ?>

    <div class="container">
        <div class="title-header d-flex justify-content-between align-items-left">
            <h1>Transactions</h1>
        </div>
    </div>

    <!-- Add filter section -->
    <div class="container mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Filters</h5>
                <?php if (!$canModifyLocation): ?>
                    <p class="small text-muted mb-0">You can only see transactions from your location: <?= htmlspecialchars($userLocation) ?></p>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form id="transactionFilters" class="row g-3">
                    <!-- Pickup Date Range -->
                    <div class="col-md-4">
                        <label class="form-label">Pickup Date Range</label>
                        <div class="input-group">
                            <input type="date" class="form-control" id="pickupDateStart" placeholder="From">
                            <span class="input-group-text">to</span>
                            <input type="date" class="form-control" id="pickupDateEnd" placeholder="To">
                        </div>
                    </div>

                    <!-- Return Date Range -->
                    <div class="col-md-4">
                        <label class="form-label">Return Date Range</label>
                        <div class="input-group">
                            <input type="date" class="form-control" id="returnDateStart" placeholder="From">
                            <span class="input-group-text">to</span>
                            <input type="date" class="form-control" id="returnDateEnd" placeholder="To">
                        </div>
                    </div>

                    <!-- Location Filter -->
                    <div class="col-md-2">
                        <label class="form-label">Location</label>
                        <select class="form-select" id="locationFilter" <?= $canModifyLocation ? '' : 'disabled' ?>>
                            <?php if ($canModifyLocation): ?>
                                <option value="">All Locations</option>
                            <?php endif; ?>
                            <option value="BACOLOD CITY" <?= (!$canModifyLocation && $userLocation == 'BACOLOD CITY') ? 'selected' : '' ?>>Bacolod City</option>
                            <option value="DUMAGUETE CITY" <?= (!$canModifyLocation && $userLocation == 'DUMAGUETE CITY') ? 'selected' : '' ?>>Dumaguete City</option>
                            <option value="ILOILO CITY" <?= (!$canModifyLocation && $userLocation == 'ILOILO CITY') ? 'selected' : '' ?>>Iloilo City</option>
                            <option value="SAN CARLOS CITY" <?= (!$canModifyLocation && $userLocation == 'SAN CARLOS CITY') ? 'selected' : '' ?>>San Carlos City</option>
                            <option value="CEBU CITY" <?= (!$canModifyLocation && $userLocation == 'CEBU CITY') ? 'selected' : '' ?>>Cebu City</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="statusFilter">
                            <option value="">All Active Statuses</option>
                            <option value="0">Unpaid</option>
                            <option value="1">Active</option>
                            <option value="2">Completed</option>
                            <option value="3">Inactive</option>
                        </select>
                    </div>

                    <!-- Filter Action Buttons -->
                    <div class="col-md d-flex justify-content-end gap-2">
                        <button type="button" id="resetFilters" class="btn btn-secondary">
                            <i class="fas fa-sync"></i> Reset
                        </button>
                        <button type="button" id="applyFilters" class="btn btn-primary me-2">
                            <i class="fas fa-filter"></i> Apply
                        </button>
                    </div>
                </form>

                <!-- Special Actions - Only for ADMIN/SUPERADMIN -->
                <?php if (in_array($userPosition, ['ADMIN', 'SUPERADMIN', 'CASHIER'])): ?>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="mb-2">Special Actions</h6>
                            <button type="button" id="updatePastTransactions" class="btn btn-danger">
                                <i class="fas fa-history"></i> Mark Unpaid Transactions as Inactive
                            </button>
                            <small class="text-muted ms-2">This will mark all transactions with no payment records as inactive</small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table id="transactionsTable" class="table table-striped table-hover">
            <thead>
                <tr class="table-header">
                    <th>ID</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Client</th>
                    <th>Pick-up</th>
                    <th>Return</th>
                    <th>Total</th>
                    <th>Discount</th>
                    <th>Bond</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction):
                    // Determine the row color class based on bondStatus
                    $rowColorClass = '';
                    $rowTooltip = '';

                    switch ($transaction['bondStatus']) {
                        case 0:
                            $rowColorClass = 'table-danger'; // Red for unpaid
                            break;
                        case 1:
                            $rowColorClass = 'table-warning'; // Yellow for active
                            break;
                        case 2:
                            $rowColorClass = 'table-success'; // Green for completed
                            break;
                        case 3:
                            $rowColorClass = 'table-secondary'; // Gray for inactive
                            $rowTooltip = 'This transaction is marked as inactive due to no payment records';
                            break;
                    }
                ?>
                    <tr class="<?= $rowColorClass ?>"
                        <?= !empty($rowTooltip) ? 'title="' . $rowTooltip . '"' : '' ?>
                        data-bond-status="<?= $transaction['bondStatus'] ?>">
                        <td><?= $transaction['transactionID'] ?></td>
                        <td><?= date('m/d/y', strtotime($transaction['dateTransaction'])) ?></td>
                        <td><?= $transaction['locationTransaction'] ?></td>
                        <td><?= htmlspecialchars($transaction['clientName']) ?></td>
                        <td><?= date('m/d/y', strtotime($transaction['datePickUp'])) ?></td>
                        <td><?= date('m/d/y', strtotime($transaction['dateReturn'])) ?></td>
                        <td><?= formatMoney($transaction['chargeTransaction']) ?></td>
                        <td><?= formatMoney($transaction['discountTransaction']) ?></td>
                        <td><?= formatMoney($transaction['bondBalance']) ?></td>
                        <td><?= formatMoney($transaction['balanceTransaction']) ?></td>
                        <td><?= getBondStatusText($transaction['bondStatus'], $transaction['non_sold_count']) ?></td>
                        <td data-status="<?= $transaction['bondStatus'] ?>">
                            <!-- Add edit button - only for SUPERADMIN -->
                            <?php if ($userPosition === 'SUPERADMIN'): ?>
                                <button class="btn btn-sm btn-primary btn-icon" title="Edit Transaction"
                                    onclick="showEditModal(<?= $transaction['transactionID'] ?>)"
                                    <?= ($transaction['bondStatus'] == 3) ? 'disabled' : '' ?>>
                                    <i class="fas fa-edit"></i>
                                </button>
                            <?php endif; ?>

                            <!-- Add payment button -->
                            <button class="btn btn-sm btn-success btn-icon" title="Add Payment"
                                onclick="showPaymentModal(<?= $transaction['transactionID'] ?>, <?= $transaction['balanceTransaction'] ?>)"
                                <?= ($transaction['bondStatus'] == 1 || $transaction['bondStatus'] == 2 || $transaction['bondStatus'] == 3) ? 'disabled' : '' ?>>
                                <i class="fas fa-money-bill"></i>
                            </button>

                            <!-- Add bond release button -->
                            <?php if ($transaction['bondStatus'] == 1 && $transaction['bondBalance'] > 0): ?>
                                <button class="btn btn-sm btn-secondary btn-icon" title="Release Bond"
                                    onclick="showBondReleaseModal(<?= $transaction['transactionID'] ?>, <?= $transaction['bondBalance'] ?>)">
                                    <i class="fas fa-hand-holding-usd"></i>
                                </button>
                            <?php endif; ?>

                            <!-- Add history button -->
                            <button class="btn btn-sm btn-history btn-icon" title="Payment & Bond History"
                                onclick="showHistory(<?= $transaction['transactionID'] ?>)"
                                <?= ($transaction['bondStatus'] == 3) ? 'disabled' : '' ?>>
                                <i class="fas fa-history"></i>
                            </button>

                            <!-- Transaction Details -->
                            <button class="btn btn-sm btn-transaction btn-icon" title="Quick View"
                                onclick='showTransactionDetails(<?= json_encode([
                                                                    'id' => $transaction['transactionID'],
                                                                    'client' => $transaction['clientName'],
                                                                    'discount' => $transaction['discountTransaction'],
                                                                    'bond' => $transaction['bondTransaction'],
                                                                    'charge' => $transaction['chargeTransaction'],
                                                                    'products' => $transaction['products'] ?: '[]'
                                                                ]) ?>)'>
                                <i class="fas fa-box"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Include all modal views -->
    <?php
    include 'assets/views/transactions2/modals/transaction_details_modal.php';
    include 'assets/views/transactions2/modals/history_modal.php';
    include 'assets/views/transactions2/modals/payment_modal.php';
    include 'assets/views/transactions2/modals/bond_modal.php';
    include 'assets/views/transactions2/modals/bond_release_modal.php';
    include 'assets/views/transactions2/modals/product_view_modal.php';

    // Only include the edit modal for SUPERADMIN users
    if ($userPosition === 'SUPERADMIN') {
        include 'assets/views/transactions2/modals/edit_transaction_modal.php';
    }
    ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <!-- Transaction modules -->
    <script src="./assets/scripts/transactions/utils.js"></script>
    <script src="./assets/scripts/transactions/table.js"></script>
    <script src="./assets/scripts/transactions/filters.js"></script>
    <script src="./assets/scripts/transactions/details.js"></script>
    <script src="./assets/scripts/transactions/payment-handler.js"></script>
    <script src="./assets/scripts/transactions/bond-handler.js"></script>
    <script src="./assets/scripts/transactions/editor.js"></script>
    <script src="./assets/scripts/transactions/special-actions.js"></script>
    <script src="./assets/scripts/transactions/global-handlers.js"></script>
    <script src="./assets/scripts/transactions/index.js"></script>
</body>

</html>
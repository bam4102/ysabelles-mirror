<?php
$employeeStats = $reports['employees'];

// Define badge colors for different positions
function getPositionBadgeClass($position) {
    switch (strtoupper($position)) {
        case 'SUPERADMIN':
            return 'bg-dark';
        case 'ADMIN':
            return 'bg-danger';
        case 'INVENTORY':
            return 'bg-warning text-dark';
        case 'SALES':
            return 'bg-primary';
        case 'CASHIER':
            return 'bg-success';
        case 'COMPUTER':
            return 'bg-info';
        default:
            return 'bg-dark';
    }
}
?>
<div class="tab-pane fade" id="employees" role="tabpanel" aria-labelledby="employees-tab">
    <div class="report-section mt-4">
        <h4>Employee Statistics <?= $selectedLocation ? "- $selectedLocation" : '' ?></h4>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Employee Name</th>
                        <th>Position</th>
                        <th>Transactions</th>
                        <th>Total Sales</th>
                        <th>Payments Processed</th>
                        <th>Bonds Processed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employeeStats as $stat): ?>
                        <tr>
                            <td><?= $stat['nameEmployee'] ?></td>
                            <td>
                                <span class="badge <?= getPositionBadgeClass($stat['positionEmployee']) ?>">
                                    <?= $stat['positionEmployee'] ?>
                                </span>
                            </td>
                            <td><?= number_format($stat['transactionCount']) ?></td>
                            <td>₱<?= number_format($stat['totalSales'], 2) ?></td>
                            <td><?= number_format($stat['paymentCount']) ?></td>
                            <td><?= number_format($stat['bondCount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div> 
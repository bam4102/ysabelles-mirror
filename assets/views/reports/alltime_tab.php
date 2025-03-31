<?php
$alltimeReport = $reports['alltime']['report'];
$alltimeSales = $reports['alltime']['sales'];
$alltimeBonds = $reports['alltime']['bonds'];
?>
<div class="tab-pane fade" id="alltime" role="tabpanel" aria-labelledby="alltime-tab">
    <div class="report-section mt-4">
        <h4>All-Time Business Summary <?= $selectedLocation ? "- $selectedLocation" : '' ?></h4>

        <div class="row">
            <!-- Summary Cards -->
            <div class="col-md-3 mb-3">
                <div class="card alltime-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Sales</h5>
                        <h3 class="card-text">₱<?= number_format($alltimeReport['totalSales'], 2) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card alltime-card">
                    <div class="card-body">
                        <h5 class="card-title">Cash on Hand</h5>
                        <h3 class="card-text">₱<?= number_format($alltimeSales['cashOnHand'], 2) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card alltime-card">
                    <div class="card-body">
                        <h5 class="card-title">Current Bond Balance</h5>
                        <h3 class="card-text">₱<?= number_format($alltimeBonds['currentBalance'], 2) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card alltime-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Transactions</h5>
                        <h3 class="card-text"><?= number_format($alltimeReport['totalTransactions']) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Sales Summary -->
            <div class="col-md-6">
                <div class="card alltime-card-row2">
                    <div class="card-body">
                        <h5>All-Time Sales Summary</h5>
                        <div class="amount-row">
                            <span>Total Sales</span>
                            <span class="amount-value"><?= number_format($alltimeSales['totalSales'], 2) ?></span>
                        </div>
                        <div class="amount-row">
                            <span>Less: Total Discounts</span>
                            <span class="amount-value"><?= number_format($alltimeReport['totalDiscounts'], 2) ?></span>
                        </div>
                        <?php if (!empty($alltimeSales['payments'])): ?>
                            <div class="indent-1">Payment Methods:</div>
                            <?php
                            $paymentMethods = [];
                            foreach ($alltimeSales['payments'] as $payment) {
                                if (!isset($paymentMethods[$payment['kindPayment']])) {
                                    $paymentMethods[$payment['kindPayment']] = 0;
                                }
                                $paymentMethods[$payment['kindPayment']] += $payment['amountPayment'];
                            }
                            foreach ($paymentMethods as $method => $amount): ?>
                                <div class="indent-2 amount-row">
                                    <span><?= $method ?></span>
                                    <span class="amount-value"><?= number_format($amount, 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div class="total-line amount-row">
                            <span>Total Cash on Hand</span>
                            <span class="amount-value"><?= number_format($alltimeSales['cashOnHand'], 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bond Summary -->
            <div class="col-md-6">
                <div class="card alltime-card-row2">
                    <div class="card-body">
                        <h5>All-Time Bond Summary</h5>
                        <div class="amount-row">
                            <span>Total Bond Deposits</span>
                            <span class="amount-value"><?= number_format($alltimeBonds['totalDeposits'], 2) ?></span>
                        </div>
                        <div class="amount-row">
                            <span>Total Bond Refunds</span>
                            <span class="amount-value">(<?= number_format($alltimeBonds['totalRefunds'], 2) ?>)</span>
                        </div>
                        <div class="total-line amount-row">
                            <span>Current Bond Balance</span>
                            <span class="amount-value"><?= number_format($alltimeBonds['currentBalance'], 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Payment Methods -->
            <div class="col-md-6">
                <div class="card alltime-card-row3">
                    <div class="card-body">
                        <h5>Payment Methods</h5>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Method</th>
                                    <th>Count</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($alltimeReport['paymentsByType'] as $payment): ?>
                                    <tr>
                                        <td><?= $payment['kindPayment'] ?></td>
                                        <td><?= $payment['count'] ?></td>
                                        <td>₱<?= number_format($payment['total'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Top Products -->
            <div class="col-md-6">
                <div class="card alltime-card-row3">
                    <div class="card-body">
                        <h5>Most Rented Products</h5>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Product</th>
                                    <th>Type</th>
                                    <th>Rentals</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($alltimeReport['topProducts'] as $product): ?>
                                    <tr>
                                        <td><?= $product['productID'] ?></td>
                                        <td><?= $product['nameProduct'] ?></td>
                                        <td><?= $product['typeProduct'] ?></td>
                                        <td><?= $product['rentCount'] ?></td>
                                        <td>₱<?= number_format($product['totalRevenue'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 
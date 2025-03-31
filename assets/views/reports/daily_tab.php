<?php
$salesReport = $reports['daily']['sales'];
$bondReport = $reports['daily']['bonds'];
?>
<div class="tab-pane fade show active" id="daily" role="tabpanel" aria-labelledby="daily-tab">
    <div class="report-section mt-4">
        <h4><?= date('F j, Y', strtotime($selectedDate)) ?></h4>

        <!-- Sales Summary -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5>Sales Summary</h5>
                        <div class="amount-row">
                            <span>Total Sales</span>
                            <span class="amount-value daily-total-sales"><?= number_format($salesReport['totalSales'], 2) ?></span>
                        </div>
                        <div class="amount-row">
                            <span>Less: Discounts</span>
                            <span class="amount-value daily-total-discounts"><?= number_format($salesReport['totalDiscounts'], 2) ?></span>
                        </div>
                        <div class="amount-row total-line">
                            <span>Total Income</span>
                            <span class="amount-value daily-total-income"><?= number_format($salesReport['totalIncome'], 2) ?></span>
                        </div>

                        <div class="daily-payment-methods">
                        <?php if (!empty($salesReport['payments'])): ?>
                            <div class="indent-1">Payment Methods:</div>
                            <?php foreach ($salesReport['payments'] as $payment): ?>
                                <div class="indent-2 amount-row">
                                    <span><?= $payment['kindPayment'] ?> - <?= $payment['clientName'] ?></span>
                                    <span class="amount-value"><?= number_format($payment['amountPayment'], 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </div>

                        <div class="total-line amount-row">
                            <span>Income</span>
                            <span class="amount-value daily-cash-on-hand"><?= number_format($salesReport['cashOnHand'], 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Bond Summary -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5>Bond Summary</h5>
                        <div class="amount-row">
                            <span>Bond Beginning</span>
                            <span class="amount-value daily-bond-beginning"><?= number_format($bondReport['beginningBalance'], 2) ?></span>
                        </div>
                        <div class="amount-row">
                            <span>Add: Bond Income</span>
                            <span class="amount-value daily-bond-income"><?= number_format($bondReport['bondIncome'], 2) ?></span>
                        </div>

                        <div class="daily-bond-deposits">
                        <?php if (!empty($bondReport['deposits'])): ?>
                            <div class="indent-1">Bond Deposits:</div>
                            <?php foreach ($bondReport['deposits'] as $deposit): ?>
                                <div class="indent-2 amount-row">
                                    <span><?= $deposit['clientName'] ?></span>
                                    <span class="amount-value"><?= number_format($deposit['amount'], 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </div>

                        <div class="amount-row">
                            <span>Less: Bond Refund</span>
                            <span class="amount-value daily-bond-refund">(<?= number_format($bondReport['bondRefund'], 2) ?>)</span>
                        </div>
                        
                        <div class="daily-bond-refunds">
                        <?php if (!empty($bondReport['refunds'])): ?>
                            <?php foreach ($bondReport['refunds'] as $refund): ?>
                                <div class="indent-2 amount-row">
                                    <span><?= $refund['clientName'] ?></span>
                                    <span class="amount-value">(<?= number_format($refund['amount'], 2) ?>)</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </div>

                        <div class="total-line amount-row">
                            <span>Bond Ending</span>
                            <span class="amount-value daily-bond-ending"><?= number_format($bondReport['endingBalance'], 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 
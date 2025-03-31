<?php
$newProducts = $reports['new_products'];
?>
<div class="tab-pane fade" id="new-products" role="tabpanel" aria-labelledby="new-products-tab">
    <div class="report-section mt-4">
        <h4>New Products Report <?= $selectedLocation ? "- $selectedLocation" : '' ?></h4>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Transaction</th>
                        <th>Client</th>
                        <th>Pick Up</th>
                        <th>Return</th>
                        <th>Bond Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($newProducts as $product): ?>
                        <tr>
                            <td><?= $product['productID'] ?></td>
                            <td><?= $product['nameProduct'] ?></td>
                            <td><?= $product['typeProduct'] ?></td>
                            <td><?= $product['locationProduct'] ?></td>
                            <td><?= $product['transactionID'] ?: 'Not Rented' ?></td>
                            <td><?= $product['clientName'] ?: '-' ?></td>
                            <td><?= $product['datePickUp'] ?: '-' ?></td>
                            <td><?= $product['dateReturn'] ?: '-' ?></td>
                            <td>
                                <span class="badge <?= $product['bondStatus'] == 1 ? 'bg-success' : 'bg-warning text-dark' ?>">
                                    <?= $product['bondStatusText'] ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div> 
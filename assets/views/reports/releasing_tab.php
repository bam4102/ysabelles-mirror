<?php
$dueForRelease = $reports['releasing'];
?>
<div class="tab-pane fade" id="releasing" role="tabpanel" aria-labelledby="releasing-tab">
    <div class="report-section mt-4">
        <h4>Due for Releasing <?= $selectedLocation ? "- $selectedLocation" : '' ?></h4>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Client Name</th>
                        <th>Contact</th>
                        <th>Items</th>
                        <th>Pick Up Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dueForRelease as $item): ?>
                        <tr>
                            <td><?= $item['transactionID'] ?></td>
                            <td><?= $item['clientName'] ?></td>
                            <td><?= $item['clientContact'] ?></td>
                            <td><?= $item['items'] ?></td>
                            <td><?= $item['datePickUp'] ?></td>
                            <td>
                                <span class="badge <?= $item['status'] === 'Ready for Release' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                    <?= $item['status'] ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
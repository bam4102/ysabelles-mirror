<?php
$unreturnedItems = $reports['unreturned'];
?>
<div class="tab-pane fade" id="unreturned" role="tabpanel" aria-labelledby="unreturned-tab">
    <div class="report-section mt-4">
        <h4>Unreturned Products <?= $selectedLocation ? "- $selectedLocation" : '' ?></h4>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Client Name</th>
                        <th>Contact</th>
                        <th>Items</th>
                        <th>Due Date</th>
                        <th>Bond Paid</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unreturnedItems as $item): ?>
                        <tr>
                            <td><?= $item['clientName'] ?></td>
                            <td><?= $item['clientContact'] ?></td>
                            <td><?= $item['items'] ?></td>
                            <td><?= $item['dateReturn'] ?></td>
                            <td>₱<?= number_format($item['bondPaid'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
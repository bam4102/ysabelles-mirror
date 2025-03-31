<?php
/**
 * Transaction Details Modal
 * Displays detailed information about a selected transaction including products
 */
?>
<!-- Transaction Details Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transaction Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Transaction ID:</strong> <span id="modalTransactionId"></span></p>
                        <p><strong>Client:</strong> <span id="modalClient"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Total Charge:</strong> <span id="modalCharge"></span></p>
                        <p><strong>Bond:</strong> <span id="modalBond"></span></p>
                        <p><strong>Discount:</strong> <span id="modalDiscount"></span></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <h6>Products:</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Product ID</th>
                                        <th>Name</th>
                                        <th>Price</th>
                                        <th>Released</th>
                                        <th>Returned</th>
                                        <th>Package</th>
                                    </tr>
                                </thead>
                                <tbody id="modalProductsTable">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
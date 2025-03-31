<?php
/**
 * Bond Deposit Modal
 * Form for processing new bond deposits
 */
?>
<!-- Add Bond Modal -->
<div class="modal fade" id="bondModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Bond Deposit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="bondForm" onsubmit="event.preventDefault();">
                    <input type="hidden" id="bondTransactionId" name="transactionId">
                    <input type="hidden" id="bondPaymentId" name="paymentId">
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" id="bondDate" name="date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bond Amount</label>
                        <input type="number" step="0.01" class="form-control" id="bondAmount" name="amount" required>
                        <small class="text-muted">Required Bond: <span id="requiredBond"></span></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea class="form-control" id="bondNote" name="note" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="submitBond()">Deposit Bond</button>
            </div>
        </div>
    </div>
</div>
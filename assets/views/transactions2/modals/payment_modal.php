<?php

/**
 * Add Payment Modal
 * Form for processing new payments for transactions
 */
?>
<!-- Add Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="paymentForm" onsubmit="event.preventDefault();">
                    <input type="hidden" id="paymentTransactionId" name="transactionId">
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" id="paymentDate" name="date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mode of Transaction</label>
                        <select class="form-control" id="paymentMode" name="mode" required>
                            <option value="CASH">CASH</option>
                            <option value="BANK TRANSFER">BANK TRANSFER</option>
                            <option value="DEBIT">DEBIT</option>
                            <option value="GCASH/E-WALLETS">GCASH/E-WALLETS</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" step="0.01" min="0" class="form-control" id="paymentAmount" name="amount" required>
                        </div>
                        <small class="text-muted">Remaining Balance: <span id="remainingBalance"></span></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea class="form-control" id="paymentNote" name="note" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="submitPayment()">Pay</button>
            </div>
        </div>
    </div>
</div>
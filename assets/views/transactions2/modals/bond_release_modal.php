<?php
/**
 * Bond Release Modal
 * Form for processing bond release/returns
 */
?>
<!-- Bond Release Modal -->
<div class="modal fade" id="bondReleaseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Release Bond</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="bondReleaseForm" onsubmit="event.preventDefault();">
                    <input type="hidden" id="bondReleaseTransactionId" name="transactionId">
                    <input type="hidden" id="bondReleaseBalance" name="bondBalance">

                    <div class="mb-3">
                        <label class="form-label">Current Bond Balance</label>
                        <div class="form-control-plaintext" id="bondReleaseBalanceDisplay"></div>
                    </div>

                    <div class="mb-3">
                        <label for="bondReleaseDate" class="form-label">Date</label>
                        <input type="date" class="form-control" id="bondReleaseDate" name="date" required>
                    </div>

                    <div class="mb-3">
                        <label for="bondReleaseAmount" class="form-label">Amount to Release</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="bondReleaseAmount" name="amount" min="0" step="0.01" required>
                        </div>
                        <div class="invalid-feedback" id="bondReleaseAmountError"></div>
                    </div>

                    <div class="mb-3">
                        <label for="bondReleaseNote" class="form-label">Note</label>
                        <textarea class="form-control" id="bondReleaseNote" name="note" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="submitBondRelease">Release Bond</button>
            </div>
        </div>
    </div>
</div>
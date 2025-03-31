<?php
/**
 * Edit Transaction Modal
 * Form for editing transaction details (SuperAdmin only)
 */
?>
<!-- Edit Transaction Modal -->
<div class="modal fade" id="editTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editTransactionForm">
                    <input type="hidden" id="editTransactionId" name="transactionId">
                    <!-- Client Information -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Client Name</label>
                                <input type="text" class="form-control" id="editClientName" name="clientName" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Client Address</label>
                                <input type="text" class="form-control" id="editClientAddress" name="clientAddress" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Client Contact</label>
                                <input type="text" class="form-control" id="editClientContact" name="clientContact" required>
                            </div>
                        </div>
                    </div>
                    <!-- Date and Location -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Transaction Date</label>
                                <input type="date" class="form-control" id="editTransactionDate" name="transactionDate" disabled>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <select class="form-select" id="editLocation" name="location" required>
                                    <option value="BACOLOD CITY">Bacolod City</option>
                                    <option value="DUMAGUETE CITY">Dumaguete City</option>
                                    <option value="ILOILO CITY">Iloilo City</option>
                                    <option value="SAN CARLOS CITY">San Carlos City</option>
                                    <option value="CEBU CITY">Cebu City</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Pick-up Date</label>
                                <input type="date" class="form-control" id="editPickupDate" name="pickupDate" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Return Date</label>
                                <input type="date" class="form-control" id="editReturnDate" name="returnDate" required>
                            </div>
                        </div>
                    </div>
                    <!-- Charge Details -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Total Charge</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="editCharge" name="charge" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Discount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="editDiscount" name="discount" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Remaining Balance</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="editBalance" name="balance" step="0.01" min="0" disabled>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Bond Details -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Bond Required</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="editBondRequired" name="bondRequired" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Bond Balance</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="editBondBalance" name="bondBalance" step="0.01" min="0" disabled>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Transaction Status</label>
                                <select class="form-select" id="editBondStatus" name="bondStatus" required>
                                    <option value="0">Unpaid</option>
                                    <option value="1">Active</option>
                                    <option value="2">Completed/Returned</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <h6>Products</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="editProductsTable">
                                        <!-- Products will be populated here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateTransaction()">Save Changes</button>
            </div>
        </div>
    </div>
</div>
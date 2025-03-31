<!-- Product Request Tab -->
<div class="tab-pane fade show active" id="request-pane" role="tabpanel" aria-labelledby="request-tab">
    <div class="row">
        <!-- Create New Request Card -->
        <div class="col d-flex">
            <div class="card shadow flex-fill">
                <div class="card-header text-white">
                    <h5 class="mb-0">Create New Request</h5>
                </div>
                <div class="card-body d-flex flex-column">
                    <form id="requestForm" class="flex-grow-1">
                        <div class="mb-3">
                            <label class="form-label">From Branch (Your Location)</label>
                            <input type="text" class="form-control" id="sourceBranch" name="sourceBranch" value="<?php echo htmlspecialchars($userBranch); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Required By Date</label>
                            <input type="date" class="form-control" id="requiredDate" name="requiredDate" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Request From Branch</label>
                            <select class="form-select" id="destinationBranch" name="destinationBranch" required>
                                <option value="">Select Branch</option>
                                <?php foreach ($branches as $branch): ?>
                                    <?php if ($branch['locationProduct'] != $userBranch): ?>
                                        <option value="<?php echo htmlspecialchars($branch['locationProduct']); ?>">
                                            <?php echo htmlspecialchars($branch['locationProduct']); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Selected Products</label>
                            <div id="selectedProductsList" class="border p-2 rounded mb-2" style="min-height: 50px;">
                                <!-- Selected products will be displayed here -->
                            </div>
                            <small class="text-muted" id="selectedProductsCount">0 products selected</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" id="requestNotes" name="requestNotes" rows="3"></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger">Submit Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Products Table Card -->
        <div class="col d-flex">
            <div class="card shadow flex-fill">
                <div class="card-header text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Available Products</h5>
                    <div>
                        <button type="button" class="btn btn-light btn-sm me-2" id="clearChoices">
                            <i class="fas fa-times"></i> Clear Choices
                        </button>
                        <button type="button" class="btn btn-light btn-sm" id="refreshProducts">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle"></i> Products with pending requests are not shown in this list.
                    </div>
                    <div class="table-responsive">
                        <div class="d-flex justify-content-between align-items-center mb-3" style="margin-bottom: 0px !important;">
                            <div class="d-flex align-items-center">
                                <label class="me-2">Show:</label>
                                <select class="form-select form-select-sm" id="showLimit" style="width: auto;">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <span class="ms-2">entries</span>
                            </div>
                            <div>
                                <input type="text" id="productSearchInput" class="form-control form-control-sm" placeholder="Search by ID or Name" style="width: 200px; padding-bottom: 5px;padding-top: 5px;margin-bottom: 5px">
                            </div>
                        </div>
                        <table id="productsTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th width="50px"><input type="checkbox" id="selectAllProducts"></th>
                                    <th class="sortable" data-sort="productID">
                                        Product ID <i class="fas fa-sort"></i>
                                    </th>
                                    <th class="sortable" data-sort="nameProduct">
                                        Product Name <i class="fas fa-sort"></i>
                                    </th>
                                    <th class="sortable" data-sort="typeProduct">
                                        Type <i class="fas fa-sort"></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Products will be loaded here -->
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="showing-entries">
                                Showing <span id="startEntry">0</span> to <span id="endEntry">0</span> of <span id="totalEntries">0</span> entries
                            </div>
                            <div class="pagination-controls">
                                <button class="btn btn-sm btn-outline-secondary" id="prevPage" disabled>
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <span class="mx-2">Page <span id="currentPage">1</span> of <span id="totalPages">1</span></span>
                                <button class="btn btn-sm btn-outline-secondary" id="nextPage" disabled>
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
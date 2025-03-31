<!-- My Requests Tab -->
<div class="tab-pane fade" id="myRequest-pane" role="tabpanel" aria-labelledby="myRequest-tab">
    <div class="card requests-list-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>My Requests</h5>
            <button type="button" class="btn btn-light btn-sm" id="refreshMyRequests">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        <div class="card-body">
            <div class="filter-container">
                <div class="filter-group">
                    <label for="statusFilter">Status:</label>
                    <select id="statusFilter">
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="declined">Declined</option>
                        <option value="canceled">Canceled</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="branchFilter">Branch:</label>
                    <select id="branchFilter">
                        <option value="">All</option>
                        <?php foreach ($branches as $branch): ?>
                            <?php if ($branch['locationProduct'] != $userBranch): ?>
                                <option value="<?php echo htmlspecialchars($branch['locationProduct']); ?>">
                                    <?php echo htmlspecialchars($branch['locationProduct']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="dateRangeFilter">Date Range:</label>
                    <input type="date" id="startDateFilter"> to
                    <input type="date" id="endDateFilter">
                </div>
            </div>

            <div class="table-responsive">
                <table id="requestsTable" class="display responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Date Requested</th>
                            <th>From Branch</th>
                            <th>To Branch</th>
                            <th>Products</th>
                            <th>Required By</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div> 
<!-- Incoming Requests Tab -->
<div class="tab-pane fade" id="incoming-pane" role="tabpanel" aria-labelledby="incoming-tab">
    <div class="card incoming-requests-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Incoming Requests</h5>
            <button type="button" class="btn btn-light btn-sm" id="refreshIncomingRequests">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="incomingRequestsTable" class="display responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Date Requested</th>
                            <th>From Branch</th>
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
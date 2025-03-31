<!-- Pending Transmittals Tab -->
<div class="tab-pane fade" id="pending-pane" role="tabpanel" aria-labelledby="pending-tab">
    <div class="card shadow">
        <div class="card-header text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Pending Transmittals</h5>
            <button type="button" class="btn btn-light btn-sm refresh-table" data-table="pending">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        <div class="card-body" id="pendingTransmittalsContent">
            <!-- Content will be loaded dynamically -->
            <div class="text-center my-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script id="requestGroupTemplate" type="text/template">
    <div class="card mb-3 request-group">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Request #{{requestId}}</h6>
            <div class="btn-group">
                <button class="btn btn-danger btn-sm group-action-btn" data-request-id="{{requestId}}" data-action="IN_TRANSIT">Set All In Transit</button>
                <button class="btn btn-danger btn-sm group-action-btn" data-request-id="{{requestId}}" data-action="CANCELLED">Cancel All</button>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Product</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    {{rows}}
                </tbody>
            </table>
        </div>
    </div>
</script>

<script id="individualGroupTemplate" type="text/template">
    <div class="card mb-3">
        <div class="card-header bg-light">
            <h6 class="mb-0">Individual Transmittals</h6>
        </div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Product</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    {{rows}}
                </tbody>
            </table>
        </div>
    </div>
</script>

<!-- Row template for individual transmittals -->
<script id="rowTemplate" type="text/template">
    <tr data-id="{{transmittalId}}">
        <td>{{productId}}</td>
        <td>{{productName}}</td>
        <td>{{fromLocation}}</td>
        <td>{{toLocation}}</td>
        <td>{{date}}</td>
        <td>{{{statusBadge}}}</td>
        <td>{{notes}}</td>
        <td>{{{actionButton}}}</td>
    </tr>
</script>

<!-- Row template for grouped transmittals (without action column) -->
<script id="groupRowTemplate" type="text/template">
    <tr data-id="{{transmittalId}}">
        <td>{{productId}}</td>
        <td>{{productName}}</td>
        <td>{{fromLocation}}</td>
        <td>{{toLocation}}</td>
        <td>{{date}}</td>
        <td>{{{statusBadge}}}</td>
        <td>{{notes}}</td>
    </tr>
</script>

<!-- Modal for updating multiple transmittals -->
<div class="modal fade" id="batchUpdateModal" tabindex="-1" aria-labelledby="batchUpdateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="batchUpdateModalLabel">Update Multiple Transmittals</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Would you like to update all related transmittals from the same request?</p>
                <input type="hidden" id="transmittalId" value="">
                <input type="hidden" id="requestId" value="">
                <input type="hidden" id="newStatus" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Just This One</button>
                <button type="button" class="btn btn-primary" id="updateAllBtn">Yes, Update All</button>
            </div>
        </div>
    </div>
</div>

<script>
// Add event listener for group action buttons
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.group-status-btn').forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.dataset.requestId;
            const action = this.dataset.action;
            const cardElement = this.closest('.card');
            
            if (confirm(`Are you sure you want to set all transmittals in this request to ${action.replace('_', ' ')}?`)) {
                // Send AJAX request to update all transmittals in the group
                fetch('assets/controllers/update_group_transmittal_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        requestId: requestId,
                        action: action
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Error parsing JSON:', text);
                            throw new Error('Invalid JSON response from server');
                        }
                    });
                })
                .then(data => {
                    if (data.success) {
                        // Update UI for all affected transmittals in this group
                        const rows = cardElement.querySelectorAll('tbody tr');
                        rows.forEach(row => {
                            const statusCell = row.querySelector('td:nth-child(6)');
                            const actionCell = row.querySelector('td:last-child');
                            
                            // Only update rows that were in PENDING status
                            const currentStatus = statusCell.querySelector('.badge').textContent;
                            if (currentStatus === 'PENDING') {
                                // Update status badge
                                const newBadgeClass = action === 'IN_TRANSIT' ? 'bg-info' : 'bg-danger';
                                statusCell.innerHTML = `<span class="badge ${newBadgeClass}">${action}</span>`;
                                
                                // Update action cell
                                actionCell.innerHTML = '<span class="text-muted">No actions available</span>';
                            }
                        });

                        // Remove the group action buttons
                        const btnGroup = cardElement.querySelector('.btn-group');
                        if (btnGroup) {
                            btnGroup.remove();
                        }

                        // Show success message
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
                        alertDiv.innerHTML = `
                            Successfully updated all transmittals to ${action.replace('_', ' ')}.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        cardElement.querySelector('.card-body').insertBefore(alertDiv, cardElement.querySelector('.table-responsive'));

                        // Auto-dismiss the alert after 3 seconds
                        setTimeout(() => {
                            alertDiv.remove();
                        }, 3000);
                    } else {
                        throw new Error(data.message || 'Unknown error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Show error message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
                    alertDiv.innerHTML = `
                        Error updating transmittals: ${error.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    cardElement.querySelector('.card-body').insertBefore(alertDiv, cardElement.querySelector('.table-responsive'));

                    // Auto-dismiss the error after 5 seconds
                    setTimeout(() => {
                        alertDiv.remove();
                    }, 5000);
                });
            }
        });
    });
});
</script> 
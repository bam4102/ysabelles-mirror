<!-- Receive Products Tab -->
<div class="tab-pane fade" id="receive-pane" role="tabpanel" aria-labelledby="receive-tab">
    <div class="card shadow mb-3">
        <div class="card-header text-white">
            <h5 class="mb-0">Scan Product</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Scan or enter product code to receive a product that is in transit.
            </div>
            <div class="input-group mb-3">
                <input type="text" id="productScanInput" class="form-control" placeholder="Scan product code here..." autofocus>
                <button class="btn btn-primary" type="button" id="productScanButton">
                    <i class="fas fa-barcode me-1"></i> Receive
                </button>
            </div>
            <div id="scanResultMessage" class="mt-2 d-none"></div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Receive Products</h5>
            <button type="button" class="btn btn-light btn-sm refresh-table" data-table="receive">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        <div class="card-body">
            <div id="receiveTableContainer">
                <!-- Content will be loaded dynamically -->
                <div class="text-center my-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Templates for Receive Transmittals - these are separate from Pending Transmittals templates -->
<script type="text/template" id="receiveRequestGroupTemplate">
    <div class="card mb-3 request-group">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Request #{{requestId}}</h6>
            <div>
                <button class="btn btn-success btn-sm receive-all" data-request-id="{{requestId}}">
                    <i class="fas fa-check-circle"></i> Receive All
                </button>
            </div>
        </div>
        <div class="table-responsive">
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

<script type="text/template" id="receiveIndividualGroupTemplate">
    <div class="card mb-3">
        <div class="card-header bg-light">
            <h6 class="mb-0">Individual Transmittals</h6>
        </div>
        <div class="table-responsive">
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

<script type="text/template" id="receiveRowTemplate">
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
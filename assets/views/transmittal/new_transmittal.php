<!-- New Transmittal Tab -->
<div class="tab-pane fade show active" id="new-pane" role="tabpanel" aria-labelledby="new-tab">
    <div class="row">
        <div class="col d-flex">
            <div class="card shadow flex-fill">
                <div class="card-header text-white">
                    <h5 class="mb-0">Product Transmittal</h5>
                </div>
                <div class="card-body d-flex flex-column">
                    <form id="transmittalForm" class="flex-grow-1">
                        <div class="mb-3">
                            <label class="form-label">From</label>
                            <input type="text" class="form-control" id="fromLocation" name="fromLocation" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To</label>
                            <select class="form-select" id="toLocation" name="toLocation" required>
                                <option value="">Select Destination</option>
                                <?php
                                $locations = [
                                    'BACOLOD CITY',
                                    'DUMAGUETE CITY',
                                    'ILOILO CITY',
                                    'SAN CARLOS CITY',
                                    'CEBU CITY'
                                ];
                                foreach ($locations as $location) {
                                    if ($location !== $userLocation) {
                                        echo "<option value=\"$location\">$location</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Selected Products</label>
                            <div id="selectedProducts" class="border p-2 rounded mb-2" style="min-height: 50px;">
                                <!-- Selected products will be displayed here -->
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" id="noteTransmittal" name="noteTransmittal" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Transmittal</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col d-flex">
            <div class="card shadow flex-fill">
                <div class="card-header text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Product List</h5>
                    <div>
                        <button type="button" class="btn btn-light btn-sm me-2 clear-choices">
                            <i class="fas fa-times"></i> Clear Choices
                        </button>
                        <button type="button" class="btn btn-light btn-sm refresh-products">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="productTable">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $userLocation = $_SESSION['user']['locationEmployee'];
                                $sql = "SELECT p.*, pc.productCategory 
                                FROM product p 
                                LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID 
                                LEFT JOIN transmittal t ON p.productID = t.productID AND t.statusTransmittal IN ('PENDING', 'IN_TRANSIT')
                                WHERE p.soldProduct = 0 
                                AND p.locationProduct = ?
                                AND t.transmittalID IS NULL
                                AND (p.isNew IS NULL OR p.isNew = 0)";
                                $stmt = $pdo->prepare($sql);
                                $stmt->execute([$userLocation]);

                                while ($row = $stmt->fetch()) {
                                    echo "<tr>";
                                    echo "<td><input type='checkbox' class='product-select' data-id='" . $row['productID'] . "' data-location='" . $row['locationProduct'] . "'></td>";
                                    echo "<td>" . $row['productID'] . "</td>";
                                    echo "<td>" . $row['nameProduct'] . "</td>";
                                    echo "<td>" . ($row['productCategory'] ?? $row['typeProduct']) . "</td>";
                                    echo "<td>" . $row['locationProduct'] . "</td>";
                                    echo "<td>" . ($row['useProduct'] ? 'In Use' : 'Available') . "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
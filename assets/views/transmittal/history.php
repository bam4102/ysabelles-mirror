<!-- Transmittal History Tab -->
<div class="tab-pane fade" id="history-pane" role="tabpanel" aria-labelledby="history-tab">
    <div class="row">
        <div class="col-md-9">
            <!-- Transmittal History Card -->
            <div class="card shadow">
                <div class="card-header text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        All Transmittals
                        <?php if ($isSuperAdmin): ?>
                        <span class="badge bg-info ms-2">Showing all locations</span>
                        <?php else: ?>
                        <span class="badge bg-secondary ms-2">Showing <?= $userLocation ?></span>
                        <?php endif; ?>
                    </h5>
                    <div class="d-flex">
                        <div class="me-2">
                            <select id="statusFilter" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                <option value="PENDING">Pending</option>
                                <option value="IN_TRANSIT">In Transit</option>
                                <option value="DELIVERED">Delivered</option>
                                <option value="CANCELLED">Cancelled</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-light btn-sm refresh-table" data-table="history">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="historyTable">
                            <thead>
                                <tr>
                                    <th>Product ID</th>
                                    <th>Product</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Date Created</th>
                                    <th>Date Delivered</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $userLocation = $_SESSION['user']['locationEmployee'];
                                
                                // Base query
                                $sql = "SELECT t.*, p.nameProduct, p.productID,
                                        e1.nameEmployee as createdByName,
                                        e2.nameEmployee as receivedByName
                                        FROM transmittal t 
                                        JOIN product p ON t.productID = p.productID 
                                        JOIN employee e1 ON t.employeeID = e1.employeeID
                                        LEFT JOIN employee e2 ON t.receivedBy = e2.employeeID";
                                
                                // Add location filter if not SUPERADMIN
                                if (!$isSuperAdmin) {
                                    $sql .= " WHERE t.fromLocation = :userLocation OR t.toLocation = :userLocation";
                                }
                                
                                // Add ordering
                                $sql .= " ORDER BY 
                                            CASE 
                                                WHEN t.statusTransmittal = 'PENDING' THEN 1
                                                WHEN t.statusTransmittal = 'IN_TRANSIT' THEN 2
                                                ELSE 3
                                            END,
                                            t.dateTransmittal DESC";
                                
                                $stmt = $pdo->prepare($sql);
                                
                                // Bind parameters if not SUPERADMIN
                                if (!$isSuperAdmin) {
                                    $stmt->bindParam(':userLocation', $userLocation, PDO::PARAM_STR);
                                }
                                
                                $stmt->execute();

                                while ($row = $stmt->fetch()) {
                                    $statusClass = '';
                                    switch ($row['statusTransmittal']) {
                                        case 'PENDING':
                                            $statusClass = 'bg-warning';
                                            break;
                                        case 'IN_TRANSIT':
                                            $statusClass = 'bg-info';
                                            break;
                                        case 'DELIVERED':
                                            $statusClass = 'bg-success';
                                            break;
                                        case 'CANCELLED':
                                            $statusClass = 'bg-danger';
                                            break;
                                        default:
                                            $statusClass = 'bg-secondary';
                                    }

                                    echo "<tr data-status='" . $row['statusTransmittal'] . "'>";
                                    echo "<td>" . $row['productID'] . "</td>";
                                    echo "<td>" . $row['nameProduct'] . "</td>";
                                    echo "<td>" . $row['fromLocation'] . "</td>";
                                    echo "<td>" . $row['toLocation'] . "</td>";
                                    echo "<td>" . date('Y-m-d H:i:s', strtotime($row['dateTransmittal'])) . "</td>";
                                    echo "<td>" . ($row['dateDelivered'] ? date('Y-m-d H:i:s', strtotime($row['dateDelivered'])) : '-') . "</td>";
                                    echo "<td><span class='badge " . $statusClass . "'>" . $row['statusTransmittal'] . "</span></td>";
                                    echo "<td>" . $row['noteTransmittal'] . "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <!-- Transmittal Stats Card -->
            <div class="card shadow mb-4">
                <div class="card-header text-white">
                    <h5 class="mb-0">
                        Transmittal Statistics
                        <?php if ($isSuperAdmin): ?>
                            <span class="badge bg-info ms-2">All locations</span>
                        <?php else: ?>
                            <span class="badge bg-secondary ms-2"><?= $userLocation ?></span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    // Get statistics for transmittals
                    $statsSql = "SELECT 
                                SUM(CASE WHEN statusTransmittal = 'PENDING' THEN 1 ELSE 0 END) as pending,
                                SUM(CASE WHEN statusTransmittal = 'IN_TRANSIT' THEN 1 ELSE 0 END) as in_transit,
                                SUM(CASE WHEN statusTransmittal = 'DELIVERED' THEN 1 ELSE 0 END) as delivered,
                                SUM(CASE WHEN statusTransmittal = 'CANCELLED' THEN 1 ELSE 0 END) as cancelled,
                                COUNT(*) as total
                            FROM transmittal";
                    
                    // Apply location filter for non-SUPERADMIN users
                    if (!$isSuperAdmin) {
                        $statsSql .= " WHERE fromLocation = :userLocation OR toLocation = :userLocation";
                    }
                    
                    $stmt = $pdo->prepare($statsSql);
                    
                    // Bind location parameter for non-SUPERADMIN users
                    if (!$isSuperAdmin) {
                        $stmt->bindParam(':userLocation', $userLocation, PDO::PARAM_STR);
                    }
                    
                    $stmt->execute();
                    $stats = $stmt->fetch();
                    
                    // Set default values if no data is returned
                    $stats['total'] = $stats['total'] ?? 0;
                    $stats['pending'] = $stats['pending'] ?? 0;
                    $stats['in_transit'] = $stats['in_transit'] ?? 0;
                    $stats['delivered'] = $stats['delivered'] ?? 0;
                    $stats['cancelled'] = $stats['cancelled'] ?? 0;
                    ?>

                    <div class="mb-3">
                        <h6>Total Transmittals</h6>
                        <div class="progress">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 100%" aria-valuenow="<?= $stats['total'] ?>" aria-valuemin="0" aria-valuemax="<?= $stats['total'] ?>">
                                <?= $stats['total'] ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <h6>Pending</h6>
                        <div class="progress">
                            <div class="progress-bar bg-warning" role="progressbar" 
                                style="width: <?= ($stats['total'] > 0) ? ($stats['pending'] / $stats['total'] * 100) : 0 ?>%" 
                                aria-valuenow="<?= $stats['pending'] ?>" aria-valuemin="0" aria-valuemax="<?= $stats['total'] ?>">
                                <?= $stats['pending'] ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <h6>In Transit</h6>
                        <div class="progress">
                            <div class="progress-bar bg-info" role="progressbar" 
                                style="width: <?= ($stats['total'] > 0) ? ($stats['in_transit'] / $stats['total'] * 100) : 0 ?>%" 
                                aria-valuenow="<?= $stats['in_transit'] ?>" aria-valuemin="0" aria-valuemax="<?= $stats['total'] ?>">
                                <?= $stats['in_transit'] ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <h6>Delivered</h6>
                        <div class="progress">
                            <div class="progress-bar bg-success" role="progressbar" 
                                style="width: <?= ($stats['total'] > 0) ? ($stats['delivered'] / $stats['total'] * 100) : 0 ?>%" 
                                aria-valuenow="<?= $stats['delivered'] ?>" aria-valuemin="0" aria-valuemax="<?= $stats['total'] ?>">
                                <?= $stats['delivered'] ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <h6>Cancelled</h6>
                        <div class="progress">
                            <div class="progress-bar bg-danger" role="progressbar" 
                                style="width: <?= ($stats['total'] > 0) ? ($stats['cancelled'] / $stats['total'] * 100) : 0 ?>%" 
                                aria-valuenow="<?= $stats['cancelled'] ?>" aria-valuemin="0" aria-valuemax="<?= $stats['total'] ?>">
                                <?= $stats['cancelled'] ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Card -->
            <div class="card shadow">
                <div class="card-header text-white">
                    <h5 class="mb-0">
                        Recent Activity
                        <?php if ($isSuperAdmin): ?>
                            <span class="badge bg-info ms-2">All locations</span>
                        <?php else: ?>
                            <span class="badge bg-secondary ms-2"><?= $userLocation ?></span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php
                        $recentSql = "SELECT t.*, p.nameProduct, e.nameEmployee 
                                    FROM transmittal t 
                                    JOIN product p ON t.productID = p.productID 
                                    JOIN employee e ON t.employeeID = e.employeeID";
                        
                        // Apply location filter for non-SUPERADMIN users
                        if (!$isSuperAdmin) {
                            $recentSql .= " WHERE t.fromLocation = :userLocation OR t.toLocation = :userLocation";
                        }
                        
                        $recentSql .= " ORDER BY 
                                        CASE 
                                            WHEN t.dateDelivered IS NOT NULL THEN t.dateDelivered 
                                            ELSE t.dateTransmittal 
                                        END DESC
                                    LIMIT 5";
                                    
                        $stmt = $pdo->prepare($recentSql);
                        
                        // Bind location parameter for non-SUPERADMIN users
                        if (!$isSuperAdmin) {
                            $stmt->bindParam(':userLocation', $userLocation, PDO::PARAM_STR);
                        }
                        
                        $stmt->execute();
                        
                        if ($stmt->rowCount() === 0) {
                            echo "<li class='list-group-item border-0 py-2 px-0'>No recent activity</li>";
                        } else {
                            while ($row = $stmt->fetch()) {
                                $icon = 'fa-circle-notch';
                                $text = '';
                                
                                switch ($row['statusTransmittal']) {
                                    case 'PENDING':
                                        $icon = 'fa-clock text-warning';
                                        $text = "<strong>{$row['nameEmployee']}</strong> created a transmittal for <strong>{$row['nameProduct']}</strong>";
                                        $time = date('M d, Y H:i', strtotime($row['dateTransmittal']));
                                        break;
                                    case 'IN_TRANSIT':
                                        $icon = 'fa-truck text-info';
                                        $text = "<strong>{$row['nameProduct']}</strong> is in transit to <strong>{$row['toLocation']}</strong>";
                                        $time = date('M d, Y H:i', strtotime($row['dateTransmittal']));
                                        break;
                                    case 'DELIVERED':
                                        $icon = 'fa-check-circle text-success';
                                        $text = "<strong>{$row['nameProduct']}</strong> was delivered to <strong>{$row['toLocation']}</strong>";
                                        $time = date('M d, Y H:i', strtotime($row['dateDelivered']));
                                        break;
                                    case 'CANCELLED':
                                        $icon = 'fa-times-circle text-danger';
                                        $text = "Transmittal for <strong>{$row['nameProduct']}</strong> was cancelled";
                                        $time = date('M d, Y H:i', strtotime($row['dateTransmittal']));
                                        break;
                                }
                                
                                echo "<li class='list-group-item border-0 py-2 px-0'>";
                                echo "<div class='d-flex'>";
                                echo "<div class='me-3'><i class='fas {$icon} fa-lg'></i></div>";
                                echo "<div class='flex-grow-1'>";
                                echo "<div class='mb-1'>{$text}</div>";
                                echo "<small class='text-muted'>{$time}</small>";
                                echo "</div>";
                                echo "</div>";
                                echo "</li>";
                            }
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div> 
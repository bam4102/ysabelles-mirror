<?php
require_once '../db.php';

header('Content-Type: application/json');

try {
    date_default_timezone_set('Asia/Manila');
    $userLocation = $_SESSION['user']['locationEmployee'];

    // Get all pending/in-transit transmittals related to user's location
    $sql = "SELECT t.*, p.nameProduct, p.productID, 
                  b.requestID as branchRequestID,
                  CASE 
                    WHEN t.requestID IS NOT NULL THEN CONCAT('Request #', t.requestID)
                    ELSE NULL
                  END as requestLabel
            FROM transmittal t 
            JOIN product p ON t.productID = p.productID 
            LEFT JOIN branch_requests b ON t.requestID = b.requestID
            WHERE (t.fromLocation = ? OR (t.toLocation = ? AND t.statusTransmittal = 'PENDING'))
            AND t.statusTransmittal IN ('PENDING', 'IN_TRANSIT')
            ORDER BY t.requestID DESC, t.dateTransmittal DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userLocation, $userLocation]);
    $transmittals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group transmittals by request
    $grouped = [];
    $individual = [];

    foreach ($transmittals as $row) {
        $statusBadgeClass = $row['statusTransmittal'] === 'PENDING' ? 'warning' : 'info';
        $statusBadge = "<span class='badge bg-{$statusBadgeClass}'>" . $row['statusTransmittal'] . "</span>";
        
        $actionButton = '';
        if ($row['fromLocation'] === $userLocation && $row['statusTransmittal'] === 'PENDING') {
            $actionButton = '<div class="dropdown">
                <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    Change Status
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item status-btn" href="#" data-id="' . $row['transmittalID'] . '" data-action="IN_TRANSIT">In Transit</a></li>
                    <li><a class="dropdown-item status-btn" href="#" data-id="' . $row['transmittalID'] . '" data-action="CANCELLED">Cancel</a></li>
                </ul>
            </div>';
        } else {
            $actionButton = "<span class='text-muted'>No actions available</span>";
        }

        $transmittalData = [
            'transmittalId' => $row['transmittalID'],
            'productId' => $row['productID'],
            'productName' => $row['nameProduct'],
            'requestId' => $row['requestID'],
            'fromLocation' => $row['fromLocation'],
            'toLocation' => $row['toLocation'],
            'date' => date('Y-m-d H:i:s', strtotime($row['dateTransmittal'])),
            'status' => $row['statusTransmittal'],
            'statusBadge' => $statusBadge,
            'notes' => $row['noteTransmittal'] ? $row['noteTransmittal'] : ($row['requestID'] ? 'Auto-generated from Branch Request #' . $row['requestID'] : ''),
            'actionButton' => $actionButton
        ];

        if ($row['requestID']) {
            if (!isset($grouped[$row['requestID']])) {
                $grouped[$row['requestID']] = [
                    'requestId' => $row['requestID'],
                    'items' => []
                ];
            }
            $grouped[$row['requestID']]['items'][] = $transmittalData;
        } else {
            $individual[] = $transmittalData;
        }
    }

    // Sort grouped requests by request ID (descending)
    krsort($grouped);

    // Check each group to see if all items are IN_TRANSIT
    foreach ($grouped as $requestId => $group) {
        $allInTransit = true;
        foreach ($group['items'] as $item) {
            if ($item['status'] !== 'IN_TRANSIT') {
                $allInTransit = false;
                break;
            }
        }
        $grouped[$requestId]['allInTransit'] = $allInTransit;
    }

    echo json_encode([
        'grouped' => array_values($grouped),
        'individual' => $individual
    ]);
} catch (Exception $e) {
    // Return an error message in case of exception
    error_log("Error in get_pending_transmittals.php: " . $e->getMessage());
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>

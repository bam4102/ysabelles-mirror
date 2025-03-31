<?php
require_once '../db.php';

header('Content-Type: application/json');

try {
    date_default_timezone_set('Asia/Manila');
    $userLocation = $_SESSION['user']['locationEmployee'];

    // Get all in_transit transmittals related to user's location
    $sql = "SELECT t.*, p.nameProduct, p.productID, 
                  b.requestID as branchRequestID,
                  CASE 
                    WHEN t.requestID IS NOT NULL THEN CONCAT('Request #', t.requestID)
                    ELSE NULL
                  END as requestLabel
            FROM transmittal t 
            JOIN product p ON t.productID = p.productID 
            LEFT JOIN branch_requests b ON t.requestID = b.requestID
            WHERE t.toLocation = ?
            AND t.statusTransmittal = 'IN_TRANSIT'
            ORDER BY t.requestID DESC, t.dateTransmittal DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userLocation]);
    $transmittals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group transmittals by request
    $grouped = [];
    $individual = [];

    foreach ($transmittals as $row) {
        $actionButton = "<button class='btn btn-sm btn-success receive-btn' data-id='" . $row['transmittalID'] . "'>Receive</button>";

        $transmittalData = [
            'transmittalId' => $row['transmittalID'],
            'productId' => $row['productID'],
            'productName' => $row['nameProduct'],
            'requestId' => $row['requestID'],
            'fromLocation' => $row['fromLocation'],
            'toLocation' => $row['toLocation'],
            'date' => date('Y-m-d H:i:s', strtotime($row['dateTransmittal'])),
            'status' => $row['statusTransmittal'],
            'statusBadge' => "<span class='badge bg-info'>" . $row['statusTransmittal'] . "</span>",
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

    echo json_encode([
        'grouped' => array_values($grouped),
        'individual' => $individual
    ]);
} catch (Exception $e) {
    // Return an error message in case of exception
    error_log("Error in get_receive_products.php: " . $e->getMessage());
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>

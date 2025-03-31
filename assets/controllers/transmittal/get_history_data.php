<?php
require_once '../db.php';

header('Content-Type: application/json');

// Get user location and position
$userLocation = $_SESSION['user']['locationEmployee'];
$userPosition = $_SESSION['user']['positionEmployee'];
$isSuperAdmin = ($userPosition === 'SUPERADMIN');

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

// Bind location parameter if not SUPERADMIN
if (!$isSuperAdmin) {
    $stmt->bindParam(':userLocation', $userLocation, PDO::PARAM_STR);
}

$stmt->execute();

$data = [];
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

    $data[] = [
        $row['productID'],
        $row['nameProduct'],
        $row['fromLocation'],
        $row['toLocation'],
        date('Y-m-d H:i:s', strtotime($row['dateTransmittal'])),
        ($row['dateDelivered'] ? date('Y-m-d H:i:s', strtotime($row['dateDelivered'])) : '-'),
        "<span class='badge " . $statusClass . "'>" . $row['statusTransmittal'] . "</span>",
        $row['noteTransmittal']
    ];
}

echo json_encode($data);
?>

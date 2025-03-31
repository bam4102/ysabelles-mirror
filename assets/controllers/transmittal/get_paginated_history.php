<?php
require_once '../db.php';

// Enable error reporting for debugging
error_log("Paginated history request received");

// Set proper content type
header('Content-Type: application/json');

try {
    // Get user location and position
    $userLocation = $_SESSION['user']['locationEmployee'];
    $userPosition = $_SESSION['user']['positionEmployee'];
    $isSuperAdmin = ($userPosition === 'SUPERADMIN');

    // DataTables server-side processing parameters
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 15;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

    // Column ordering
    $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 4; // Default to date column
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';

    // Status filter
    $statusFilter = isset($_POST['status']) ? $_POST['status'] : '';

    // Map DataTables column index to database column name
    $columns = [
        0 => 'p.productID',
        1 => 'p.nameProduct',
        2 => 't.fromLocation',
        3 => 't.toLocation',
        4 => 't.dateTransmittal',
        5 => 't.dateDelivered',
        6 => 't.statusTransmittal',
        7 => 't.noteTransmittal'
    ];

    // Base query for both count and data
    $baseQuery = "FROM transmittal t 
                JOIN product p ON t.productID = p.productID 
                JOIN employee e1 ON t.employeeID = e1.employeeID
                LEFT JOIN employee e2 ON t.receivedBy = e2.employeeID
                WHERE 1=1";

    // Add location filter if not SUPERADMIN
    if (!$isSuperAdmin) {
        $baseQuery .= " AND (t.fromLocation = :userLocation OR t.toLocation = :userLocation)";
    }

    // Add search condition if search is provided
    if (!empty($search)) {
        $baseQuery .= " AND (
            p.productID LIKE :search OR 
            p.nameProduct LIKE :search OR 
            t.fromLocation LIKE :search OR 
            t.toLocation LIKE :search OR 
            t.statusTransmittal LIKE :search OR 
            t.noteTransmittal LIKE :search
        )";
    }

    // Add status filter if provided
    if (!empty($statusFilter)) {
        $baseQuery .= " AND t.statusTransmittal = :statusFilter";
    }

    // Get total count of all records - we need to adjust this for non-superadmins
    $totalSql = "SELECT COUNT(*) FROM transmittal t";
    // For non-superadmins, we need to filter the total count too
    if (!$isSuperAdmin) {
        $totalSql .= " WHERE t.fromLocation = :userLocation OR t.toLocation = :userLocation";
    }
    $stmt = $pdo->prepare($totalSql);
    
    if (!$isSuperAdmin) {
        $stmt->bindParam(':userLocation', $userLocation, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $totalRecords = $stmt->fetchColumn();

    // Get count of filtered records
    $filteredSql = "SELECT COUNT(*) " . $baseQuery;
    $stmt = $pdo->prepare($filteredSql);
    
    if (!$isSuperAdmin) {
        $stmt->bindParam(':userLocation', $userLocation, PDO::PARAM_STR);
    }
    
    if (!empty($search)) {
        $searchParam = '%' . $search . '%';
        $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }
    
    if (!empty($statusFilter)) {
        $stmt->bindParam(':statusFilter', $statusFilter, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $filteredRecords = $stmt->fetchColumn();

    // Column to sort by
    $orderColumnName = $columns[$orderColumn];
    $orderColumnDirection = $orderDir === 'asc' ? 'ASC' : 'DESC';

    // Priority sorting for status (PENDING, IN_TRANSIT, others)
    $prioritySorting = "";
    if ($orderColumn == 6) { // If sorting by status
        $prioritySorting = "CASE 
                                WHEN t.statusTransmittal = 'PENDING' THEN 1
                                WHEN t.statusTransmittal = 'IN_TRANSIT' THEN 2
                                ELSE 3
                            END " . $orderColumnDirection . ", ";
    }

    // Query for data with pagination and ordering
    $dataQuery = "SELECT t.*, p.nameProduct, p.productID,
                    e1.nameEmployee as createdByName,
                    e2.nameEmployee as receivedByName
                " . $baseQuery . "
                ORDER BY " . $prioritySorting . $orderColumnName . " " . $orderColumnDirection . "
                LIMIT :start, :length";
                
    $stmt = $pdo->prepare($dataQuery);
    
    // Bind location param if not SUPERADMIN
    if (!$isSuperAdmin) {
        $stmt->bindParam(':userLocation', $userLocation, PDO::PARAM_STR);
    }
    
    if (!empty($search)) {
        $searchParam = '%' . $search . '%';
        $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }
    
    if (!empty($statusFilter)) {
        $stmt->bindParam(':statusFilter', $statusFilter, PDO::PARAM_STR);
    }
    
    $stmt->bindParam(':start', $start, PDO::PARAM_INT);
    $stmt->bindParam(':length', $length, PDO::PARAM_INT);
    $stmt->execute();

    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Format status with appropriate badge
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

    // Format response for DataTables
    $response = [
        "draw" => $draw,
        "recordsTotal" => $totalRecords,
        "recordsFiltered" => $filteredRecords,
        "data" => $data
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in paginated history: " . $e->getMessage());
    
    // Return error response
    $response = [
        "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "error" => $e->getMessage()
    ];
    
    echo json_encode($response);
}
?>

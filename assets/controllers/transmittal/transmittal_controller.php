<?php
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = array();
    
    try {
        // Set timezone to Philippine Time (UTC+8)
        date_default_timezone_set('Asia/Manila');
        
        // Ensure we have product IDs
        if (!isset($_POST['productIDs']) || !is_array($_POST['productIDs'])) {
            throw new Exception('No products selected');
        }

        $productIDs = $_POST['productIDs'];
        $fromLocation = $_POST['fromLocation'];
        $toLocation = $_POST['toLocation'];
        $noteTransmittal = $_POST['noteTransmittal'];
        $employeeID = $_SESSION['user']['employeeID'];
        $currentDateTime = date('Y-m-d H:i:s'); // Get current date and time in PHT

        // Validate destination
        if (empty($toLocation)) {
            throw new Exception('Please select a destination');
        }

        // Begin transaction
        $pdo->beginTransaction();

        // Insert transmittal record for each product
        $sql = "INSERT INTO transmittal (productID, employeeID, dateTransmittal, fromLocation, toLocation, noteTransmittal, statusTransmittal) 
                VALUES (?, ?, ?, ?, ?, ?, 'PENDING')";
        $stmt = $pdo->prepare($sql);

        foreach ($productIDs as $productID) {
            $result = $stmt->execute([
                $productID,
                $employeeID,
                $currentDateTime,
                $fromLocation,
                $toLocation,
                $noteTransmittal
            ]);

            if (!$result) {
                throw new Exception('Failed to create transmittal for product ID: ' . $productID);
            }
        }

        // Commit transaction
        $pdo->commit();

        $response['status'] = 'success';
        $response['message'] = 'Transmittals created successfully';
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $response['status'] = 'error';
        $response['message'] = $e->getMessage();
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

<?php
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = array();
    
    try {
        // Set timezone to Philippines
        date_default_timezone_set('Asia/Manila');
        
        if (!isset($_POST['transmittalId'])) {
            throw new Exception('Missing transmittal ID');
        }

        $transmittalId = $_POST['transmittalId'];
        $userLocation = $_SESSION['user']['locationEmployee'];
        $employeeId = $_SESSION['user']['employeeID'];

        // Begin transaction
        $pdo->beginTransaction();

        // Verify the transmittal exists and is in correct state
        $checkSql = "SELECT t.*, p.productID, t.requestID 
                     FROM transmittal t 
                     JOIN product p ON t.productID = p.productID 
                     WHERE t.transmittalID = ? 
                     AND t.statusTransmittal = 'IN_TRANSIT'
                     AND t.toLocation = ?";
        $stmt = $pdo->prepare($checkSql);
        $stmt->execute([$transmittalId, $userLocation]);
        $transmittal = $stmt->fetch();

        if (!$transmittal) {
            throw new Exception('Invalid transmittal or unauthorized to receive');
        }

        // Update transmittal status with explicit UTC+8 time
        $updateTransmittalSql = "SET time_zone = '+08:00';
                                UPDATE transmittal 
                                SET statusTransmittal = 'DELIVERED',
                                    dateDelivered = NOW(),
                                    receivedBy = ?
                                WHERE transmittalID = ?";
        $stmt = $pdo->prepare($updateTransmittalSql);
        $stmt->execute([$employeeId, $transmittalId]);

        // Update product location and returnedProduct status
        $updateProductSql = "UPDATE product 
                            SET locationProduct = ?,
                                returnedProduct = 0
                            WHERE productID = ?";
        $stmt = $pdo->prepare($updateProductSql);
        $stmt->execute([$userLocation, $transmittal['productID']]);

        // No need to update branch request status anymore
        // We're now keeping APPROVED as the final status

        $pdo->commit();
        
        $response['status'] = 'success';
        $response['message'] = 'Product received successfully';
    } catch (Exception $e) {
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

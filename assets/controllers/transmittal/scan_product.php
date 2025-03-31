<?php
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = array();
    
    try {
        // Set timezone to Philippines
        date_default_timezone_set('Asia/Manila');
        
        if (!isset($_POST['productCode']) || empty($_POST['productCode'])) {
            throw new Exception('Missing product code');
        }

        $productCode = $_POST['productCode'];
        $userLocation = $_SESSION['user']['locationEmployee'];
        $employeeId = $_SESSION['user']['employeeID'];

        // Begin transaction
        $pdo->beginTransaction();

        // Get the transmittal with matching product code that is in transit to this location
        $findSql = "SELECT t.*, p.productID, p.nameProduct, p.codeProduct 
                    FROM transmittal t 
                    JOIN product p ON t.productID = p.productID 
                    WHERE p.codeProduct = ? 
                    AND t.statusTransmittal = 'IN_TRANSIT'
                    AND t.toLocation = ?";
        $stmt = $pdo->prepare($findSql);
        $stmt->execute([$productCode, $userLocation]);
        $transmittal = $stmt->fetch();

        if (!$transmittal) {
            throw new Exception('No product with this code is in transit to your location');
        }

        // Update transmittal status with explicit UTC+8 time
        $updateTransmittalSql = "SET time_zone = '+08:00';
                               UPDATE transmittal 
                               SET statusTransmittal = 'DELIVERED',
                                   dateDelivered = NOW(),
                                   receivedBy = ?
                               WHERE transmittalID = ?";
        $stmt = $pdo->prepare($updateTransmittalSql);
        $stmt->execute([$employeeId, $transmittal['transmittalID']]);

        // Update product location and reset returned status
        $updateProductSql = "UPDATE product 
                           SET locationProduct = ?,
                               returnedProduct = 0
                           WHERE productID = ?";
        $stmt = $pdo->prepare($updateProductSql);
        $stmt->execute([$userLocation, $transmittal['productID']]);

        $pdo->commit();
        
        $response['status'] = 'success';
        $response['message'] = 'Product received successfully';
        $response['productName'] = $transmittal['nameProduct'];
        $response['transmittalId'] = $transmittal['transmittalID'];
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
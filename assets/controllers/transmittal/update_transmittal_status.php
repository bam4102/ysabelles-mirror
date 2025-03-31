<?php
require_once '../db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Enable detailed error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log the raw POST data
$rawPostData = file_get_contents('php://input');
error_log('Raw POST data: ' . $rawPostData);
error_log('$_POST: ' . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = array();

    try {
        // Verify required parameters
        if (empty($_POST['transmittalId'])) {
            throw new Exception('Missing required parameter: transmittalId');
        }
        
        if (empty($_POST['status'])) {
            throw new Exception('Missing required parameter: status');
        }

        $transmittalId = $_POST['transmittalId'];
        $newStatus = $_POST['status'];

        // Make sure we have a valid session
        if (!isset($_SESSION['user']) || empty($_SESSION['user']['locationEmployee'])) {
            throw new Exception('User session invalid or missing location information');
        }

        $userLocation = $_SESSION['user']['locationEmployee'];
        error_log("Processing status change request: Transmittal ID=$transmittalId, New Status=$newStatus, User Location=$userLocation");

        // Check if transmittal exists and get its details
        $checkSql = "SELECT * FROM transmittal WHERE transmittalID = ?";
        $stmt = $pdo->prepare($checkSql);
        $stmt->execute([$transmittalId]);
        $transmittal = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$transmittal) {
            throw new Exception("Transmittal with ID $transmittalId not found");
        }

        error_log("Found transmittal: " . print_r($transmittal, true));

        // Validate permissions and status
        if ($transmittal['fromLocation'] !== $userLocation) {
            throw new Exception("Permission denied: You can only modify transmittals from your location ($userLocation)");
        }

        if ($transmittal['statusTransmittal'] !== 'PENDING') {
            throw new Exception('Only PENDING transmittals can be updated');
        }

        if (!in_array($newStatus, ['IN_TRANSIT', 'CANCELLED'])) {
            throw new Exception("Invalid status: $newStatus. Allowed values are IN_TRANSIT and CANCELLED");
        }

        // Begin transaction
        $pdo->beginTransaction();

        // Update the transmittal status
        $updateSql = "UPDATE transmittal SET statusTransmittal = ? WHERE transmittalID = ?";
        $stmt = $pdo->prepare($updateSql);
        $result = $stmt->execute([$newStatus, $transmittalId]);
        
        if (!$result) {
            throw new Exception("Database error: Failed to update transmittal status");
        }
        
        $rowCount = $stmt->rowCount();
        error_log("Update result: rows affected = $rowCount");

        if ($rowCount === 0) {
            throw new Exception("No records were updated. The transmittal may have been modified by another user.");
        }

        // Additional product updates for IN_TRANSIT status
        if ($newStatus === 'IN_TRANSIT') {
            // Get product ID first
            $productId = $transmittal['productID'];
            
            // Update product's returned status
            $updateProductSql = "UPDATE product SET returnedProduct = 1 WHERE productID = ?";
            $stmt = $pdo->prepare($updateProductSql);
            $stmt->execute([$productId]);
            error_log("Product status updated for IN_TRANSIT: rows affected = " . $stmt->rowCount());
        }

        // Commit the transaction
        $pdo->commit();

        // Return success
        $response['status'] = 'success';
        $response['message'] = "Transmittal status updated to $newStatus successfully";
        error_log("Status change completed successfully");
    } 
    catch (Exception $e) {
        // Roll back any changes if an error occurred
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Log the error and return error message
        error_log("Error in update_transmittal_status.php: " . $e->getMessage());
        $response['status'] = 'error';
        $response['message'] = $e->getMessage();
    }

    // Send the response
    echo json_encode($response);
    exit;
} 
else {
    // Handle non-POST requests
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. This endpoint only accepts POST requests.'
    ]);
    exit;
}
?>

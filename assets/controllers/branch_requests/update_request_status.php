<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['employeeID'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Include database connection
require_once '../../controllers/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['requestId']) && isset($_POST['status'])) {
    $requestId = $_POST['requestId'];
    $status = strtoupper($_POST['status']);
    $employeeId = $_SESSION['employeeID'];
    
    // Validate status values - remove COMPLETED
    $validStatuses = ['APPROVED', 'DECLINED', 'CANCELED'];
    if (!in_array($status, $validStatuses)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }
    
    try {
        // Start a transaction
        $pdo->beginTransaction();
        
        // First, check if the user has permission to update this request
        $stmt = $pdo->prepare("
            SELECT r.*, e.locationEmployee 
            FROM branch_requests r 
            JOIN employee e ON e.employeeID = ?
            WHERE r.requestID = ?
        ");
        $stmt->execute([$employeeId, $requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            throw new Exception('Request not found');
        }
        
        $userBranch = $request['locationEmployee'];
        
        // Check if the user has permission based on the status change
        $canUpdate = false;
        
        if ($status === 'CANCELED' && $request['sourceBranch'] === $userBranch) {
            // Only source branch can cancel
            $canUpdate = true;
            $updateFields = "status = ?, respondedBy = ?";
        } else if (($status === 'APPROVED' || $status === 'DECLINED') && $request['destinationBranch'] === $userBranch) {
            // Only destination branch can approve/decline
            $canUpdate = true;
            $updateFields = "status = ?, respondedBy = ?";
        }
        
        if (!$canUpdate) {
            throw new Exception('You do not have permission to perform this action');
        }
        
        // Update the request status
        $stmt = $pdo->prepare("
            UPDATE branch_requests 
            SET $updateFields
            WHERE requestID = ?
        ");
        
        $stmt->execute([$status, $employeeId, $requestId]);
        
        // If the status is APPROVED, create transmittals for all products
        if ($status === 'APPROVED') {
            // Get all products in the request
            $products = json_decode($request['products'], true);
            
            // Get location info
            $fromBranch = $request['destinationBranch']; // Products are coming FROM the destination branch
            $toBranch = $request['sourceBranch']; // Going TO the source branch (requester)
            $notes = "Auto-generated from Branch Request #$requestId";
            
            // Create a transmittal for each product
            foreach ($products as $product) {
                $productId = $product['id'];
                
                // Create transmittal record
                $stmt = $pdo->prepare("
                    INSERT INTO transmittal 
                    (productID, employeeID, dateTransmittal, fromLocation, toLocation, statusTransmittal, noteTransmittal, requestID) 
                    VALUES (?, ?, NOW(), ?, ?, 'PENDING', ?, ?)
                ");
                $stmt->execute([
                    $productId,
                    $employeeId,
                    $fromBranch,
                    $toBranch,
                    $notes,
                    $requestId
                ]);
            }
        }
        
        // Commit the transaction
        $pdo->commit();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Request status updated successfully',
            'redirect' => $status === 'APPROVED' ? 'transmittal.php#pending' : null
        ]);
    } catch (Exception $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

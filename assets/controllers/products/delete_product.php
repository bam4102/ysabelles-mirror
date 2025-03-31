<?php
require_once '../../controllers/db.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set headers for JSON response
header('Content-Type: application/json');

try {
    // Get product ID from request
    $productId = isset($_POST['productId']) ? $_POST['productId'] : null;
    
    if (!$productId) {
        // Try to get from JSON input if not in POST
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        $productId = isset($data['productId']) ? $data['productId'] : null;
    }
    
    if (!$productId) {
        throw new Exception('Product ID is required');
    }
    
    // Log the delete request
    error_log("Delete request for product ID: " . $productId);
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Check if the product exists
    $checkStmt = $pdo->prepare("SELECT productID FROM product WHERE productID = ?");
    $checkStmt->execute([$productId]);
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception('Product not found');
    }
    
    // Delete associated images
    // First get the image paths to delete the physical files
    $imageStmt = $pdo->prepare("SELECT pictureLocation FROM picture WHERE productID = ? AND isActive = 1");
    $imageStmt->execute([$productId]);
    $imagePaths = $imageStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Delete image records (soft delete)
    $updateImagesStmt = $pdo->prepare("UPDATE picture SET isActive = 0 WHERE productID = ?");
    $updateImagesStmt->execute([$productId]);
    
    // Delete the product (hard delete)
    $deleteStmt = $pdo->prepare("DELETE FROM product WHERE productID = ?");
    $deleteStmt->execute([$productId]);
    
    // Check if the delete was successful
    if ($deleteStmt->rowCount() === 0) {
        throw new Exception('Failed to delete product');
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Delete physical image files
    foreach ($imagePaths as $path) {
        if (file_exists($path)) {
            unlink($path);
            error_log("Deleted file: " . $path);
        } else {
            error_log("File not found: " . $path);
        }
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Product deleted successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log the error
    error_log("Error in delete_product.php: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 
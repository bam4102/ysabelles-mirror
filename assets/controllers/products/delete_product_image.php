<?php
require_once '../../controllers/db.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set headers for JSON response
header('Content-Type: application/json');

try {
    // Get JSON data from request body
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    
    // Log received data for debugging
    error_log("Received data: " . print_r($data, true));
    
    if (!$data || !isset($data['pictureID']) || !isset($data['productID'])) {
        throw new Exception('Invalid request data: ' . $jsonData);
    }

    // Start transaction
    $pdo->beginTransaction();

    // Check if this is the primary image (using raw values for logging)
    $pictureID = $data['pictureID'];
    $productID = $data['productID'];
    
    error_log("Checking for image with pictureID: $pictureID, productID: $productID");
    
    // First just check if the picture exists at all
    $checkQuery = "SELECT pictureID, isPrimary, pictureLocation FROM picture WHERE pictureID = ?";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$pictureID]);
    $image = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$image) {
        throw new Exception("Image not found with ID: $pictureID");
    }
    
    error_log("Found image: " . print_r($image, true));

    if (isset($image['isPrimary']) && $image['isPrimary'] == 1) {
        throw new Exception('Cannot delete primary image');
    }

    // Soft delete the image by setting isActive = 0
    $sql = "UPDATE picture SET isActive = 0 WHERE pictureID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$pictureID]);
    
    $rowsAffected = $stmt->rowCount();
    error_log("Rows affected by update: $rowsAffected");

    // Try to delete the physical file
    if (!empty($image['pictureLocation'])) {
        // Get the exact path from the database
        $pictureLocation = $image['pictureLocation'];
        
        // Construct full absolute path to the file
        // First check if the path is already absolute
        if (strpos($pictureLocation, ':') !== false || strpos($pictureLocation, '/') === 0) {
            // This is already an absolute path
            $filePath = $pictureLocation;
        } else {
            // This is a relative path, construct the full path
            $projectRoot = realpath(__DIR__ . '/../../..');
            
            // Remove any leading slashes or relative path indicators
            $pictureLocation = ltrim($pictureLocation, '/\\');
            
            // Combine paths
            $filePath = $projectRoot . '/' . $pictureLocation;
        }
        
        // Normalize path separators for the current OS
        $filePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $filePath);
        
        error_log("Attempting to delete physical file at: $filePath");
        
        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                error_log("Successfully deleted physical file: $filePath");
            } else {
                error_log("Failed to delete physical file (permission issue): $filePath");
            }
        } else {
            error_log("Physical file does not exist at: $filePath");
            
            // Try alternative paths
            // For "pictures/products/ID/filename.jpg" format
            if (preg_match('|pictures/products/(\d+)/([^/]+)$|', $pictureLocation, $matches)) {
                $productId = $matches[1];
                $filename = $matches[2];
                $altPath = $projectRoot . '/pictures/products/' . $productId . '/' . $filename;
                $altPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $altPath);
                
                error_log("Trying alternative path: $altPath");
                
                if (file_exists($altPath)) {
                    if (unlink($altPath)) {
                        error_log("Successfully deleted physical file at alternative path: $altPath");
                    } else {
                        error_log("Failed to delete physical file at alternative path: $altPath");
                    }
                } else {
                    error_log("Physical file not found at alternative path: $altPath");
                }
            }
        }
    }

    // Commit transaction
    $pdo->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Image deleted successfully',
        'rowsAffected' => $rowsAffected
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Log the error
    error_log("Error in delete_product_image.php: " . $e->getMessage());

    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 
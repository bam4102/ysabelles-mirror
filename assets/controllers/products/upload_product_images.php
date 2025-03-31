<?php
// Include the global error handler
require_once __DIR__ . '/error_handler.php';
require_once __DIR__ . '/../../controllers/db.php';

// For debugging purposes
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set headers for JSON response
header('Content-Type: application/json');

// Define the debug_log function if not already defined
if (!function_exists('debug_log')) {
    function debug_log($message, $data = null) {
        $log_file = __DIR__ . '/../../logs/upload_debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp] $message";
        
        if ($data !== null) {
            $log_message .= " - Data: " . json_encode($data);
        }
        
        // Append to log file
        file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
        
        // Also log to PHP error log for convenience
        error_log($log_message);
    }
}

// Define send_json_response function if not already defined
if (!function_exists('send_json_response')) {
    function send_json_response($success, $message, $data = []) {
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        if (!empty($data)) {
            $response = array_merge($response, $data);
        }
        
        echo json_encode($response);
        exit;
    }
}

// Start output buffering to capture any unexpected output
ob_start();

try {
    debug_log("Upload request started");
    
    // Get product ID
    $productID = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    debug_log("Processing upload for product ID: $productID");

    // Validate product ID
    if ($productID <= 0) {
        debug_log("Invalid product ID: $productID");
        send_json_response(false, "Invalid product ID", []);
        exit;
    }

    // Check if files are present in the request
    if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
        debug_log("No files uploaded");
        send_json_response(false, "No files were uploaded", []);
        exit;
    }

    // Process each image
    $uploadedFiles = [];
    $failedFiles = [];
    $totalFiles = count($_FILES['images']['name']);

    debug_log("Processing $totalFiles files");
    
    // Get base upload directory - ensure it uses absolute paths
    $baseDir = __DIR__ . '/../../../pictures/products/';
    $uploadsDir = $baseDir . $productID . '/';
    
    debug_log("Base upload directory: $baseDir");
    debug_log("Product upload directory: $uploadsDir");

    // Create uploads directory if it doesn't exist
    if (!file_exists($uploadsDir)) {
        debug_log("Creating upload directory: $uploadsDir");
        if (!mkdir($uploadsDir, 0755, true)) {
            debug_log("Failed to create upload directory: $uploadsDir");
            send_json_response(false, "Failed to create upload directory", []);
            exit;
        }
    }

    // Attempt to start a transaction
    $pdo->beginTransaction();
    $transactionActive = true;

    try {
        // First check if max image limit reached
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM picture WHERE productID = ? AND isActive = 1");
        $stmt->execute([$productID]);
        $currentImageCount = $stmt->fetchColumn();
        
        debug_log("Current image count: $currentImageCount");
        
        if ($currentImageCount >= 5) {
            throw new Exception("Maximum limit of 5 images has been reached");
        }
        
        // Calculate how many more images can be added
        $remainingSlots = 5 - $currentImageCount;
        if ($totalFiles > $remainingSlots) {
            debug_log("Too many files: $totalFiles files, only $remainingSlots slots available");
            send_json_response(false, "You can only upload up to $remainingSlots more images", []);
            exit;
        }
        
        // Prepare statement for inserting new images
        // Use the proper fields from your picture table
        $insertStmt = $pdo->prepare("INSERT INTO picture (productID, pictureLocation, isPrimary, dateAdded, isActive) 
                                    VALUES (?, ?, ?, NOW(), 1)");
        
        // Process each file
        foreach ($_FILES['images']['name'] as $key => $fileName) {
            $tmpName = $_FILES['images']['tmp_name'][$key];
            $fileSize = $_FILES['images']['size'][$key];
            $fileError = $_FILES['images']['error'][$key];
            $fileType = $_FILES['images']['type'][$key];
            
            debug_log("Processing file: $fileName, Type: $fileType, Size: $fileSize bytes");
            
            try {
                // Validate the file
                list($isValid, $errorMessage) = validate_uploaded_file([
                    'name' => $fileName,
                    'tmp_name' => $tmpName,
                    'size' => $fileSize,
                    'error' => $fileError,
                    'type' => $fileType
                ]);
                
                if (!$isValid) {
                    debug_log("Validation failed: $errorMessage");
                    $failedFiles[] = [
                        'name' => $fileName,
                        'error' => $errorMessage
                    ];
                    continue;
                }
                
                // Generate a unique filename to prevent overwrites
                $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = 'pic_' . uniqid() . rand(1000000, 9999999) . '.' . $extension;
                $uploadFilePath = $uploadsDir . $newFileName;
                
                debug_log("Moving uploaded file to: $uploadFilePath");
                
                // Move the uploaded file to the destination
                if (!move_uploaded_file($tmpName, $uploadFilePath)) {
                    debug_log("Failed to move uploaded file to $uploadFilePath");
                    throw new Exception("Failed to move uploaded file");
                }
                
                debug_log("File successfully moved to: $uploadFilePath");
                
                // Set relative paths for database storage
                $relativePath = "pictures/products/$productID/$newFileName";
                
                // Determine if this should be the primary image
                $isPrimary = ($currentImageCount == 0 && count($uploadedFiles) == 0) ? 1 : 0;
                
                debug_log("Inserting into database: $relativePath, isPrimary: $isPrimary");
                
                // Insert into database
                if (!$insertStmt->execute([$productID, $relativePath, $isPrimary])) {
                    $errorInfo = $insertStmt->errorInfo();
                    debug_log("Database insertion failed: " . $errorInfo[2]);
                    throw new Exception("Failed to save image information to database");
                }
                
                $uploadedFiles[] = [
                    'original_name' => $fileName,
                    'saved_name' => $newFileName,
                    'path' => $relativePath,
                    'is_primary' => $isPrimary,
                    'size' => $fileSize
                ];
                
                debug_log("Successfully processed: $fileName");
            } catch (Exception $e) {
                debug_log("Error processing file {$fileName}: " . $e->getMessage());
                $failedFiles[] = [
                    'name' => $fileName,
                    'error' => $e->getMessage()
                ];
                
                // Continue processing other files even if one fails
                continue;
            }
        }
        
        // Commit the transaction if at least one file was processed successfully
        if (count($uploadedFiles) > 0) {
            $pdo->commit();
            $transactionActive = false;
            debug_log("Transaction committed for " . count($uploadedFiles) . " files");
        } else if ($transactionActive) {
            $pdo->rollBack();
            $transactionActive = false;
            debug_log("Transaction rolled back - no files were successfully processed");
        }
        
        // Prepare the response
        if (count($uploadedFiles) > 0) {
            if (count($failedFiles) > 0) {
                // Some files succeeded, some failed
                debug_log("Partial success: " . count($uploadedFiles) . " succeeded, " . count($failedFiles) . " failed");
                send_json_response(true, "Some files were uploaded successfully", [
                    'uploaded' => $uploadedFiles,
                    'failed' => $failedFiles
                ]);
            } else {
                // All files succeeded
                debug_log("Complete success: All " . count($uploadedFiles) . " files uploaded");
                send_json_response(true, "All files uploaded successfully", [
                    'uploaded' => $uploadedFiles
                ]);
            }
        } else {
            // All files failed
            debug_log("Complete failure: All " . count($failedFiles) . " files failed");
            send_json_response(false, "Failed to upload any files", [
                'failed' => $failedFiles
            ]);
        }
    } catch (Exception $e) {
        // Handle any uncaught exceptions
        debug_log("Uncaught exception: " . $e->getMessage());
        
        // Rollback the transaction if active
        if ($transactionActive) {
            $pdo->rollBack();
            $transactionActive = false;
            debug_log("Transaction rolled back due to exception");
        }
        
        send_json_response(false, "Error processing uploads: " . $e->getMessage(), [
            'failed' => $failedFiles
        ]);
    } finally {
        // Clean up the output buffer
        $output = ob_get_clean();
        if (!empty($output)) {
            debug_log("Unexpected output captured: " . $output);
        }
    }
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        debug_log("Rolling back transaction due to exception: " . $e->getMessage());
        $pdo->rollBack();
    }
    
    // Delete any uploaded files if there was an error
    if (isset($uploadedFiles) && is_array($uploadedFiles)) {
        debug_log("Cleaning up " . count($uploadedFiles) . " partially uploaded files");
        foreach ($uploadedFiles as $file) {
            if (isset($file['path'])) {
                $path = __DIR__ . '/../../../' . $file['path'];
                if (file_exists($path)) {
                    unlink($path);
                    debug_log("Deleted file: $path");
                }
            }
        }
    }
    
    // Log the error
    debug_log("Fatal error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    // Capture any output that might have been generated
    $output = ob_get_clean();
    if (!empty($output)) {
        debug_log("Unexpected output captured: $output");
    }
    
    // Return error response
    http_response_code(500);
    $response = [
        'success' => false,
        'message' => "A fatal error occurred on the server: " . $e->getMessage()
    ];
    
    debug_log("Sending exception response", $response);
    echo json_encode($response);
} finally {
    // End output buffering if it's still active
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
}

// Function to validate uploaded file with detailed error messages
function validate_uploaded_file($file) {
    // Check if file was uploaded properly
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return [false, "No file was uploaded or upload failed"];
    }
    
    // Check if there was an upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
            UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form",
            UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded",
            UPLOAD_ERR_NO_FILE => "No file was uploaded",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
            UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload"
        ];
        
        $error_message = isset($error_messages[$file['error']]) ? 
            $error_messages[$file['error']] : "Unknown upload error code: " . $file['error'];
        
        return [false, $error_message];
    }
    
    // Check file exists and is readable
    if (!file_exists($file['tmp_name']) || !is_readable($file['tmp_name'])) {
        return [false, "Cannot access uploaded file or file does not exist"];
    }
    
    // Get file information including MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    
    // List of allowed image MIME types
    $allowed_types = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'
    ];
    
    // Validate MIME type
    if (!in_array($mime_type, $allowed_types)) {
        return [false, "Invalid file type. Got: $mime_type. Allowed types: JPEG, PNG, GIF, WebP"];
    }
    
    // Check file size (5MB limit)
    $max_size = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $max_size) {
        return [false, "File size exceeds limit of 5MB. Got: " . round($file['size']/1024/1024, 2) . "MB"];
    }
    
    // Make sure the file is actually an image by attempting to get its dimensions
    $image_info = @getimagesize($file['tmp_name']);
    if ($image_info === false) {
        return [false, "The file is not a valid image"];
    }
    
    // All validations passed
    return [true, ""];
}
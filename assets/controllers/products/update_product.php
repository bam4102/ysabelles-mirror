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
    
    if (!$data || !isset($data['productID'])) {
        throw new Exception('Invalid request data: ' . $jsonData);
    }

    // Start transaction
    $pdo->beginTransaction();

    // Prepare the update query
    $sql = "UPDATE product SET 
            nameProduct = :nameProduct,
            categoryID = :categoryID,
            locationProduct = :locationProduct,
            genderProduct = :genderProduct,
            codeProduct = :codeProduct,
            priceProduct = :priceProduct,
            sizeProduct = :sizeProduct,
            colorProduct = :colorProduct,
            descProduct = :descProduct,
            damageProduct = :damageProduct,
            soldProduct = :soldProduct,
            useProduct = :useProduct,
            returnedProduct = :returnedProduct,
            bustProduct = :bustProduct,
            waistProduct = :waistProduct,
            lengthProduct = :lengthProduct,
            typeProduct = :typeProduct,
            isNew = :isNew
            WHERE productID = :productID";

    $stmt = $pdo->prepare($sql);

    // Bind parameters
    $params = [
        ':productID' => $data['productID'],
        ':nameProduct' => $data['nameProduct'],
        ':categoryID' => $data['categoryID'],
        ':locationProduct' => $data['locationProduct'],
        ':genderProduct' => $data['genderProduct'],
        ':codeProduct' => isset($data['codeProduct']) && $data['codeProduct'] !== '' ? $data['codeProduct'] : null,
        ':priceProduct' => isset($data['priceProduct']) && $data['priceProduct'] !== '' ? $data['priceProduct'] : 0,
        ':sizeProduct' => isset($data['sizeProduct']) && $data['sizeProduct'] !== '' ? $data['sizeProduct'] : null,
        ':colorProduct' => isset($data['colorProduct']) && $data['colorProduct'] !== '' ? $data['colorProduct'] : null,
        ':descProduct' => isset($data['descProduct']) && $data['descProduct'] !== '' ? $data['descProduct'] : null,
        ':damageProduct' => isset($data['damageProduct']) ? $data['damageProduct'] : 0,
        ':soldProduct' => isset($data['soldProduct']) ? $data['soldProduct'] : 0,
        ':useProduct' => isset($data['useProduct']) ? $data['useProduct'] : 0,
        ':returnedProduct' => isset($data['returnedProduct']) ? $data['returnedProduct'] : 0,
        ':bustProduct' => isset($data['bustProduct']) && $data['bustProduct'] !== '' ? $data['bustProduct'] : null,
        ':waistProduct' => isset($data['waistProduct']) && $data['waistProduct'] !== '' ? $data['waistProduct'] : null,
        ':lengthProduct' => isset($data['lengthProduct']) && $data['lengthProduct'] !== '' ? $data['lengthProduct'] : null,
        ':typeProduct' => isset($data['typeProduct']) && $data['typeProduct'] !== '' ? $data['typeProduct'] : null,
        ':isNew' => isset($data['isNew']) ? $data['isNew'] : 0
    ];

    // Log the SQL and parameters for debugging
    error_log("SQL Query: " . $sql);
    error_log("Parameters: " . print_r($params, true));

    try {
        // Execute the update
        $stmt->execute($params);
    } catch (PDOException $e) {
        // Log the specific database error
        error_log("Database Error: " . $e->getMessage());
        error_log("Error Code: " . $e->getCode());
        throw new Exception('Database error: ' . $e->getMessage());
    }

    // Check if the update was successful
    if ($stmt->rowCount() === 0) {
        // This might not be an error if no changes were made
        error_log("No rows were updated for product ID: " . $data['productID']);
    }

    // Commit transaction
    $pdo->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Product updated successfully',
        'rowsAffected' => $stmt->rowCount()
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Log the error
    error_log("Error in update_product.php: " . $e->getMessage());

    // Return error response with more details
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'details' => 'Check server logs for more information'
    ]);
} 
<?php
session_start();

// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Connect to the database
include '../../controllers/db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if this is an AJAX request
if (!isset($_POST['ajax_validate'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    // Log validation attempt
    error_log("Multiple product validation started");
    
    // Check for required fields
    $requiredFields = ['product_name', 'product_type', 'product_color', 'product_location', 'size_variations'];
    $errors = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $errors[] = "Missing required field: $field";
        }
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields', 'errors' => $errors]);
        exit;
    }
    
    // Validate category exists
    $stmt = $pdo->prepare("SELECT categoryID FROM productcategory WHERE categoryCode = ?");
    $stmt->execute([$_POST['product_type']]);
    $categoryData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$categoryData) {
        echo json_encode(['success' => false, 'message' => 'Invalid category code']);
        exit;
    }
    
    // Validate size variations JSON
    $variations = json_decode($_POST['size_variations'], true);
    if (!is_array($variations) || empty($variations)) {
        echo json_encode(['success' => false, 'message' => 'Invalid size variations data']);
        exit;
    }
    
    // Validate each variation
    $variationErrors = [];
    $totalQuantity = 0;
    
    foreach ($variations as $index => $variation) {
        if (empty($variation['size'])) {
            $variationErrors[] = "Variation #" . ($index + 1) . ": Size is required";
        }
        
        if (!isset($variation['price']) || !is_numeric($variation['price'])) {
            $variationErrors[] = "Variation #" . ($index + 1) . ": Invalid price";
        }
        
        if (!isset($variation['quantity']) || !is_numeric($variation['quantity']) || $variation['quantity'] < 1) {
            $variationErrors[] = "Variation #" . ($index + 1) . ": Invalid quantity";
        } else {
            $totalQuantity += intval($variation['quantity']);
        }
        
        // Check scan codes
        $scanCodes = isset($variation['scanCodes']) ? $variation['scanCodes'] : [];
        $validCodes = array_filter($scanCodes, function($code) { 
            return !empty(trim($code)); 
        });
        
        // For each variation, we need exactly one scan code per quantity
        $quantity = intval($variation['quantity'] ?? 1);
        if (count($validCodes) !== $quantity) {
            $variationErrors[] = "Size '" . $variation['size'] . 
                               "': You need exactly " . $quantity . " scan code(s) for this size, but found " . 
                               count($validCodes);
        }
        
        // Check for duplicate scan codes within this variation
        if (count($validCodes) !== count(array_unique($validCodes))) {
            $variationErrors[] = "Size '" . $variation['size'] . 
                               "': Contains duplicate scan codes";
        }
    }
    
    // Check for unique scan codes across all variations
    $allCodes = [];
    foreach ($variations as $variation) {
        if (isset($variation['scanCodes']) && is_array($variation['scanCodes'])) {
            foreach ($variation['scanCodes'] as $code) {
                if (!empty(trim($code))) {
                    $allCodes[] = trim($code);
                }
            }
        }
    }
    
    // Check for duplicates across all variations
    $duplicates = array_filter(array_count_values($allCodes), function($count) {
        return $count > 1;
    });
    
    if (!empty($duplicates)) {
        $variationErrors[] = "Duplicate scan codes found across different sizes: " . 
                           implode(", ", array_keys($duplicates));
    }
    
    // Check if scan codes already exist in database
    if (!empty($allCodes)) {
        $placeholders = implode(',', array_fill(0, count($allCodes), '?'));
        $stmt = $pdo->prepare("SELECT codeProduct FROM product WHERE codeProduct IN ($placeholders)");
        $stmt->execute($allCodes);
        $existingCodes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($existingCodes)) {
            $variationErrors[] = "The following scan codes already exist: " . implode(", ", $existingCodes);
        }
        
        // Remove check for product_scan_codes table since we're not using it anymore
    }
    
    if (!empty($variationErrors)) {
        echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $variationErrors]);
        exit;
    }
    
    // Calculate total products (one product per scan code)
    $totalProducts = 0;
    foreach ($variations as $variation) {
        if (isset($variation['quantity']) && is_numeric($variation['quantity']) && $variation['quantity'] > 0) {
            $totalProducts += intval($variation['quantity']);
        }
    }
    
    // If we got this far, validation is successful
    echo json_encode([
        'success' => true, 
        'message' => 'Validation successful',
        'data' => [
            'sizes_count' => count($variations),
            'total_products' => $totalProducts
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Multiple product validation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

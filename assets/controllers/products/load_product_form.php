<?php
require_once '../../controllers/db.php';

// Check if product ID is provided
if (!isset($_GET['productId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID is required']);
    exit;
}

$productId = $_GET['productId'];

try {
    // Get product details
    $stmt = $pdo->prepare("SELECT * FROM product WHERE productID = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_OBJ);
    
    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit;
    }
    
    // Get categories for the form
    $stmt = $pdo->prepare("SELECT * FROM productcategory ORDER BY productCategory");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get product images
    $stmt = $pdo->prepare("SELECT * FROM picture WHERE productID = ? AND isActive = 1 ORDER BY isPrimary DESC");
    $stmt->execute([$productId]);
    $product->images = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    // Keep image paths as they are in the database
    // The frontend will use the normalizeImageUrl function to handle different formats
    
    // Get product variations
    $stmt = $pdo->prepare("
        SELECT p.*, psv.group_id 
        FROM product p
        JOIN product_size_variations psv ON p.productID = psv.product_id
        WHERE psv.group_id IN (
            SELECT group_id 
            FROM product_size_variations 
            WHERE product_id = ?
        )
        ORDER BY p.sizeProduct
    ");
    $stmt->execute([$productId]);
    $variations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate variations HTML
    $variationsHtml = '';
    if (!empty($variations) && count($variations) > 1) {
        $variationsHtml = '<div class="row mt-3">
            <div class="col-12">
                <div class="variations-container">
                    <h5 class="variation-heading">Available Sizes</h5>
                    <div class="variation-sizes">';
        
        foreach ($variations as $variation) {
            $isActive = $variation['productID'] == $productId;
            $variationsHtml .= sprintf('
                <div class="variation-size%s" data-product-id="%s" title="Edit product with size %s">
                    %s
                </div>',
                $isActive ? ' active' : '',
                htmlspecialchars($variation['productID']),
                htmlspecialchars($variation['sizeProduct'] ?: 'Unknown'),
                htmlspecialchars($variation['sizeProduct'] ?: 'Unknown Size')
            );
        }
        
        $variationsHtml .= '</div>
                <div class="mt-2 variation-tip">
                    <small class="text-muted">Click on a size to edit that product</small>
                </div>
            </div>
        </div>
    </div>';
    }
    
    // Include the form view
    require_once '../../views/products/product-edit-form.php';
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load product details: ' . $e->getMessage()]);
    exit;
} 
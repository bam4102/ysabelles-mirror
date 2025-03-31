<?php
header('Content-Type: application/json');
include_once 'homesearch_controller.php';

// This file will be called via AJAX to return product data without the full page HTML
$response = [
    'success' => true,
    'products' => $paginatedProducts,
    'pagination' => [
        'current_page' => $current_page,
        'total_pages' => $totalPages,
        'total_products' => $totalProducts
    ]
];

// Loop through products and add image URLs
foreach ($response['products'] as &$product) {
    $product['lowQualityImage'] = getLowQualityPicture($pdo, $product['productID']);
    $product['mediumQualityImage'] = str_replace('_low.', '_medium.', $product['lowQualityImage']);
    $product['highQualityImage'] = str_replace('_low.', '.', $product['lowQualityImage']);
}

// Include any additional information needed by the front-end
if (isset($categoryName)) {
    $response['categoryName'] = $categoryName;
}

echo json_encode($response);
exit;
?>
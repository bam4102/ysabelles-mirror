<?php
header('Content-Type: application/json');
require_once '../assets/controllers/db.php';

try {
    $stmt = $pdo->query('SELECT * FROM category ORDER BY name');
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize categories by type
    $categorized = [
        'WEDDING' => [],
        'WOMENSWEAR' => [],
        'MENSWEAR' => [],
        'BOYS' => [],
        'GIRLS' => [],
        'ACCESSORIES' => []
    ];
    
    foreach ($categories as $category) {
        $name = strtolower($category['name'] ?? '');
        
        if (strpos($name, 'bridal') !== false || strpos($name, 'wedding') !== false || strpos($name, 'bride') !== false) {
            $categorized['WEDDING'][] = $category;
        } elseif (strpos($name, 'women') !== false || strpos($name, 'dress') !== false || strpos($name, 'gown') !== false) {
            $categorized['WOMENSWEAR'][] = $category;
        } elseif (strpos($name, 'men') !== false || strpos($name, 'suit') !== false || strpos($name, 'tuxedo') !== false) {
            $categorized['MENSWEAR'][] = $category;
        } elseif (strpos($name, 'boy') !== false) {
            $categorized['BOYS'][] = $category;
        } elseif (strpos($name, 'girl') !== false) {
            $categorized['GIRLS'][] = $category;
        } elseif (strpos($name, 'accessory') !== false || strpos($name, 'accessories') !== false || 
                 strpos($name, 'jewelry') !== false || strpos($name, 'veil') !== false) {
            $categorized['ACCESSORIES'][] = $category;
        } else {
            $categorized['WOMENSWEAR'][] = $category;
        }
    }

    echo json_encode([
        'success' => true,
        'categories' => $categorized
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch categories'
    ]);
}

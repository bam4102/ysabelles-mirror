<?php
// Enable output compression if not already enabled at server level
if (!ini_get('zlib.output_compression')) {
    ini_set('zlib.output_compression', 'On');
    ini_set('zlib.output_compression_level', 6);
}

// Set content type to JSON and charset
header('Content-Type: application/json; charset=utf-8');

// Start output buffering for compression
ob_start("ob_gzhandler");

// Set error reporting for API
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Include database connection
include '../assets/controllers/db.php';

// Start measuring execution time
$startTime = microtime(true);

try {
    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $itemsPerPage = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $itemsPerPage;

    // Determine if this is a minimal request (only basic fields)
    $minimal = isset($_GET['minimal']) && $_GET['minimal'] === 'true';

    // Basic select fields for all queries - optimized to return only what's needed
    $baseFields = $minimal ? 
        "p.productID, p.nameProduct, p.priceProduct, " : 
        "p.productID, p.nameProduct, p.priceProduct, p.typeProduct, p.colorProduct, p.entourageID, p.sizeProduct, ";

    // Build query conditions
    $conditions = [];
    $params = [];

    // Category filter
    if (!empty($_GET['type'])) {
        $conditions[] = "p.typeProduct = ?";
        $params[] = $_GET['type'];
    }

    // Entourage filter
    if (!empty($_GET['entourage'])) {
        $conditions[] = "e.nameEntourage LIKE ?";
        $params[] = "%" . $_GET['entourage'] . "%";
    }

    // Search filter
    if (!empty($_GET['search'])) {
        $searchTerm = $_GET['search'];
        $conditions[] = "(
            p.nameProduct LIKE ? OR 
            pc.productCategory LIKE ? OR
            e.nameEntourage LIKE ?
        )";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
    }

    // Advanced filters
    $numericRangeFields = [
        'bust' => 'p.bustProduct',
        'waist' => 'p.waistProduct',
        'length' => 'p.lengthProduct',
    ];

    $textSearchFields = [
        'color' => 'p.colorProduct',
        'location' => 'p.locationProduct',
        'size' => 'p.sizeProduct'
    ];

    // Add range field conditions - optimized with FORCE INDEX hints
    foreach ($numericRangeFields as $field => $column) {
        if (isset($_GET[$field.'_min']) || isset($_GET[$field.'_max'])) {
            $min = isset($_GET[$field.'_min']) ? floatval($_GET[$field.'_min']) : 0;
            $max = isset($_GET[$field.'_max']) ? floatval($_GET[$field.'_max']) : 9999;
            
            // Optimized extraction and comparison
            $conditions[] = "CAST(REGEXP_REPLACE($column, '[^0-9.]', '') AS DECIMAL(10,2)) BETWEEN ? AND ?";
            $params[] = $min;
            $params[] = $max;
        }
    }

    // Add text field conditions
    foreach ($textSearchFields as $field => $column) {
        if (!empty($_GET[$field])) {
            $conditions[] = "$column LIKE ?";
            $params[] = "%" . $_GET[$field] . "%";
        }
    }

    // Generate a unique cache key based on the query parameters
    $cacheKey = md5(json_encode([
        'conditions' => $conditions,
        'params' => $params,
        'page' => $page,
        'limit' => $itemsPerPage,
        'minimal' => $minimal
    ]));
    
    // Set HTTP caching headers
    $etag = '"' . $cacheKey . '"';
    header("ETag: $etag");
    header("Cache-Control: max-age=60, public"); // 1 minute cache
    header("Expires: " . gmdate("D, d M Y H:i:s", time() + 60) . " GMT");
    
    // Check if client has a valid cached version
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
        header("HTTP/1.1 304 Not Modified");
        exit;
    }

    // First, run a fast count query with query hints to get total products
    $totalQuery = "SELECT SQL_CALC_FOUND_ROWS COUNT(*) 
                  FROM product p FORCE INDEX (PRIMARY)
                  LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID
                  LEFT JOIN entourage e ON p.entourageID = e.entourageID";
    
    // Add WHERE conditions if any exist
    if ($conditions) {
        $totalQuery .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $totalStmt = $pdo->prepare($totalQuery);
    $totalStmt->execute($params);
    $totalProducts = $totalStmt->fetchColumn();
    $totalPages = ceil($totalProducts / $itemsPerPage);

    // Now run the main query with only the necessary fields and optimized JOINs
    // Include STRAIGHT_JOIN hint to force join order for optimization
    $mainQuery = "SELECT STRAIGHT_JOIN 
                    {$baseFields}
                    COALESCE(
                        (SELECT pictureLocation FROM picture 
                         WHERE productID = p.productID 
                         ORDER BY dateAdded DESC, pictureID ASC
                         LIMIT 1),
                        'assets/img/default_low.jpg'
                    ) as image,
                    pc.productCategory
                  FROM product p
                  LEFT JOIN productcategory pc ON p.typeProduct = pc.categoryCode
                  LEFT JOIN entourage e ON p.entourageID = e.entourageID";
    
    // Add WHERE conditions if any exist
    if ($conditions) {
        $mainQuery .= " WHERE " . implode(" AND ", $conditions);
    }
    
    // Add explicit ORDER BY and LIMIT for pagination
    $mainQuery .= " ORDER BY p.productID DESC LIMIT ? OFFSET ?";
    
    // Add pagination parameters to param array
    $params[] = $itemsPerPage;
    $params[] = $offset;
    
    $mainStmt = $pdo->prepare($mainQuery);
    $mainStmt->execute($params);
    $products = $mainStmt->fetchAll(PDO::FETCH_ASSOC);

    // If products are found, prepare the batch image loading
    if (!empty($products) && !$minimal) {
        // Extract all product IDs for batch image loading
        $productIds = array_column($products, 'productID');
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        
        // Batch load all product images in a single query
        $imageQuery = "SELECT productID, pictureLocation 
                      FROM picture 
                      WHERE productID IN ($placeholders)
                      ORDER BY productID, dateAdded DESC";
        
        $imageStmt = $pdo->prepare($imageQuery);
        $imageStmt->execute($productIds);
        $images = $imageStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group images by product ID
        $productImages = [];
        foreach ($images as $image) {
            $pid = $image['productID'];
            if (!isset($productImages[$pid])) {
                $productImages[$pid] = [];
            }
            $productImages[$pid][] = $image['pictureLocation'];
        }
        
        // Add image arrays to each product
        foreach ($products as &$product) {
            $pid = $product['productID'];
            $product['images'] = isset($productImages[$pid]) ? $productImages[$pid] : [];
        }
    }

    // Calculate execution time
    $executionTime = microtime(true) - $startTime;
    
    // Prepare API response
    $response = [
        'success' => true,
        'totalProducts' => $totalProducts,
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'products' => $products,
        'hasMore' => $page < $totalPages,
        'executionTime' => round($executionTime * 1000, 2) . 'ms', // in milliseconds
    ];
    
    // Return the response
    echo json_encode($response);
    
    // End output buffering and flush
    ob_end_flush();
    
} catch (Exception $e) {
    // Handle errors
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?>

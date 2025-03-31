<?php
// Add error reporting at the beginning of the file
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

include 'db.php';
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

// if (!isset($_SESSION['user'])) {
//     header("Location: login_page.php");
//     exit;
// }

// Process category filtering from URL
$categoryFilter = null;
$categoryName = null;

// Extract category from URL pattern
$requestUri = $_SERVER['REQUEST_URI'];
$urlPatterns = [
    '/\/wedding-gown/' => ['type' => 'BRG', 'name' => 'Bridal Gown'],
    '/\/entourage/' => ['type' => 'EN', 'name' => 'Entourage'],
    '/\/entourage\/([^\/]+)/' => ['filter' => 'entourage'],
    '/\/bridesmaid/' => ['type' => 'BM', 'name' => 'Brides Maid'],
    '/\/wedding\/([^\/]+)/' => ['section' => 'WEDDING'],
    '/\/womens\/([^\/]+)/' => ['section' => 'WOMENSWEAR'],
    '/\/mens\/([^\/]+)/' => ['section' => 'MENSWEAR'],
    '/\/boys\/([^\/]+)/' => ['section' => 'BOYS'],
    '/\/girls\/([^\/]+)/' => ['section' => 'GIRLS'],
    '/\/accessories\/([^\/]+)/' => ['section' => 'ACCESSORIES']
];

foreach ($urlPatterns as $pattern => $filterInfo) {
    if (preg_match($pattern, $requestUri, $matches)) {
        if (isset($filterInfo['type'])) {
            // Direct category code filter (like BRG)
            $categoryFilter = ['type' => $filterInfo['type']];
            $categoryName = $filterInfo['name'];
        } else if (isset($filterInfo['filter']) && $filterInfo['filter'] == 'entourage' && isset($matches[1])) {
            // Entourage name filter
            $entourageName = str_replace('-', ' ', $matches[1]);
            $conditions[] = "e.nameEntourage LIKE ?";
            $params[] = "%" . $entourageName . "%";
            $categoryName = "Entourage: " . $entourageName;
        } else if (isset($filterInfo['section']) && isset($matches[1])) {
            // Section + slug filter (needs to be converted back to category name)
            $categorySlug = $matches[1];
            $categoryName = str_replace('-', ' ', $categorySlug);
            
            // Look up the category code based on the name and section
            $stmt = $pdo->prepare("
                SELECT categoryCode 
                FROM productcategory 
                WHERE genderCategory = ? AND LOWER(productCategory) = LOWER(?)
            ");
            $stmt->execute([$filterInfo['section'], $categoryName]);
            $categoryData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($categoryData) {
                $categoryFilter = ['type' => $categoryData['categoryCode']];
                // Use proper case from database
                $stmt = $pdo->prepare("SELECT productCategory FROM productcategory WHERE categoryCode = ?");
                $stmt->execute([$categoryData['categoryCode']]);
                $properName = $stmt->fetchColumn();
                if ($properName) {
                    $categoryName = $properName;
                }
            }
        }
        break;
    }
}

// Alternative GET parameter approach
if (!empty($_GET['type'])) {
    $categoryFilter = ['type' => $_GET['type']];
    
    // Get category name for display using traditional if/else approach
    $stmt = $pdo->prepare("SELECT productCategory FROM productcategory WHERE categoryCode = ?");
    $stmt->execute([$_GET['type']]);
    
    // Simple approach that avoids all ternary operators
    $categoryName = $stmt->fetchColumn();
    if ($categoryName === false) {
        $categoryName = 'Products';
    }
}

// Check if we should group products by size variations
$groupProducts = true; // Force grouped variations for homesearch

// Process date filtering parameters
function getDateRangeParams() {
    $dateFrom = !empty($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d');
    $dateTo = !empty($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d', strtotime('+30 days'));
    
    // Apply date allowance if option is checked
    if (isset($_GET['date_allowance']) && $_GET['date_allowance'] == '1') {
        $dateFrom = date('Y-m-d', strtotime($dateFrom . ' -7 days'));
        $dateTo = date('Y-m-d', strtotime($dateTo . ' +7 days'));
    }
    
    return [
        'from' => $dateFrom,
        'to' => $dateTo,
        'has_filter' => !empty($_GET['date_from']) || !empty($_GET['date_to']),
        'show_all' => isset($_GET['show_all_products']) && $_GET['show_all_products'] === '1',
        'show_unavailable' => isset($_GET['show_unavailable']) && $_GET['show_unavailable'] === '1'
    ];
}

// Get date filtering parameters
$dateParams = getDateRangeParams();

// Define default values for start_date and end_date
$dateParams['start_date'] = $dateParams['start_date'] ?? date('Y-m-d');
$dateParams['end_date'] = $dateParams['end_date'] ?? date('Y-m-d', strtotime('+30 days'));

// Start building the base query
$conditions = [];
$params = [];
$joins = [];

// Add category filter to conditions
if ($categoryFilter) {
    if (isset($categoryFilter['type'])) {
        $conditions[] = "p.typeProduct = ?";
        $params[] = $categoryFilter['type'];
    }
}

// Add entourage filter option
if (!empty($_GET['entourage'])) {
    $conditions[] = "e.nameEntourage LIKE ?";
    $params[] = "%" . $_GET['entourage'] . "%";
}

// Only apply these filters if "show all products" is not enabled
if (!$dateParams['show_all']) {
    // Add condition to exclude products in fully paid transactions not yet released/returned
    $conditions[] = "(
        NOT EXISTS (
            SELECT 1
            FROM purchase pu
            JOIN transaction t ON pu.transactionID = t.transactionID
            WHERE pu.productID = p.productID
            AND t.balanceTransaction = 0  -- Fully paid transactions
            AND NOT EXISTS (
                SELECT 1 
                FROM product_history ph
                WHERE ph.productID = pu.productID 
                AND ph.transactionID = t.transactionID
                AND (ph.action_type = 'RELEASE' OR ph.action_type = 'RETURN')
            )
        )
    )";

    // Add condition to exclude products in transactions with any payments (where balance is not equal to the original charge)
    $conditions[] = "(
        NOT EXISTS (
            SELECT 1
            FROM purchase pu
            JOIN transaction t ON pu.transactionID = t.transactionID
            WHERE pu.productID = p.productID
            AND t.balanceTransaction < t.chargeTransaction -- Transactions with any payments
            AND t.balanceTransaction > 0 -- Not fully paid yet
            AND NOT EXISTS (
                SELECT 1 
                FROM product_history ph
                WHERE ph.productID = pu.productID 
                AND ph.transactionID = t.transactionID
                AND ph.action_type = 'RETURN'
            )
        )
    )";
}

// Exclude sold products
$conditions[] = "p.soldProduct = 0";

// Exclude new products that are marked as hidden - Modified to include new products when show_all is true
if (!$dateParams['show_all']) {
    $conditions[] = "(p.isNew IS NULL OR p.isNew = 0)";
}

// Improved date filtering - only apply if not showing all products and date filter is active
if ($dateParams['has_filter'] && !$dateParams['show_all']) {
    // Create a more efficient availability check via LEFT JOIN instead of complex subqueries
    $joins[] = "
        LEFT JOIN (
            SELECT DISTINCT pu_avail.productID
            FROM purchase pu_avail
            JOIN transaction t_avail ON pu_avail.transactionID = t_avail.transactionID
            LEFT JOIN product_history ph_avail ON 
                ph_avail.productID = pu_avail.productID AND
                ph_avail.transactionID = t_avail.transactionID
            WHERE 
                (
                    -- Include products that are already in active transactions during the requested period
                    (t_avail.datePickUp <= ? AND t_avail.dateReturn >= ?) OR
                    (? <= t_avail.dateReturn AND ? >= t_avail.datePickUp)
                ) AND
                -- Only consider transactions that are not completed (bond not returned)
                t_avail.bondStatus IN (0, 1) AND
                -- Only consider transactions where the product hasn't been returned yet
                (ph_avail.action_type IS NULL OR 
                 (ph_avail.action_type = 'RELEASE' AND NOT EXISTS (
                    SELECT 1 
                    FROM product_history ph_return
                    WHERE 
                        ph_return.productID = pu_avail.productID AND
                        ph_return.transactionID = t_avail.transactionID AND
                        ph_return.action_type = 'RETURN'
                 ))
                )
        ) AS unavailable_by_date ON p.productID = unavailable_by_date.productID
    ";
    
    // Add date parameters only once
    $params[] = $dateParams['to'];
    $params[] = $dateParams['from'];
    $params[] = $dateParams['from'];
    $params[] = $dateParams['to'];
    
    // If we're not showing unavailable products, add condition to exclude them
    if (!$dateParams['show_unavailable']) {
        $conditions[] = "unavailable_by_date.productID IS NULL";  
    }
    
    // Add condition for product size variations within date range
    $joins[] = "
        LEFT JOIN (
            SELECT psv.product_id
            FROM product_size_variations psv
            JOIN product p ON psv.product_id = p.productID
            WHERE psv.created_at BETWEEN ? AND ?
        ) AS variation_availability ON p.productID = variation_availability.product_id
    ";
    
    $params[] = $dateParams['start_date'];
    $params[] = $dateParams['end_date'];
}

// Ensure unique table alias for 'unavailable_products'
if ($dateParams['has_filter'] && !$dateParams['show_all']) {
    // Create a more efficient availability check via LEFT JOIN instead of complex subqueries
    $joins[] = "
        LEFT JOIN (
            SELECT psv.product_id
            FROM product_size_variations psv
            JOIN product p ON psv.product_id = p.productID
            WHERE psv.created_at BETWEEN '{$dateParams['start_date']}' AND '{$dateParams['end_date']}'
        ) AS unavailable_variations ON p.productID = unavailable_variations.product_id
    ";
}

if ($dateParams['has_filter'] && !$dateParams['show_all']) {
    // Create a more efficient availability check via LEFT JOIN instead of complex subqueries
    $joins[] = "
        LEFT JOIN (
            SELECT psv.product_id
            FROM product_size_variations psv
            JOIN product p ON psv.product_id = p.productID
            WHERE psv.created_at BETWEEN '{$dateParams['start_date']}' AND '{$dateParams['end_date']}'
        ) AS unavailable_products ON p.productID = unavailable_products.product_id
    ";
}

// Main search with category consideration
if (!empty($_GET['navbarSearch'])) {
    $searchTerm = $_GET['navbarSearch'];
    $conditions[] = "(
        p.nameProduct LIKE ? OR 
        EXISTS (
            SELECT 1 
            FROM productcategory pc2 
            WHERE pc2.categoryCode = p.typeProduct 
            AND pc2.productCategory LIKE ?
        ) OR
        EXISTS (
            SELECT 1 
            FROM productcategory pc3
            WHERE p.typeProduct = pc3.categoryCode
            AND pc3.productCategory LIKE ?
        ) OR
        e.nameEntourage LIKE ? OR
        EXISTS (
            SELECT 1
            FROM entourage e2
            WHERE p.entourageID = e2.entourageID
            AND e2.nameEntourage LIKE ?
        )
    )";
    $params[] = "%$searchTerm%";  // for nameProduct
    $params[] = "%$searchTerm%";  // for first EXISTS clause
    $params[] = "%$searchTerm%";  // for second EXISTS clause
    $params[] = "%$searchTerm%";  // for nameEntourage direct match
    $params[] = "%$searchTerm%";  // for nameEntourage via entourageID
}

// Add an explicit condition to handle products that are in active transactions
// This specifically addresses the issue with products like Aljur size 56 (ID 227) in transaction 10
if ($dateParams['has_filter'] && !$dateParams['show_all'] && !$dateParams['show_unavailable']) {
    $conditions[] = "NOT EXISTS (
        SELECT 1
        FROM purchase pu 
        JOIN transaction t ON pu.transactionID = t.transactionID
        WHERE pu.productID = p.productID 
        AND t.bondStatus IN (0, 1)
        AND (
            (t.datePickUp <= ? AND t.dateReturn >= ?) OR
            (? <= t.dateReturn AND ? >= t.datePickUp)
        )
        AND NOT EXISTS (
            SELECT 1 FROM product_history ph 
            WHERE ph.productID = pu.productID 
            AND ph.transactionID = t.transactionID 
            AND ph.action_type = 'RETURN'
        )
    )";
    
    // Add the date parameters
    $params[] = $dateParams['to'];
    $params[] = $dateParams['from'];
    $params[] = $dateParams['from'];
    $params[] = $dateParams['to'];
}

// Modified advanced search fields - separate numeric ranges from text fields
$numericRangeFields = [
    'bust' => 'p.bustProduct',
    'waist' => 'p.waistProduct',
    'length' => 'p.lengthProduct',
];

$textSearchFields = [
    'color' => 'p.colorProduct',
    'location' => 'p.locationProduct',
    'type' => 'p.typeProduct',
    'size' => 'p.sizeProduct'
];

// Combine all search fields for the sidebar form
$searchFields = array_merge($numericRangeFields, $textSearchFields);

// Get accurate min/max values for range fields from database
$rangeMinMax = [];
foreach ($numericRangeFields as $field => $column) {
    // Initialize with reasonable defaults that will be overridden if database values are found
    $fieldDefaults = [
        'bust' => ['min' => 30, 'max' => 45],
        'waist' => ['min' => 20, 'max' => 40],
        'length' => ['min' => 30, 'max' => 60]
    ];
    
    $min = $fieldDefaults[$field]['min'];
    $max = $fieldDefaults[$field]['max'];
    
    // Apply any category filters to make the ranges more relevant to the current view
    $filterQuery = "";
    $filterParams = [];
    
    if (isset($categoryFilter['type'])) {
        $filterQuery = " AND p.typeProduct = ?";
        $filterParams[] = $categoryFilter['type'];
    }

    try {
        // Try different methods to extract numeric values, starting with the most reliable
        
        // Method 1: Use REGEXP to extract numeric values (works in MySQL 8+)
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    MIN(CAST(REGEXP_REPLACE($column, '[^0-9.]', '') AS DECIMAL(10,2))) AS min_val,
                    MAX(CAST(REGEXP_REPLACE($column, '[^0-9.]', '') AS DECIMAL(10,2))) AS max_val
                FROM product p
                WHERE $column REGEXP '[0-9]'
                AND LENGTH(REGEXP_REPLACE($column, '[^0-9.]', '')) > 0
                AND p.soldProduct = 0
                " . ($dateParams['show_all'] ? "" : "AND (p.isNew IS NULL OR p.isNew = 0)") . "
                $filterQuery
            ");
            
            if (!empty($filterParams)) {
                $stmt->execute($filterParams);
            } else {
                $stmt->execute();
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['min_val'] !== null && $result['max_val'] !== null) {
                $min = floatval($result['min_val']);
                $max = floatval($result['max_val']);
                
                // Add a small buffer to the range for better UX
                $min = max(0, floor($min - 1));
                $max = ceil($max + 1);
            } else {
                throw new Exception("REGEXP query returned no results");
            }
        }
        // Method 2: If REGEXP is not available, try using LIKE with manual parsing
        catch (Exception $e) {
            $stmt = $pdo->prepare("
                SELECT $column
                FROM product p
                WHERE $column LIKE '%[0-9]%' 
                AND $column IS NOT NULL
                AND $column != ''
                AND p.soldProduct = 0
                " . ($dateParams['show_all'] ? "" : "AND (p.isNew IS NULL OR p.isNew = 0)") . "
                $filterQuery
            ");
            
            if (!empty($filterParams)) {
                $stmt->execute($filterParams);
            } else {
                $stmt->execute();
            }
            
            $values = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Extract numeric parts manually using PHP
            $numericValues = [];
            foreach ($values as $value) {
                if (preg_match_all('/(\d+(\.\d+)?)/', $value, $matches)) {
                    foreach ($matches[1] as $match) {
                        if ($match !== '') {
                            $numericValues[] = floatval($match);
                        }
                    }
                }
            }
            
            if (!empty($numericValues)) {
                $min = min($numericValues);
                $max = max($numericValues);
                
                // Add a small buffer to the range for better UX
                $min = max(0, floor($min - 1));
                $max = ceil($max + 1);
            } else {
                // Use the field-specific defaults we initialized earlier
                // We've already set these at the beginning of the function
            }
        }
    } catch (Exception $e) {
        // Log the error for debugging
        error_log("Error calculating measurement range for $field: " . $e->getMessage());
        // Default values are already set
    }
    
    // Get current min/max values from URL params or use calculated values
    $currentMin = isset($_GET[$field.'_min']) ? floatval($_GET[$field.'_min']) : $min;
    $currentMax = isset($_GET[$field.'_max']) ? floatval($_GET[$field.'_max']) : $max;
    
    // Ensure minimum and maximum are not equal (for slider to work properly)
    if ($min == $max) {
        $min = max(0, $min - 5);
        $max = $max + 5;
    }
    
    // Store all values in the array
    $rangeMinMax[$field] = [
        'min' => $min,
        'max' => $max,
        'current_min' => $currentMin,
        'current_max' => $currentMax
    ];
}

// Add range field conditions
foreach ($numericRangeFields as $field => $column) {
    if (isset($_GET[$field.'_min']) || isset($_GET[$field.'_max'])) {
        $min = isset($_GET[$field.'_min']) ? floatval($_GET[$field.'_min']) : $rangeMinMax[$field]['min'];
        $max = isset($_GET[$field.'_max']) ? floatval($_GET[$field.'_max']) : $rangeMinMax[$field]['max'];
        
        // Only add condition if the range is not the full range
        if ($min > $rangeMinMax[$field]['min'] || $max < $rangeMinMax[$field]['max']) {
            // Extract numeric part and compare as numbers
            $conditions[] = "CAST(REGEXP_REPLACE($column, '[^0-9.]', '') AS DECIMAL(10,2)) BETWEEN ? AND ?";
            $params[] = $min;
            $params[] = $max;
        }
    }
}

// Add text field conditions
foreach ($textSearchFields as $field => $column) {
    if (!empty($_GET[$field])) {
        $conditions[] = "$column LIKE ?";
        $params[] = "%" . $_GET[$field] . "%";
    }
}

// Build the base query with the standard joins
$baseQuery = "
    SELECT DISTINCT p.*, pc.productCategory, pc.categoryCode, pc.genderCategory, e.nameEntourage,
           psv.group_id, COUNT(psv2.product_id) OVER (PARTITION BY psv.group_id) as variation_count,
           " . ($dateParams['has_filter'] && !$dateParams['show_all'] ? "CASE WHEN unavailable_by_date.productID IS NOT NULL THEN 1 ELSE 0 END as is_unavailable" : "0 as is_unavailable") . "
    FROM product p 
    LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID
    LEFT JOIN entourage e ON p.entourageID = e.entourageID
    LEFT JOIN product_size_variations psv ON p.productID = psv.product_id
    LEFT JOIN product_size_variations psv2 ON psv.group_id = psv2.group_id
";

// Add any dynamic joins for date filtering
if (!empty($joins)) {
    $baseQuery .= implode("\n", $joins);
}

// Pagination settings
$itemsPerPage = 1000;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $itemsPerPage;

// Complete base query with conditions
$completeQuery = $baseQuery;
if (!empty($conditions)) {
    $completeQuery .= " WHERE " . implode(" AND ", $conditions);
}

// Get total count using group_id for proper pagination
$totalParams = $params;
$totalQuery = "SELECT COUNT(";
if ($groupProducts) {
    $totalQuery .= "DISTINCT CASE WHEN psv.group_id IS NOT NULL THEN psv.group_id ELSE p.productID END";
} else {
    $totalQuery .= "DISTINCT p.productID";
}
$totalQuery .= ") FROM product p 
               LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID 
               LEFT JOIN entourage e ON p.entourageID = e.entourageID
               LEFT JOIN product_size_variations psv ON p.productID = psv.product_id";

// Add dynamic joins to total query
if (!empty($joins)) {
    $totalQuery .= implode("\n", $joins);
}

if (!empty($conditions)) {
    $totalQuery .= " WHERE " . implode(" AND ", $conditions);
}

$totalStmt = $pdo->prepare($totalQuery);
$totalStmt->execute($totalParams);
$totalProducts = $totalStmt->fetchColumn();
$totalPages = ceil($totalProducts / $itemsPerPage);

// Set current_page for use in pagination
$current_page = $page;

// Ensure current page is within valid range
if ($current_page > $totalPages && $totalPages > 0) {
    $current_page = 1;
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . '?' . http_build_query(array_merge($_GET, ['page' => 1])));
    exit;
}

// Modify the query based on whether we're grouping products
if ($groupProducts) {
    // The critical fix: When show_all is true, we need to include ALL products in the CTE,
    // not just the ones that pass the filters. We can still filter the final result.
    $baseQuery = "
        SELECT 
            p.*, 
            pc.productCategory, 
            pc.categoryCode, 
            pc.genderCategory, 
            e.nameEntourage,
            psv.group_id,
            (
                SELECT COUNT(*) 
                FROM product_size_variations psv2 
                WHERE psv2.group_id = psv.group_id
            ) as variation_count,
            " . ($dateParams['has_filter'] && !$dateParams['show_all'] ? "CASE WHEN unavailable_by_date.productID IS NOT NULL THEN 1 ELSE 0 END as is_unavailable" : "0 as is_unavailable") . "
        FROM product p 
        LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID
        LEFT JOIN entourage e ON p.entourageID = e.entourageID
        LEFT JOIN product_size_variations psv ON p.productID = psv.product_id
        " . (!empty($joins) ? implode("\n", $joins) : "");

    if (!empty($conditions)) {
        $baseQuery .= " WHERE " . implode(" AND ", $conditions);
    }

    $query = "
        WITH AllProducts AS (
            " . $baseQuery . "
        ),
        GroupProducts AS (
            SELECT 
                group_id,
                MIN(productID) as main_product_id
            FROM AllProducts
            WHERE group_id IS NOT NULL AND group_id > 0
            GROUP BY group_id
        )
        SELECT 
            ap.*,
            (
                SELECT GROUP_CONCAT(DISTINCT p_sizes.sizeProduct SEPARATOR ', ') 
                FROM product p_sizes 
                JOIN product_size_variations psv_sizes ON p_sizes.productID = psv_sizes.product_id 
                WHERE psv_sizes.group_id = ap.group_id 
                " . ($dateParams['show_all'] ? "" : "AND (p_sizes.isNew IS NULL OR p_sizes.isNew = 0)") . "
                AND p_sizes.sizeProduct IS NOT NULL AND p_sizes.sizeProduct != ''
            ) as size_options,
            (
                SELECT MIN(p_price.priceProduct) 
                FROM product p_price 
                JOIN product_size_variations psv_price ON p_price.productID = psv_price.product_id
                WHERE psv_price.group_id = ap.group_id
                " . ($dateParams['show_all'] ? "" : "AND (p_price.isNew IS NULL OR p_price.isNew = 0)") . "
            ) as min_price,
            (
                SELECT MAX(p_price.priceProduct) 
                FROM product p_price 
                JOIN product_size_variations psv_price ON p_price.productID = psv_price.product_id
                WHERE psv_price.group_id = ap.group_id
                " . ($dateParams['show_all'] ? "" : "AND (p_price.isNew IS NULL OR p_price.isNew = 0)") . "
            ) as max_price
        FROM AllProducts ap
        WHERE 
            (ap.group_id IS NULL OR ap.group_id = 0 OR ap.productID IN (SELECT main_product_id FROM GroupProducts))
        ORDER BY ap.productID DESC
        LIMIT " . ((int)$itemsPerPage) . " OFFSET " . ((int)$offset);
} else {
    $query = $completeQuery;
    $query .= " ORDER BY p.productID DESC LIMIT " . ((int)$itemsPerPage) . " OFFSET " . ((int)$offset);
}

// Execute the paginated query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$paginatedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ensure current page is within valid range
if ($page > $totalPages && $totalPages > 0) {
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . '?' . http_build_query(array_merge($_GET, ['page' => 1])));
    exit;
}

// After fetching products, prepare them for display
// For grouped products, get all variations in each group
if ($groupProducts) {
    $groupedProducts = [];
    $processedGroups = [];
    
    foreach ($paginatedProducts as $product) {
        $groupId = $product['group_id'];
        
        // If no group_id or we've already processed this group, show the product individually
        if ((!$groupId || $groupId == 0) || isset($processedGroups[$groupId])) {
            if (!$groupId || $groupId == 0) {
                // Add individual products that don't belong to a group
                $groupedProducts[] = $product;
            }
            continue;
        }
        
        // Mark this group as processed
        $processedGroups[$groupId] = true;
        
        // Add special handling for specific products in active transactions
        // Get all products in this group, considering if they're in active transactions
        // CRITICAL FIX: When show_all is true, do not filter by isNew
        $stmt = $pdo->prepare("
            SELECT p.*, pc.productCategory,
                   CASE WHEN exists_in_transaction.productID IS NOT NULL THEN 1 ELSE 0 END as in_transaction
            FROM product p
            LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID
            LEFT JOIN product_size_variations psv ON p.productID = psv.product_id
            LEFT JOIN (
                SELECT DISTINCT pu.productID
                FROM purchase pu
                JOIN transaction t ON pu.transactionID = t.transactionID
                WHERE t.bondStatus IN (0, 1)
                AND NOT EXISTS (
                    SELECT 1 FROM product_history ph 
                    WHERE ph.productID = pu.productID 
                    AND ph.transactionID = t.transactionID 
                    AND ph.action_type = 'RETURN'
                )
                AND " . ($dateParams['has_filter'] ? "(
                    (t.datePickUp <= ? AND t.dateReturn >= ?) OR
                    (? <= t.dateReturn AND ? >= t.datePickUp)
                )" : "1=1") . "
            ) as exists_in_transaction ON p.productID = exists_in_transaction.productID
            WHERE psv.group_id = ?
            " . ($dateParams['show_all'] ? "" : "AND (p.isNew IS NULL OR p.isNew = 0)") . "
            ORDER BY 
                CASE 
                    WHEN p.sizeProduct REGEXP '^[0-9]+$' THEN CAST(p.sizeProduct AS UNSIGNED)
                    ELSE 999
                END,
                p.sizeProduct,
                p.priceProduct
        ");
        
        if ($dateParams['has_filter']) {
            $stmt->execute([
                $dateParams['to'],
                $dateParams['from'],
                $dateParams['from'],
                $dateParams['to'],
                $groupId
            ]);
        } else {
            $stmt->execute([$groupId]);
        }
        
        $variations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If filtering by date is active and we're not showing all products,
        // filter out variations that are in active transactions during the selected date range
        if ($dateParams['has_filter'] && !$dateParams['show_all'] && !$dateParams['show_unavailable']) {
            $variations = array_filter($variations, function($var) {
                return $var['in_transaction'] == 0;
            });
        }
        
        // Store the variations with the original product
        if (!empty($variations)) {
            $product['variations'] = $variations;
            $groupedProducts[] = $product;
        } else if ($dateParams['show_all']) {
            // CRITICAL FIX: Even if no variations are found with the current filter,
            // if show_all is enabled, we should still get the variations without the filter
            $stmt = $pdo->prepare("
                SELECT p.*, pc.productCategory
                FROM product p
                LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID
                LEFT JOIN product_size_variations psv ON p.productID = psv.product_id
                WHERE psv.group_id = ?
                ORDER BY 
                    CASE 
                        WHEN p.sizeProduct REGEXP '^[0-9]+$' THEN CAST(p.sizeProduct AS UNSIGNED)
                        ELSE 999
                    END,
                    p.sizeProduct,
                    p.priceProduct
            ");
            $stmt->execute([$groupId]);
            $variations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($variations)) {
                $product['variations'] = $variations;
                $groupedProducts[] = $product;
            }
        }
    }
    
    // Replace the original products array with our grouped version if we have grouped products
    if (!empty($groupedProducts)) {
        $paginatedProducts = $groupedProducts;
    }
}

// Get all entourage sets if we're on the entourage page
$entourageSets = [];
if (preg_match('/\/entourage$/', $requestUri)) {
    $entourageQuery = "SELECT DISTINCT e.entourageID, e.nameEntourage, COUNT(p.productID) as productCount
                      FROM entourage e
                      LEFT JOIN product p ON e.entourageID = p.entourageID
                      WHERE (p.isNew IS NULL OR p.isNew = 0)
                      GROUP BY e.entourageID, e.nameEntourage
                      ORDER BY e.nameEntourage";
    $stmt = $pdo->query($entourageQuery);
    $entourageSets = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Prepare carousel products (first 9 products)
$carouselProducts = array_slice($paginatedProducts, 0, 9);

// Improved function to get all pictures for a product with DISTINCT to prevent duplicates
function getPictures($pdo, $productID) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT pictureLocation 
        FROM picture 
        WHERE productID = ? 
        ORDER BY dateAdded ASC, pictureID ASC
    ");
    $stmt->execute([$productID]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Direct function to get only the first picture - more efficient
function getFirstPicture($pdo, $productID) {
    $stmt = $pdo->prepare("
        SELECT pictureLocation 
        FROM picture 
        WHERE productID = ? 
        ORDER BY dateAdded ASC, pictureID ASC
        LIMIT 1
    ");
    $stmt->execute([$productID]);
    $picture = $stmt->fetchColumn();
    
    // Return default image if no picture found
    return $picture ?: 'assets/img/default.jpg';
}

// Function to get the low-quality version of the first picture
function getLowQualityPicture($pdo, $productID) {
    $picture = getFirstPicture($pdo, $productID);
    
    if ($picture === 'assets/img/default.jpg') {
        return 'assets/img/default_low.jpg';
    }
    
    return str_replace('high', 'low', $picture);
}

// Function to get the first picture for an entourage
function getEntouragePicture($pdo, $entourageID) {
    $stmt = $pdo->prepare("
        SELECT pictureLocation 
        FROM picture 
        WHERE entourageID = ? 
        ORDER BY dateAdded ASC 
        LIMIT 1
    ");
    $stmt->execute([$entourageID]);
    return $stmt->fetchColumn() ?: 'default.jpg';
}

// Function to get all variations for a specific group
function getProductVariations($pdo, $groupId) {
    if (!$groupId) return [];
    
    // Get show_all parameter value
    $show_all = isset($_GET['show_all_products']) && $_GET['show_all_products'] === '1';
    
    $stmt = $pdo->prepare("
        SELECT p.*, pc.productCategory
        FROM product p
        LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID
        LEFT JOIN product_size_variations psv ON p.productID = psv.product_id
        WHERE psv.group_id = ?
        " . ($show_all ? "" : "AND (p.isNew IS NULL OR p.isNew = 0)") . "
        ORDER BY 
            CASE 
                WHEN p.sizeProduct REGEXP '^[0-9]+$' THEN CAST(p.sizeProduct AS UNSIGNED)
                ELSE 999
            END,
            p.sizeProduct,
            p.priceProduct
    ");
    $stmt->execute([$groupId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Generate autocomplete suggestions
$suggestionFields = [
    'name' => 'SELECT DISTINCT nameProduct FROM product WHERE (isNew IS NULL OR isNew = 0)',
    'length' => 'SELECT DISTINCT lengthProduct FROM product WHERE (isNew IS NULL OR isNew = 0)',
    'bust' => 'SELECT DISTINCT bustProduct FROM product WHERE (isNew IS NULL OR isNew = 0)',
    'waist' => 'SELECT DISTINCT waistProduct FROM product WHERE (isNew IS NULL OR isNew = 0)',
    'color' => 'SELECT DISTINCT colorProduct FROM product WHERE (isNew IS NULL OR isNew = 0)',
    'location' => 'SELECT DISTINCT locationProduct FROM product WHERE (isNew IS NULL OR isNew = 0)',
    'type' => 'SELECT DISTINCT typeProduct FROM product WHERE (isNew IS NULL OR isNew = 0)',
    'size' => 'SELECT DISTINCT sizeProduct FROM product WHERE (isNew IS NULL OR isNew = 0)'
];

$suggestions = [];
foreach ($suggestionFields as $key => $sql) {
    $stmt = $pdo->query($sql . " ORDER BY 1 ASC LIMIT 5");
    $suggestions[$key] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Add entourage suggestions
$stmt = $pdo->query("SELECT DISTINCT nameEntourage FROM entourage ORDER BY nameEntourage ASC LIMIT 5");
$suggestions['entourage'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

ob_end_flush();
?>

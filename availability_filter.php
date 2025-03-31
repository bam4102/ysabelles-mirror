<?php
// availability_filter.php - Direct SQL query approach for availability filtering

// Include database connection
require_once 'assets/controllers/db.php';

// Get date parameters from GET request
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;

// Validate dates
if (!$startDate || !$endDate || strtotime($startDate) === false || strtotime($endDate) === false) {
    // Return error if dates are invalid
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid date parameters']);
    exit;
}

// Debug log
error_log("Availability filter called with startDate: $startDate, endDate: $endDate");

try {
    // Direct SQL approach to get available products
    $sql = "
        SELECT DISTINCT p.productID
        FROM product p
        WHERE p.soldProduct = 0
        AND p.productID NOT IN (
            -- Subquery to find products that are unavailable in the date range
            SELECT DISTINCT pu.productID
            FROM purchase pu
            JOIN `transaction` t ON pu.transactionID = t.transactionID
            LEFT JOIN product_history ph ON 
                ph.productID = pu.productID AND
                ph.transactionID = t.transactionID
            WHERE 
                -- Date range overlaps
                (t.datePickUp <= ? AND t.dateReturn >= ?) 
                AND
                -- Only consider transactions that are not completed (bond not returned)
                t.bondStatus IN (0, 1)
                AND
                -- Only consider transactions where the product hasn't been returned yet
                (ph.action_type IS NULL OR 
                 (ph.action_type = 'RELEASE' AND NOT EXISTS (
                    SELECT 1 
                    FROM product_history ph_return
                    WHERE 
                        ph_return.productID = pu.productID AND
                        ph_return.transactionID = t.transactionID AND
                        ph_return.action_type = 'RETURN'
                 ))
                )
        )
    ";
    
    // Debug log
    error_log("Executing SQL: " . str_replace(["\n", "\r"], " ", $sql));
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$endDate, $startDate]);
    $availableProducts = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Debug log
    error_log("Found " . count($availableProducts) . " available products");
    
    // Get products with size variations
    $variationSql = "
        SELECT psv.product_id, psv.group_id
        FROM product_size_variations psv
        JOIN product p ON psv.product_id = p.productID
        WHERE p.soldProduct = 0
    ";
    
    $variationStmt = $pdo->prepare($variationSql);
    $variationStmt->execute();
    $variations = $variationStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group variations
    $variationGroups = [];
    foreach ($variations as $variation) {
        if (!isset($variationGroups[$variation['group_id']])) {
            $variationGroups[$variation['group_id']] = [];
        }
        $variationGroups[$variation['group_id']][] = $variation['product_id'];
    }
    
    // Find variation groups where at least one size is available
    $availableVariationGroups = [];
    foreach ($variationGroups as $groupId => $productIds) {
        foreach ($productIds as $productId) {
            if (in_array($productId, $availableProducts)) {
                $availableVariationGroups[$groupId] = true;
                break;
            }
        }
    }
    
    // Add all products from available variation groups
    $finalAvailableProducts = $availableProducts;
    foreach ($variationGroups as $groupId => $productIds) {
        if (isset($availableVariationGroups[$groupId])) {
            foreach ($productIds as $productId) {
                if (!in_array($productId, $finalAvailableProducts)) {
                    $finalAvailableProducts[] = $productId;
                }
            }
        }
    }
    
    // Debug log
    error_log("Final available products count: " . count($finalAvailableProducts));
    
    // Return results
    header('Content-Type: application/json');
    echo json_encode([
        'available' => $finalAvailableProducts,
        'startDate' => $startDate,
        'endDate' => $endDate
    ]);
    
} catch (PDOException $e) {
    // Log error
    error_log("Database error in availability_filter.php: " . $e->getMessage());
    
    // Return error
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

<?php
include '../../controllers/db.php';

$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = 15;

$baseQuery = "
    SELECT DISTINCT p.*, pc.productCategory, pc.categoryCode, pc.genderCategory, e.nameEntourage 
    FROM product p 
    LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID
    LEFT JOIN entourage e ON p.entourageID = e.entourageID
";

$conditions = [];
$params = [];

// Add search conditions if navbarSearch parameter exists
if (!empty($_GET['navbarSearch'])) {
    $searchTerm = $_GET['navbarSearch'];
    if (strtolower($searchTerm) === 'entourage') {
        $conditions[] = "e.nameEntourage IS NOT NULL";
    } else {
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
            e.nameEntourage LIKE ?
        )";
        $params = array_merge($params, 
            ["%$searchTerm%", "%$searchTerm%", "%$searchTerm%", "%$searchTerm%"]
        );
    }
}

// Build the complete query
$query = $baseQuery;
if ($conditions) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}
$query .= " ORDER BY p.productID DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Execute the query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to get the first picture for a product
function getPicture($pdo, $productID) {
    $stmt = $pdo->prepare("
        SELECT pictureLocation 
        FROM picture 
        WHERE productID = ? 
        ORDER BY dateAdded asc 
        LIMIT 1
    ");
    $stmt->execute([$productID]);
    return $stmt->fetchColumn() ?: 'default.jpg';
}

// Generate HTML for products
foreach ($products as $product) {
    $image = getPicture($pdo, $product['productID']);
    echo '<div class="grid-product-card">';
    echo '<img src="' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($product['nameProduct']) . '">';
    echo '<h3>' . htmlspecialchars($product['nameProduct']) . '</h3>';
    if (!empty($product['productCategory'])) {
        echo '<p class="product-category">' . htmlspecialchars($product['productCategory']) . '</p>';
    }
    if (!empty($product['nameEntourage'])) {
        echo '<p class="product-entourage">Entourage: ' . htmlspecialchars($product['nameEntourage']) . '</p>';
    }
    echo '</div>';
}
?>

<!-- nav_controller.php  -->

<?php
// Include database connection
include_once __DIR__ . '/db.php';

// Function to get categories by gender
function getCategoriesByGender($pdo, $gender) {
    $query = "SELECT categoryID, productCategory, categoryCode 
              FROM productcategory 
              WHERE genderCategory = ? 
              ORDER BY productCategory";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$gender]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get categories for each section
$weddingCategories    = getCategoriesByGender($pdo, 'WEDDING');
$womensCategories     = getCategoriesByGender($pdo, 'WOMENSWEAR');
$mensCategories       = getCategoriesByGender($pdo, 'MENSWEAR');
$boysCategories       = getCategoriesByGender($pdo, 'BOYS');
$girlsCategories      = getCategoriesByGender($pdo, 'GIRLS');
$accessoryCategories  = getCategoriesByGender($pdo, 'ACCESSORIES');

?>

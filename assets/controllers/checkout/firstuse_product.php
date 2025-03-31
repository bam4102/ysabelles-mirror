<?php
session_start();
include '../db.php'; // adjust path if needed

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $categoryID = $_POST['categoryID'] ?? null;
    $nameProduct = $_POST['nameProduct'] ?? '';
    $priceProduct = isset($_POST['priceProduct']) ? floatval($_POST['priceProduct']) : 0;
    $descProduct = $_POST['descProduct'] ?? ''; // Add description field

    if (!$categoryID || empty($nameProduct) || $priceProduct <= 0) {
        echo "Required fields missing or invalid.";
        exit;
    }

    // Query productcategory to get the category name.
    $sql = "SELECT productCategory FROM productcategory WHERE categoryID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$categoryID]);
    $cat = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cat) {
        echo "Invalid category.";
        exit;
    }
    $productCategory = $cat['productCategory'];

    // Generate a temporary productID.
    $newProductID = "new_" . uniqid();

    // Build new product array.
    $newProduct = [
        'productID' => $newProductID,
        'categoryID' => $categoryID,
        'nameProduct' => $nameProduct,
        'priceProduct' => $priceProduct,
        'productCategory' => $productCategory,
        'descProduct' => $descProduct,  // Include description in session
        'isNew' => 1  // Add isNew flag
    ];

    // Add the new product to the session cart.
    $_SESSION['cart'][] = $newProduct;
    
    echo "success";
    exit;
} else {
    echo "Invalid request.";
}
?>

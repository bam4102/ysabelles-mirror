<?php
session_start();
include 'auth.php';
require_once './assets/controllers/db.php';
// Check if the user session exists and is not empty
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header("Location: login_page.php");
    exit;
}

// Allow only ADMIN and INVENTORY positions to access this page
$allowedPositions = ['ADMIN', 'INVENTORY', 'SUPERADMIN'];
if (!in_array(strtoupper($_SESSION['user']['positionEmployee']), $allowedPositions)) {
    header("Location: credential_error.php");
    exit;
}

// Get the name of the currently logged-in user from the session.
$currentName = $_SESSION['user']['nameEmployee'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product List</title>
    <link rel="icon" type="image/png" href="./assets/img/sitelogo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/global.css">
    <link href="assets/css/products/products.css" rel="stylesheet">
</head>

<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
    <?php include './assets/nav/nav.php'; ?>
    <div class="container">
        <div class="title-header d-flex justify-content-between align-items-left">
            <h1>Product List</h1>
        </div>

        <div class="filters">
            <div class="filters-header">
                <h5>Filters</h5>
            </div>
            <div class="filters-body">
                <div class="row g-3">
                    <div class="col-md">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control filter-control" id="search" name="search" placeholder="Search by ID, name or code">
                    </div>
                    <div class="col-md">
                        <label for="location" class="form-label">Location</label>
                        <select name="location" id="location" class="form-select filter-control">
                            <option value="">All Locations</option>
                            <?php
                            // Fetch distinct locations from the product table
                            $locationQuery = "SELECT DISTINCT locationProduct FROM product WHERE locationProduct != '' ORDER BY locationProduct";
                            $locationStmt = $pdo->prepare($locationQuery);
                            $locationStmt->execute();

                            while ($location = $locationStmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='{$location['locationProduct']}'>{$location['locationProduct']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md">
                        <label for="gender" class="form-label">Type</label>
                        <select name="gender" id="gender" class="form-select filter-control">
                            <option value="">All Type</option>
                            <?php
                            // Fetch unique gender types from productcategory table
                            $genderQuery = "SELECT DISTINCT genderCategory FROM productcategory ORDER BY genderCategory";
                            $genderStmt = $pdo->prepare($genderQuery);
                            $genderStmt->execute();

                            while ($gender = $genderStmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='{$gender['genderCategory']}'>{$gender['genderCategory']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md">
                        <label for="category" class="form-label">Category</label>
                        <select name="category" id="category" class="form-select filter-control">
                            <option value="">All Categories</option>
                            <?php
                            // Fetch categories
                            $categoryQuery = "SELECT categoryID, productCategory FROM productcategory ORDER BY productCategory";
                            $categoryStmt = $pdo->prepare($categoryQuery);
                            $categoryStmt->execute();

                            while ($category = $categoryStmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='{$category['categoryID']}'>{$category['productCategory']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md">
                        <label for="entourage" class="form-label">Entourage</label>
                        <select name="entourage" id="entourage" class="form-select filter-control">
                            <option value="">All Entourage</option>
                            <?php
                            // Fetch entourage options
                            $entourageQuery = "SELECT entourageID, nameEntourage FROM entourage ORDER BY nameEntourage";
                            $entourageStmt = $pdo->prepare($entourageQuery);
                            $entourageStmt->execute();

                            while ($entourage = $entourageStmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='{$entourage['entourageID']}'>{$entourage['nameEntourage']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <!-- Measurements and Attributes Search Filters -->
                <div class="row g-3 mt-2">
                    <div class="col-md">
                        <input type="text" class="form-control filter-control" id="colorFilter" placeholder="Color" title="Search by color">
                    </div>
                    <div class="col-md">
                        <input type="text" class="form-control filter-control" id="sizeFilter" placeholder="Size" title="Search by size">
                    </div>
                    <div class="col-md">
                        <input type="number" class="form-control filter-control" id="bustFilter" placeholder="Bust" title="Search by bust measurement">
                    </div>
                    <div class="col-md">
                        <input type="number" class="form-control filter-control" id="waistFilter" placeholder="Waist" title="Search by waist measurement">
                    </div>
                    <div class="col-md">
                        <input type="number" class="form-control filter-control" id="lengthFilter" placeholder="Length" title="Search by length measurement">
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md">
                        <div class="form-switch">
                            <input class="form-check-input filter-control" type="checkbox" id="showVariations">
                            <label class="form-check-label" for="showVariations">
                                Group Products
                            </label>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="form-switch">
                            <input class="form-check-input filter-control" type="checkbox" id="showDamaged">
                            <label class="form-check-label" for="showDamaged">
                                <span class="badge bg-danger">Damaged</span>
                            </label>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="form-switch">
                            <input class="form-check-input filter-control" type="checkbox" id="showSold">
                            <label class="form-check-label" for="showSold">
                                <span class="badge bg-success">Sold</span>
                            </label>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="form-switch">
                            <input class="form-check-input filter-control" type="checkbox" id="showReturned">
                            <label class="form-check-label" for="showReturned">
                                <span class="badge bg-warning">Released</span>
                            </label>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="form-switch">
                            <input class="form-check-input filter-control" type="checkbox" id="showAvailable" checked>
                            <label class="form-check-label" for="showAvailable">
                                <span class="badge bg-info">Available</span>
                            </label>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="form-switch">
                            <input class="form-check-input filter-control" type="checkbox" id="showNew">
                            <label class="form-check-label" for="showNew">
                                <span class="badge status-new">New</span>
                            </label>
                        </div>
                    </div>
                    <div class="col-md text-end">
                        <button type="button" id="resetFilters" class="btn btn-secondary">Reset Filters</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Image</th>
                    <th data-sort="productID">ID</th>
                    <th data-sort="nameProduct">Name</th>
                    <th data-sort="locationProduct">Location</th>
                    <th data-sort="typeProduct">Type</th>
                    <th data-sort="sizeProduct">Size</th>
                    <th data-sort="colorProduct">Color</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="productTableBody">
                <!-- Product rows will be inserted here by JavaScript -->
            </tbody>
        </table>

        <nav aria-label="Product pagination">
            <ul class="pagination" id="pagination">
                <!-- Pagination will be generated by JavaScript -->
            </ul>
        </nav>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="editProductContent">
                    <!-- Product edit form will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="saveProductChanges">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Product Image Modal -->
    <?php include './assets/views/products/product-image-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Load product data from PHP -->
    <!-- Needs to be internal to work -->
    <script>
        <?php
        try {
            // Fetch all products with their categories
            $query = "SELECT p.*, pc.productCategory, e.nameEntourage 
                      FROM product p 
                      LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID 
                      LEFT JOIN entourage e ON p.entourageID = e.entourageID
                      ORDER BY p.productID DESC";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch product images for all products
            $productIDs = array_column($allProducts, 'productID');
            $images = [];
            $allProductImages = [];

            if (!empty($productIDs)) {
                $placeholders = implode(',', array_fill(0, count($productIDs), '?'));
                $imageQuery = "SELECT productID, pictureID, pictureLocation, isPrimary 
                               FROM picture 
                               WHERE productID IN ($placeholders) 
                               AND isActive = 1 
                               ORDER BY isPrimary DESC, dateAdded ASC";

                $imageStmt = $pdo->prepare($imageQuery);
                $imageStmt->execute($productIDs);

                while ($row = $imageStmt->fetch(PDO::FETCH_ASSOC)) {
                    // Normalize image path to ensure consistency
                    $imagePath = $row['pictureLocation'];

                    // For the primary/first image to display in table
                    if (!isset($images[$row['productID']])) {
                        $images[$row['productID']] = $imagePath;
                    }

                    // Store all images for each product
                    if (!isset($allProductImages[$row['productID']])) {
                        $allProductImages[$row['productID']] = [];
                    }

                    $allProductImages[$row['productID']][] = [
                        'id' => $row['pictureID'],
                        'url' => $imagePath,
                        'isPrimary' => $row['isPrimary']
                    ];
                }
            }

            // Fetch product size variations
            $variationGroups = [];
            $variationQuery = "SELECT psv.id, psv.group_id, psv.product_id, psv.nameProduct, p.sizeProduct  
                              FROM product_size_variations psv
                              LEFT JOIN product p ON psv.product_id = p.productID
                              ORDER BY psv.group_id, p.sizeProduct";
            $variationStmt = $pdo->prepare($variationQuery);
            $variationStmt->execute();

            while ($row = $variationStmt->fetch(PDO::FETCH_ASSOC)) {
                $groupId = $row['group_id'];
                $productId = $row['product_id'];

                if (!isset($variationGroups[$groupId])) {
                    $variationGroups[$groupId] = [];
                }

                // Store basic variation info
                $variationGroups[$groupId][] = [
                    'id' => $row['id'],
                    'product_id' => $productId,
                    'nameProduct' => $row['nameProduct'] ?: null,
                    'sizeProduct' => $row['sizeProduct'] ?: null
                ];
            }

            // Add image data and variation groups to products
            foreach ($allProducts as $key => $product) {
                $productId = $product['productID'];

                // Set primary image
                $allProducts[$key]['imageUrl'] = isset($images[$productId])
                    ? $images[$productId]
                    : './assets/img/placeholder.jpg';

                // Set all images
                $allProducts[$key]['images'] = isset($allProductImages[$productId])
                    ? $allProductImages[$productId]
                    : [['id' => 0, 'url' => './assets/img/placeholder.jpg', 'isPrimary' => 1]];

                // Set variation group
                $allProducts[$key]['variationGroupId'] = null;
                $allProducts[$key]['variations'] = [];

                // Find if this product belongs to a variation group
                foreach ($variationGroups as $groupId => $variations) {
                    foreach ($variations as $variation) {
                        if ($variation['product_id'] == $productId) {
                            $allProducts[$key]['variationGroupId'] = $groupId;
                            $allProducts[$key]['variations'] = $variations;
                            break 2; // Break out of both loops
                        }
                    }
                }
            }

            $count = count($allProducts);

            // Fetch all categories
            $categoryQuery = "SELECT categoryID, productCategory, categoryCode, genderCategory FROM productcategory ORDER BY productCategory";
            $categoryStmt = $pdo->prepare($categoryQuery);
            $categoryStmt->execute();
            $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Fetch all entourage sets
            $entourageQuery = "SELECT entourageID, nameEntourage FROM entourage ORDER BY nameEntourage";
            $entourageStmt = $pdo->prepare($entourageQuery);
            $entourageStmt->execute();
            $entourageSets = $entourageStmt->fetchAll(PDO::FETCH_ASSOC);

            // Convert to JSON for JavaScript and add debug information
            echo "window.allProducts = " . json_encode($allProducts) . ";\n";
            echo "window.categories = " . json_encode($categories) . ";\n";
            echo "window.entourageSets = " . json_encode($entourageSets) . ";\n";
            echo "console.log('PHP loaded " . $count . " products');\n";

            if ($count === 0) {
                echo "console.warn('No products were found in the database!');\n";
            }
        } catch (Exception $e) {
            echo "console.error('Database error: " . addslashes($e->getMessage()) . "');\n";
            echo "window.allProducts = [];\n";
        }
        ?>
    </script>

    <!-- Load JavaScript modules -->
    <script src="./assets/scripts/products/debug.js"></script>
    <script type="module" src="./assets/scripts/products/products.js"></script>

    <!-- Check URL parameters to see if we need to automatically open product detail modal -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Parse URL query parameters
            const urlParams = new URLSearchParams(window.location.search);
            const action = urlParams.get('action');
            const productId = urlParams.get('productId');
            
            // If this is a view action with a product ID, automatically open the modal
            if (action === 'view' && productId) {
                console.log('Opening product view for ID:', productId);
                
                // Find the product in the loaded products array
                const product = window.allProducts.find(p => p.productID == productId);
                
                if (product) {
                    // Get modal and content elements
                    const modalElement = document.getElementById('editProductModal');
                    const contentElement = document.getElementById('editProductContent');
                    
                    // Create Bootstrap modal object
                    const bootstrapModal = new bootstrap.Modal(modalElement);
                    
                    // Load the product edit form
                    fetch(`assets/controllers/products/load_product_form.php?productId=${productId}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.text();
                        })
                        .then(html => {
                            // Set modal title to indicate view mode
                            const modalTitle = document.getElementById('editProductModalLabel');
                            modalTitle.innerHTML = `View Product: ${product.nameProduct || 'Details'}`;
                            
                            // Insert the form HTML
                            contentElement.innerHTML = html;
                            
                            // Disable all form inputs to make it view-only
                            const form = contentElement.querySelector('form');
                            if (form) {
                                const formElements = form.querySelectorAll('input, select, textarea, button');
                                formElements.forEach(element => {
                                    element.disabled = true;
                                });
                            }
                            
                            // Hide save button, update close button
                            const saveButton = document.getElementById('saveProductChanges');
                            if (saveButton) {
                                saveButton.style.display = 'none';
                            }
                            
                            // Show the modal
                            bootstrapModal.show();
                        })
                        .catch(error => {
                            console.error('Error loading product view:', error);
                            contentElement.innerHTML = '<div class="alert alert-danger">Failed to load product details</div>';
                            bootstrapModal.show();
                        });
                } else {
                    console.error('Product not found:', productId);
                }
            }
        });
    </script>
</body>

</html>
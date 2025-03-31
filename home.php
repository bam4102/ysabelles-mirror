<?php
// Include the database connection and model
require_once 'assets/controllers/db.php';
require_once 'assets/models/ProductModel.php';

// Initialize product model
try {
    // Check if PDO object is valid
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception("Database connection not established or invalid");
    }

    $productModel = new ProductModel($pdo);

    // Get featured products data (top 10 by view count)
    $featuredProducts = $productModel->getProducts([
        'orderBy' => 'counterProduct',
        'sortDirection' => 'DESC',
        'limit' => 10
    ]);

    // Get all products data for grid (first 12 alphabetically)
    $allProducts = $productModel->getProducts([
        'orderBy' => 'nameProduct',
        'sortDirection' => 'ASC',
        'limit' => 0 // Remove the limit to get all products
    ]);

    // Get total product count for pagination
    $totalProductCount = $productModel->getTotalProductCount();
    
    // Get transaction history data for availability filtering
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;
    $transactions = $productModel->getTransactionHistoryForAvailability($startDate, $endDate);
    
    // Get active reservations (products already reserved)
    // We pass null parameters to get all current and future reservations
    $reservations = $productModel->getProductReservations();

    // Get categories data
    $categories = $productModel->getCategories();
    
    // PRELOAD ENTOURAGE DATA to prevent additional database connections later
    // Get all entourage sets with their products and images
    $entourageSets = $productModel->getProductEntourage([
        'limit' => 0,  // No limit - get all entourage sets
        'orderBy' => 'nameEntourage',
        'sortDirection' => 'ASC',
        'onlyActive' => true
    ]);
    
    // Prepare entourage images map for client-side caching
    $entourageImagesMap = [];
    $productImagesMap = [];
    
    // Process entourage sets to organize images by entourageID and productID
    foreach ($entourageSets as $set) {
        $entourageID = $set['entourageID'];
        
        // Store entourage images
        if (!empty($set['pictures'])) {
            $entourageImagesMap[$entourageID] = $set['pictures'];
        }
        
        // Store product images
        if (!empty($set['products'])) {
            foreach ($set['products'] as $product) {
                $productID = $product['productID'];
                if (!empty($product['pictures'])) {
                    $productImagesMap[$productID] = $product['pictures'];
                }
            }
        }
    }
    
    // Debug logs
    error_log("Featured Products Count: " . count($featuredProducts));
    error_log("All Products Count: " . count($allProducts));
    error_log("Total Product Count: " . $totalProductCount);
    error_log("Transaction data Count: " . count($transactions));
    error_log("Reservation data Count: " . count($reservations));
    error_log("Entourage Sets Count: " . count($entourageSets));
    error_log("Entourage Images Map Size: " . count($entourageImagesMap));
    error_log("Product Images Map Size: " . count($productImagesMap));

    // Test if we've actually got data
    if (empty($featuredProducts)) {
        error_log("WARNING: Featured products array is empty");
    }

    if (empty($allProducts)) {
        error_log("WARNING: All products array is empty");
    }

    // Check if total product count is consistent with query results
    if ($totalProductCount > 0 && (empty($featuredProducts) || empty($allProducts))) {
        error_log("WARNING: Inconsistency detected - Total product count is " . $totalProductCount .
            " but featured products or all products arrays are empty");
    }
} catch (Exception $e) {
    error_log("ERROR loading product data: " . $e->getMessage());
    $featuredProducts = [];
    $allProducts = [];
    $totalProductCount = 0;
    $transactions = [];
    $reservations = [];
    $categories = [];
    $entourageSets = [];
    $entourageImagesMap = [];
    $productImagesMap = [];
}

// Page title
$pageTitle = "Ysabelles";

// Make sure variables are available in the included views
$allProductData = $featuredProducts ?? [];
$allProductsData = $allProducts ?? [];

// Debug variables before sending to JavaScript
error_log("Variables prepared for views:");
error_log("allProductData count: " . count($allProductData));
error_log("allProductsData count: " . count($allProductsData));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Slick Carousel CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css" />

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/home/featured_products.css">
    <link rel="stylesheet" href="assets/css/home/all_products.css">
    <link rel="stylesheet" href="assets/css/home/product_details_popup.css">
    <link rel="stylesheet" href="assets/css/home/cart_popup.css">
    <link rel="stylesheet" href="assets/css/home/components/filter_sidebar.css">
    <link rel="stylesheet" href="assets/css/navbar_search.css">
    <link rel="stylesheet" href="assets/css/availability_filter.css">
    
    <style>
        :root {
            --sidebar-width: 25%;
            --content-width: 75%;
            --padding-standard: 15px;
            --padding-expanded: 30px;
        }
        
        body {
            overflow-x: hidden;
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 0;
            position: relative;
        }
        .container-fluid {
            padding-left: 0;
            padding-right: 0;
            max-width: 100%;
            width: 100%;
            overflow-x: hidden;
        }
        .row {
            margin-right: 0;
            margin-left: 0;
            width: 100%;
        }
        .sidebar-column {
            padding-left: 0;
        }
        .main-content {
            padding-left: var(--padding-standard);
            padding-right: var(--padding-standard);
        }
        .main-content.expanded {
            padding-left: var(--padding-expanded);
            padding-right: var(--padding-expanded);
        }
    </style>

</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-main">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="./assets/img/default_old.jpg" alt="Ysabelle's Bridal & Couture" onerror="this.src='./assets/img/placeholder.jpg'; this.onerror='';">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="http://localhost:3000/home.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">All Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="category.php?cat=bridal-gown">Bridal Gown</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="event.preventDefault(); if(typeof loadEntouragePage === 'function') { 
                            // Store entourage preloaded data in sessionStorage if available
                            if (window.entouragePreloadedData && window.entouragePreloadedData.isPreloaded) {
                                console.log('Storing entourage preloaded data in sessionStorage before navigation');
                                sessionStorage.setItem('entouragePreloadedData', JSON.stringify(window.entouragePreloadedData));
                            }
                            
                            loadEntouragePage(); 
                        } else { 
                            // Store entourage preloaded data in sessionStorage if available
                            if (window.entouragePreloadedData && window.entouragePreloadedData.isPreloaded) {
                                console.log('Storing entourage preloaded data in sessionStorage before navigation');
                                sessionStorage.setItem('entouragePreloadedData', JSON.stringify(window.entouragePreloadedData));
                            }
                            
                            window.location.href='entourage_page.php'; 
                        }">Entourage</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="category.php?cat=suit">Suit</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" id="navbarDropdown" role="button" aria-expanded="false">
                            More
                        </a>
                        <div class="categories-container" id="categoriesDropdown">
                            <div class="categories-wrapper">
                                <!-- Categories will be populated by JavaScript -->
                            </div>
                        </div>
                    </li>
                </ul>

                <form class="d-flex search-form">
                    <input class="form-control me-2" type="search" placeholder="Search products..." aria-label="Search">
                    <button class="btn btn-search" type="submit">Search</button>
                </form>

                <a href="cart.php" class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count">0</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar - Filter (3 columns) -->
            <div class="col-lg-3 sidebar-column" id="sidebarColumn">
                <div class="sidebar">
                    <?php include 'assets/views/home/components/filter_sidebar.php'; ?>
                </div>
            </div>
            
            <!-- Main Content (9 columns) -->
            <div class="col-lg-9 main-content" id="mainContent">
                <!-- Open Sidebar Button (visible when sidebar is closed) -->
                <button class="open-sidebar-btn d-none" id="openSidebarBtn">
                    <i class="fas fa-filter"></i>
                </button>
                
                <!-- Featured Products Section -->
                <?php include 'assets/views/home/featured_products.php'; ?>

                <!-- All Products Section -->
                <?php include 'assets/views/home/all_products.php'; ?>
            </div>
        </div>
    </div>
    
    <!-- Product Details Popup -->
    <?php include 'assets/views/home/product_details_popup.php'; ?>
    
    <!-- Cart Popup -->
    <?php include 'assets/views/home/cart_popup.php'; ?>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Slick Carousel JS -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>

    <!-- Pass PHP data to JavaScript -->
    <script>
        // Make product data available for JavaScript
        window.allProductData = <?php echo json_encode($allProductData); ?>;
        window.allProductsData = <?php echo json_encode($allProductsData); ?>;
        window.totalProductCount = <?php echo json_encode($totalProductCount); ?>;
        window.transactionData = <?php echo json_encode($transactions); ?>;
        window.reservationData = <?php echo json_encode($reservations); ?>;
        window.categoryData = <?php echo json_encode($categories); ?>;
        
        // Make entourage data available for JavaScript - PRELOADED DATA
        window.entouragePreloadedData = {
            sets: <?php echo json_encode($entourageSets); ?>,
            imagesMap: <?php echo json_encode($entourageImagesMap); ?>,
            productImagesMap: <?php echo json_encode($productImagesMap); ?>,
            isPreloaded: true
        };
        
        // Log details about the global data
        console.log("Global data initialized:");
        console.log("Featured products count:", window.allProductData?.length || 0);
        console.log("Featured products data:", window.allProductData || []);
        console.log("All products count:", window.allProductsData?.length || 0);
        console.log("All products data:", window.allProductsData || []);
        console.log("Total product count:", window.totalProductCount || 0);
        console.log("Transaction data count:", Object.keys(window.transactionData || {}).length);
        console.log("Reservation data count:", window.reservationData?.length || 0);
        console.log("Entourage sets count:", window.entouragePreloadedData?.sets?.length || 0);
        console.log("Entourage images map size:", Object.keys(window.entouragePreloadedData?.imagesMap || {}).length);
        console.log("Product images map size:", Object.keys(window.entouragePreloadedData?.productImagesMap || {}).length);
    </script>

    <!-- Custom JS -->
    <script src="assets/scripts/navbar_search.js"></script>
    <script src="assets/scripts/home/featured_products.js"></script>
    <script src="assets/scripts/home/all_products.js"></script>
    <script src="assets/scripts/home/product_details_popup.js"></script>
    <script src="assets/scripts/home/cart_popup.js"></script>
    <script src="assets/scripts/home/entourage_page.js"></script>
    <script src="assets/scripts/direct_availability_filter.js"></script>
    
    <!-- Check for cached entourage data when returning from entourage page -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check if we have entourage cache in sessionStorage
            const cachedEntourageData = sessionStorage.getItem('entourageCache');
            if (cachedEntourageData) {
                try {
                    console.log('Found cached entourage data in sessionStorage');
                    const parsedCache = JSON.parse(cachedEntourageData);
                    
                    // Restore to window.entourageCache if not already initialized
                    if (!window.entourageCache || !window.entourageCache.isPreloaded) {
                        window.entourageCache = parsedCache;
                        console.log('Restored entourage cache from sessionStorage');
                        console.log(`Restored ${Object.keys(parsedCache.sets || {}).length} entourage sets`);
                        console.log(`Restored ${Object.keys(parsedCache.images || {}).length} entourage image sets`);
                        console.log(`Restored ${Object.keys(parsedCache.productImages || {}).length} product image sets`);
                    }
                    
                    // Clear the sessionStorage to prevent memory issues
                    // sessionStorage.removeItem('entourageCache');
                } catch (error) {
                    console.error('Error restoring entourage cache from sessionStorage:', error);
                }
            }
        });
    </script>
</body>

</html>
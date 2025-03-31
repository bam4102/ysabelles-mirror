<?php
session_start();

include 'auth.php';
// Connect to the database.
include './assets/controllers/db.php';
include './assets/controllers/utilities_section/utilites_section_controller.php';

// If there is no logged-in user or the user's position is not "ADMIN", redirect to login_page.php.
// Check if the user session exists and is not empty
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header("Location: login_page.php");
    exit;
}

// Check if the user's position is either ADMIN, INVENTORY, or SUPERADMIN
if (
    strtoupper($_SESSION['user']['positionEmployee']) !== 'ADMIN' &&
    strtoupper($_SESSION['user']['positionEmployee']) !== 'INVENTORY' &&
    strtoupper($_SESSION['user']['positionEmployee']) !== 'SUPERADMIN'
) {
    header("Location: credential_error.php");
    exit;
}


// Get the name of the currently logged-in user from the session.
$currentName = $_SESSION['user']['nameEmployee'];

// Fetch existing categories for dropdown
$categories = $pdo->query("SELECT DISTINCT productCategory FROM productcategory ORDER BY productCategory")->fetchAll(PDO::FETCH_COLUMN);
$categories2 = $pdo->query("
    SELECT categoryID, productCategory, categoryCode, genderCategory
    FROM productcategory
    ORDER BY productCategory
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilities</title>
    <link rel="icon" type="image/png" href="./assets/img/sitelogo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="./assets/css/utilities_section.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="./assets/css/global.css">
</head>

<body>
    <div>
        <!-- Include Navigation -->
        <?php include 'assets/nav/nav.php'; ?>

        <div class="container py-4">
            <div class="title-container">
                <div class="d-flex justify-content-between align-items-center">
                    <h1>Utilities</h1>
                    <!-- Navigation Pills -->
                    <ul class="nav nav-pills" id="utilityTabs" role="tablist">
                        <li class="utility-nav-item" role="presentation">
                            <button class="utility-nav-link active" id="product-tab" data-bs-toggle="tab" data-bs-target="#product" type="button" role="tab" aria-controls="product" aria-selected="true">
                                Add Product
                            </button>
                        </li>
                        <li class="utility-nav-item" role="presentation">
                            <button class="utility-nav-link" id="category-tab" data-bs-toggle="tab" data-bs-target="#category" type="button" role="tab" aria-controls="category" aria-selected="false">
                                Categories
                            </button>
                        </li>
                        <li class="utility-nav-item" role="presentation">
                            <button class="utility-nav-link" id="entourage-tab" data-bs-toggle="tab" data-bs-target="#entourage" type="button" role="tab" aria-controls="entourage" aria-selected="false">
                                Entourages
                            </button>
                        </li>
                        <li class="utility-nav-item" role="presentation">
                            <button class="utility-nav-link" id="variation-tab" data-bs-toggle="tab" data-bs-target="#variation" type="button" role="tab" aria-controls="variation" aria-selected="false">
                                Add Variation
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="tab-content" id="utilityTabsContent">
                <!-- Add Variation Tab Content (to be added in the tab-content div) -->
                <div class="tab-pane fade" id="variation" role="tabpanel" aria-labelledby="variation-tab">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="./assets/controllers/utilities_section/utilites_section_controller.php" class="needs-validation" novalidate>
                                <!-- Add Product Type dropdown before Product Name -->
                                <div class="mb-3">
                                    <label class="form-label">Product Type</label>
                                    <select class="form-select" name="variation_typeProduct" required>
                                        <option value="">Select a product type</option>
                                        <?php
                                        // Fetch product categories
                                        $typeStmt = $pdo->query("SELECT categoryCode, productCategory FROM productcategory ORDER BY productCategory ASC");
                                        if ($typeStmt) {
                                            while ($type = $typeStmt->fetch(PDO::FETCH_ASSOC)) {
                                                echo '<option value="' . htmlspecialchars($type['categoryCode']) . '">' .
                                                    htmlspecialchars($type['productCategory']) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                    <div class="invalid-feedback">Please select a product type</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Product Name</label>
                                    <input type="text" class="form-control" name="variation_nameProduct" id="variation_nameProduct" required list="productNamesDatalist" autocomplete="off">
                                    <datalist id="productNamesDatalist">
                                        <?php
                                        // Default will be replaced dynamically when category is selected
                                        $stmt = $pdo->query("
                            SELECT DISTINCT nameProduct 
                            FROM product_size_variations 
                            ORDER BY id DESC
                            LIMIT 20
                        ");

                                        if ($stmt) {
                                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                echo '<option value="' . htmlspecialchars($row['nameProduct']) . '">';
                                            }
                                        }
                                        ?>
                                    </datalist>
                                    <div class="invalid-feedback">Please enter a product name</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Size</label>
                                    <input type="text" class="form-control" name="variation_size" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Price</label>
                                    <input type="number" class="form-control" name="variation_price" required min="0" step="0.01">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Scan Code</label>
                                    <input type="text" class="form-control" name="variation_scan_code" required>
                                    <small class="text-muted">Unique code for this variation</small>
                                </div>

                                <button type="submit" name="add_variation" class="btn btn-danger">Add Variation</button>
                            </form>


                        </div>
                    </div>
                </div>
                <!-- Add Product Tab -->
                <div class="tab-pane fade show active" id="product" role="tabpanel" aria-labelledby="product-tab">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <!-- Debug field -->
                                <input type="hidden" name="debug" value="1">
                                <div class="row mb-4">
                                    <!-- Image Gallery -->
                                    <div class="col-md-5">
                                        <div id="addProductPreviewContainer" class="d-flex flex-wrap gap-2">
                                            <div class="add-image btn btn-outline-secondary" onclick="document.getElementById('addProductFileInput').click()">
                                                + Add Image
                                            </div>
                                        </div>
                                        <input type="file" id="addProductFileInput" name="product_images[]" accept="image/*" multiple style="display: none;">
                                    </div>

                                    <!-- Product Details -->
                                    <div class="col-md-7">
                                        <div class="mb-3">
                                            <label class="form-label">Entourage:</label>
                                            <select class="form-select" name="entourage_id">
                                                <option value="">None</option>
                                                <?php
                                                $entourage_query = "SELECT entourageID, nameEntourage FROM entourage ORDER BY entourageID";
                                                $entourage_stmt = $pdo->query($entourage_query);
                                                while ($entourage = $entourage_stmt->fetch(PDO::FETCH_ASSOC)):
                                                ?>
                                                    <option value="<?php echo $entourage['entourageID']; ?>">
                                                        <?php echo htmlspecialchars($entourage['nameEntourage']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col">
                                                <label class="form-label">Product Name:</label>
                                                <input type="text" class="form-control" name="product_name" required list="productNamesList">
                                                <datalist id="productNamesList">
                                                    <?php
                                                    // Fix the query for product suggestions
                                                    $productNameQuery = $pdo->query("
                                                        SELECT DISTINCT nameProduct 
                                                        FROM product 
                                                        ORDER BY productID DESC
                                                        LIMIT 20
                                                    ");

                                                    if ($productNameQuery) {
                                                        while ($productName = $productNameQuery->fetch(PDO::FETCH_ASSOC)) {
                                                            echo '<option value="' . htmlspecialchars($productName['nameProduct']) . '">';
                                                        }
                                                    }
                                                    ?>
                                                </datalist>
                                            </div>
                                            <div class="col">
                                                <label class="form-label">Color:</label>
                                                <input type="text" class="form-control" name="product_color" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Category:</label>
                                            <select class="form-select" name="product_type" id="productCategory" required>
                                                <option value="">Select Category</option>
                                                <?php
                                                $category_query = "SELECT DISTINCT * FROM productcategory ORDER BY categoryID";
                                                $category_stmt = $pdo->query($category_query);
                                                while ($category = $category_stmt->fetch(PDO::FETCH_ASSOC)):
                                                ?>
                                                    <option value="<?php echo htmlspecialchars($category['categoryCode']); ?>"
                                                        data-gender="<?php echo htmlspecialchars($category['genderCategory']); ?>">
                                                        <?php echo htmlspecialchars($category['productCategory']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col">
                                                <label class="form-label">Location:</label>
                                                <select class="form-select" name="product_location" required>
                                                    <option value="BACOLOD CITY">Bacolod City</option>
                                                    <option value="DUMAGUETE CITY">Dumaguete City</option>
                                                    <option value="CEBU CITY">Cebu City</option>
                                                    <option value="ILOILO CITY">Iloilo City</option>
                                                    <option value="SAN CARLOS CITY">San Carlos City</option>
                                                </select>
                                            </div>
                                            <div class="col">
                                                <label class="form-label">Classification:</label>
                                                <select class="form-select" name="product_gender" id="productGender" required>
                                                    <option value="">Select Classification</option>
                                                    <option value="WOMENSWEAR">Womenswear</option>
                                                    <option value="MENSWEAR">Menswear</option>
                                                    <option value="WEDDING">Wedding</option>

                                                    <option value="BOYS">Boys</option>
                                                    <option value="GIRLS">Girls</option>
                                                    <option value="ACCESSORIES">Accessories</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Toggle for Multiple Sizes -->
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="enableMultipleSizes">
                                                <label class="form-check-label" for="enableMultipleSizes">
                                                    Enable multiple sizes & prices
                                                </label>
                                            </div>
                                        </div>
                                        <div class="row mb-3 measurement-fields">
                                            <div class="col">
                                                <label class="form-label">Size:</label>
                                                <input type="text" class="form-control single-size-input" name="product_size">
                                            </div>
                                            <div class="col">
                                                <label class="form-label">Bust:</label>
                                                <input type="text" class="form-control" name="product_bust"
                                                    pattern="^\d+(\s+\d+\/\d+)?$"
                                                    title="Enter a valid measurement (e.g., '55' or '55 1/2')">
                                            </div>
                                            <div class="col">
                                                <label class="form-label">Waist:</label>
                                                <input type="text" class="form-control" name="product_waist"
                                                    pattern="^\d+(\s+\d+\/\d+)?$"
                                                    title="Enter a valid measurement (e.g., '55' or '55 1/2')">
                                            </div>
                                            <div class="col">
                                                <label class="form-label">Length:</label>
                                                <input type="text" class="form-control" name="product_length"
                                                    pattern="^\d+(\s+\d+\/\d+)?$"
                                                    title="Enter a valid measurement (e.g., '55' or '55 1/2')">
                                            </div>
                                        </div>


                                        <div class="mb-3 single-price-input">
                                            <label class="form-label">Price:</label>
                                            <input type="number"
                                                class="form-control"
                                                name="product_price"
                                                required
                                                min="0"
                                                step="0.01">
                                        </div>

                                        <!-- Size Variations Section -->
                                        <div class="mb-3 multiple-sizes-section" style="display: none;">
                                            <label class="form-label">Size Variations:</label>
                                            <div class="card">
                                                <div class="card-body p-3">
                                                    <div id="sizeVariationsContainer">
                                                        <!-- Size rows will be added here -->
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="addSizeBtn">
                                                        <i class="bi bi-plus-circle"></i> Add Size Variation
                                                    </button>
                                                    <input type="hidden" name="size_variations" id="sizeVariationsJson">
                                                    <small class="text-muted d-block mt-2">Add different sizes with their specific prices and quantities</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Description:</label>
                                            <textarea class="form-control" name="product_description" rows="3"></textarea>
                                        </div>

                                        <div class="mb-3 single-scan-code">
                                            <label class="form-label">Scan Code</label>
                                            <input type="text" class="form-control" name="product_scan" required>
                                        </div>

                                        <div class="text-end">
                                            <button type="submit" name="add_product" class="btn btn-danger">
                                                <i class="bi bi-plus-lg"></i> Add Product
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Add Category Tab -->
                <div class="tab-pane fade" id="category" role="tabpanel" aria-labelledby="category-tab">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Category Name</label>
                                        <input type="text" class="form-control" name="category_name" required>
                                        <div class="invalid-feedback">
                                            Please provide a category name.
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Category Code</label>
                                        <input type="text" class="form-control" name="category_code"
                                            required pattern="[A-Za-z]+"
                                            oninput="this.value = this.value.toUpperCase()"
                                            placeholder="e.g., DRS for Dress">
                                        <div class="invalid-feedback">
                                            Please provide a valid category code (letters only).
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <!-- Spacer Column -->
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Classification:</label>
                                        <select class="form-select" name="genderCat" id="genderCat" required>

                                            <option value="WOMENSWEAR">Womenswear</option>

                                            <option value="MENSWEAR">Menswear</option>
                                            <option value="WEDDING">Wedding</option>

                                            <option value="BOYS">Boys</option>
                                            <option value="GIRLS">Girls</option>
                                            <option value="ACCESSORIES">Accessories</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" name="add_category" class="btn btn-danger">Add Category</button>
                            </form>

                            <!-- Display Existing Categories -->
                            <div class="mt-4">
                                <h6>Existing Categories</h6>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Category Name</th>
                                                <th>Category Code</th>
                                                <th>Category Type</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categories2 as $category): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($category['productCategory']); ?></td>
                                                    <td><code><?php echo htmlspecialchars($category['categoryCode']); ?></code></td>
                                                    <td><code><?php echo htmlspecialchars($category['genderCategory']); ?></code></td>
                                                    <td>
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-primary edit-category-btn"
                                                            data-id="<?php echo $category['categoryID']; ?>"
                                                            data-name="<?php echo htmlspecialchars($category['productCategory']); ?>"
                                                            data-code="<?php echo htmlspecialchars($category['categoryCode']); ?>"
                                                            data-classification="<?php echo htmlspecialchars($category['genderCategory']); ?>">
                                                            Edit
                                                        </button>

                                                        <a
                                                            href="./assets/controllers/utilities_section/delete_category.php?id=<?php echo $category['categoryID']; ?>"
                                                            class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Are you sure you want to delete this category?')">
                                                            Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Add Entourage Tab -->
                <div class="tab-pane fade" id="entourage" role="tabpanel" aria-labelledby="entourage-tab">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label">Entourage Name</label>
                                    <input type="text" class="form-control" name="entourage_name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Entourage Images</label>
                                    <div class="d-flex gap-3 mb-3">
                                        <div class="image-preview"></div>
                                        <div class="image-preview"></div>
                                        <div class="image-preview"></div>
                                    </div>
                                    <input type="file" class="form-control" name="entourage_images[]" multiple accept="image/*">
                                </div>
                                <button type="submit" name="add_entourage" class="btn btn-danger">Add Entourage</button>
                            </form>
                        </div>
                    </div>
                    <!-- Existing Entourage Table -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Existing Entourage</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Fetch all entourage entries
                                        $entourageList = $pdo->query("SELECT * FROM entourage ORDER BY nameEntourage ASC")
                                            ->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($entourageList as $entourage):
                                            // Count associated images
                                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM picture WHERE entourageID = :id");
                                            $stmt->execute([':id' => $entourage['entourageID']]);
                                            $imgCount = $stmt->fetchColumn();

                                            // Also fetch associated images for editing (as an array of pictureID and pictureLocation)
                                            $imgStmt = $pdo->prepare("SELECT pictureID, pictureLocation FROM picture WHERE entourageID = :id");
                                            $imgStmt->execute([':id' => $entourage['entourageID']]);
                                            $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
                                            // Encode images array for use in a data attribute
                                            $imagesData = htmlspecialchars(json_encode($images), ENT_QUOTES, 'UTF-8');
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($entourage['entourageID']); ?></td>
                                                <td><?php echo htmlspecialchars($entourage['nameEntourage']); ?></td>
                                                <td>
                                                    <button type="button"
                                                        class="btn btn-sm btn-primary edit-entourage-btn"
                                                        data-id="<?php echo $entourage['entourageID']; ?>"
                                                        data-name="<?php echo htmlspecialchars($entourage['nameEntourage']); ?>"
                                                        data-images='<?php echo $imagesData; ?>'>
                                                        Edit
                                                    </button>
                                                    <a href="./assets/controllers/utilities_section/delete_entourage.php?id=<?php echo $entourage['entourageID']; ?>"
                                                        class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Are you sure you want to delete this entourage?')">
                                                        Delete
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Entourage Modal -->
    <div class="modal fade" id="editEntourageModal" tabindex="-1" aria-labelledby="editEntourageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="editEntourageForm" method="POST" enctype="multipart/form-data">
                <!-- Pass the entourage id and a hidden field to track removed image IDs -->
                <input type="hidden" name="entourage_id" id="edit_entourage_id">
                <input type="hidden" name="removed_images" id="removed_images" value="[]">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editEntourageModalLabel">Edit Entourage</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Entourage Name -->
                        <div class="mb-3">
                            <label for="edit_entourage_name" class="form-label">Entourage Name</label>
                            <input type="text" class="form-control" id="edit_entourage_name" name="entourage_name" required>
                        </div>
                        <!-- Existing Images -->
                        <div class="mb-3">
                            <label class="form-label">Existing Images</label>
                            <div id="existingImages" class="d-flex gap-3 flex-wrap">
                                <!-- Thumbnails with a remove button will be injected here -->
                            </div>
                        </div>
                        <!-- New Images Upload -->
                        <div class="mb-3">
                            <label class="form-label">Add New Images (max 5 total)</label>
                            <input type="file" class="form-control" id="edit_entourage_images" name="entourage_images[]" multiple accept="image/*">
                            <div id="newImagePreview" class="d-flex gap-3 mt-2">
                                <!-- Previews of new images will be shown here -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_entourage" class="btn btn-primary">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editCategoryForm" method="POST">
                <input type="hidden" name="category_id" id="edit_category_id">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Category Name -->
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="category_name" id="edit_category_name" required>
                        </div>
                        <!-- Category Code -->
                        <div class="mb-3">
                            <label class="form-label">Category Code</label>
                            <input type="text" class="form-control" name="category_code" id="edit_category_code" required pattern="[A-Za-z]+"
                                oninput="this.value = this.value.toUpperCase()"
                                placeholder="e.g., DRS">
                        </div>
                        <!-- Classification -->
                        <div class="mb-3">
                            <label class="form-label">Classification</label>
                            <select class="form-select" name="genderCat" id="edit_genderCat" required>
                                <option value="WOMENSWEAR">Womenswear</option>
                                <option value="MENSWEAR">Menswear</option>
                                <option value="WEDDING">Wedding</option>

                                <option value="BOYS">Boys</option>
                                <option value="GIRLS">Girls</option>
                                <option value="ACCESSORIES">Accessories</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_category" class="btn btn-primary">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="./assets/scripts/utilities_section.js"></script>
    <!-- Removed vanta.js initialization script -->
</body>

</html>
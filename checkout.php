<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
}

include './assets/controllers/db.php';

// If the cart is not set, initialize it (though get_cart.php will handle an empty cart gracefully).
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Calculate total charge from session cart (this is optional if you'll always rely on updateCart()).
$totalCharge = 0;
foreach ($_SESSION['cart'] as $item) {
    $totalCharge += $item['priceProduct'];
}

// Set today's date.
$todaysDate = date('Y-m-d');

// Get user's location from session (assuming it's stored there after login)
// If not available, default to BACOLOD CITY
$userLocation = isset($_SESSION['userLocation']) ? $_SESSION['userLocation'] : 'BACOLOD CITY';

// Query Sales Agent: employees with "SALES" in their position.
$queryAgents = "SELECT employeeID, nameEmployee FROM employee WHERE positionEmployee LIKE '%SALES%'";
$stmtAgents = $pdo->prepare($queryAgents);
$stmtAgents->execute();
$agents = $stmtAgents->fetchAll(PDO::FETCH_ASSOC);

// Query product categories for the Add New Product modal.
$sqlCategories = "SELECT * FROM productcategory";
$stmtCategories = $pdo->prepare($sqlCategories);
$stmtCategories->execute();
$categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);

// Add input validation function
function sanitize_input($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="./assets/css/checkout.css" rel="stylesheet">
</head>

<body>
    <div class="container my-4">
        <!-- Title -->  
        <div class="checkout-title">
            <h1 class="mb-0 fw-bold">Checkout</h1>
        </div>

        <!-- Import Transaction Form -->
        <form id="importTransactionForm" class="mb-4">
            <div class="mb-3">
                <label for="transactionID" class="form-label">Import Transaction by ID</label>
                <input type="text" class="form-control" id="transactionID" name="transactionID" required>
            </div>
            <button type="submit" class="btn btn-secondary">Import Transaction</button>
        </form>



        <form id="checkoutForm" action="./assets/controllers/checkout/process_checkout.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <!-- Hidden input to store the imported transaction ID -->
            <input type="hidden" id="importedTransactionID" name="importedTransactionID" value="">
            <!-- Hidden cartID -->
            <input type="hidden" name="cartID"
                value="<?= isset($_GET['cartID']) ? htmlspecialchars($_GET['cartID']) : uniqid(); ?>">

            <!-- Hidden field for package selections -->
            <input type="hidden" name="packageSelections" id="packageSelections" value="">
            
            <!-- Hidden field for price changes -->
            <input type="hidden" name="priceChanges" id="priceChanges" value="">

            <div class="row">
                <!-- Left Column: Transaction Details -->
                <div class="col-md-6">
                    <h4>Transaction Details</h4>
                    <div class="mb-3">
                        <input type="date" class="form-control"
                            id="dateTransaction"
                            name="dateTransaction"
                            value="<?= $todaysDate; ?>"
                            disabled>
                    </div>
                    <div class="mb-3">
                        <label for="locationTransaction" class="form-label">Location</label>
                        <select class="form-select" name="locationTransaction" id="locationTransaction" required disabled>
                            <option value="BACOLOD CITY" <?= ($userLocation == 'BACOLOD CITY') ? 'selected' : '' ?>>BACOLOD CITY</option>
                            <option value="DUMAGUETE CITY" <?= ($userLocation == 'DUMAGUETE CITY') ? 'selected' : '' ?>>DUMAGUETE CITY</option>
                            <option value="ILOILO CITY" <?= ($userLocation == 'ILOILO CITY') ? 'selected' : '' ?>>ILOILO CITY</option>
                            <option value="SAN CARLOS CITY" <?= ($userLocation == 'SAN CARLOS CITY') ? 'selected' : '' ?>>SAN CARLOS CITY</option>
                            <option value="CEBU CITY" <?= ($userLocation == 'CEBU CITY') ? 'selected' : '' ?>>CEBU CITY</option>
                        </select>
                        <!-- Hidden field to ensure the location is submitted even with disabled select -->
                        <input type="hidden" name="locationTransaction" value="<?= htmlspecialchars($userLocation) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="agent" class="form-label">Sales Agent</label>
                        <select class="form-select" id="agent" name="agent" required>
                            <?php if (!empty($agents)): ?>
                                <?php foreach ($agents as $agent): ?>
                                    <option value="<?= htmlspecialchars($agent['employeeID']); ?>">
                                        <?= htmlspecialchars($agent['nameEmployee']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">No agents available</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col">
                            <div class="mb-3">
                                <label for="clientName" class="form-label">Name</label>
                                <input type="text" class="form-control" id="clientName" name="clientName" required>
                            </div>
                        </div>
                        <div class="col">
                            <div class="mb-3">
                                <label for="clientContact" class="form-label">Contact Information</label>
                                <input type="text" class="form-control" id="clientContact" name="clientContact" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col">
                            <div class="mb-3">
                                <label for="clientAddress" class="form-label">Address</label>
                                <input type="text" class="form-control" id="clientAddress" name="clientAddress" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col">
                            <div class="mb-3">
                                <label for="datePickUp" class="form-label">Pickup Date</label>
                                <input type="date" class="form-control" id="datePickUp" name="datePickUp" required>
                            </div>
                        </div>
                        <div class="col">
                            <div class="mb-3">
                                <label for="dateReturn" class="form-label">Return Date</label>
                                <input type="date" class="form-control" id="dateReturn" name="dateReturn" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col">
                            <div class="mb-3">
                                <label for="chargeTransaction" class="form-label">Charge</label>
                                <input type="number" step="0.01"
                                    class="form-control"
                                    name="chargeTransaction"
                                    id="chargeTransaction"
                                    readonly
                                    value="<?= $totalCharge; ?>">
                            </div>
                        </div>
                        <div class="col">
                            <div class="mb-3">
                                <label for="bondTransaction" class="form-label">Bond</label>
                                <input type="number" step="0.01" class="form-control"
                                    name="bondTransaction"
                                    id="bondTransaction"
                                    required>
                            </div>
                        </div>
                        <div class="col">
                            <div class="mb-3">
                                <label for="discountTransaction" class="form-label">Discount</label>
                                <input type="number"
                                    step="0.01"
                                    class="form-control"
                                    name="discountTransaction"
                                    id="discountTransaction"
                                    min="0"
                                    required>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3">Submit Checkout</button>
                </div>

                <!-- Right Column: Cart Summary & Payment Fields -->
                <div class="col-md-6">
                    <div class="row">
                        <div class="col">
                            <h4>Cart Summary</h4>
                        </div>
                        <div class="col">
                            <p id="totalDisplay" class="fw-bold">Total: <?= $totalCharge; ?></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <button type="button" class="btn btn-warning mb-3" id="clearCart">Clear Cart</button>
                        </div>
                        <div class="col">
                            <!-- Button to trigger Add New Product modal -->
                            <button type="button" class="btn btn-outline-info"
                                data-bs-toggle="modal"
                                data-bs-target="#addProductModal">
                                Add New Product
                            </button>
                        </div>
                    </div>

                    <!-- Cart Items Table -->
                    <div id="cartContainer">
                        <table class="table table-bordered cart-table">
                            <thead>
                                <tr>
                                    <th>Product ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Pkg A</th>
                                    <th>Pkg B</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="cartItems">
                                <!-- Rows loaded via AJAX from get_cart_checkout.php -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Add New Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1"
        aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="addProductForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Category Dropdown -->
                        <div class="mb-3">
                            <label for="categoryID" class="form-label">Category</label>
                            <select class="form-select" name="categoryID" id="categoryID" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['categoryID']); ?>">
                                        <?= htmlspecialchars($cat['productCategory']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Product Name -->
                        <div class="mb-3">
                            <label for="nameProduct" class="form-label">Product Name</label>
                            <input type="text" class="form-control" name="nameProduct"
                                id="nameProduct" required>
                        </div>
                        <!-- Product Price Input -->
                        <div class="mb-3">
                            <label for="priceProduct" class="form-label">Price</label>
                            <input type="number" step="0.01" class="form-control"
                                name="priceProduct" id="priceProduct" required>
                        </div>
                        <!-- Product Description/Note -->
                        <div class="mb-3">
                            <label for="descProduct" class="form-label">Note/Description</label>
                            <textarea class="form-control" name="descProduct"
                                id="descProduct" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">
                            Add Product to Cart
                        </button>
                        <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">
                            Close
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="./assets/scripts/checkout.js"></script>
</body>

</html>
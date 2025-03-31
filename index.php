<?php
include './assets/controllers/db.php';

// Initialize cart session if not already set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Fetch products from the database
$query = "SELECT * FROM product";
$stmt = $pdo->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total charge from session cart for display in modal
$totalCharge = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $cartItem) {
        $totalCharge += $cartItem['priceProduct'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Product Listing</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h2>Products</h2>
        <!-- Checkout and other navigation buttons -->
        <?php if (!empty($_SESSION['cart'])): ?>
            <a href="checkout.php?cartID=<?= uniqid(); ?>" class="btn btn-primary">Checkout</a>
        <?php else: ?>
            <button class="btn btn-primary" disabled>Checkout</button>
        <?php endif; ?>
        <!-- <button type="button" class="btn btn-secondary">Continue Shopping</button> -->
        <button class="btn btn-success" data-toggle="modal" data-target="#cartModal">View Cart</button>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>ProductID</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= $product['productID']; ?></td>
                        <td><?= $product['nameProduct']; ?></td>
                        <td><?= $product['priceProduct']; ?></td>
                        <td>
                            <!-- The buttons call AJAX functions to add the product -->
                            <button class="btn btn-primary add-to-cart" data-id="<?= $product['productID']; ?>">Add to Cart</button>
                            <button class="btn btn-success buy-product" data-id="<?= $product['productID']; ?>">Buy</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Cart Modal -->
    <div class="modal fade" id="cartModal" tabindex="-1" role="dialog" aria-labelledby="cartModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><strong>Cart Items</strong></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Display cart items from the session -->
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ProductID</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Action</th> <!-- Added Action column -->
                            </tr>
                        </thead>
                        <tbody id="cart-items">
                            <?php if (!empty($_SESSION['cart'])): ?>
                                <?php foreach ($_SESSION['cart'] as $index => $cartItem): ?>
                                    <tr data-index="<?= $index; ?>" data-productid="<?= $cartItem['productID']; ?>">
                                        <td><?= $cartItem['productID']; ?></td>
                                        <td>
                                            <?= $cartItem['nameProduct']; ?>
                                            <?php if (isset($cartItem['toBuy']) && $cartItem['toBuy']): ?>
                                                <span class="badge bg-success">To Buy</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $cartItem['priceProduct']; ?></td>
                                        <td>
                                            <!-- Remove button -->
                                            <button class="btn btn-danger remove-from-cart" data-index="<?= $index; ?>">Remove</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">Cart is empty.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <!-- Total Charge Display -->
                    <div class="mt-3">
                        <h5>Total Charge: <span id="totalCharge"><?= $totalCharge; ?></span></h5>
                    </div>
                </div>
                <div class="modal-footer">
                    <!-- Clear Cart Button -->
                    <button class="btn btn-warning" id="clearCart">Clear Cart</button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS (using full jQuery) -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>

    <script>
        $(document).ready(function() {
            // Add to Cart AJAX with duplicate check
            $('.add-to-cart').click(function() {
                var productId = $(this).data('id');
                // Check if product already exists in cart by searching for a row with matching data-productid.
                if ($('#cart-items tr[data-productid="' + productId + '"]').length > 0) {
                    alert('Product already added to cart.');
                    return;
                }
                $.ajax({
                    url: './assets/controllers/add_to_cart.php',
                    method: 'POST',
                    data: { productID: productId, toBuy: 0 },
                    success: function(response) {
                        alert('Product added to cart!');
                        // Reload page to update the modal cart list and total charge.
                        location.reload();
                    }
                });
            });
            
            // Buy Product AJAX with duplicate check
            $('.buy-product').click(function() {
                var productId = $(this).data('id');
                // Check if product already exists in cart
                if ($('#cart-items tr[data-productid="' + productId + '"]').length > 0) {
                    alert('Product already added to cart.');
                    return;
                }
                $.ajax({
                    url: './assets/controllers/add_to_cart.php',
                    method: 'POST',
                    data: { productID: productId, toBuy: 1 },
                    success: function(response) {
                        alert('Product marked for purchase!');
                        // Reload page to update the modal cart list and total charge.
                        location.reload();
                    }
                });
            });
            
            // Remove from Cart AJAX
            $('.remove-from-cart').click(function() {
                var index = $(this).data('index');
                $.ajax({
                    url: './assets/controllers/checkout/remove_from_cart.php',
                    method: 'POST',
                    data: { index: index },
                    success: function(response) {
                        alert('Product removed from cart!');
                        // Reload page to update the modal cart list and total charge.
                        location.reload();
                    }
                });
            });

            // Clear Cart button functionality
            $('#clearCart').click(function(){
                if(confirm('Are you sure you want to clear the entire cart?')) {
                    $.ajax({
                        url: './assets/controllers/checkout/clear_cart.php',
                        method: 'POST',
                        success: function(response) {
                            alert('Cart cleared!');
                            location.reload();
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>

<?php
/**
 * Cart Popup View
 * Displays a modal popup with cart items and checkout options
 */
?>
<div id="cart-popup" class="cart-popup">
    <div class="cart-popup-content">
        <span class="close-cart-popup">&times;</span>
        <div class="cart-popup-header">
            <h2>Your Shopping Cart</h2>
        </div>
        <div class="cart-popup-body">
            <div id="cart-items-container">
                <!-- Cart items will be loaded here dynamically -->
                <div class="empty-cart-message">
                    <i class="fas fa-shopping-cart fa-3x"></i>
                    <p>Your cart is empty</p>
                    <p class="empty-cart-subtext">Add items to get started</p>
                </div>
            </div>
        </div>
        <div class="cart-popup-footer">
            <div class="cart-summary">
                <div class="cart-total">
                    <span>Total:</span>
                    <span id="cart-total-amount">₱0.00</span>
                </div>
            </div>
            <div class="cart-actions">
                <button id="clear-cart" class="btn-secondary">Clear Cart</button>
                <button id="checkout-cart" class="btn-primary" disabled>Checkout</button>
            </div>
        </div>
    </div>
</div> 
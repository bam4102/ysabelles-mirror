// Cart Popup functionality
$(document).ready(function() {
    console.log("Cart Popup JS loaded");
    
    // DOM elements
    const cartIcon = $(".cart-icon");
    const cartPopup = $("#cart-popup");
    const closeCartPopup = $(".close-cart-popup");
    const cartItemsContainer = $("#cart-items-container");
    const clearCartBtn = $("#clear-cart");
    const checkoutBtn = $("#checkout-cart");
    const cartTotalAmount = $("#cart-total-amount");
    const cartCount = $(".cart-count");
    
    // Initialize cart
    let cartItems = [];
    
    // Load cart items on page load
    loadCartItems();
    
    // Event Listeners
    cartIcon.on("click", function(e) {
        e.preventDefault();
        cartPopup.fadeIn(300);
        loadCartItems(); // Refresh cart when opening
    });
    
    closeCartPopup.on("click", function() {
        cartPopup.fadeOut(300);
    });
    
    // Close when clicking outside the popup content
    $(window).on("click", function(event) {
        if ($(event.target).is(cartPopup)) {
            cartPopup.fadeOut(300);
        }
    });
    
    // Handle "Add to Cart" click from product popup
    $(document).on("click", "#popup-add-to-cart", function() {
        const productId = $(this).data("product-id");
        addToCart(productId);
    });
    
    // Handle "Remove" item from cart
    $(document).on("click", ".cart-item-remove", function() {
        const index = $(this).data("index");
        removeFromCart(index);
    });
    
    // Handle "Clear Cart" button
    clearCartBtn.on("click", function() {
        clearCart();
    });
    
    // Handle "Checkout" button
    checkoutBtn.on("click", function() {
        // Redirect to checkout page
        window.location.href = "checkout.php";
    });
    
    // Functions
    function loadCartItems() {
        $.ajax({
            url: "assets/controllers/get_cart_items.php",
            type: "GET",
            dataType: "json",
            success: function(data) {
                cartItems = data;
                updateCartDisplay();
            },
            error: function(xhr, status, error) {
                console.error("Error loading cart items:", error);
                cartItems = [];
                updateCartDisplay();
            }
        });
    }
    
    function addToCart(productId) {
        $.ajax({
            url: "assets/controllers/add_to_cart.php",
            type: "POST",
            data: { productID: productId },
            success: function(response) {
                if (response.trim() === "success") {
                    // Show success message
                    showNotification("Product added to cart!");
                    
                    // Refresh cart
                    loadCartItems();
                } else {
                    showNotification("Error adding product to cart: " + response, "error");
                }
            },
            error: function(xhr, status, error) {
                console.error("Error adding to cart:", error);
                showNotification("Error adding product to cart. Please try again.", "error");
            }
        });
    }
    
    function removeFromCart(index) {
        $.ajax({
            url: "assets/controllers/remove_from_cart.php",
            type: "POST",
            data: { index: index },
            success: function(response) {
                if (response.trim() === "success") {
                    showNotification("Product removed from cart!");
                    loadCartItems();
                } else {
                    showNotification("Error removing product: " + response, "error");
                }
            },
            error: function(xhr, status, error) {
                console.error("Error removing from cart:", error);
                showNotification("Error removing product. Please try again.", "error");
            }
        });
    }
    
    function clearCart() {
        $.ajax({
            url: "assets/controllers/clear_cart.php",
            type: "POST",
            success: function(response) {
                if (response.trim() === "success") {
                    showNotification("Cart cleared!");
                    loadCartItems();
                } else {
                    showNotification("Error clearing cart: " + response, "error");
                }
            },
            error: function(xhr, status, error) {
                console.error("Error clearing cart:", error);
                showNotification("Error clearing cart. Please try again.", "error");
            }
        });
    }
    
    function updateCartDisplay() {
        // Update cart count badge
        const itemCount = cartItems.length;
        cartCount.text(itemCount);
        
        // Enable/disable checkout button
        checkoutBtn.prop("disabled", itemCount === 0);
        
        // Update cart items container
        if (itemCount === 0) {
            // Show empty cart message
            cartItemsContainer.html(`
                <div class="empty-cart-message">
                    <i class="fas fa-shopping-cart fa-3x"></i>
                    <p>Your cart is empty</p>
                    <p class="empty-cart-subtext">Add items to get started</p>
                </div>
            `);
            cartTotalAmount.text("₱0.00");
        } else {
            // Build cart items HTML
            let cartHtml = "";
            let total = 0;
            
            cartItems.forEach((item, index) => {
                const price = parseFloat(item.priceProduct) || 0;
                total += price;
                
                // Get image path with fallback
                const imagePath = item.pictureLocation || "assets/img/placeholder.jpg";
                
                cartHtml += `
                    <div class="cart-item">
                        <img class="cart-item-image" src="${imagePath}" alt="${item.nameProduct}" onerror="this.src='assets/img/placeholder.jpg';">
                        <div class="cart-item-details">
                            <div class="cart-item-name">${item.nameProduct}</div>
                            <div class="cart-item-price">₱${price.toFixed(2)}</div>
                            <div class="cart-item-category">${item.productCategory || 'Uncategorized'}</div>
                            <div class="cart-item-id">ID: ${item.formattedId || item.productID}</div>
                        </div>
                        <button class="cart-item-remove" data-index="${index}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            });
            
            cartItemsContainer.html(cartHtml);
            cartTotalAmount.text("₱" + total.toFixed(2));
        }
    }
    
    // Helper function to show notifications
    function showNotification(message, type = "success") {
        // Check if notifications container exists, if not create it
        if ($('#notifications-container').length === 0) {
            $('body').append('<div id="notifications-container"></div>');
        }
        
        // Create notification element
        const notification = $(`
            <div class="notification ${type}">
                <span class="notification-message">${message}</span>
            </div>
        `);
        
        // Add notification to container
        $('#notifications-container').append(notification);
        
        // Show notification with animation
        setTimeout(() => {
            notification.addClass('show');
        }, 10);
        
        // Remove notification after delay
        setTimeout(() => {
            notification.removeClass('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
    
    // Add notification styles if not already present
    if ($('#notification-styles').length === 0) {
        $('head').append(`
            <style id="notification-styles">
                #notifications-container {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10000;
                }
                .notification {
                    background-color: white;
                    border-radius: 4px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    padding: 12px 20px;
                    margin-bottom: 10px;
                    opacity: 0;
                    transform: translateX(50px);
                    transition: all 0.3s ease;
                    max-width: 300px;
                }
                .notification.show {
                    opacity: 1;
                    transform: translateX(0);
                }
                .notification.success {
                    border-left: 4px solid #4caf50;
                }
                .notification.error {
                    border-left: 4px solid #f44336;
                }
                .notification-message {
                    color: #333;
                    font-size: 14px;
                }
            </style>
        `);
    }
}); 
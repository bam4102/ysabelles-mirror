<!-- nav.php  -->
<?php
include './assets/controllers/nav_controller.php';
include './assets/controllers/cart_functions.php';

$cartItems = getCartItems($pdo);
$cartItemCount = count($cartItems);
?>

<!-- Keep existing navbar structure -->
<nav class="navbar">
    <div class="navbar-container">
        <!-- The search form remains the same -->
        <form method="GET" action="homesearch.php" class="homesearch-form">
            <input type="text" name="navbarSearch" placeholder="Search products..." />
            <button type="submit">Search</button>
        </form>
        <ul class="nav-menu">
            <li><a href="home.php">Home</a></li>
            <li><a href="homesearch.php">All</a></li>
            <!-- For category links, we remove the default href and add an onclick redirect -->
            <li>
                <a href="#" onclick="window.location.href='homesearch.php?navbarSearch=' + encodeURIComponent('Bridal Gown'); return false;">
                    Bridal Gown
                </a>
            </li>
            <li>
                <a href="#" onclick="event.preventDefault(); if(typeof loadEntouragePage === 'function') { loadEntouragePage(); } else { window.location.href='entourage_page.php'; }">
                    Entourage
                </a>
            </li>
            <li>
                <a href="#" onclick="window.location.href='homesearch.php?navbarSearch=' + encodeURIComponent('Suit'); return false;">
                    Suit
                </a>
            </li>
           
            <li class="has-dropdown">
                <a href="#" id="more-menu-toggle">More <span class="dropdown-arrow">▼</span></a>
            </li>
            <li class="has-dropdown">
                <a href="#" id="cart-menu-toggle">
                    <img src="assets/img/shopping-bag.png" alt="Cart" class="cart-icon" />
                    <span class="cart-count"<?= ($cartItemCount == 0) ? ' style="display: none;"' : '' ?>><?= $cartItemCount ?></span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Categories Container for More menu -->
    <div class="categories-container">
        <div class="categories-wrapper">
            <!-- Wedding Section -->
            <div class="category-group">
                <h3>Wedding</h3>
                <ul>
                    <?php foreach ($weddingCategories as $category): 
                        // Prepare a URL-friendly category name (if needed)
                        $catName = htmlspecialchars($category['productCategory']);
                    ?>
                        <li>
                            <a href="#" onclick="window.location.href='homesearch.php?navbarSearch=' + encodeURIComponent('<?= $catName ?>'); return false;">
                                <?= $catName ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Women's Section -->
            <div class="category-group">
                <h3>Women's</h3>
                <ul>
                    <?php foreach ($womensCategories as $category): 
                        $catName = htmlspecialchars($category['productCategory']);
                    ?>
                        <li>
                            <a href="#" onclick="window.location.href='homesearch.php?navbarSearch=' + encodeURIComponent('<?= $catName ?>'); return false;">
                                <?= $catName ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Men's Section -->
            <div class="category-group">
                <h3>Men's</h3>
                <ul>
                    <?php foreach ($mensCategories as $category): 
                        $catName = htmlspecialchars($category['productCategory']);
                    ?>
                        <li>
                            <a href="#" onclick="window.location.href='homesearch.php?navbarSearch=' + encodeURIComponent('<?= $catName ?>'); return false;">
                                <?= $catName ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Children's Section with separate Boys and Girls subsections -->
            <div class="category-group">
                <h3>Children's</h3>
                <?php if (!empty($boysCategories)): ?>
                <h4>Boys</h4>
                <ul>
                    <?php foreach ($boysCategories as $category): 
                        $catName = htmlspecialchars($category['productCategory']);
                    ?>
                        <li>
                            <a href="#" onclick="window.location.href='homesearch.php?navbarSearch=' + encodeURIComponent('<?= $catName ?>'); return false;">
                                <?= $catName ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <?php if (!empty($girlsCategories)): ?>
                <h4>Girls</h4>
                <ul>
                    <?php foreach ($girlsCategories as $category): 
                        $catName = htmlspecialchars($category['productCategory']);
                    ?>
                        <li>
                            <a href="#" onclick="window.location.href='homesearch.php?navbarSearch=' + encodeURIComponent('<?= $catName ?>'); return false;">
                                <?= $catName ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>

            <!-- Accessories Section -->
            <div class="category-group">
                <h3>Accessories</h3>
                <ul>
                    <?php foreach ($accessoryCategories as $category): 
                        $catName = htmlspecialchars($category['productCategory']);
                    ?>
                        <li>
                            <a href="#" onclick="window.location.href='homesearch.php?navbarSearch=' + encodeURIComponent('<?= $catName ?>'); return false;">
                                <?= $catName ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Placeholder content for Build and Cart dropdowns -->
    <div class="dropdown-content" id="build-dropdown-content">
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
    </div>
    <div class="dropdown-content" id="cart-dropdown-content">
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
    </div>
</nav>

<!-- Cart Modal - Restructured for better element placement -->
<div id="cartModal" class="modal">
    <div class="modal-content">
        <!-- Modal header is fixed and not recreated dynamically -->
        <div class="modal-header">
            <h4>Shopping Cart</h4>
            <span class="close">&times;</span>
        </div>
        
        <!-- Cart content container that will be updated by JavaScript -->
        <div id="cart-content-container"></div>
    </div>
</div>

<style>
/* Cart count badge */
.cart-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background-color: #DE3C3C;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

#cart-menu-toggle {
    position: relative;
    display: inline-block;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.6);
    justify-content: center;
    align-items: center;
    backdrop-filter: blur(5px);
}

.modal-content {
    background-color: #fff;
    border-radius: 16px;
    width: 70%;
    height: 85vh;
    max-height: 85vh;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;
}

/* Header styling */
.modal-header {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f0;
    background: #fff;
    position: relative;
    z-index: 5;
}

.modal-header h4 {
    margin: 0;
    font-size: 1.5em;
    font-weight: 600;
    color: #333;
    text-align: center;
}

.close {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 24px;
    font-weight: 300;
    color: #555;
    cursor: pointer;
    padding: 0 5px;
    line-height: 1;
    transition: all 0.2s;
    background: none;
    border: none;
}

.close:hover {
    color: #e41e3f;
}

/* Cart container */
.cart-container {
    display: flex;
    flex-direction: column;
    height: calc(100% - 60px); /* Accounting for header */
    overflow: hidden;
}

.cart-items-count {
    padding: 10px 25px;
    font-size: 0.9em;
    color: #666;
    border-bottom: 1px solid #f0f0f0;
    background-color: #f9f9f9;
}

/* Item list with scrolling */
.cart-items-list {
    display: flex;
    flex-direction: column;
    padding: 8px;
    overflow-y: auto;
    flex: 1;
    gap: 6px;
    scrollbar-width: thin;
    scrollbar-color: #ddd #f5f5f5;
    background-color: #f5f5f5;
}

.cart-items-list::-webkit-scrollbar {
    width: 8px;
}

.cart-items-list::-webkit-scrollbar-track {
    background: #f5f5f5;
    border-radius: 10px;
}

.cart-items-list::-webkit-scrollbar-thumb {
    background-color: #ddd;
    border-radius: 10px;
    border: 2px solid #f5f5f5;
}

.cart-items-list::-webkit-scrollbar-thumb:hover {
    background-color: #ccc;
}

/* Cart item styling */
.cart-item {
    display: flex;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    overflow: hidden;
    position: relative;
    transition: box-shadow 0.2s, transform 0.2s;
    min-height: 120px;
    padding: 10px;
    margin-bottom: 6px;
    flex-shrink: 0;
}

.cart-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

.item-image-container {
    width: 120px;
    min-width: 120px;
    height: 120px;
    padding: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f8f8;
    border-radius: 6px;
}

.item-image {
    max-width: 100%;
    max-height: 110px;
    object-fit: contain;
}

.item-details {
    flex: 1;
    padding: 10px 15px;
    display: flex;
    flex-direction: column;
    position: relative;
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.item-name {
    font-weight: 700;
    font-size: 1.1em;
    color: #333;
    margin: 0;
    flex: 1;
    padding-right: 8px;
    white-space: normal;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    height: auto;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 300px;
}

.item-price {
    font-weight: 600;
    color: #e41e3f;
    font-size: 1.2em;
    white-space: nowrap;
}

.item-info {
    flex: 1;
    display: flex;
    flex-wrap: wrap;
    gap: 4px 8px;
}

.info-row {
    display: flex;
    margin-bottom: 5px;
    font-size: 0.9em;
    flex: 1 0 100%;
}

.info-label {
    color: #666;
    font-weight: 500;
    width: 85px;
    flex-shrink: 0;
}

.info-value {
    color: #333;
    font-weight: 500;
}

.remove-btn {
    align-self: flex-end;
    margin-top: 8px;
    background-color: transparent;
    border: 1px solid #dc3545;
    color: #dc3545;
    font-size: 0.9em;
    padding: 5px 12px;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.2s;
}

.remove-btn:hover {
    background-color: #dc3545;
    color: white;
}

/* Cart summary */
.cart-summary {
    border-top: 1px solid #f0f0f0;
    background-color: #fff;
    padding: 15px 20px;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
    z-index: 5;
}

.summary-section {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.summary-total {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.summary-total span:first-child {
    font-size: 0.9em;
    color: #666;
}

.summary-total span:last-child {
    font-size: 1.4em;
    font-weight: 700;
    color: #333;
}

.cart-buttons {
    display: flex;
    gap: 15px;
}

.btn-primary {
    background-color: #e41e3f;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: background-color 0.2s;
    font-size: 0.95em;
}

.btn-primary:hover {
    background-color: #c41732;
}

.btn-secondary {
    background-color: #f5f5f5;
    color: #333;
    border: 1px solid #ddd;
    padding: 12px 24px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
    font-size: 0.95em;
}

.btn-secondary:hover {
    background-color: #eaeaea;
}

/* Empty cart */
.empty-cart {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 50px 20px;
    text-align: center;
    height: 100%;
    background-color: #f9f9f9;
}

.empty-cart-icon {
    font-size: 4em;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-cart p {
    margin: 15px 0 25px;
    font-size: 1.2em;
    color: #666;
}

/* Responsive styles */
@media (max-width: 992px) {
    .modal-content {
        width: 80%;
    }
}

@media (max-width: 768px) {
    .modal-content {
        width: 90%;
        max-height: 90vh;
    }
    
    .cart-item {
        flex-direction: row;
    }
    
    .item-image-container {
        width: 80px;
        min-width: 80px;
        height: 80px;
    }
    
    .item-image {
        max-height: 70px;
    }
    
    .item-name {
        font-size: 1em;
    }
    
    .item-price {
        font-size: 1em;
    }
    
    .info-row {
        font-size: 0.85em;
    }
}

@media (max-width: 576px) {
    .modal-content {
        width: 95%;
        height: 90vh;
    }
    
    .cart-item {
        flex-direction: column;
    }
    
    .item-image-container {
        width: 100%;
        height: 120px;
    }
    
    .item-header {
        flex-direction: column;
    }
    
    .item-name {
        margin-bottom: 8px;
        max-width: 100%;
    }
    
    .summary-section {
        flex-direction: column;
    }
    
    .summary-total {
        flex-direction: row;
        justify-content: space-between;
        width: 100%;
        margin-bottom: 15px;
    }
    
    .cart-buttons {
        width: 100%;
    }
    
    .btn-primary, .btn-secondary {
        flex: 1;
        padding: 10px;
    }
    
    .info-row {
        flex-direction: column;
    }
    
    .info-label {
        width: 100%;
        margin-bottom: 2px;
    }
}
</style>

<script src="assets/js/add-to-cart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const cartModal = document.getElementById("cartModal");
    const cartContentContainer = document.getElementById("cart-content-container");
    const cartCloseSpan = document.querySelector(".modal-content .close");
    let cartItems = <?= json_encode($cartItems) ?>; // Use PHP-generated initial data

    // More menu dropdown functionality
    const moreMenuToggle = document.getElementById("more-menu-toggle");
    const categoriesContainer = document.querySelector(".categories-container");
    
    if (moreMenuToggle && categoriesContainer) {
        moreMenuToggle.addEventListener("click", (e) => {
            e.preventDefault();
            categoriesContainer.classList.toggle("active");
            
            const arrow = moreMenuToggle.querySelector(".dropdown-arrow");
            if (arrow) {
                arrow.textContent = categoriesContainer.classList.contains("active") ? "▲" : "▼";
            }
        });

        // Close categories when clicking outside
        document.addEventListener("click", (e) => {
            if (!e.target.closest("#more-menu-toggle") && !e.target.closest(".categories-container")) {
                categoriesContainer.classList.remove("active");
                
                const arrow = moreMenuToggle.querySelector(".dropdown-arrow");
                if (arrow) {
                    arrow.textContent = "▼";
                }
            }
        });
    }

    // Cart rendering function - separating structure from content
    function renderCart(items) {
        if (!items || items.length === 0) {
            // Render empty cart
            return `
                <div class="cart-container">
                    <div class="empty-cart">
                        <div class="empty-cart-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <p>Your cart is empty</p>
                        <a href="homesearch.php" class="btn-primary">Browse Products</a>
                    </div>
                </div>
            `;
        } else {
            // Calculate total
            const total = items.reduce((sum, item) => sum + parseFloat(item.priceProduct || 0), 0);
            
            // Render cart with items
            return `
                <div class="cart-container">
                    <div class="cart-items-count">
                        <span>${items.length} item${items.length > 1 ? 's' : ''} in cart</span>
                    </div>
                    <div class="cart-items-list">
                        ${items.map(item => `
                            <div class="cart-item" data-index="${item.index}" data-price="${item.priceProduct}">
                                <div class="item-image-container">
                                    <img src="${item.pictureLocation || 'assets/img/default.jpg'}" 
                                         alt="${item.nameProduct}" 
                                         class="item-image"
                                         onerror="this.src='assets/img/default.jpg';" />
                                </div>
                                <div class="item-details">
                                    <div class="item-header">
                                        <h5 class="item-name">${item.nameProduct}</h5>
                                        <span class="item-price">₱${parseFloat(item.priceProduct).toFixed(2)}</span>
                                    </div>
                                    <div class="item-info">
                                        <div class="info-row">
                                            <span class="info-label">Category:</span>
                                            <span class="info-value">${item.productCategory}</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Code:</span>
                                            <span class="info-value">#${item.formattedId || item.productID}</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Type:</span>
                                            <span class="info-value">${item.typeProduct || 'N/A'}</span>
                                        </div>
                                        ${item.entourageID ? `
                                        <div class="info-row">
                                            <span class="info-label">Entourage ID:</span>
                                            <span class="info-value">${item.entourageID}</span>
                                        </div>` : ''}
                                    </div>
                                    <button type="button" class="remove-btn" data-index="${item.index}">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    <div class="cart-summary">
                        <div class="summary-section">
                            <div class="summary-total">
                                <span>Total</span>
                                <span id="totalDisplay">₱${total.toFixed(2)}</span>
                            </div>
                            <div class="cart-buttons">
                                <button class="btn-secondary" id="clearCart">Clear Cart</button>
                                <button class="btn-primary" id="checkoutButton">Checkout</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
    }

    // Load cart items function
    const loadCartItems = () => {
        fetch('assets/controllers/get_cart_items.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Check if there's an error property in the response
            if (data.error) {
                throw new Error(data.message || 'Unknown cart error');
            }
            
            const cartCountBadge = document.querySelector('.cart-count');
            
            // Update cart count badge
            if (cartCountBadge) {
                if (data.length > 0) {
                    cartCountBadge.textContent = data.length;
                    cartCountBadge.style.display = 'flex';
                } else {
                    cartCountBadge.style.display = 'none';
                }
            }
            
            // Render cart content
            cartContentContainer.innerHTML = renderCart(data);
            
            // Attach event listeners for buttons in the cart
            attachCartEventListeners();
            
            // Make cart items global for other functions to use
            cartItems = data;
        })
        .catch(error => {
            console.error('Error updating cart:', error);
            cartContentContainer.innerHTML = `
                <div class="cart-container">
                    <div class="empty-cart">
                        <p>Error loading cart. Please try again.</p>
                        <button class="btn-secondary" onclick="loadCartItems()">Retry</button>
                    </div>
                </div>
            `;
        });
    };
    
    // Attach event listeners for cart buttons
    function attachCartEventListeners() {
        // Handle remove buttons
        document.querySelectorAll('.remove-btn').forEach(button => {
            button.addEventListener('click', function() {
                const index = this.dataset.index;
                if (!index && index !== '0') return;
                
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';
                
                removeCartItem(index);
            });
        });
        
        // Handle clear cart button
        const clearCartBtn = document.getElementById('clearCart');
        if (clearCartBtn) {
            clearCartBtn.addEventListener('click', () => {
                if (confirm("Are you sure you want to clear your cart?")) {
                    clearCart();
                }
            });
        }
        
        // Handle checkout button
        const checkoutBtn = document.getElementById('checkoutButton');
        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', () => {
                const cartID = Date.now();
                window.location.href = `checkout.php?cartID=${cartID}`;
            });
        }
    }
    
    // Function to remove cart item
    function removeCartItem(index) {
        fetch('assets/controllers/remove_from_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'index=' + encodeURIComponent(index)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(result => {
            if (result.includes("success")) {
                loadCartItems();
                window.dispatchEvent(new CustomEvent('cartUpdated', { detail: true }));
            } else {
                throw new Error(result || "Failed to remove item");
            }
        })
        .catch(error => {
            console.error('Error removing item:', error);
            alert('Error removing item. Please try again.');
            loadCartItems(); // Refresh to reset buttons
        });
    }
    
    // Function to clear cart
    function clearCart() {
        fetch('assets/controllers/clear_cart.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(result => {
            if(result.includes("success")){
                loadCartItems();
                window.dispatchEvent(new CustomEvent('cartUpdated', { detail: true }));
            } else {
                throw new Error(result || "Unknown error");
            }
        })
        .catch(error => {
            console.error('Error clearing cart:', error);
            alert('Error clearing cart. Please try again.');
        });
    }

    // Initial cart load
    loadCartItems();

    // Modal close button
    if (cartCloseSpan) {
        cartCloseSpan.onclick = function() {
            cartModal.style.display = "none";
        };
    }

    // Close when clicking outside the modal
    window.onclick = function(event) {
        if (event.target == cartModal) {
            cartModal.style.display = "none";
        }
    };

    // Cart icon click event
    document.getElementById("cart-menu-toggle").addEventListener("click", (e) => {
        e.preventDefault();
        cartModal.style.display = "flex";
        loadCartItems();  // Always refresh cart when opening
    });

    // Listen for the custom "cartUpdated" event
    window.addEventListener("cartUpdated", (e) => {
        if(e.detail){
            loadCartItems();
        }
    });
    
    // Expose loadCartItems function globally
    window.loadCartItems = loadCartItems;
});
</script>
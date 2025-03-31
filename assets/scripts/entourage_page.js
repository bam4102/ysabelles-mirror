document.addEventListener('DOMContentLoaded', function() {
    // Cache DOM elements to avoid repeated lookups
    const collectionModal = document.getElementById("collectionModal");
    
    if (!collectionModal) {
        console.error("Collection modal element not found");
        return;
    }
    
    // Cache frequently used DOM elements
    const elements = {
        closeButton: collectionModal.querySelector(".close"),
        entourageCards: document.querySelectorAll('.grid-product-card[data-entourage-id]'),
        selectAllCheckbox: document.getElementById('selectAllCheckbox'),
        addSelectedToCartBtn: document.getElementById('addSelectedToCart'),
        cancelSelectionBtn: document.getElementById('cancelSelection'),
        modalContent: collectionModal.querySelector('.modal-content'),
        previewImage: document.getElementById('previewImage'),
        collectionName: document.getElementById('collectionName'),
        productDetailsBlock: document.getElementById('productDetailsBlock'),
        modalAddToCart: document.getElementById('modalAddToCart'),
        collectionProducts: document.getElementById("collectionProducts")
    };
        
    // Touch support variables
    let touchTimer = null;
    let touchStarted = false;
    const HOLD_DURATION = 700; // milliseconds for long press
    
    // Set to store selected product IDs
    let selectedProducts = new Set();
    
    // Flag to track multi-select mode
    let multiSelectMode = false;
    
    console.log("Found entourage cards:", elements.entourageCards.length);
    
    // Store entourage details globally so we can revert to them
    let currentEntourageImage = '';
    let currentEntourageName = '';
    
    // Add variables for image navigation
    let currentImageIndex = 0;
    let productImages = [];
    // New array to store entourage images
    let entourageImages = [];
    
    // Modal event handlers
    if (elements.closeButton) {
        elements.closeButton.addEventListener('click', () => {
            collectionModal.style.display = "none";
            exitMultiSelectMode();
        });
    }
    
    // Close when clicking outside the modal content
    window.addEventListener('click', (e) => {
        if (e.target === collectionModal) {
            collectionModal.style.display = "none";
            exitMultiSelectMode();
        }
    });
    
    // Select all functionality
    if (elements.selectAllCheckbox) {
        elements.selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('.collection-product-card').forEach(card => {
                const productID = card.dataset.productId;
                const checkbox = card.querySelector('.product-checkbox');
                
                if (isChecked) {
                    selectedProducts.add(productID);
                    card.classList.add('selected');
                    if (checkbox) checkbox.classList.add('selected');
                } else {
                    selectedProducts.delete(productID);
                    card.classList.remove('selected');
                    if (checkbox) checkbox.classList.remove('selected');
                }
            });
            
            // When unchecking "Select All", exit multi-select mode if no selections remain
            if (!isChecked && selectedProducts.size === 0) {
                exitMultiSelectMode();
                // Show entourage preview to reset the view
                showEntouragePreview();
            } else {
                // Update selection count and selected products list
                updateSelectedCount();
                updateSelectedProductsList();
                updateTotalSummary();
            }
        });
    }
    
    // Add selected products to cart
    if (elements.addSelectedToCartBtn) {
        elements.addSelectedToCartBtn.addEventListener('click', addSelectedProductsToCart);
    }
    
    // Cancel selection
    if (elements.cancelSelectionBtn) {
        elements.cancelSelectionBtn.addEventListener('click', function() {
            exitMultiSelectMode();
            
            // Show the product preview if there was an active product
            const activeCard = document.querySelector('.collection-product-card.active');
            if (activeCard) {
                const productId = activeCard.dataset.productId;
                const product = entourageCache[currentEntourageId]?.find(p => p.productID === productId);
                if (product) {
                    showProductPreview(product);
                }
            } else {
                showEntouragePreview();
            }
        });
    }
    
    // Helper functions
    function updateSelectedCount() {
        // Update the Add to Cart button text to show selection count
        if (elements.modalAddToCart) {
            if (selectedProducts.size > 0) {
                elements.modalAddToCart.textContent = `Add to Cart (${selectedProducts.size})`;
                elements.modalAddToCart.disabled = false;
                elements.modalAddToCart.style.opacity = '1';
            } else {
                // If no products selected in multi-select mode
                elements.modalAddToCart.textContent = 'Add to Cart';
                elements.modalAddToCart.disabled = multiSelectMode;
                elements.modalAddToCart.style.opacity = multiSelectMode ? '0.5' : '1';
            }
        }
    }
    
    function updateSelectionUI(isActive) {
        if (isActive) {
            elements.modalContent.classList.add('selection-active');
            
            // Show selection UI elements
            const selectAllContainer = document.querySelector('.select-all-container');
            const multiSelectActions = document.querySelector('.multi-select-actions');
            
            // Change the add to cart button functionality to add selected items
            if (elements.modalAddToCart) {
                elements.modalAddToCart.onclick = addSelectedProductsToCart;
                updateSelectedCount(); // Update button text based on selections
            }
            
            // Make select all checkbox visible
            if (selectAllContainer) {
                selectAllContainer.style.display = 'flex';
                // Reset select all checkbox state
                const selectAllCheckbox = document.getElementById('selectAllCheckbox');
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = false;
                }
            }
            
            if (multiSelectActions) multiSelectActions.style.display = 'flex';
        } else {
            elements.modalContent.classList.remove('selection-active');
            elements.modalContent.classList.remove('multi-select-mode');
            
            // Reset add to cart button to default state
            if (elements.modalAddToCart) {
                elements.modalAddToCart.textContent = 'Add to Cart';
            }
            
            // Hide selection UI elements
            const selectAllContainer = document.querySelector('.select-all-container');
            const multiSelectActions = document.querySelector('.multi-select-actions');
            
            if (selectAllContainer) selectAllContainer.style.display = 'none';
            if (multiSelectActions) multiSelectActions.style.display = 'none';
        }
    }
    
    function exitMultiSelectMode() {
        multiSelectMode = false;
        
        // Reset all card states
        document.querySelectorAll('.collection-product-card').forEach(card => {
            card.classList.remove('selected');
            const checkbox = card.querySelector('.product-checkbox');
            if (checkbox) checkbox.classList.remove('selected');
        });
        
        selectedProducts.clear();
        updateSelectedCount();
        updateSelectionUI(false);
        
        // Reset select all checkbox
        if (elements.selectAllCheckbox) {
            elements.selectAllCheckbox.checked = false;
        }
        
        // Hide the total summary
        const summaryElement = document.getElementById('selectionTotalSummary');
        if (summaryElement) {
            summaryElement.style.display = 'none';
        }
        
        // Show the entourage preview when exiting selection mode
        // This ensures the UI is reset completely
        showEntouragePreview();
    }
    
    function enterMultiSelectMode() {
        multiSelectMode = true;
        updateSelectionUI(true);
        updateSelectedCount();
        
        // Show entourage preview when entering selection mode
        if (elements.previewImage && entourageImages.length > 0) {
            elements.previewImage.src = entourageImages[0] || currentEntourageImage;
            elements.previewImage.style.opacity = '1';
        }
        
        // Reset collection name element to show entourage name
        if (elements.collectionName) {
            elements.collectionName.textContent = currentEntourageName;
        }
        
        // Update the list of selected products (initially empty)
        updateSelectedProductsList();
    }
    
    function toggleProductSelection(card) {
        const productID = card.dataset.productId;
        const checkbox = card.querySelector('.product-checkbox');
        
        if (selectedProducts.has(productID)) {
            selectedProducts.delete(productID);
            card.classList.remove('selected');
            if (checkbox) checkbox.classList.remove('selected');
            
            // If we have no more selections, exit selection mode completely
            if (selectedProducts.size === 0) {
                exitMultiSelectMode();
                
                // Also show entourage preview when exiting selection mode to reset the view
                showEntouragePreview();
                return; // Exit early since we're leaving multi-select mode
            }
        } else {
            // If this is the first selection, enter multi-select mode
            if (!multiSelectMode) {
                enterMultiSelectMode();
            }
            
            selectedProducts.add(productID);
            card.classList.add('selected');
            if (checkbox) checkbox.classList.add('selected');
        }
        
        // Update the list of selected products in the details block
        if (multiSelectMode) {
            updateSelectedProductsList();
        }
        
        updateSelectedCount();
    }
    
    // Consolidated cart functionality
    async function addToCart(productID) {
        try {
            const response = await fetch('./assets/controllers/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `productID=${productID}&quantity=1`
            });
            
            const result = await response.text();
            return result.trim() === "success";
        } catch (error) {
            console.error(`Error adding product ${productID} to cart:`, error);
            return false;
        }
    }
    
    async function addSelectedProductsToCart() {
        if (selectedProducts.size === 0) {
            alert('Please select at least one product.');
            return;
        }
        
        const productArray = Array.from(selectedProducts);
        let successCount = 0;
        
        // Update button to show loading state
        const originalButtonText = elements.modalAddToCart.textContent;
        elements.modalAddToCart.textContent = 'Adding...';
        elements.modalAddToCart.disabled = true;
        
        // Add each product to cart
        for (const productID of productArray) {
            const success = await addToCart(productID);
            if (success) successCount++;
        }
        
        if (successCount > 0) {
            showToast(`✓ Added ${successCount} product${successCount !== 1 ? 's' : ''} to cart`);
            exitMultiSelectMode();
            
            // Notify navbar that cart has been updated
            const event = new CustomEvent('cartUpdated', { detail: true });
            window.dispatchEvent(event);
            
            // Reset button
            elements.modalAddToCart.textContent = 'Add to Cart';
            elements.modalAddToCart.disabled = false;
        } else {
            alert('Failed to add products to cart. Please try again.');
            // Reset button
            elements.modalAddToCart.textContent = originalButtonText;
            elements.modalAddToCart.disabled = false;
        }
    }
    
    // Cache for entourage products to prevent repeated fetching
    const entourageCache = {};
    // Add a new cache for entourage images
    const entourageImagesCache = {};
    let currentEntourageId = null;
    
    // Function to fetch entourage products
    const fetchEntourageProducts = async (entourageID) => {
        try {
            console.log("Fetching products for entourage ID:", entourageID);
            
            // Ensure proper URL construction with no caching
            const timestamp = new Date().getTime();
            const response = await fetch(`./get_entourage.php?id=${entourageID}&_=${timestamp}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Cache-Control': 'no-cache'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            const jsonData = await response.json();
            return jsonData;
        } catch (error) {
            console.error('Error fetching products:', error);
            return [];
        }
    };
    
    // Add click event to each entourage card
    elements.entourageCards.forEach(card => {
        // Ensure we're adding listener to valid elements
        if (!card.dataset.entourageId) {
            console.warn("Card missing entourage ID:", card);
            return;
        }
        
        card.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log("Card clicked, showing modal");
            
            // Get entourage data
            const entourageID = this.dataset.entourageId;
            currentEntourageId = entourageID;
            const entourageImage = this.querySelector('img').src;
            
            // Get the collection name directly from the first child text node
            const nameTextNode = Array.from(this.querySelector('.product-overlay').childNodes)
                .find(node => node.nodeType === Node.TEXT_NODE);
            const entourageName = nameTextNode ? nameTextNode.nodeValue.trim() : "Collection";
            
            // Store entourage details globally
            currentEntourageImage = entourageImage;
            currentEntourageName = entourageName;
            
            // Reset image arrays and use cached images if available
            productImages = [];
            
            // Check if we have cached entourage images
            if (entourageImagesCache[entourageID]) {
                console.log("Using cached entourage images");
                entourageImages = entourageImagesCache[entourageID];
            } else {
                entourageImages = [entourageImage]; // Initialize with the main image
            }
            
            currentImageIndex = 0;
            
            // Update modal header
            if (elements.collectionName) elements.collectionName.textContent = entourageName;
            
            // Show modal immediately
            collectionModal.style.display = "flex";
            
            // Show initial loading state
            if (elements.collectionProducts) {
                elements.collectionProducts.innerHTML = `
                    <div class="loading-container" style="text-align: center; padding: 30px; width: 100%;">
                        <div class="spinner" style="display: inline-block; width: 40px; height: 40px; border: 4px solid rgba(222, 60, 60, 0.1); border-radius: 50%; border-top-color: #de3c3c; animation: spin 1s linear infinite;"></div>
                        <p style="margin-top: 15px; color: #666;">Loading products...</p>
                    </div>
                    <style>@keyframes spin { to { transform: rotate(360deg); } }</style>
                `;
            }
            
            // Show entourage image while loading
            if (elements.previewImage) elements.previewImage.src = entourageImage;
            
            // Show entourage preview immediately with loading indicator for details
            showEntouragePreview(true); // Pass true to indicate it's the initial load
            
            // Check if we've already loaded this entourage's products
            let products;
            if (entourageCache[entourageID]) {
                console.log("Using cached products for entourage:", entourageID);
                products = entourageCache[entourageID];
                
                // Render products with a slight delay to allow the modal to display first
                setTimeout(() => {
                    renderProducts(products);
                    // Update entourage preview with full product details
                    showEntouragePreview(false);
                }, 50);
                
                // If we have products but not images, still try to load the images in background
                if (!entourageImagesCache[entourageID]) {
                    loadEntourageImages(entourageID);
                }
            } else {
                // If not cached, fetch products and images in parallel for better performance
                console.log("Fetching products and images for entourage:", entourageID);
                
                // Start products fetch
                fetchEntourageProducts(entourageID)
                    .then(fetchedProducts => {
                        if (fetchedProducts && fetchedProducts.length > 0) {
                            // Cache the products
                            entourageCache[entourageID] = fetchedProducts;
                            // Render products
                            renderProducts(fetchedProducts);
                            // Update entourage preview with full product details
                            showEntouragePreview(false);
                        } else {
                            // Handle no products case
                            renderProducts([]);
                            showEntouragePreview(false);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching products:', error);
                        renderProducts([]);
                        showEntouragePreview(false);
                    });
                
                // Start images fetch in parallel
                loadEntourageImages(entourageID);
            }
        });
    });

    // Modified function to load entourage images with callback support
    function loadEntourageImages(entourageID, callback) {
        // Check if already cached
        if (entourageImagesCache[entourageID]) {
            entourageImages = entourageImagesCache[entourageID];
            updateEntourageImageUI();
            if (callback) callback(entourageImages);
            return;
        }
        
        // Fetch all images for this entourage
        fetch(`get_entourage_images.php?id=${entourageID}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Received entourage image data:', data);
                if (data.images && data.images.length > 0) {
                    // Store in cache for future use
                    entourageImagesCache[entourageID] = data.images;
                    entourageImages = data.images;
                    
                    // If entourage has multiple images, show thumbnails
                    if (entourageImages.length > 1) {
                        updateEntourageImageUI();
                    }
                    
                    // Update the preview to use the first image
                    const img = elements.previewImage;
                    if (img && entourageImages.length > 0) {
                        img.src = entourageImages[0];
                    }
                    
                    // Execute callback if provided
                    if (callback) callback(entourageImages);
                }
            })
            .catch(error => {
                console.error('Error loading entourage images:', error);
                if (callback) callback(null);
            });
    }
    
    // Optimized function to render products with virtual scrolling for performance
    function renderProducts(products) {
        const collectionProducts = elements.collectionProducts;
        if (!collectionProducts) return;
        
        // Clear previous content
        collectionProducts.innerHTML = '';
        
        // Handle no products case
        if (!products || products.length === 0) {
            collectionProducts.innerHTML = `
                <div style="padding: 20px; text-align: center;">
                    <p style="color: #666;">No products found in this collection.</p>
                </div>`;
            return;
        }

        // Use DocumentFragment for better performance
        const fragment = document.createDocumentFragment();
        
        // Check page width to determine optimal grid layout
        const containerWidth = collectionProducts.clientWidth;
        const optimalColumns = containerWidth < 500 ? 4 : 
                              containerWidth < 768 ? 6 : 8;
        collectionProducts.style.gridTemplateColumns = `repeat(${optimalColumns}, 1fr)`;
        
        // Check if we need to batch load (more than 30 products)
        const initialBatchSize = 32; // Show more products initially for a fuller grid
        const needsBatchLoading = products.length > initialBatchSize;
        
        // Add first batch of products immediately
        const initialProducts = needsBatchLoading ? products.slice(0, initialBatchSize) : products;
        initialProducts.forEach(product => {
            const card = createProductCard(product);
            if (card) fragment.appendChild(card);
        });
        
        collectionProducts.appendChild(fragment);
        
        // If we have more products, add a "Load More" button
        if (needsBatchLoading) {
            const remainingProducts = products.slice(initialBatchSize);
            addLoadMoreButton(collectionProducts, remainingProducts);
        }
    }
    
    // Create product card element with improved multi-selection support for both desktop and mobile
    function createProductCard(product) {
        if (!product) return null;
        
        const productCard = document.createElement('div');
        productCard.className = 'collection-product-card';
        productCard.dataset.productId = product.productID;
        
        // Add touch feedback element to the card markup
        productCard.innerHTML = `
            <img src="${product.image || 'assets/img/default.jpg'}" 
                 alt="${product.nameProduct || 'Untitled'}"
                 loading="lazy">
            <div class="product-checkbox"></div>
            <div class="touch-feedback"></div>
        `;
        
        // Add click event with multi-selection support
        productCard.addEventListener('click', function(e) {
            // Prevent click event from triggering immediately after long-press
            if (productCard.dataset.longPressHandled) {
                delete productCard.dataset.longPressHandled;
                return;
            }
            
            // If in multi-select mode, single click toggles selection
            if (multiSelectMode) {
                toggleProductSelection(this);
                return;
            }
            
            // Otherwise, show product details
            if (elements.modalAddToCart) {
                elements.modalAddToCart.style.opacity = '1';
                elements.modalAddToCart.disabled = false;
                elements.modalAddToCart.textContent = 'Add to Cart';
            }
            
            showProductPreview(product);
        });
        
        // Add desktop long-press support (mouse events)
        let mouseTimer;
        const MOUSE_HOLD_DURATION = 700;
        let mouseHeld = false;
        
        productCard.addEventListener('mousedown', function(e) {
            if (e.button === 0) { // Only for left mouse button
                mouseHeld = true;
                mouseTimer = setTimeout(() => {
                    if (mouseHeld) {
                        // Mark the card as having been handled by long-press
                        productCard.dataset.longPressHandled = 'true';
                        
                        // Enter multi-select mode if not already in it
                        if (!multiSelectMode) {
                            enterMultiSelectMode();
                        }
                        
                        // Then toggle selection for this product
                        toggleProductSelection(this);
                        
                        // Provide visual feedback
                        const touchFeedback = this.querySelector('.touch-feedback');
                        if (touchFeedback) {
                            touchFeedback.classList.add('active');
                            setTimeout(() => touchFeedback.classList.remove('active'), 300);
                        }
                        
                        // Important: Prevent default browser behavior for long-press
                        e.preventDefault();
                    }
                }, MOUSE_HOLD_DURATION);
            }
        });
        
        productCard.addEventListener('mouseup', function(e) {
            mouseHeld = false;
            clearTimeout(mouseTimer);
        });
        
        productCard.addEventListener('mouseleave', function(e) {
            mouseHeld = false;
            clearTimeout(mouseTimer);
        });
        
        productCard.addEventListener('contextmenu', function(e) {
            // Right-click also enters multi-select mode
            if (!multiSelectMode) {
                enterMultiSelectMode();
            }
            toggleProductSelection(this);
            e.preventDefault(); // Prevent context menu
        });
        
        // Add touch events for mobile devices
        attachTouchEvents(productCard);
        
        // Add click event for checkbox
        const checkbox = productCard.querySelector('.product-checkbox');
        if (checkbox) {
            checkbox.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleProductSelection(productCard);
            });
        }
        
        return productCard;
    }
    
    // Attach touch events to a product card for mobile devices
    function attachTouchEvents(card) {
        // Touch start handler
        card.addEventListener('touchstart', function(e) {
            touchStarted = true;
            card.classList.add('active-touch');
            
            // Create ripple effect
            const ripple = document.createElement('span');
            ripple.className = 'ripple';
            const rect = card.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = `${size}px`;
            ripple.style.left = `${e.touches[0].clientX - rect.left - size/2}px`;
            ripple.style.top = `${e.touches[0].clientY - rect.top - size/2}px`;
            card.appendChild(ripple);
            
            // Set timer for long press
            touchTimer = setTimeout(() => {
                if (touchStarted) {
                    // Enter multi-select mode if not already in it
                    if (!multiSelectMode) {
                        enterMultiSelectMode();
                    }
                    // Then toggle selection for this product
                    toggleProductSelection(card);
                }
            }, HOLD_DURATION);
            
            // Remove ripple after animation
            setTimeout(() => ripple.remove(), 600);
        }, { passive: true });
        
        // Consolidated function for ending touch interaction
        const endTouch = () => {
            clearTimeout(touchTimer);
            touchStarted = false;
            card.classList.remove('active-touch');
        };
        
        // Add all touch end events with the same handler
        card.addEventListener('touchend', endTouch);
        card.addEventListener('touchmove', endTouch);
        card.addEventListener('touchcancel', endTouch);
    }
    
    // Add load more button
    function addLoadMoreButton(container, remainingProducts) {
        const loadMoreContainer = document.createElement('div');
        loadMoreContainer.className = 'load-more-container';
        loadMoreContainer.style.cssText = 'grid-column: 1 / -1; text-align: center; padding: 15px; width: 100%;';
        
        const loadMoreBtn = document.createElement('button');
        loadMoreBtn.textContent = `Load More (${remainingProducts.length} products)`;
        loadMoreBtn.className = 'load-more-btn';
        loadMoreBtn.style.cssText = 'padding: 8px 15px; background: #de3c3c; color: white; border: none; border-radius: 4px; cursor: pointer;';
        
        loadMoreBtn.addEventListener('click', function() {
            // Remove the button
            loadMoreContainer.remove();
            
            // Add the remaining products in batches
            loadProductBatches(remainingProducts, container);
        });
        
        loadMoreContainer.appendChild(loadMoreBtn);
        container.appendChild(loadMoreContainer);
    }
    
    // Load products in batches for better performance
    function loadProductBatches(products, container) {
        const batchSize = 20;
        let currentIndex = 0;
        
        const loadNextBatch = () => {
            if (currentIndex >= products.length) return;
            
            const batch = products.slice(currentIndex, currentIndex + batchSize);
            const batchFragment = document.createDocumentFragment();
            
            batch.forEach(product => {
                const card = createProductCard(product);
                if (card) batchFragment.appendChild(card);
            });
            
            container.appendChild(batchFragment);
            currentIndex += batchSize;
            
            if (currentIndex < products.length) {
                // Schedule next batch
                setTimeout(loadNextBatch, 10);
            }
        };
        
        loadNextBatch();
    }

 
    
    // Function to show product preview with updated references for new layout
    const showProductPreview = (product) => {
        // Don't update preview in multi-select mode
        if (multiSelectMode) return;
        
        // Reset image navigation state
        currentImageIndex = 0;
        productImages = [];
        
        // Update image with loading state
        if (elements.previewImage) {
            elements.previewImage.style.opacity = '0.5';
            elements.previewImage.src = product.image || 'assets/img/default.jpg';
        }
        
        // Use the collection name element to show product name
        if (elements.collectionName) {
            elements.collectionName.textContent = product.nameProduct || 'Unknown Product';
        }
        
        // Load and display all product images
        loadProductImages(product);
        
        // Consolidate all details into one block
        if (elements.productDetailsBlock) {
            let detailsHTML = '';
            
            // Add category if available
            if (product.productCategory) {
                detailsHTML += `<p><strong>Category:</strong> ${product.productCategory}</p>`;
            }
            
            // Combine measurements
            const measurements = [];
            if (product.sizeProduct) measurements.push(`Size: ${product.sizeProduct}`);
            if (product.bustProduct) measurements.push(`Bust: ${product.bustProduct}`);
            if (product.waistProduct) measurements.push(`Waist: ${product.waistProduct}`);
            if (product.lengthProduct) measurements.push(`Length: ${product.lengthProduct}`);
            
            if (measurements.length > 0) {
                detailsHTML += `<p><strong>Measurements:</strong> ${measurements.join(' | ')}</p>`;
            }
            
            // Add color if available
            if (product.colorProduct) {
                detailsHTML += `<p><strong>Color:</strong> ${product.colorProduct}</p>`;
            }
            
            // Add description if available
            if (product.descProduct) {
                detailsHTML += `<p><strong>Description:</strong> ${product.descProduct}</p>`;
            }
            
            // Add price with special styling
            if (product.priceProduct) {
                detailsHTML += `<p><strong>Price:</strong> <span class="price-tag">₱${parseFloat(product.priceProduct).toFixed(2)}</span></p>`;
            }
            
            elements.productDetailsBlock.innerHTML = detailsHTML;
        }
        
        // Set product ID for add to cart button
        const productID = product.productID;
        
        // Update add to cart button - restore single product functionality
        if (elements.modalAddToCart) {
            elements.modalAddToCart.textContent = 'Add to Cart';
            elements.modalAddToCart.style.opacity = '1';
            elements.modalAddToCart.disabled = false;
            elements.modalAddToCart.onclick = async () => {
                const success = await addToCart(productID);
                if (success) {
                    showToast('✓ Added to Cart');
                    
                    // Notify navbar that cart has been updated
                    const event = new CustomEvent('cartUpdated', { detail: true });
                    window.dispatchEvent(event);
                } else {
                    alert("Error adding to cart. Please try again.");
                }
            };
        }
        
        // Mark active product card in list
        document.querySelectorAll('.collection-product-card').forEach(card => {
            card.classList.toggle('active', card.dataset.productId === String(productID));
        });
    };
    
    // Enhanced function to load and display product images
    function loadProductImages(product) {
        const productId = product.productID;
        console.log('Loading images for product ID:', productId);
        
        // First add the main image if available
        if (product.image) {
            productImages = [product.image];
            updateImageUI();
        }
        
        // Fetch all images for this product
        fetch(`get_product_images.php?id=${productId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Received image data:', data);
                if (data.images && data.images.length > 0) {
                    productImages = data.images;
                    updateImageUI();
                    // Make the main image fully visible
                    if (elements.previewImage) {
                        elements.previewImage.style.opacity = '1';
                    }
                }
            })
            .catch(error => {
                console.error('Error loading product images:', error);
                // Make the main image fully visible even on error
                if (elements.previewImage) {
                    elements.previewImage.style.opacity = '1';
                }
            });
    }
    
    // Enhanced function to update the image UI with thumbnails
    function updateImageUI() {
        console.log('Updating image UI with', productImages.length, 'images');
        
        // Update image counter
        const totalImagesEl = document.querySelector('.total-images');
        const currentImageEl = document.querySelector('.current-image');
        
        if (totalImagesEl) totalImagesEl.textContent = productImages.length;
        if (currentImageEl) currentImageEl.textContent = currentImageIndex + 1;
        
        // Show/hide image counter based on image count
        const imageCounter = document.querySelector('.image-counter');
        if (imageCounter) {
            imageCounter.style.display = productImages.length > 1 ? 'block' : 'none';
        }
        
        // Create thumbnails
        const thumbnailsContainer = document.querySelector('.image-thumbnails-container');
        if (thumbnailsContainer) {
            thumbnailsContainer.innerHTML = '';
            
            productImages.forEach((imgSrc, index) => {
                const thumbnail = document.createElement('div');
                thumbnail.className = 'image-thumbnail';
                if (index === currentImageIndex) thumbnail.classList.add('active');
                
                const img = document.createElement('img');
                img.src = imgSrc;
                img.alt = `Product thumbnail ${index + 1}`;
                img.loading = 'lazy';
                
                thumbnail.appendChild(img);
                thumbnail.addEventListener('click', (e) => {
                    e.stopPropagation();
                    showImage(index);
                });
                thumbnailsContainer.appendChild(thumbnail);
            });
        }
        
        // Show/hide thumbnails wrapper based on image count
        const thumbnailsWrapper = document.querySelector('.image-thumbnails-wrapper');
        if (thumbnailsWrapper) {
            thumbnailsWrapper.style.display = productImages.length > 1 ? 'flex' : 'none';
        }
        
        // Show/hide navigation buttons based on image count
        const navButtons = document.querySelectorAll('.img-nav-button');
        navButtons.forEach(btn => {
            btn.style.display = productImages.length > 1 ? 'flex' : 'none';
        });
    }
    
    // Enhanced function to show specific image by index with better error handling
    function showImage(index) {
        if (!productImages || productImages.length === 0) {
            console.warn('No product images available');
            return;
        }
        
        // Make sure index is within bounds
        if (index < 0) index = productImages.length - 1;
        if (index >= productImages.length) index = 0;
        
        // Update current image
        currentImageIndex = index;
        
        // Animate image change
        const img = elements.previewImage;
        if (img) {
            img.style.opacity = '0';
            
            setTimeout(() => {
                img.src = productImages[index];
                img.style.opacity = '1';
                
                // Update thumbnails - mark the current one as active
                const thumbnails = document.querySelectorAll('.image-thumbnail');
                thumbnails.forEach((thumb, i) => {
                    thumb.classList.toggle('active', i === index);
                });
                
                // Update counter
                const currentImageEl = document.querySelector('.current-image');
                if (currentImageEl) currentImageEl.textContent = index + 1;
            }, 300);
        }
    }
    
    
    
    // Optimized function to render entourage summary with improved performance and UX
    function renderEntourageSummary(products) {
        // Start with a loading skeleton while we process the data
        elements.productDetailsBlock.innerHTML = `
            <h3>Entourage Contents</h3>
            <div class="entourage-summary-loading">Processing ${products.length} products...</div>
        `;
        
        // Use setTimeout to avoid blocking the UI
        setTimeout(() => {
            // Group products by category
            const categories = {};
            const uncategorized = [];
            
            products.forEach(product => {
                const category = product.productCategory || product.typeProduct;
                if (category && category.trim() !== '') {
                    if (!categories[category]) {
                        categories[category] = [];
                    }
                    categories[category].push(product);
                } else {
                    uncategorized.push(product);
                }
            });
            
            // Add uncategorized products if any
            if (uncategorized.length > 0) {
                categories['Uncategorized'] = uncategorized;
            }
            
            // Build HTML for product details block
            let detailsHTML = '<h3>Entourage Contents</h3>';
            
            // Add category summary
            const categoryCount = Object.keys(categories).length;
            detailsHTML += `<p><strong>${categoryCount} ${categoryCount === 1 ? 'Category' : 'Categories'}</strong> with ${products.length} ${products.length === 1 ? 'product' : 'products'}</p>`;
            
            // List each category and its products
            Object.keys(categories).sort().forEach(category => {
                const productsInCategory = categories[category];
                detailsHTML += `<div class="entourage-category">
                              <p><strong>${category}</strong> (${productsInCategory.length})</p>
                              <ul class="entourage-product-list">`;
                
                productsInCategory.forEach(product => {
                    // Include product details if available
                    let productDetails = '';
                    if (product.sizeProduct) productDetails += ` | Size: ${product.sizeProduct}`;
                    if (product.colorProduct) productDetails += ` | ${product.colorProduct}`;
                    
                    detailsHTML += `<li class="entourage-product-item" data-product-id="${product.productID}">
                                  ${product.nameProduct || 'Unnamed Product'}${productDetails}
                               </li>`;
                });
                
                detailsHTML += `</ul></div>`;
            });
            
            // Update the HTML
            elements.productDetailsBlock.innerHTML = detailsHTML;
            
            // Add click handlers to product items for quick selection
            document.querySelectorAll('.entourage-product-item').forEach(item => {
                item.addEventListener('click', function() {
                    const productId = this.getAttribute('data-product-id');
                    const product = products.find(p => String(p.productID) === productId);
                    if (product) {
                        showProductPreview(product);
                        
                        // Also highlight the corresponding product card
                        document.querySelectorAll('.collection-product-card').forEach(card => {
                            card.classList.toggle('active', card.dataset.productId === productId);
                        });
                        
                        // Scroll to the product card if it's not visible
                        const activeCard = document.querySelector(`.collection-product-card[data-product-id="${productId}"]`);
                        if (activeCard) {
                            activeCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    }
                });
            });
        }, 50); // Small delay to ensure UI stays responsive
    }

    // Modify the product image container to include thumbnails and navigation
    const productImageContainer = document.querySelector('.product-image-container');
    if (productImageContainer) {
        // Add image thumbnails container and navigation buttons
        const thumbnailsHTML = `
            <div class="image-thumbnails-wrapper">
                <div class="image-thumbnails-container">
                    <!-- Thumbnails will be dynamically inserted here -->
                </div>
            </div>
            <div class="image-counter"><span class="current-image">1</span>/<span class="total-images">1</span></div>
            <button class="img-nav-button img-nav-prev" aria-label="Previous image">&lt;</button>
            <button class="img-nav-button img-nav-next" aria-label="Next image">&gt;</button>
        `;
        productImageContainer.insertAdjacentHTML('beforeend', thumbnailsHTML);
        
        // Add event listeners to navigation buttons
        const prevButton = productImageContainer.querySelector('.img-nav-prev');
        const nextButton = productImageContainer.querySelector('.img-nav-next');
        
        if (prevButton) {
            prevButton.addEventListener('click', (e) => {
                e.stopPropagation();
                showImage(currentImageIndex - 1);
            });
        }
        
        if (nextButton) {
            nextButton.addEventListener('click', (e) => {
                e.stopPropagation();
                showImage(currentImageIndex + 1);
            });
        }
    }
    
    // Update navigation button click handlers to work for both product and entourage images
    const prevButton = productImageContainer.querySelector('.img-nav-prev');
    const nextButton = productImageContainer.querySelector('.img-nav-next');
    
    if (prevButton) {
        prevButton.addEventListener('click', (e) => {
            e.stopPropagation();
            // Check if we're viewing a product or entourage
            if (productImages.length > 0) {
                showImage(currentImageIndex - 1);
            } else {
                showEntourageImage(currentImageIndex - 1);
            }
        });
    }
    
    if (nextButton) {
        nextButton.addEventListener('click', (e) => {
            e.stopPropagation();
            // Check if we're viewing a product or entourage
            if (productImages.length > 0) {
                showImage(currentImageIndex + 1);
            } else {
                showEntourageImage(currentImageIndex + 1);
            }
        });
    }
    
    // Add keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (collectionModal.style.display === 'flex') {
            // Determine which array to use based on context
            const imagesArray = productImages.length > 0 ? productImages : entourageImages;
            if (imagesArray.length > 1) {
                if (e.key === 'ArrowRight') {
                    if (productImages.length > 0) {
                        showImage(currentImageIndex + 1);
                    } else {
                        showEntourageImage(currentImageIndex + 1);
                    }
                }
                if (e.key === 'ArrowLeft') {
                    if (productImages.length > 0) {
                        showImage(currentImageIndex - 1);
                    } else {
                        showEntourageImage(currentImageIndex - 1);
                    }
                }
            }
        }
    });

    // Add click event to handle clicks outside product cards
    document.addEventListener('click', function(e) {
        // Check if modal is visible
        if (collectionModal.style.display !== 'flex') return;
        
        // Check if click is inside products list but not on a product card
        if (elements.collectionProducts && 
            elements.collectionProducts.contains(e.target) && 
            !e.target.closest('.collection-product-card')) {
            // Show entourage preview
            showEntouragePreview();
        }
    });

    // Function to show entourage image by index (similar to showImage)
    function showEntourageImage(index) {
        if (!entourageImages || entourageImages.length === 0) {
            console.warn('No entourage images available');
            return;
        }
        
        // Make sure index is within bounds
        if (index < 0) index = entourageImages.length - 1;
        if (index >= entourageImages.length) index = 0;
        
        // Update current image
        currentImageIndex = index;
        
        // Animate image change
        const img = elements.previewImage;
        if (img) {
            img.style.opacity = '0';
            
            setTimeout(() => {
                img.src = entourageImages[index];
                img.style.opacity = '1';
                
                // Update thumbnails - mark the current one as active
                const thumbnails = document.querySelectorAll('.image-thumbnail');
                thumbnails.forEach((thumb, i) => {
                    thumb.classList.toggle('active', i === index);
                });
                
                // Update counter
                const currentImageEl = document.querySelector('.current-image');
                if (currentImageEl) currentImageEl.textContent = index + 1;
            }, 300);
        }
    }
    
    // Function to update entourage image UI
    function updateEntourageImageUI() {
        // Update image counter
        const totalImagesEl = document.querySelector('.total-images');
        const currentImageEl = document.querySelector('.current-image');
        
        if (totalImagesEl) totalImagesEl.textContent = entourageImages.length;
        if (currentImageEl) currentImageEl.textContent = currentImageIndex + 1;
        
        // Show image counter based on image count
        const imageCounter = document.querySelector('.image-counter');
        if (imageCounter) {
            imageCounter.style.display = entourageImages.length > 1 ? 'block' : 'none';
        }
        
        // Create thumbnails
        const thumbnailsContainer = document.querySelector('.image-thumbnails-container');
        if (thumbnailsContainer) {
            thumbnailsContainer.innerHTML = '';
            
            // Only proceed if we have multiple images
            if (entourageImages.length > 1) {
                entourageImages.forEach((imgSrc, index) => {
                    const thumbnail = document.createElement('div');
                    thumbnail.className = 'image-thumbnail';
                    if (index === currentImageIndex) thumbnail.classList.add('active');
                    
                    const img = document.createElement('img');
                    img.src = imgSrc;
                    img.alt = `Entourage thumbnail ${index + 1}`;
                    img.loading = 'lazy';
                    
                    thumbnail.appendChild(img);
                    thumbnail.addEventListener('click', (e) => {
                        e.stopPropagation();
                        showEntourageImage(index);
                    });
                    thumbnailsContainer.appendChild(thumbnail);
                });
                
                // Show thumbnails wrapper when we have thumbnails
                const thumbnailsWrapper = document.querySelector('.image-thumbnails-wrapper');
                if (thumbnailsWrapper) {
                    thumbnailsWrapper.style.display = 'flex';
                    thumbnailsWrapper.classList.add('has-thumbnails');
                }
                
                // Show navigation buttons
                const navButtons = document.querySelectorAll('.img-nav-button');
                navButtons.forEach(btn => {
                    btn.style.display = 'flex';
                });
            }
        }
    }

    // Function to reset preview to show entourage image with improved loading states
    const showEntouragePreview = (isInitialLoad = false) => {
        // Don't update preview in multi-select mode
        if (multiSelectMode) return;

        // Reset image navigation state
        currentImageIndex = 0;
        productImages = [];
        
        // Show entourage image
        if (elements.previewImage && entourageImages.length > 0) {
            elements.previewImage.src = entourageImages[0] || currentEntourageImage;
            elements.previewImage.style.opacity = '1';
        }
        
        // Reset collection name element to show entourage name
        if (elements.collectionName) {
            elements.collectionName.textContent = currentEntourageName;
        }
        
        // Update thumbnails if entourage has multiple images
        updateEntourageImageUI();
        
        // Display entourage summary or loading state in the product details block
        if (elements.productDetailsBlock) {
            if (isInitialLoad) {
                // Show loading indicator during initial load
                elements.productDetailsBlock.innerHTML = `
                    <div style="text-align: center; padding: 15px;">
                        <div class="spinner" style="display: inline-block; width: 30px; height: 30px; border: 3px solid rgba(222, 60, 60, 0.1); border-radius: 50%; border-top-color: #de3c3c; animation: spin 1s linear infinite;"></div>
                        <p style="margin-top: 10px; color: #666;">Loading entourage details...</p>
                    </div>
                `;
            } else if (currentEntourageId && entourageCache[currentEntourageId]) {
                const products = entourageCache[currentEntourageId];
                
                if (products && products.length > 0) {
                    // Render entourage summary with product details
                    renderEntourageSummary(products);
                } else {
                    elements.productDetailsBlock.innerHTML = '<p>No products found in this entourage</p>';
                }
            } else {
                elements.productDetailsBlock.innerHTML = '<p>No entourage details available</p>';
            }
        }
        
        // Disable add to cart button
        if (elements.modalAddToCart) {
            elements.modalAddToCart.style.opacity = '0.5';
            elements.modalAddToCart.disabled = true;
            elements.modalAddToCart.textContent = 'Select a Product';
        }
        
        // Remove active class from all product cards
        document.querySelectorAll('.collection-product-card').forEach(card => {
            card.classList.remove('active');
        });
    };

    // Add CSS for visual feedback
    const style = document.createElement('style');
    style.textContent = `
        .touch-feedback {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(222, 60, 60, 0.1);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        .touch-feedback.active {
            opacity: 1;
        }
    `;
    document.head.appendChild(style);

    // Prevent text selection in the product grid to avoid interfering with long-press
    document.querySelectorAll('#collectionProducts').forEach(container => {
        container.addEventListener('mousedown', e => {
            if (e.detail > 1) { // Prevent double-click selection
                e.preventDefault();
            }
        });
    });

    // Update keyboard navigation to exit multi-select mode with ESC key
    document.addEventListener('keydown', function(e) {
        if (collectionModal.style.display === 'flex') {
            // Handle image navigation
            // ...existing code for arrow keys...
            
            // Add ESC key to cancel multi-select mode
            if (e.key === 'Escape' && multiSelectMode) {
                exitMultiSelectMode();
            }
        }
    });

    // Function to format product ID similar to PHP's formatProductId
    function formatProductId(product) {
        // If product is part of an entourage
        if (product.entourageID && product.entourageID !== '') {
            return "EN" + product.entourageID + (product.typeProduct || '') + product.productID;
        }
        // Standard product
        else if (product.categoryID && product.categoryID !== '') {
            return product.categoryID + product.productID;
        }
        // Fallback to just product ID if missing data
        return product.productID;
    }

    // New function to update the details block with the list of selected products
    function updateSelectedProductsList() {
        if (!multiSelectMode || !elements.productDetailsBlock) return;
        
        // If no products are selected yet
        if (selectedProducts.size === 0) {
            elements.productDetailsBlock.innerHTML = `
                <h3>Selected Products (0)</h3>
                <p>No products selected yet. Tap on products to select them.</p>
            `;
            return;
        }
        
        // Debug the selected products
        console.log("Selected products IDs:", Array.from(selectedProducts));
        console.log("Current entourage ID:", currentEntourageId);
        console.log("Entourage cache contents:", entourageCache);
        
        // Get products information from the cache
        const selectedProductsArray = Array.from(selectedProducts);
        const selectedProductsInfo = [];
        
        if (currentEntourageId && entourageCache[currentEntourageId]) {
            const allProducts = entourageCache[currentEntourageId];
            console.log("All products in entourage:", allProducts);
            
            selectedProductsArray.forEach(productId => {
                // Convert to string for comparison if needed
                const stringProductId = String(productId);
                // Try to find the product by string ID or numeric ID
                const product = allProducts.find(p => String(p.productID) === stringProductId);
                console.log(`Looking for product ${productId}, found:`, product);
                
                if (product) {
                    selectedProductsInfo.push(product);
                }
            });
        }
        
        console.log("Found product details:", selectedProductsInfo);
        
        // Build the HTML for the selected products list using Bootstrap grid
        let detailsHTML = `<h3>Selected Products (${selectedProducts.size})</h3>`;
        
        if (selectedProductsInfo.length > 0) {
            selectedProductsInfo.forEach(product => {
                // Format the product ID using our helper function
                const formattedId = formatProductId(product);
                
                // Build each row with col-12 containing nested col-3 items
                detailsHTML += `
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="row">
                                <div class="col-3">
                                    <strong>${product.nameProduct || 'Unnamed Product'}</strong>
                                </div>
                                <div class="col-3">
                                    <small class="text-muted">${formattedId}</small>
                                </div>
                                <div class="col-3">
                                    ${product.productCategory ? `<small class="product-category">${product.productCategory}</small>` : ''}
                                </div>
                                <div class="col-3">
                                    ${product.priceProduct ? `<span class="product-price">₱${parseFloat(product.priceProduct).toFixed(2)}</span>` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
        } else {
            // IMPORTANT: Update this fallback to show more debugging info
            detailsHTML += `
                <div class="row">
                    ${selectedProductsArray.map(id => `
                        <div class="col-12 mb-3">
                            <div class="row">
                                <div class="col-12">
                                    Product ID: ${id}
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
                <p style="color: #999; font-style: italic; margin-top: 10px;">Note: Unable to find detailed product information. This is likely a data issue.</p>
            `;
        }
        
        // Update the details block
        elements.productDetailsBlock.innerHTML = detailsHTML;
        
        // Log the final HTML for debugging
        console.log("Generated HTML for selected products:", detailsHTML);
    }
});

// Wait for the document to be fully loaded
$(document).ready(function() {
    console.log("All Products JS loaded");
    console.log("allProductsData:", window.allProductsData);
    console.log("totalProductCount:", window.totalProductCount);
    
    // Process product data on client side
    if (window.allProductsData && window.allProductsData.length > 0) {
        // Create maps to organize products and their variations
        const productsMap = new Map();
        const variationGroups = new Map(); // Group products by variation group
        let skippedSoldProducts = 0;
        
        // Variables for pagination
        const productsPerPage = 25; // products per page
        let currentPage = 1;
        let isLoading = false;
        
        // Track filtered products
        let filteredProducts = [];
        
        // Process all products to organize data
        function processProductData(productsArray) {
            // Clear previous maps
            productsMap.clear();
            variationGroups.clear();
            skippedSoldProducts = 0;
            
            // Process product data
            productsArray.forEach(item => {
                // Skip sold products
                if (item.soldProduct == 1) {
                    skippedSoldProducts++;
                    console.log(`Skipping sold product ${item.productID} (${item.nameProduct}) from grid display`);
                    return;
                }
                
                const productId = item.productID;
                
                if (!productsMap.has(productId)) {
                    // Initialize product entry
                    productsMap.set(productId, {
                        productID: productId,
                        nameProduct: item.nameProduct,
                        priceProduct: item.priceProduct,
                        productCategory: item.productCategory,
                        counterProduct: item.counterProduct || 0,
                        damageProduct: item.damageProduct || 0,
                        images: [],
                        groupId: item.group_id || null
                    });
                    
                    // Track products by variation group
                    if (item.group_id) {
                        if (!variationGroups.has(item.group_id)) {
                            variationGroups.set(item.group_id, new Set());
                        }
                        variationGroups.get(item.group_id).add(productId);
                    }
                }
                
                // Add image to product if it exists
                if (item.pictureLocation) {
                    const product = productsMap.get(productId);
                    
                    // Only add the image if it doesn't already exist
                    const imageExists = product.images.some(img => 
                        img.location === item.pictureLocation
                    );
                    
                    if (!imageExists) {
                        product.images.push({
                            location: item.pictureLocation,
                            isPrimary: item.isPrimary
                        });
                        
                        // Sort images to ensure primary image is first
                        product.images.sort((a, b) => (b.isPrimary || 0) - (a.isPrimary || 0));
                    }
                }
            });
            
            console.log(`Skipped ${skippedSoldProducts} sold products from grid display`);
            
            // Filter out variations to show only one product per group
            const uniqueProducts = [];
            const processedGroups = new Set();
            
            // Convert map to array and filter unique products
            Array.from(productsMap.values()).forEach(product => {
                // If product belongs to a variation group
                if (product.groupId) {
                    // Only add one product per group to the display
                    if (!processedGroups.has(product.groupId)) {
                        uniqueProducts.push(product);
                        processedGroups.add(product.groupId);
                        console.log(`Added product ${product.productID} as representative for group ${product.groupId}`);
                    }
                } else {
                    // Product has no variations, always include it
                    uniqueProducts.push(product);
                }
            });
            
            // Sort alphabetically by name
            const processedProducts = uniqueProducts.sort((a, b) => a.nameProduct.localeCompare(b.nameProduct));
            
            console.log(`Processed ${processedProducts.length} unique products for display after grouping variations`);
            console.log(`Found ${variationGroups.size} variation groups`);
            
            // Debug: Log variation groups
            variationGroups.forEach((productIds, groupId) => {
                console.log(`Group ${groupId} has ${productIds.size} variations: ${Array.from(productIds).join(', ')}`);
            });
            
            return processedProducts;
        }
        
        // Initial processing
        let processedProducts = processProductData(window.allProductsData);
        filteredProducts = processedProducts; // Initially, filtered products = all products
        
        // Function to display products
        function displayProducts(page, products) {
            isLoading = true;
            const startIndex = (page - 1) * productsPerPage;
            const endIndex = startIndex + productsPerPage;
            const paginatedProducts = products.slice(startIndex, endIndex);
            
            console.log(`Displaying products ${startIndex+1} to ${Math.min(endIndex, products.length)} of ${products.length}`);
            
            // Populate grid with processed data
            const productGrid = $('.product-grid');
            
            if (page === 1) {
                productGrid.empty(); // Clear only on first page load
            }
            
            // Add loading indicator for 1 second delay
            const loadingIndicator = $('<div class="loading-indicator text-center my-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading products...</p></div>');
            if (page === 1) {
                productGrid.append(loadingIndicator);
            } else {
                loadingIndicator.insertAfter(productGrid.children().last());
            }
            
            // Delay product display by 1 second
            setTimeout(function() {
                // Remove loading indicator
                loadingIndicator.remove();
                
                // Check if we have products to show
                if (paginatedProducts.length === 0) {
                    productGrid.append(`
                        <div class="no-products-message">
                            <i class="fas fa-search fa-3x mb-3"></i>
                            <h3>No products found</h3>
                            <p>Try adjusting your filters or search criteria.</p>
                        </div>
                    `);
                } else {
                    // Add active filter notification for date filters
                    if (window.dateFiltersFormatted && page === 1) {
                        const filterNotice = $(`
                            <div class="active-filter-notice">
                                <i class="fas fa-calendar-check me-2"></i>
                                Showing products available from <strong>${window.dateFiltersFormatted.pickup}</strong> 
                                to <strong>${window.dateFiltersFormatted.return}</strong>
                                <button class="btn btn-sm btn-outline-secondary ms-2 clear-date-filter-btn">
                                    Clear Date Filter
                                </button>
                            </div>
                        `);
                        productGrid.before(filterNotice);
                        
                        // Add click handler to clear date filter button
                        $('.clear-date-filter-btn').on('click', function() {
                            // Clear date inputs in filter form
                            $('#datePickup, #dateReturn').val('');
                            
                            // Clear date filter globals
                            window.dateFilters = null;
                            window.dateFiltersFormatted = null;
                            
                            // Re-apply filters to show all products
                            if (typeof resetFilters === 'function') {
                                resetFilters();
                            }
                            
                            // Remove the filter notice
                            $('.active-filter-notice').remove();
                        });
                    }
                    
                    // Add products with improved animation
                    paginatedProducts.forEach((product, index) => {
                        // Check for images and use placeholder if none available
                        let imageUrl = './assets/img/placeholder.jpg'; // Default to placeholder
                        
                        if (product.images && product.images.length > 0 && product.images[0].location) {
                            imageUrl = product.images[0].location;
                        }
                        
                        // Add variation badge if this product has variations
                        let variationBadge = '';
                        if (product.groupId && variationGroups.has(product.groupId) && variationGroups.get(product.groupId).size > 1) {
                            variationBadge = `<div class="variation-badge">${variationGroups.get(product.groupId).size} sizes</div>`;
                        }
                        
                        // Add damage badge if product is damaged
                        let damageBadge = '';
                        if (product.damageProduct === '1') {
                            damageBadge = '<div class="damage-badge">DAMAGED</div>';
                        }
                        
                        // Add availability badge if date filters are applied
                        let availabilityBadge = '';
                        if (window.dateFiltersFormatted) {
                            // Check if this product is available during the selected date range
                            const isAvailable = checkProductAvailability(product.productID);
                            if (isAvailable === true) {
                                availabilityBadge = `<div class="availability-badge">
                                    <i class="fas fa-calendar-check"></i> Available
                                </div>`;
                            } else if (isAvailable === false) {
                                availabilityBadge = `<div class="availability-badge unavailable">
                                    <i class="fas fa-calendar-times"></i> Unavailable
                                </div>`;
                            } else if (typeof isAvailable === 'object' && isAvailable.available === false && isAvailable.reason === 'size_unavailable') {
                                availabilityBadge = `<div class="availability-badge unavailable-size">
                                    <i class="fas fa-calendar-times"></i> Unavailable in size ${isAvailable.sizeProduct}
                                </div>`;
                            }
                        }
                            
                        const productItem = $(`
                            <div class="product-item" data-id="${product.productID}">
                                <div class="product-item-image-container">
                                    <img src="${imageUrl}" alt="${product.nameProduct}" class="product-item-image" 
                                         onerror="this.src='./assets/img/placeholder.jpg'; this.onerror='';">
                                    ${variationBadge}
                                    ${damageBadge}
                                    ${availabilityBadge}
                                    <div class="product-item-name-overlay">
                                        ${product.nameProduct}
                                    </div>
                                </div>
                            </div>
                        `);
                        
                        // Set initial opacity and transform for animation
                        productItem.css({
                            'opacity': 0,
                            'transform': 'translateY(20px)'
                        });
                        
                        productGrid.append(productItem);
                        
                        // Apply fade-in and slide-up animation with staggered delay
                        setTimeout(function() {
                            productItem.css({
                                'transition': 'opacity 0.5s ease, transform 0.5s ease',
                                'opacity': 1,
                                'transform': 'translateY(0)'
                            });
                        }, 50 * index); // Stagger the animations
                    });
                }
                
                // Update load more button visibility
                updateLoadMoreButton(endIndex, products.length);
                isLoading = false;
            }, 1000); // 1 second delay
        }
        
        // Function to update the load more button
        function updateLoadMoreButton(currentEndIndex, totalItems) {
            const loadMoreBtn = $('#load-more-btn');
            const loadMoreStatus = $('.load-more-status');
            
            if (currentEndIndex >= totalItems) {
                loadMoreBtn.hide();
                if (loadMoreStatus.length) {
                    loadMoreStatus.text(`Showing all ${totalItems} products`);
                } else {
                    $('<div class="load-more-status text-center mt-3">').text(`Showing all ${totalItems} products`).insertAfter(loadMoreBtn);
                }
            } else {
                loadMoreBtn.show();
                if (loadMoreStatus.length) {
                    loadMoreStatus.text(`Showing ${currentEndIndex} of ${totalItems} products`);
                } else {
                    $('<div class="load-more-status text-center mt-3">').text(`Showing ${currentEndIndex} of ${totalItems} products`).insertBefore(loadMoreBtn);
                }
            }
        }
        
        // Function to check if user has scrolled near the bottom
        function isNearBottom() {
            const windowHeight = $(window).height();
            const scrollPosition = $(window).scrollTop();
            const documentHeight = $(document).height();
            
            // Load more when user is 300px from the bottom
            return (windowHeight + scrollPosition >= documentHeight - 300);
        }
        
        // Function to check if a product is available during the selected date range
        function checkProductAvailability(productId) {
            // If no date filters are set, all products are considered available
            if (!window.dateFilters) {
                return true;
            }
            
            // Get the requested date range
            const requestedPickupDate = window.dateFilters.pickup;
            const requestedReturnDate = window.dateFilters.return;
            
            // Get product data to check for variations
            const product = window.allProductsData.find(p => p.productID == productId);
            if (!product) {
                return true; // If we can't find the product, assume it's available
            }
            
            // Get variation group ID for this product
            const variationGroupId = product.group_id;
            
            // If this product has variations (is part of a variation group)
            if (variationGroupId) {
                // Get all products in this variation group
                const variationProducts = window.allProductsData.filter(p => p.group_id === variationGroupId);
                
                // If there are other available size variations, we should show this product
                // but mark it as unavailable if this specific size is not available
                const sizeIsUnavailable = checkSizeAvailability(productId, product.sizeProduct);
                
                // If this specific size is unavailable but there are other sizes available,
                // we still show the product but mark it as unavailable
                if (sizeIsUnavailable) {
                    // Check if there are other available sizes in this variation group
                    const otherSizesAvailable = variationProducts.some(p => {
                        if (p.productID == productId) return false; // Skip the current product
                        return !checkSizeAvailability(p.productID, p.sizeProduct);
                    });
                    
                    // If there are other available sizes, mark this as a size variation issue
                    if (otherSizesAvailable) {
                        return {
                            available: false,
                            reason: 'size_unavailable',
                            sizeProduct: product.sizeProduct
                        };
                    }
                    
                    // If no other sizes are available, mark as completely unavailable
                    return false;
                }
                
                // This specific size is available
                return true;
            }
            
            // For non-variation products, check availability normally
            return checkSizeAvailability(productId);
        }

        // Helper function to check if a specific product size is available
        function checkSizeAvailability(productId, size = null) {
            // Get the requested date range
            const requestedPickupDate = window.dateFilters.pickup;
            const requestedReturnDate = window.dateFilters.return;
            
            // First check active transactions (products currently released but not returned)
            const activeTransactions = window.transactionData?.active || [];
            
            // Check if this product has an active transaction
            const activeTransaction = activeTransactions.find(t => t.productID == productId);
            if (activeTransaction) {
                // Check if the active transaction overlaps with the requested date range
                const transactionPickup = activeTransaction.datePickUp;
                const transactionReturn = activeTransaction.dateReturn;
                
                // Transaction overlaps if:
                // - Transaction pickup date is before or equal to the requested end date AND
                // - Transaction return date is after or equal to the requested start date
                const hasOverlap = transactionPickup <= requestedReturnDate && transactionReturn >= requestedPickupDate;
                
                // Product is unavailable if there's an overlap
                if (hasOverlap) {
                    return true; // Size is unavailable
                }
            }
            
            // Now check transaction history
            const transactionHistory = window.transactionData?.history || [];
            
            // Find the transactions for this product
            const productTransactions = transactionHistory.filter(t => t.productID == productId);
            
            if (productTransactions.length === 0) {
                // No transactions found, product is available
                return false; // Size is available
            }
            
            // Sort transactions by date (newest first)
            productTransactions.sort((a, b) => new Date(b.action_date) - new Date(a.action_date));
            
            // Get the most recent transaction
            const latestTransaction = productTransactions[0];
            
            // If the most recent action is RETURN, the product is available
            if (latestTransaction.action_type === 'RETURN') {
                return false; // Size is available
            }
            
            // If the most recent action is RELEASE, check if the transaction overlaps with the selected date range
            if (latestTransaction.action_type === 'RELEASE') {
                // If the transaction has datePickUp and dateReturn, check for overlap
                if (latestTransaction.datePickUp && latestTransaction.dateReturn) {
                    const transactionPickup = latestTransaction.datePickUp;
                    const transactionReturn = latestTransaction.dateReturn;
                    
                    // Check if there's an overlap
                    const hasOverlap = transactionPickup <= requestedReturnDate && transactionReturn >= requestedPickupDate;
                    
                    // Size is unavailable if there's an overlap
                    return hasOverlap;
                }
            }
            
            // Default to available if we can't determine availability
            return false; // Size is available
        }
        
        // Initial display
        displayProducts(currentPage, processedProducts);
        
        // Event handlers
        $('#load-more-btn').on('click', function() {
            if (!isLoading) {
                currentPage++;
                displayProducts(currentPage, filteredProducts);
            }
        });
        
        // Add filter integration

        // Custom event to listen for filter changes from the filter sidebar
        $(document).off('productsFiltered').on('productsFiltered', function(event, newFilteredProducts) {
            console.log('Received filtered products:', newFilteredProducts.length);
            filteredProducts = newFilteredProducts;
            currentPage = 1; // Reset to first page
            displayProducts(currentPage, filteredProducts);
        });
        
        // Make the processProductData function available globally
        window.processProductDataForFiltering = processProductData;
        
        // Add scroll-based loading
        $(window).on('scroll', function() {
            if (!isLoading && isNearBottom()) {
                const currentEndIndex = currentPage * productsPerPage;
                if (currentEndIndex < filteredProducts.length) {
                    currentPage++;
                    displayProducts(currentPage, filteredProducts);
                }
            }
        });
        
        // Process function - expose to window for use by other scripts
        window.processProductData = processProductData;
        window.displayProducts = displayProducts;
        window.updateLoadMoreButton = updateLoadMoreButton;
        window.filteredProducts = filteredProducts;
        window.currentPage = currentPage;
        
    } else {
        console.error("No product data available for All Products section");
        $('#load-more-btn').hide();
        
        // Display a message if no products
        $('.product-grid').html(`
            <div class="no-products-message">
                <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                <h3>No products available</h3>
                <p>Please check back later or contact the administrator.</p>
            </div>
        `);
    }
});
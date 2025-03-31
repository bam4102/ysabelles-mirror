$(document).ready(function() {
    console.log("Navbar search functionality initialized");
    
    // Get the search input element - make selector more specific to ensure we get the navbar search
    const searchInput = $('.navbar-main .search-form input');
    const productGrid = $('.product-grid');
    
    console.log("Search input found:", searchInput.length > 0);
    console.log("Product grid found:", productGrid.length > 0);
    
    // Add event listener for input changes (for instant search) - use 300ms debounce for better performance
    let searchTimeout;
    searchInput.on('input', function() {
        clearTimeout(searchTimeout);
        const searchQuery = $(this).val().toLowerCase().trim();
        
        // Use debounce to avoid excessive searching while typing
        searchTimeout = setTimeout(function() {
            // If search query is empty, show all products
            if (searchQuery === '') {
                // Reset to show all products
                resetSearch();
                return;
            }
            
            // Filter products based on search query
            performSearch(searchQuery);
        }, 300); // 300ms debounce
    });
    
    // Add event listener for form submission
    $('.navbar-main .search-form').on('submit', function(e) {
        e.preventDefault(); // Prevent form submission
        const searchQuery = searchInput.val().toLowerCase().trim();
        
        // Perform search
        performSearch(searchQuery);
    });
    
    // Function to perform search
    function performSearch(query) {
        console.log(`Searching for: ${query}`);
        
        // Show loading state
        $('.navbar-main .search-form').addClass('is-loading');
        
        // If there's no allProductsData available yet, we can't search
        if (!window.allProductsData || window.allProductsData.length === 0) {
            console.warn("Cannot search: Product data not available");
            $('.navbar-main .search-form').removeClass('is-loading');
            return;
        }
        
        // We'll use a slight delay to make the loading indicator visible 
        // for very fast searches (better user experience)
        setTimeout(() => {
            // Filter products based on query
            const filteredProducts = window.allProductsData.filter(product => 
                product.nameProduct.toLowerCase().includes(query) ||
                (product.productCategory && product.productCategory.toLowerCase().includes(query))
            );
            
            // Process the filtered products
            let processedProducts = [];
            
            // Check if all_products.js has the processProductData function
            if (typeof window.processProductData === 'function') {
                // Use the existing function if available
                processedProducts = window.processProductData(filteredProducts);
            } else {
                // Basic processing if the function isn't available
                const uniqueProductIds = new Set();
                processedProducts = filteredProducts.filter(item => {
                    if (!uniqueProductIds.has(item.productID) && item.soldProduct != 1) {
                        uniqueProductIds.add(item.productID);
                        return true;
                    }
                    return false;
                });
            }
            
            // Display filtered products
            displaySearchResults(processedProducts, query);
            
            // Hide loading state
            $('.navbar-main .search-form').removeClass('is-loading');
        }, 300);
    }
    
    // Function to display search results
    function displaySearchResults(products, query) {
        // Clear product grid
        productGrid.empty();
        
        // If no products found
        if (products.length === 0) {
            productGrid.append(`
                <div class="no-products-message">
                    <i class="fas fa-search fa-3x mb-3"></i>
                    <h3>No products found</h3>
                    <p>No products match your search: "${query}"</p>
                    <button class="btn btn-outline-primary mt-3 reset-search-btn">Show All Products</button>
                </div>
            `);
            
            // Update load more button if it exists
            if (typeof updateLoadMoreButton === 'function') {
                updateLoadMoreButton(0, 0);
            } else {
                $('#load-more-btn').hide();
            }
            
            return;
        }
        
        // Display search results
        products.forEach((product, index) => {
            // Check for images and use placeholder if none available
            let imageUrl = './assets/img/placeholder.jpg'; // Default to placeholder
            
            if (product.images && product.images.length > 0 && product.images[0].location) {
                imageUrl = product.images[0].location;
            } else if (product.pictureLocation) {
                imageUrl = product.pictureLocation;
            }
            
            // Simplified product display
            const productItem = $(`
                <div class="product-item" data-id="${product.productID}">
                    <div class="product-item-image-container">
                        <img src="${imageUrl}" alt="${product.nameProduct}" class="product-item-image" 
                             onerror="this.src='./assets/img/placeholder.jpg'; this.onerror='';">
                        <div class="product-item-name-overlay">
                            ${product.nameProduct}
                        </div>
                    </div>
                </div>
            `);
            
            // Set animation
            productItem.css({
                'opacity': 0,
                'transform': 'translateY(20px)'
            });
            
            productGrid.append(productItem);
            
            // Apply fade-in and slide-up animation with staggered delay
            setTimeout(function() {
                productItem.css({
                    'transition': 'opacity 0.3s ease, transform 0.3s ease',
                    'opacity': 1,
                    'transform': 'translateY(0)'
                });
            }, 30 * index);
        });
        
        // Update the search status
        if (!$('.search-status').length) {
            productGrid.before(`<div class="search-status alert alert-info">Found ${products.length} products matching "${query}" <button class="btn btn-sm btn-outline-secondary ms-2 reset-search-btn">Clear Search</button></div>`);
        } else {
            $('.search-status').html(`Found ${products.length} products matching "${query}" <button class="btn btn-sm btn-outline-secondary ms-2 reset-search-btn">Clear Search</button>`);
        }
        
        // Update load more button if it exists
        if (typeof window.updateLoadMoreButton === 'function') {
            window.updateLoadMoreButton(products.length, products.length);
        } else {
            $('#load-more-btn').hide();
        }
    }
    
    // Function to reset search
    function resetSearch() {
        // Remove search status notification
        $('.search-status').remove();
        
        // Reset the product display
        if (typeof window.displayProducts === 'function' && window.filteredProducts) {
            // Use the existing function from all_products.js if available
            window.currentPage = 1;
            window.displayProducts(1, window.filteredProducts);
        } else {
            // Basic reset if the function isn't available
            productGrid.empty();
            
            // If processProductData and allProductsData are available
            if (window.allProductsData && window.allProductsData.length > 0) {
                let processedProducts = [];
                
                if (typeof window.processProductData === 'function') {
                    processedProducts = window.processProductData(window.allProductsData);
                } else {
                    // Basic processing
                    const uniqueProductIds = new Set();
                    processedProducts = window.allProductsData.filter(item => {
                        if (!uniqueProductIds.has(item.productID) && item.soldProduct != 1) {
                            uniqueProductIds.add(item.productID);
                            return true;
                        }
                        return false;
                    });
                }
                
                // Display first 25 products
                displaySearchResults(processedProducts.slice(0, 25));
                
                // Show load more button
                $('#load-more-btn').show();
            }
        }
    }
    
    // Event delegation for reset search button
    $(document).on('click', '.reset-search-btn', function() {
        // Clear search input
        searchInput.val('');
        
        // Reset search
        resetSearch();
    });
    
    // Expose functions to window for potential use by other scripts
    window.navbarSearch = {
        performSearch,
        resetSearch
    };
}); 
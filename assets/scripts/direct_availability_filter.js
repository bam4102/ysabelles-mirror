/**
 * Direct Availability Filter Implementation
 * A simpler approach that works with the existing product structure
 */

// Wait for the document to be fully loaded
$(document).ready(function() {
    console.log("Direct Availability Filter loaded");
    
    // DOM Elements
    const datePickupInput = $('#datePickup');
    const dateReturnInput = $('#dateReturn');
    const clearFilterBtn = $('#clearFilterBtn');
    
    // Track filter state
    let activeFilter = {
        isActive: false,
        startDate: null,
        endDate: null,
        availableProducts: []
    };
    
    // Set minimum date to today for date pickers
    const today = new Date();
    const formattedToday = today.toISOString().split('T')[0]; // Format: YYYY-MM-DD
    datePickupInput.attr('min', formattedToday);
    dateReturnInput.attr('min', formattedToday);
    
    // Add event listener to ensure return date is not before pickup date
    datePickupInput.on('change', function() {
        dateReturnInput.attr('min', $(this).val());
        // If return date is before pickup date, reset it
        if (dateReturnInput.val() && dateReturnInput.val() < $(this).val()) {
            dateReturnInput.val($(this).val());
        }
    });
    
    // Intercept the main form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        
        // Get date values
        const startDate = datePickupInput.val();
        const endDate = dateReturnInput.val();
        
        // Only proceed with availability filter if both dates are set
        if (startDate && endDate) {
            applyAvailabilityFilter(startDate, endDate);
        }
        
        // Allow the form to continue with other filters
        return true;
    });
    
    // Hook into the existing Apply Filter button
    $('.filter-section button[type="submit"]').on('click', function(e) {
        const startDate = datePickupInput.val();
        const endDate = dateReturnInput.val();
        
        // Only proceed with availability filter if both dates are set
        if (startDate && endDate) {
            e.preventDefault(); // Prevent default form submission
            applyAvailabilityFilter(startDate, endDate);
        }
    });
    
    // Listen for "Load More" button clicks and product grid updates
    $(document).on('click', '#load-more-btn', function() {
        // If we have an active filter, apply it to newly loaded products after a short delay
        if (activeFilter.isActive) {
            setTimeout(function() {
                applyFilterToCurrentProducts(activeFilter.availableProducts);
            }, 500); // Short delay to ensure new products are loaded
        }
    });
    
    // Also listen for scroll-based loading
    $(window).on('scroll', function() {
        // If we have an active filter and new products might be loading
        if (activeFilter.isActive) {
            // Use a debounce technique to avoid excessive calls
            clearTimeout(window.scrollDebounceTimer);
            window.scrollDebounceTimer = setTimeout(function() {
                applyFilterToCurrentProducts(activeFilter.availableProducts);
            }, 300);
        }
    });
    
    // Listen for product grid updates from other filters
    $(document).on('productsFiltered', function() {
        // If we have an active filter, reapply it after other filters
        if (activeFilter.isActive) {
            setTimeout(function() {
                applyFilterToCurrentProducts(activeFilter.availableProducts);
            }, 300);
        }
    });
    
    // Hook into the displayProducts function to apply filter to newly loaded products
    const originalDisplayProducts = window.displayProducts;
    if (originalDisplayProducts) {
        window.displayProducts = function(page, products) {
            // Call the original function first
            originalDisplayProducts(page, products);
            
            // Then apply our filter if active
            if (activeFilter.isActive) {
                setTimeout(function() {
                    applyFilterToCurrentProducts(activeFilter.availableProducts);
                }, 100);
            }
        };
    }
    
    // Function to apply filter to current products in the DOM
    function applyFilterToCurrentProducts(availableProducts) {
        console.log('Applying filter to current products in DOM');
        
        // Apply filter to product items
        $('.product-item').each(function() {
            const productId = $(this).data('id');
            
            if (productId && !availableProducts.includes(parseInt(productId))) {
                // Product is not available
                $(this).addClass('unavailable');
                
                // Add or update badge
                const imageContainer = $(this).find('.product-item-image-container');
                let badge = imageContainer.find('.availability-badge');
                
                if (badge.length === 0) {
                    // Create new badge
                    badge = $('<div class="availability-badge unavailable"><i class="fas fa-calendar-times"></i> Unavailable</div>');
                    imageContainer.append(badge);
                } else {
                    // Update existing badge
                    badge.removeClass('available').addClass('unavailable');
                    badge.html('<i class="fas fa-calendar-times"></i> Unavailable');
                }
            } else if (productId) {
                // Product is available
                $(this).removeClass('unavailable');
                
                // Add or update badge
                const imageContainer = $(this).find('.product-item-image-container');
                let badge = imageContainer.find('.availability-badge');
                
                if (badge.length === 0) {
                    // Create new badge
                    badge = $('<div class="availability-badge available"><i class="fas fa-calendar-check"></i> Available</div>');
                    imageContainer.append(badge);
                } else {
                    // Update existing badge
                    badge.removeClass('unavailable').addClass('available');
                    badge.html('<i class="fas fa-calendar-check"></i> Available');
                }
            }
        });
    }
    
    // Function to apply the availability filter
    function applyAvailabilityFilter(startDate, endDate) {
        console.log('Applying availability filter:', { startDate, endDate });
        
        // Show loading overlay
        $('body').append('<div class="availability-loading-overlay"><div class="spinner"></div></div>');
        
        // Call the availability filter API
        $.ajax({
            url: 'availability_filter.php',
            type: 'GET',
            data: {
                startDate: startDate,
                endDate: endDate
            },
            dataType: 'json',
            success: function(response) {
                console.log('Filter API response:', response);
                
                if (response.error) {
                    console.error('API Error:', response.error);
                    alert('Error: ' + response.error);
                    $('.availability-loading-overlay').remove();
                    return;
                }
                
                const availableProducts = response.available;
                
                // Update active filter state
                activeFilter = {
                    isActive: true,
                    startDate: startDate,
                    endDate: endDate,
                    availableProducts: availableProducts
                };
                
                // Apply filter to current products
                applyFilterToCurrentProducts(availableProducts);
                
                // Show filter notice
                const formattedStart = new Date(startDate).toLocaleDateString('en-US', {
                    month: 'short', day: 'numeric'
                });
                const formattedEnd = new Date(endDate).toLocaleDateString('en-US', {
                    month: 'short', day: 'numeric'
                });
                
                // Remove existing notice
                $('.active-filter-notice').remove();
                
                // Create new notice
                const notice = $(`
                    <div class="active-filter-notice">
                        <i class="fas fa-calendar-check me-2"></i>
                        Showing products available from <strong>${formattedStart}</strong> 
                        to <strong>${formattedEnd}</strong>
                        <button class="btn btn-sm btn-outline-secondary ms-2" id="clearFilterNoticeBtn">
                            Clear Date Filter
                        </button>
                    </div>
                `);
                
                // Add notice to page
                $('.product-grid').before(notice);
                
                // Add click handler to clear button
                $('#clearFilterNoticeBtn').on('click', function() {
                    clearFilterBtn.click();
                });
                
                // Remove loading overlay
                $('.availability-loading-overlay').remove();
                
                // Store filter state in sessionStorage for persistence
                sessionStorage.setItem('availabilityFilter', JSON.stringify({
                    startDate: startDate,
                    endDate: endDate,
                    availableProducts: availableProducts
                }));
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('Error applying filter. Please try again.');
                $('.availability-loading-overlay').remove();
            }
        });
    }
    
    // Clear filter button click handler
    clearFilterBtn.on('click', function(e) {
        e.preventDefault();
        console.log('Clear filter button clicked');
        
        // Clear date inputs
        datePickupInput.val('');
        dateReturnInput.val('');
        
        // Reset active filter state
        activeFilter = {
            isActive: false,
            startDate: null,
            endDate: null,
            availableProducts: []
        };
        
        // Remove filter notice
        $('.active-filter-notice').remove();
        
        // Reset product items
        $('.product-item').removeClass('unavailable');
        $('.availability-badge').remove();
        
        // Clear sessionStorage
        sessionStorage.removeItem('availabilityFilter');
    });
    
    // Check for existing filter in sessionStorage on page load
    const savedFilter = sessionStorage.getItem('availabilityFilter');
    if (savedFilter) {
        try {
            const filterData = JSON.parse(savedFilter);
            if (filterData.startDate && filterData.endDate && filterData.availableProducts) {
                // Set date inputs
                datePickupInput.val(filterData.startDate);
                dateReturnInput.val(filterData.endDate);
                
                // Apply filter
                setTimeout(function() {
                    applyAvailabilityFilter(filterData.startDate, filterData.endDate);
                }, 500);
            }
        } catch (e) {
            console.error('Error parsing saved filter:', e);
            sessionStorage.removeItem('availabilityFilter');
        }
    }
    
    // Add CSS for loading overlay and badges
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .availability-loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(255, 255, 255, 0.7);
                z-index: 9999;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            
            .availability-loading-overlay .spinner {
                width: 50px;
                height: 50px;
                border: 5px solid #f3f3f3;
                border-top: 5px solid #3498db;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .product-item.unavailable .product-item-image-container::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.2);
                z-index: 1;
            }
            
            .availability-badge {
                position: absolute;
                top: 10px;
                left: 10px;
                padding: 5px 10px;
                border-radius: 20px;
                font-size: 0.8rem;
                z-index: 2;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                color: white;
                width: 50%; /* Set badge to half-width */
            }
            
            .availability-badge.available {
                background-color: #28a745;
            }
            
            .availability-badge.unavailable {
                background-color: #dc3545;
            }
            
            .active-filter-notice {
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                padding: 10px 15px;
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                font-size: 0.9rem;
            }
        `)
        .appendTo('head');
    
    // Set up MutationObserver to watch for new products being added
    const productGridObserver = new MutationObserver((mutations) => {
        if (activeFilter.isActive) {
            mutations.forEach((mutation) => {
                if (mutation.addedNodes.length > 0) {
                    // Check if any of the added nodes are product items
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1 && node.classList.contains('product-item')) {
                            const productId = $(node).data('id');
                            if (productId) {
                                // Apply filter to this specific product
                                const isAvailable = activeFilter.availableProducts.includes(parseInt(productId));
                                const imageContainer = $(node).find('.product-item-image-container');
                                
                                if (!isAvailable) {
                                    $(node).addClass('unavailable');
                                    const badge = $('<div class="availability-badge unavailable"><i class="fas fa-calendar-times"></i> Unavailable</div>');
                                    imageContainer.append(badge);
                                } else {
                                    $(node).removeClass('unavailable');
                                    const badge = $('<div class="availability-badge available"><i class="fas fa-calendar-check"></i> Available</div>');
                                    imageContainer.append(badge);
                                }
                            }
                        }
                    });
                }
            });
        }
    });
    
    // Start observing the product grid once the document is ready
    $(document).ready(() => {
        const productGrid = document.querySelector('.product-grid');
        if (productGrid) {
            productGridObserver.observe(productGrid, {
                childList: true,
                subtree: true
            });
        }
    });
    
    // Listen for "Load More" button clicks
    $(document).on('click', '#load-more-btn', function() {
        if (activeFilter.isActive) {
            // Add a small delay to ensure new products are in the DOM
            setTimeout(() => {
                applyFilterToCurrentProducts(activeFilter.availableProducts);
            }, 300);
        }
    });
});

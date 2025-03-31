/**
 * Filter Sidebar Component
 * Handles the filter sidebar UI and functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const filterSidebar = document.getElementById('filterSidebar');
    const filterBackdrop = document.getElementById('filterBackdrop');
    const closeFilterBtn = document.getElementById('closeFilterBtn');
    const filterForm = document.getElementById('filterForm');
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');
    const applyFiltersBtn = document.getElementById('applyFiltersBtn');
    const filterPopupBtn = document.getElementById('filterPopupBtn');
    
    // Date picker elements
    const datePickupInput = document.getElementById('datePickup');
    const dateReturnInput = document.getElementById('dateReturn');
    
    // Set minimum date to today for date pickers
    const today = new Date();
    const formattedToday = today.toISOString().split('T')[0]; // Format: YYYY-MM-DD
    if (datePickupInput) datePickupInput.min = formattedToday;
    if (dateReturnInput) dateReturnInput.min = formattedToday;
    
    // Add event listener to ensure return date is not before pickup date
    if (datePickupInput && dateReturnInput) {
        datePickupInput.addEventListener('change', function() {
            dateReturnInput.min = this.value;
            // If return date is before pickup date, reset it
            if (dateReturnInput.value && dateReturnInput.value < this.value) {
                dateReturnInput.value = this.value;
            }
            
            // Update availability notice
            updateAvailabilityNotice();
        });
        
        dateReturnInput.addEventListener('change', function() {
            // Update availability notice
            updateAvailabilityNotice();
        });
    }
    
    // State
    let activeFilters = 0;
    
    // Show Filter Sidebar
    function showFilterSidebar() {
        filterSidebar.classList.add('show');
        filterBackdrop.classList.add('show');
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }
    
    // Hide Filter Sidebar
    function hideFilterSidebar() {
        filterSidebar.classList.remove('show');
        filterBackdrop.classList.remove('show');
        document.body.style.overflow = ''; // Restore scrolling
    }
    
    // Update filter badge count
    function updateFilterBadge(count) {
        activeFilters = count;
        
        // Update filter button in All Products section
        if (filterPopupBtn) {
            // Update button text to show active filter count
            const btnText = filterPopupBtn.querySelector('.btn-text') || filterPopupBtn;
            if (count > 0) {
                if (!filterPopupBtn.querySelector('.filter-count-badge')) {
                    const countBadge = document.createElement('span');
                    countBadge.className = 'filter-count-badge';
                    countBadge.textContent = count;
                    filterPopupBtn.appendChild(countBadge);
                } else {
                    filterPopupBtn.querySelector('.filter-count-badge').textContent = count;
                }
                filterPopupBtn.classList.add('has-active-filters');
            } else {
                const countBadge = filterPopupBtn.querySelector('.filter-count-badge');
                if (countBadge) {
                    countBadge.remove();
                }
                filterPopupBtn.classList.remove('has-active-filters');
            }
        }
    }
    
    // Count number of active filters
    function countActiveFilters(filters) {
        let count = 0;
        
        if (filters.location) count++;
        if (filters.size) count++;
        if (filters.bust_min || filters.bust_max) count++;
        if (filters.waist_min || filters.waist_max) count++;
        if (filters.length_min || filters.length_max) count++;
        if (filters.price_min || filters.price_max) count++;
        if (filters.date_pickup || filters.date_return) count++;
        
        return count;
    }
    
    // Reset filter form values
    function resetFilters() {
        filterForm.reset();
        
        // Apply empty filters to reset to all products
        const emptyFilters = {
            location: '',
            size: '',
            bust_min: '',
            bust_max: '',
            waist_min: '',
            waist_max: '',
            length_min: '',
            length_max: '',
            price_min: '',
            price_max: '',
            date_pickup: '',
            date_return: ''
        };
        
        // Reset to all products
        filterProducts(emptyFilters);
        
        // Update filter badge
        updateFilterBadge(0);
        
        // Hide the sidebar
        hideFilterSidebar();
    }
    
    // Apply Filters
    function applyFilters(event) {
        // Ensure the event object exists and prevent default behavior
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        // Get all filter values
        const filters = {
            location: document.getElementById('locationFilter').value,
            size: document.getElementById('sizeFilter').value,
            bust_min: document.getElementById('bustMin').value,
            bust_max: document.getElementById('bustMax').value,
            waist_min: document.getElementById('waistMin').value,
            waist_max: document.getElementById('waistMax').value,
            length_min: document.getElementById('lengthMin').value,
            length_max: document.getElementById('lengthMax').value,
            price_min: document.getElementById('priceMin').value,
            price_max: document.getElementById('priceMax').value,
            date_pickup: document.getElementById('datePickup').value,
            date_return: document.getElementById('dateReturn').value
        };
        
        // Log the filter values for debugging
        console.log('Applying filters:', filters);
        
        // Count and update active filters
        const activeFilterCount = countActiveFilters(filters);
        updateFilterBadge(activeFilterCount);

        // Handle date filters separately if they are present
        if (filters.date_pickup && filters.date_return) {
            window.dateFilters = {
                pickup: filters.date_pickup,
                return: filters.date_return
            };
            
            // Format dates for display
            const formattedPickup = new Date(filters.date_pickup).toLocaleDateString('en-US', {
                month: 'short', day: 'numeric'
            });
            const formattedReturn = new Date(filters.date_return).toLocaleDateString('en-US', {
                month: 'short', day: 'numeric'
            });
            
            window.dateFiltersFormatted = {
                pickup: formattedPickup,
                return: formattedReturn
            };
        } else {
            window.dateFilters = null;
            window.dateFiltersFormatted = null;
        }

        // Apply the filters to the products
        filterProducts(filters);
        
        // Hide the sidebar after applying filters
        hideFilterSidebar();
    }
    
    // Check if a product is available during the selected date range
    function isProductAvailable(product, pickupDate, returnDate) {
        // If no dates selected, product is considered available
        if (!pickupDate || !returnDate) return true;
        
        // Convert to date objects
        const requestStart = new Date(pickupDate);
        const requestEnd = new Date(returnDate);
        
        // Reset time part to ensure proper comparison
        requestStart.setHours(0, 0, 0, 0);
        requestEnd.setHours(23, 59, 59, 999);
        
        // First check: See if the product has active reservations that overlap with our date range
        // These come directly from the server and include pre-booked items
        const reservations = window.reservations || [];
        
        if (Array.isArray(reservations) && reservations.length > 0) {
            // Find reservations for this product
            const productReservations = reservations.filter(r => r.productID == product.productID);
            
            // Check if any reservations overlap with our requested date range
            const hasOverlap = productReservations.some(reservation => {
                // Convert reservation dates to Date objects
                const reservationStart = new Date(reservation.datePickUp);
                const reservationEnd = new Date(reservation.dateReturn);
                
                // Reset time parts
                reservationStart.setHours(0, 0, 0, 0);
                reservationEnd.setHours(23, 59, 59, 999);
                
                // Check for overlap
                // Two date ranges overlap if:
                // - The start of range 1 is before or equal to the end of range 2 AND
                // - The end of range 1 is after or equal to the start of range 2
                return (requestStart <= reservationEnd && requestEnd >= reservationStart);
            });
            
            if (hasOverlap) {
                console.log(`Product ${product.productID} has overlapping reservations and is not available.`);
                return false;
            }
        }
        
        // Second check: Use the transaction history as a backup to check current status
        const transactions = window.transactions || [];
        
        if (Array.isArray(transactions) && transactions.length > 0) {
            // Find all transactions for this product
            const productTransactions = transactions.filter(t => 
                t.productID == product.productID && t.action_type === 'RELEASE'
            );
            
            // For each release transaction, check if there's a corresponding return
            for (const tx of productTransactions) {
                // Find corresponding return transaction
                const returnTx = transactions.find(t => 
                    t.productID == product.productID && 
                    t.transactionID == tx.transactionID && 
                    t.action_type === 'RETURN'
                );
                
                // If no return transaction found, the product is currently out
                if (!returnTx) {
                    console.log(`Product ${product.productID} has been released but not returned yet.`);
                    return false;
                }
            }
        }
        
        // If we made it here, the product should be available
        console.log(`Product ${product.productID} is available for the requested period.`);
        return true;
    }
    
    // Function to update availability notice
    function updateAvailabilityNotice() {
        const datePickup = datePickupInput.value;
        const dateReturn = dateReturnInput.value;
        
        // Find parent filter section
        const filterSection = dateReturnInput.closest('.filter-section');
        
        // Remove existing notice if any
        const existingNotice = filterSection.querySelector('.availability-notice');
        if (existingNotice) {
            existingNotice.remove();
        }
        
        // If both dates are set, show the notice
        if (datePickup && dateReturn) {
            const notice = document.createElement('div');
            notice.className = 'availability-notice';
            notice.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i> Checking availability...`;
            
            // Add notice to filter section
            filterSection.appendChild(notice);
            
            // Format dates for display
            const formattedPickup = new Date(datePickup).toLocaleDateString('en-US', {
                month: 'short', day: 'numeric'
            });
            const formattedReturn = new Date(dateReturn).toLocaleDateString('en-US', {
                month: 'short', day: 'numeric'
            });
            
            // Call the API to check availability
            checkAvailabilityAPI(datePickup, dateReturn)
                .then(data => {
                    if (data.success) {
                        const availableCount = data.data.availableCount;
                        const totalCount = data.data.totalCount;
                        
                        if (availableCount === 0) {
                            // No products available
                            notice.className = 'availability-notice unavailable';
                            notice.innerHTML = `<i class="fas fa-times-circle me-1"></i> 
                                No products available for ${formattedPickup} - ${formattedReturn}.`;
                        } else {
                            // Some products available
                            notice.className = 'availability-notice available';
                            notice.innerHTML = `<i class="fas fa-check-circle me-1"></i> 
                                ${availableCount} of ${totalCount} products available for 
                                ${formattedPickup} - ${formattedReturn}.`;
                        }
                        
                        // Store the available products in the window for later use
                        window.availableProductIds = data.data.availableProducts.map(p => p.productID);
                    } else {
                        // API call failed
                        notice.className = 'availability-notice warning';
                        notice.innerHTML = `<i class="fas fa-exclamation-triangle me-1"></i> 
                            ${data.message || "Error checking availability"}`;
                    }
                })
                .catch(error => {
                    console.error("Error checking availability:", error);
                    notice.className = 'availability-notice warning';
                    notice.innerHTML = `<i class="fas fa-exclamation-triangle me-1"></i> 
                        Error checking availability. Using local data instead.`;
                    
                    // Fallback to local data
                    fallbackToLocalAvailabilityCheck(notice, datePickup, dateReturn, formattedPickup, formattedReturn);
                });
        }
    }
    
    // Fallback function to use local data for availability
    function fallbackToLocalAvailabilityCheck(notice, datePickup, dateReturn, formattedPickup, formattedReturn) {
        // Get count of available products using local data
        const availableCount = getAvailableProductsCount(datePickup, dateReturn);
        const totalProducts = (window.allProductsData || []).filter(p => p.soldProduct != 1).length;
        
        if (availableCount === -1) {
            // No transaction data available
            notice.className = 'availability-notice warning';
            notice.innerHTML = `<i class="fas fa-exclamation-triangle me-1"></i> 
                Can't check availability for ${formattedPickup} - ${formattedReturn}.`;
        } else if (availableCount === 0) {
            // No products available
            notice.className = 'availability-notice unavailable';
            notice.innerHTML = `<i class="fas fa-times-circle me-1"></i> 
                No products available for ${formattedPickup} - ${formattedReturn}.`;
        } else {
            // Some products available
            notice.className = 'availability-notice available';
            notice.innerHTML = `<i class="fas fa-check-circle me-1"></i> 
                ${availableCount} of ${totalProducts} products available for 
                ${formattedPickup} - ${formattedReturn}.`;
        }
    }
    
    // Function to call the API for availability check
    function checkAvailabilityAPI(startDate, endDate) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: './assets/api/check_availability.php',
                type: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate
                },
                dataType: 'json',
                success: function(response) {
                    resolve(response);
                },
                error: function(xhr, status, error) {
                    console.error("API call failed:", error);
                    reject(error);
                }
            });
        });
    }
    
    // Function to get count of available products for a date range
    function getAvailableProductsCount(pickupDate, returnDate) {
        // If no date data, return -1 (unknown)
        if (!pickupDate || !returnDate || 
            (!window.reservations && !window.transactions)) {
            return -1;
        }
        
        // Get all products
        const allProducts = window.allProductsData || [];
        if (!Array.isArray(allProducts) || allProducts.length === 0) {
            return 0;
        }
        
        // Count available products
        const availableProducts = allProducts.filter(product => 
            product.soldProduct != 1 && isProductAvailable(product, pickupDate, returnDate)
        );
        
        return availableProducts.length;
    }
    
    // Update the filter products function to use the API data for availability filtering
    function filterProducts(filters) {
        // Get all products from global variable
        const allProducts = window.allProductsData || [];
        
        if (!Array.isArray(allProducts) || allProducts.length === 0) {
            console.error('No products data available');
            return;
        }
        
        console.log('Filtering products with criteria:', filters);
        console.log('Total products before filtering:', allProducts.length);

        // Check if we're filtering by measurements (bust, waist, length)
        const hasMeasurementFilters = filters.bust_min || filters.bust_max || 
                                    filters.waist_min || filters.waist_max || 
                                    filters.length_min || filters.length_max;

        // Apply filters
        const filteredProducts = allProducts.filter(product => {
            // Skip sold products
            if (product.soldProduct == 1) {
                return false;
            }
            
            // Location filter
            if (filters.location && product.locationProduct !== filters.location) {
                return false;
            }
            
            // If filtering by size, exclude products with measurements
            if (filters.size) {
                // Skip products that have any measurements
                if (product.bustProduct || product.waistProduct || product.lengthProduct) {
                    return false;
                }
                
                // Apply size filter - partial case-insensitive match
                if (product.sizeProduct) {
                    const size = product.sizeProduct.toString().toLowerCase();
                    const filterSize = filters.size.toLowerCase();
                    if (!size.includes(filterSize)) {
                        return false;
                    }
                } else {
                    return false; // Skip products without size when filtering by size
                }
            }
            
            // If filtering by measurements, exclude products with size
            if (hasMeasurementFilters && product.sizeProduct) {
                return false;
            }
            
            // Bust range filter
            if (filters.bust_min || filters.bust_max) {
                // Skip products without bust measurements when filtering by bust
                if (!product.bustProduct) return false;
                
                const bust = parseFloat(product.bustProduct);
                if (isNaN(bust)) return false;
                
                if (filters.bust_min && bust < parseFloat(filters.bust_min)) return false;
                if (filters.bust_max && bust > parseFloat(filters.bust_max)) return false;
            }
            
            // Waist range filter
            if (filters.waist_min || filters.waist_max) {
                // Skip products without waist measurements when filtering by waist
                if (!product.waistProduct) return false;
                
                const waist = parseFloat(product.waistProduct);
                if (isNaN(waist)) return false;
                
                if (filters.waist_min && waist < parseFloat(filters.waist_min)) return false;
                if (filters.waist_max && waist > parseFloat(filters.waist_max)) return false;
            }
            
            // Length range filter
            if (filters.length_min || filters.length_max) {
                // Skip products without length measurements when filtering by length
                if (!product.lengthProduct) return false;
                
                const length = parseFloat(product.lengthProduct);
                if (isNaN(length)) return false;
                
                if (filters.length_min && length < parseFloat(filters.length_min)) return false;
                if (filters.length_max && length > parseFloat(filters.length_max)) return false;
            }

            // Price range filter
            if (filters.price_min || filters.price_max) {
                // Skip products without price when filtering by price
                if (!product.priceProduct) return false;
                
                const price = parseFloat(product.priceProduct);
                if (isNaN(price)) return false;
                
                if (filters.price_min && price < parseFloat(filters.price_min)) return false;
                if (filters.price_max && price > parseFloat(filters.price_max)) return false;
            }
            
            return true;
        });
        
        console.log('Filtered products count:', filteredProducts.length);
        
        // Process the filtered products if necessary
        let processedProducts = filteredProducts;
        if (window.processProductDataForFiltering) {
            processedProducts = window.processProductDataForFiltering(filteredProducts);
            console.log('Processed filtered products:', processedProducts.length);
        }
        
        // Trigger the custom event for the product grid to update
        $(document).trigger('productsFiltered', [processedProducts]);
    }
    
    // Event Listeners
    if (filterPopupBtn) {
        filterPopupBtn.addEventListener('click', showFilterSidebar);
    }
    
    if (closeFilterBtn) {
        closeFilterBtn.addEventListener('click', hideFilterSidebar);
    }
    
    if (filterBackdrop) {
        filterBackdrop.addEventListener('click', hideFilterSidebar);
    }
    
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', resetFilters);
    }
    
    // Add event listener for apply filters button click
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', applyFilters);
    }
    
    // Add event listener for form submit
    if (filterForm) {
        filterForm.addEventListener('submit', applyFilters);
    }
    
    // Close sidebar with ESC key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && filterSidebar.classList.contains('show')) {
            hideFilterSidebar();
        }
    });

    // Get filter sections
    const sizeFilterSection = document.querySelector('.filter-section:has(#sizeFilter)');
    const measurementSections = document.querySelectorAll('.filter-section:has([id^="bust"], [id^="waist"], [id^="length"])');
    
    // Add input event listeners to handle mutual exclusion
    if (sizeFilterSection && measurementSections.length > 0) {
        const sizeInput = document.getElementById('sizeFilter');
        const measurementInputs = document.querySelectorAll('#bustMin, #bustMax, #waistMin, #waistMax, #lengthMin, #lengthMax');
        
        sizeInput.addEventListener('input', function() {
            const hasSizeValue = this.value.trim().length > 0;
            measurementSections.forEach(section => {
                section.classList.toggle('disabled', hasSizeValue);
            });
        });
        
        measurementInputs.forEach(input => {
            input.addEventListener('input', function() {
                const hasMeasurementValue = Array.from(measurementInputs).some(inp => inp.value.trim().length > 0);
                sizeFilterSection.classList.toggle('disabled', hasMeasurementValue);
            });
        });
    }
});
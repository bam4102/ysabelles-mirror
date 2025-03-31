/**
 * Availability Filter
 * Handles date range filtering for product availability
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Availability filter script loaded');
    
    // DOM Elements
    const datePickupInput = document.getElementById('datePickup');
    const dateReturnInput = document.getElementById('dateReturn');
    const applyFilterBtn = document.getElementById('applyFilterBtn');
    const clearFilterBtn = document.getElementById('clearFilterBtn');
    
    console.log('Filter elements:', { 
        datePickupInput, 
        dateReturnInput, 
        applyFilterBtn, 
        clearFilterBtn 
    });
    
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
        });
    }
    
    // Apply filter button click handler
    if (applyFilterBtn) {
        console.log('Adding click handler to apply filter button');
        applyFilterBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Apply filter button clicked');
            
            const startDate = datePickupInput.value;
            const endDate = dateReturnInput.value;
            
            console.log('Selected dates:', { startDate, endDate });
            
            if (!startDate || !endDate) {
                alert('Please select both pickup and return dates');
                return;
            }
            
            // Show loading indicator
            document.body.classList.add('loading');
            
            // Call the availability filter API
            const apiUrl = `availability_filter.php?startDate=${startDate}&endDate=${endDate}`;
            console.log('Calling API:', apiUrl);
            
            fetch(apiUrl)
                .then(response => {
                    console.log('API response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('API response data:', data);
                    
                    if (data.error) {
                        console.error('Error:', data.error);
                        alert('Error applying filter: ' + data.error);
                        document.body.classList.remove('loading');
                        return;
                    }
                    
                    // Store available products in localStorage
                    localStorage.setItem('availableProducts', JSON.stringify(data.available));
                    localStorage.setItem('dateFilter', JSON.stringify({
                        startDate: data.startDate,
                        endDate: data.endDate
                    }));
                    
                    // Apply filter to current page
                    applyAvailabilityFilter(data.available);
                    
                    // Show filter notice
                    showFilterNotice(data.startDate, data.endDate);
                    
                    // Hide loading indicator
                    document.body.classList.remove('loading');
                })
                .catch(error => {
                    console.error('API call error:', error);
                    alert('Error applying filter. Please try again.');
                    document.body.classList.remove('loading');
                });
        });
    } else {
        console.error('Apply filter button not found in the DOM');
    }
    
    // Clear filter button click handler
    if (clearFilterBtn) {
        console.log('Adding click handler to clear filter button');
        clearFilterBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Clear filter button clicked');
            
            // Clear date inputs
            if (datePickupInput) datePickupInput.value = '';
            if (dateReturnInput) dateReturnInput.value = '';
            
            // Clear localStorage
            localStorage.removeItem('availableProducts');
            localStorage.removeItem('dateFilter');
            
            // Remove filter notice
            const filterNotice = document.querySelector('.active-filter-notice');
            if (filterNotice) filterNotice.remove();
            
            // Show all products
            document.querySelectorAll('.product-item').forEach(card => {
                card.style.display = '';
                const imageContainer = card.querySelector('.product-item-image-container');
                if (imageContainer) {
                    const badge = imageContainer.querySelector('.availability-badge');
                    if (badge) badge.remove();
                }
            });
        });
    } else {
        console.error('Clear filter button not found in the DOM');
    }
    
    // Check if we have a stored filter
    const storedFilter = localStorage.getItem('dateFilter');
    const storedProducts = localStorage.getItem('availableProducts');
    
    if (storedFilter && storedProducts) {
        console.log('Found stored filter:', JSON.parse(storedFilter));
        console.log('Found stored products count:', JSON.parse(storedProducts).length);
        
        const filter = JSON.parse(storedFilter);
        const availableProducts = JSON.parse(storedProducts);
        
        // Set date inputs
        if (datePickupInput) datePickupInput.value = filter.startDate;
        if (dateReturnInput) dateReturnInput.value = filter.endDate;
        
        // Apply filter
        applyAvailabilityFilter(availableProducts);
        
        // Show filter notice
        showFilterNotice(filter.startDate, filter.endDate);
    } else {
        console.log('No stored filter found');
    }
    
    // Function to apply filter to current page
    function applyAvailabilityFilter(availableProducts) {
        const productItems = document.querySelectorAll('.product-item');
        
        console.log('Applying filter to', productItems.length, 'products');
        console.log('Available products count:', availableProducts.length);
        
        if (productItems.length === 0) {
            console.error('No product items found on the page');
            return;
        }
        
        // Debug: Check the first few product items
        const sampleItems = Array.from(productItems).slice(0, 3);
        sampleItems.forEach(item => {
            console.log('Sample product item:', {
                element: item,
                dataId: item.getAttribute('data-id'),
                classes: item.className,
                html: item.outerHTML.substring(0, 100) + '...'
            });
        });
        
        let availableCount = 0;
        let unavailableCount = 0;
        
        productItems.forEach(item => {
            const productId = item.getAttribute('data-id');
            
            if (!productId) {
                console.error('Product item missing data-id attribute:', item);
                return;
            }
            
            const isAvailable = availableProducts.includes(parseInt(productId));
            
            if (!isAvailable) {
                // Product is not available - add overlay and badge
                item.classList.add('unavailable');
                unavailableCount++;
                
                // Add unavailable badge if not already present
                const imageContainer = item.querySelector('.product-item-image-container');
                if (imageContainer) {
                    // Remove any existing badge
                    const existingBadge = imageContainer.querySelector('.availability-badge');
                    if (existingBadge) existingBadge.remove();
                    
                    // Add unavailable badge
                    const badge = document.createElement('div');
                    badge.className = 'availability-badge unavailable';
                    badge.innerHTML = '<i class="fas fa-calendar-times"></i> Unavailable';
                    imageContainer.appendChild(badge);
                }
            } else {
                // Product is available
                item.classList.remove('unavailable');
                availableCount++;
                
                // Add available badge if not already present
                const imageContainer = item.querySelector('.product-item-image-container');
                if (imageContainer) {
                    // Remove any existing badge
                    const existingBadge = imageContainer.querySelector('.availability-badge');
                    if (existingBadge) existingBadge.remove();
                    
                    // Add available badge
                    const badge = document.createElement('div');
                    badge.className = 'availability-badge';
                    badge.innerHTML = '<i class="fas fa-calendar-check"></i> Available';
                    imageContainer.appendChild(badge);
                }
            }
        });
        
        console.log('Filter applied:', { availableCount, unavailableCount });
    }
    
    // Function to show filter notice
    function showFilterNotice(startDate, endDate) {
        // Remove existing notice
        const existingNotice = document.querySelector('.active-filter-notice');
        if (existingNotice) existingNotice.remove();
        
        // Format dates for display
        const formattedStart = new Date(startDate).toLocaleDateString('en-US', {
            month: 'short', day: 'numeric'
        });
        const formattedEnd = new Date(endDate).toLocaleDateString('en-US', {
            month: 'short', day: 'numeric'
        });
        
        // Create notice
        const notice = document.createElement('div');
        notice.className = 'active-filter-notice';
        notice.innerHTML = `
            <i class="fas fa-calendar-check me-2"></i>
            Showing products available from <strong>${formattedStart}</strong> 
            to <strong>${formattedEnd}</strong>
            <button class="btn btn-sm btn-outline-secondary ms-2" id="clearFilterNoticeBtn">
                Clear Date Filter
            </button>
        `;
        
        // Add notice to page
        const productGrid = document.querySelector('.product-grid');
        if (productGrid) {
            productGrid.parentNode.insertBefore(notice, productGrid);
            console.log('Added filter notice before product grid');
        } else {
            console.error('Product grid not found, cannot add filter notice');
        }
        
        // Add click handler to clear button
        const clearBtn = document.getElementById('clearFilterNoticeBtn');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                console.log('Clear filter notice button clicked');
                // Clear filter
                if (clearFilterBtn) clearFilterBtn.click();
            });
        }
    }
});

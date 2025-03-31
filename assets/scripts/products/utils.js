// Utility functions for product management

/**
 * Determine product status based on product data
 * @param {Object} product - The product object
 * @returns {string} HTML string representing the status badge(s)
 */
export function getProductStatus(product) {
    const badges = [];
    
    if (product.damageProduct == 1) {
        badges.push('<span class="badge bg-danger">Damaged</span>');
    }
    
    if (product.soldProduct == 1) {
        badges.push('<span class="badge bg-success">Sold</span>');
    }
    
    if (product.useProduct == 1) {
        badges.push('<span class="badge bg-primary">In Use</span>');
    }
    
    if (product.returnedProduct == 1) {
        badges.push('<span class="badge bg-warning">Released</span>');
    }
    
    if (product.isNew == 1) {
        badges.push('<span class="badge status-new">New</span>');
    }
    
    // If no status badges were added, the product is available
    if (badges.length === 0) {
        badges.push('<span class="badge bg-info">Available</span>');
    }
    
    // Join all badges with a small gap
    return badges.join(' ');
}

/**
 * Normalize product image URL to handle different formats
 * @param {string} imageUrl - The image URL from the database
 * @returns {string} Normalized image URL
 */
export function normalizeImageUrl(imageUrl) {
    if (!imageUrl) return './assets/img/placeholder.jpg';
    
    // If it's already an absolute URL or starts with a dot, return as is
    if (imageUrl.startsWith('http') || imageUrl.startsWith('./')) {
        return imageUrl;
    }
    
    // Ensure the path starts with ./
    if (!imageUrl.startsWith('/')) {
        return './' + imageUrl;
    }
    
    return imageUrl;
}

/**
 * Update URL with current filters, sort, and page
 * @param {string} categoryFilter - Selected category
 * @param {string} locationFilter - Selected location
 * @param {string} genderFilter - Selected gender
 * @param {string} searchFilter - Search query
 * @param {number} currentPage - Current page number
 * @param {string} sortField - Field to sort by
 * @param {string} sortDirection - Sort direction (asc/desc)
 * @param {boolean} showVariations - Whether to group size variations
 * @param {Object} statusFilters - Status filters (damaged, sold, inUse, returned, available)
 * @param {Object} attributeFilters - Attribute filters (size, color, bust, waist, length)
 * @param {string} entourageFilter - Selected entourage
 */
export function updateUrl(
    categoryFilter, 
    locationFilter, 
    genderFilter, 
    searchFilter, 
    currentPage, 
    sortField, 
    sortDirection,
    showVariations = false,
    statusFilters = {
        damaged: false,
        sold: false,
        returned: false,
        available: true,
        new: false
    },
    attributeFilters = {
        size: '',
        color: '',
        bust: '',
        waist: '',
        length: ''
    },
    entourageFilter = ''
) {
    // Create URL object with current page URL
    const url = new URL(window.location.href);
    
    // Set or remove parameters based on filter values
    if (categoryFilter) {
        url.searchParams.set('category', categoryFilter);
    } else {
        url.searchParams.delete('category');
    }
    
    if (locationFilter) {
        url.searchParams.set('location', locationFilter);
    } else {
        url.searchParams.delete('location');
    }
    
    if (genderFilter) {
        url.searchParams.set('gender', genderFilter);
    } else {
        url.searchParams.delete('gender');
    }
    
    if (searchFilter) {
        url.searchParams.set('search', searchFilter);
    } else {
        url.searchParams.delete('search');
    }
    
    if (entourageFilter) {
        url.searchParams.set('entourage', entourageFilter);
    } else {
        url.searchParams.delete('entourage');
    }
    
    if (currentPage && currentPage > 1) {
        url.searchParams.set('page', currentPage);
    } else {
        url.searchParams.delete('page');
    }
    
    if (sortField && sortField !== 'productID') {
        url.searchParams.set('sort', sortField);
    } else {
        url.searchParams.delete('sort');
    }
    
    if (sortDirection && sortDirection !== 'desc') {
        url.searchParams.set('order', sortDirection);
    } else {
        url.searchParams.delete('order');
    }
    
    if (showVariations) {
        url.searchParams.set('variations', '1');
    } else {
        url.searchParams.delete('variations');
    }
    
    // Add status filters to URL parameters
    if (statusFilters.damaged) {
        url.searchParams.set('damaged', '1');
    } else {
        url.searchParams.delete('damaged');
    }
    
    if (statusFilters.sold) {
        url.searchParams.set('sold', '1');
    } else {
        url.searchParams.delete('sold');
    }
    
    if (statusFilters.returned) {
        url.searchParams.set('returned', '1');
    } else {
        url.searchParams.delete('returned');
    }
    
    if (statusFilters.available) {
        url.searchParams.set('available', '1');
    } else {
        url.searchParams.delete('available');
    }
    
    if (statusFilters.new) {
        url.searchParams.set('new', '1');
    } else {
        url.searchParams.delete('new');
    }
    
    // Add attribute filters to URL parameters
    if (attributeFilters.size) {
        url.searchParams.set('size', attributeFilters.size);
    } else {
        url.searchParams.delete('size');
    }
    
    if (attributeFilters.color) {
        url.searchParams.set('color', attributeFilters.color);
    } else {
        url.searchParams.delete('color');
    }
    
    if (attributeFilters.bust) {
        url.searchParams.set('bust', attributeFilters.bust);
    } else {
        url.searchParams.delete('bust');
    }
    
    if (attributeFilters.waist) {
        url.searchParams.set('waist', attributeFilters.waist);
    } else {
        url.searchParams.delete('waist');
    }
    
    if (attributeFilters.length) {
        url.searchParams.set('length', attributeFilters.length);
    } else {
        url.searchParams.delete('length');
    }
    
    // Update URL without reloading page
    window.history.pushState({}, '', url.toString());
}

/**
 * Get URL parameters and return filter values
 * @returns {Object} Object containing filter values from URL
 */
export function getUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // Default status filter: available products are shown by default only if no other filters are selected
    const statusFilters = {
        damaged: urlParams.has('damaged'),
        sold: urlParams.has('sold'),
        returned: urlParams.has('returned'),
        available: urlParams.has('available'),
        new: urlParams.has('new')
    };
    
    // If no status filters are explicitly set, default to showing available products
    if (!urlParams.has('damaged') && !urlParams.has('sold') && !urlParams.has('returned') && !urlParams.has('new') && !urlParams.has('available')) {
        statusFilters.available = true;
    }
    
    // Get attribute filters from URL
    const attributeFilters = {
        size: urlParams.get('size') || '',
        color: urlParams.get('color') || '',
        bust: urlParams.get('bust') || '',
        waist: urlParams.get('waist') || '',
        length: urlParams.get('length') || ''
    };
    
    return {
        categoryFilter: urlParams.get('category') || '',
        locationFilter: urlParams.get('location') || '',
        genderFilter: urlParams.get('gender') || '',
        searchFilter: urlParams.get('search') || '',
        entourageFilter: urlParams.get('entourage') || '',
        currentPage: parseInt(urlParams.get('page') || '1'),
        sortField: urlParams.get('sort') || 'productID',
        sortDirection: urlParams.get('order') || 'desc',
        showVariations: urlParams.has('variations'),
        statusFilters: statusFilters,
        attributeFilters: attributeFilters
    };
} 
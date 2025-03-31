// Filter module for product management
import { updateUrl } from './utils.js';
import ProductConfig from './config.js';

/**
 * Filter and sort products based on selected criteria
 * @param {Array} allProducts - All products array
 * @param {string} categoryFilter - Selected category
 * @param {string} locationFilter - Selected location
 * @param {string} genderFilter - Selected gender
 * @param {string} searchFilter - Search query
 * @param {string} sortField - Field to sort by
 * @param {string} sortDirection - Sort direction (asc/desc)
 * @param {boolean} showVariations - Whether to group size variations
 * @param {Object} statusFilters - Status filters (damaged, sold, inUse, returned, available, new)
 * @param {Object} attributeFilters - Attribute filters (size, color, bust, waist, length)
 * @param {string} entourageFilter - Selected entourage
 * @returns {Array} Filtered and sorted products
 */
export function filterAndSortProducts(
    allProducts, 
    categoryFilter, 
    locationFilter, 
    genderFilter, 
    searchFilter, 
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
    // Debug input parameters
    console.log('Filter inputs:', { 
        productsCount: allProducts.length,
        categoryFilter, 
        locationFilter, 
        genderFilter, 
        searchFilter,
        statusFilters,
        attributeFilters,
        entourageFilter
    });

    // Apply filters
    let filteredProducts = allProducts.filter(product => {
        const categoryMatch = !categoryFilter || product.categoryID == categoryFilter;
        const locationMatch = !locationFilter || product.locationProduct === locationFilter;
        const genderMatch = !genderFilter || product.genderProduct === genderFilter;
        const entourageMatch = !entourageFilter || product.entourageID == entourageFilter;
        
        const nameProduct = product.nameProduct ? product.nameProduct.toLowerCase() : '';
        const codeProduct = product.codeProduct ? product.codeProduct.toLowerCase() : '';
        const productID = product.productID ? product.productID.toString().toLowerCase() : '';
        const searchLower = searchFilter ? searchFilter.toLowerCase() : '';
        const searchMatch = !searchFilter || 
                          nameProduct.includes(searchLower) || 
                          codeProduct.includes(searchLower) ||
                          productID.includes(searchLower);
        
        // Status filtering logic
        let statusMatch = false;
        if (statusFilters.damaged && product.damageProduct == 1) {
            statusMatch = true;
        } else if (statusFilters.sold && product.soldProduct == 1) {
            statusMatch = true;
        } else if (statusFilters.returned && product.returnedProduct == 1) {
            statusMatch = true;
        } else if (statusFilters.new && product.isNew == 1) {
            statusMatch = true;
        } else if (statusFilters.available && 
                 product.damageProduct != 1 && 
                 product.soldProduct != 1 && 
                 product.useProduct != 1 && 
                 product.returnedProduct != 1) {
            statusMatch = true;
        }
        
        // If no status filters are checked, show all products
        if (!statusFilters.damaged && 
            !statusFilters.sold && 
            !statusFilters.returned && 
            !statusFilters.new &&
            !statusFilters.available) {
            statusMatch = true;
        }

        // Size filter - case insensitive partial match
        const sizeMatch = !attributeFilters.size || 
                         (product.sizeProduct && 
                          product.sizeProduct.toString().toLowerCase().includes(attributeFilters.size.toLowerCase()));

        // Color filter - case insensitive partial match
        const colorMatch = !attributeFilters.color || 
                          (product.colorProduct && 
                           product.colorProduct.toLowerCase().includes(attributeFilters.color.toLowerCase()));

        // Measurements filtering - exact numeric match
        let bustMatch = true, waistMatch = true, lengthMatch = true;

        if (attributeFilters.bust) {
            const bust = parseFloat(product.bustProduct);
            if (isNaN(bust)) {
                bustMatch = false;
            } else {
                bustMatch = bust === parseFloat(attributeFilters.bust);
            }
        }

        if (attributeFilters.waist) {
            const waist = parseFloat(product.waistProduct);
            if (isNaN(waist)) {
                waistMatch = false;
            } else {
                waistMatch = waist === parseFloat(attributeFilters.waist);
            }
        }

        if (attributeFilters.length) {
            const length = parseFloat(product.lengthProduct);
            if (isNaN(length)) {
                lengthMatch = false;
            } else {
                lengthMatch = length === parseFloat(attributeFilters.length);
            }
        }
        
        return categoryMatch && locationMatch && genderMatch && entourageMatch && 
               searchMatch && statusMatch && sizeMatch && colorMatch && 
               bustMatch && waistMatch && lengthMatch;
    });

    // Debug filtered count
    console.log('After filtering:', filteredProducts.length);
    
    // Handle product variation grouping if enabled
    if (showVariations) {
        const processedGroups = new Set();
        const results = [];
        
        // First, add all products that don't have variations
        filteredProducts.forEach(product => {
            if (!product.variationGroupId) {
                results.push(product);
            } else if (!processedGroups.has(product.variationGroupId)) {
                // Mark this group as processed and add the primary product
                processedGroups.add(product.variationGroupId);
                
                // Find all products in this group that match filters
                const groupProducts = filteredProducts.filter(p => 
                    p.variationGroupId === product.variationGroupId
                );
                
                // If there are multiple products, mark this as having variations
                if (groupProducts.length > 1) {
                    // Use the first product as the main one and add variation info
                    const mainProduct = { ...product, hasVariations: true, variationProducts: groupProducts };
                    results.push(mainProduct);
                } else {
                    // Only one product in this group, add it normally
                    results.push(product);
                }
            }
        });
        
        filteredProducts = results;
    }
    
    // Apply sorting
    filteredProducts.sort((a, b) => {
        let valA = a[sortField];
        let valB = b[sortField];
        
        // Handle numeric values
        if (sortField === 'productID' || sortField === 'priceProduct' || 
            sortField === 'bustProduct' || sortField === 'waistProduct' || sortField === 'lengthProduct') {
            valA = parseFloat(valA) || 0; // Convert to number, default to 0 if NaN
            valB = parseFloat(valB) || 0;
            return sortDirection === 'asc' ? valA - valB : valB - valA;
        }
        
        // Handle strings (with null checking)
        valA = valA ? valA.toString() : '';
        valB = valB ? valB.toString() : '';
        return sortDirection === 'asc' 
                ? valA.localeCompare(valB) 
                : valB.localeCompare(valA);
    });
    
    return filteredProducts;
}

/**
 * Apply filters and update state
 * @param {Object} state - Current application state
 * @param {Function} renderTable - Function to render the table
 * @param {Function} renderPagination - Function to render pagination
 */
export function applyFilters(state, renderTable, renderPagination) {
    // Get filter values - adding null checks
    const categoryEl = document.getElementById('category');
    const locationEl = document.getElementById('location');
    const genderEl = document.getElementById('gender');
    const searchEl = document.getElementById('search');
    const showVariationsEl = document.getElementById('showVariations');
    const entourageEl = document.getElementById('entourage');
    
    const categoryFilter = categoryEl ? categoryEl.value : '';
    const locationFilter = locationEl ? locationEl.value : '';
    const genderFilter = genderEl ? genderEl.value : '';
    const searchFilter = searchEl ? searchEl.value : '';
    const showVariations = showVariationsEl ? showVariationsEl.checked : false;
    const entourageFilter = entourageEl ? entourageEl.value : '';
    
    // Get status filter values with proper null checks
    const showDamagedEl = document.getElementById('showDamaged');
    const showSoldEl = document.getElementById('showSold');
    const showReturnedEl = document.getElementById('showReturned');
    const showAvailableEl = document.getElementById('showAvailable');
    const showNewEl = document.getElementById('showNew');
    
    const statusFilters = {
        damaged: showDamagedEl ? showDamagedEl.checked : false,
        sold: showSoldEl ? showSoldEl.checked : false,
        returned: showReturnedEl ? showReturnedEl.checked : false,
        available: showAvailableEl ? showAvailableEl.checked : false,
        new: showNewEl ? showNewEl.checked : false
    };
    
    // If no status filter is checked, auto-select "available" for better UX
    if (!statusFilters.damaged && !statusFilters.sold && !statusFilters.returned && !statusFilters.new && !statusFilters.available) {
        statusFilters.available = true;
    }
    
    // Get attribute filter values
    const sizeFilterEl = document.getElementById('sizeFilter');
    const colorFilterEl = document.getElementById('colorFilter');
    const bustFilterEl = document.getElementById('bustFilter');
    const waistFilterEl = document.getElementById('waistFilter');
    const lengthFilterEl = document.getElementById('lengthFilter');
    
    const attributeFilters = {
        size: sizeFilterEl ? sizeFilterEl.value : '',
        color: colorFilterEl ? colorFilterEl.value : '',
        bust: bustFilterEl ? bustFilterEl.value : '',
        waist: waistFilterEl ? waistFilterEl.value : '',
        length: lengthFilterEl ? lengthFilterEl.value : ''
    };
    
    // Set current page to 1 when filter changes
    state.currentPage = 1;
    
    // Get sort field and direction from the DOM/state
    const sortField = document.getElementById('sortField') ? document.getElementById('sortField').value : state.sortField;
    const sortDirection = document.getElementById('sortDirection') ? document.getElementById('sortDirection').value : state.sortDirection;
    
    // Update state with filtered products
    state.filteredProducts = filterAndSortProducts(
        state.allProducts,
        categoryFilter,
        locationFilter,
        genderFilter,
        searchFilter,
        sortField,
        sortDirection,
        showVariations,
        statusFilters,
        attributeFilters,
        entourageFilter
    );
    
    console.log(`Applied filters: ${state.filteredProducts.length} products in filtered list`);
    
    // Update display
    renderTable();
    renderPagination();
    
    // Update URL for bookmarking
    updateUrl(
        categoryFilter, 
        locationFilter, 
        genderFilter, 
        searchFilter, 
        state.currentPage, 
        sortField, 
        sortDirection,
        showVariations,
        statusFilters,
        attributeFilters,
        entourageFilter
    );
}

/**
 * Reset all filters to default values
 * @param {Object} state - Current application state
 * @param {NodeList} filterControls - All filter control elements
 * @param {Function} renderTable - Function to render the table
 * @param {Function} renderPagination - Function to render pagination
 */
export function resetFilters(state, filterControls, renderTable, renderPagination) {
    // Reset filter controls
    filterControls.forEach(control => {
        if (control.type === 'checkbox') {
            // Only check "Available" by default, uncheck others
            if (control.id === 'showAvailable') {
                control.checked = true;
            } else {
                control.checked = false;
            }
        } else if (control.type === 'select-one') {
            control.value = '';
        } else {
            control.value = '';
        }
    });
    
    // Ensure entourage filter is reset
    const entourageEl = document.getElementById('entourage');
    if (entourageEl) {
        entourageEl.value = '';
    }
    
    // Reset to page 1
    state.currentPage = 1;
    
    // Trigger filter application
    applyFilters(state, renderTable, renderPagination);
}

/**
 * Handle sort column click
 * @param {Object} state - Current application state
 * @param {HTMLElement} header - Clicked header element
 * @param {NodeList} sortableHeaders - All sortable header elements
 * @param {Function} renderTable - Function to render the table
 * @param {Function} renderPagination - Function to render pagination
 */
export function handleSort(state, header, sortableHeaders, renderTable, renderPagination) {
    const field = header.dataset.sort;
    
    // Toggle sort direction if clicking the same header
    if (field === state.sortField) {
        state.sortDirection = state.sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        state.sortField = field;
        state.sortDirection = 'asc';
    }
    
    // Clear any existing sort indicators
    sortableHeaders.forEach(h => {
        h.classList.remove('asc', 'desc');
    });
    
    // Add sort indicator
    header.classList.add(state.sortDirection);
    
    // Apply filters and sort with new sort settings - adding null checks
    const categoryEl = document.getElementById('category');
    const locationEl = document.getElementById('location');
    const genderEl = document.getElementById('gender');
    const searchEl = document.getElementById('search');
    const showVariationsEl = document.getElementById('showVariations');
    
    const categoryFilter = categoryEl ? categoryEl.value : '';
    const locationFilter = locationEl ? locationEl.value : '';
    const genderFilter = genderEl ? genderEl.value : '';
    const searchFilter = searchEl ? searchEl.value : '';
    const showVariations = showVariationsEl ? showVariationsEl.checked : false;
    
    // Get status filter values with proper null checks and no default true
    const showDamagedEl = document.getElementById('showDamaged');
    const showSoldEl = document.getElementById('showSold');
    const showReturnedEl = document.getElementById('showReturned');
    const showAvailableEl = document.getElementById('showAvailable');
    const showNewEl = document.getElementById('showNew');

    const statusFilters = {
        damaged: showDamagedEl ? showDamagedEl.checked : false,
        sold: showSoldEl ? showSoldEl.checked : false,
        returned: showReturnedEl ? showReturnedEl.checked : false,
        available: showAvailableEl ? showAvailableEl.checked : false,
        new: showNewEl ? showNewEl.checked : false
    };
    
    // If no status filter is checked, auto-select "available" for better UX
    if (!statusFilters.damaged && !statusFilters.sold && !statusFilters.returned && !statusFilters.new && !statusFilters.available) {
        statusFilters.available = true;
    }
    
    // Get attribute filter values
    const sizeFilterEl = document.getElementById('sizeFilter');
    const colorFilterEl = document.getElementById('colorFilter');
    const bustFilterEl = document.getElementById('bustFilter');
    const waistFilterEl = document.getElementById('waistFilter');
    const lengthFilterEl = document.getElementById('lengthFilter');
    
    const attributeFilters = {
        size: sizeFilterEl ? sizeFilterEl.value : '',
        color: colorFilterEl ? colorFilterEl.value : '',
        bust: bustFilterEl ? bustFilterEl.value : '',
        waist: waistFilterEl ? waistFilterEl.value : '',
        length: lengthFilterEl ? lengthFilterEl.value : ''
    };
    
    state.filteredProducts = filterAndSortProducts(
        state.allProducts,
        categoryFilter,
        locationFilter,
        genderFilter,
        searchFilter,
        state.sortField,
        state.sortDirection,
        showVariations,
        statusFilters,
        attributeFilters
    );
    
    renderTable();
    renderPagination();
    
    // Update URL
    updateUrl(
        categoryFilter, 
        locationFilter, 
        genderFilter, 
        searchFilter, 
        state.currentPage, 
        state.sortField, 
        state.sortDirection,
        showVariations,
        statusFilters,
        attributeFilters
    );
}
// Main product management module
import ProductConfig from './config.js';
import { getUrlParameters } from './utils.js';
import { filterAndSortProducts, applyFilters, resetFilters, handleSort } from './filter.js';
import { renderPagination } from './pagination.js';
import { renderTable } from './table.js';
import { initLightbox, openProductLightbox, showSingleImageLightbox } from './lightbox.js';
import { initBackupRender, debugRenderProducts } from './backup-render.js';
import { loadProductData } from './data-loader.js';

// Make functions globally available for use in inline scripts or other modules
window.openProductLightbox = openProductLightbox;
window.showSingleImageLightbox = showSingleImageLightbox;
window.debugRenderProducts = debugRenderProducts;

document.addEventListener('DOMContentLoaded', function () {
    // Initialize lightbox functionality
    initLightbox();

    // Initialize backup rendering
    initBackupRender();

    // Create sort inputs if they don't exist
    if (!document.getElementById('sortField')) {
        const sortFieldInput = document.createElement('input');
        sortFieldInput.type = 'hidden';
        sortFieldInput.id = 'sortField';
        sortFieldInput.value = 'productID';
        document.body.appendChild(sortFieldInput);
    }

    if (!document.getElementById('sortDirection')) {
        const sortDirectionInput = document.createElement('input');
        sortDirectionInput.type = 'hidden';
        sortDirectionInput.id = 'sortDirection';
        sortDirectionInput.value = 'desc';
        document.body.appendChild(sortDirectionInput);
    }

    // Make sure we can access the products
    const productsData = window.allProducts;

    // Check if allProducts is available
    if (!productsData || !Array.isArray(productsData) || productsData.length === 0) {
        console.error('Products data is not available or empty:', productsData);
        document.getElementById('productTableBody').innerHTML =
            '<tr><td colspan="11" class="text-center text-danger">Error loading product data. Please refresh the page.</td></tr>';
        document.getElementById('loadingOverlay').style.display = 'none';
        return;
    }

    console.log('Total products loaded:', productsData.length);

    // State object to hold application state
    const state = {
        allProducts: productsData,
        filteredProducts: [...productsData], // Initialize with all products
        currentPage: 1,
        sortField: ProductConfig.defaultSortField,
        sortDirection: ProductConfig.defaultSortDirection
    };

    // DOM Elements
    const filterControls = document.querySelectorAll('.filter-control');
    const resetButton = document.getElementById('resetFilters');
    const tableBody = document.getElementById('productTableBody');
    const pagination = document.getElementById('pagination');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const sortableHeaders = document.querySelectorAll('th[data-sort]');
    const editProductModal = new bootstrap.Modal(document.getElementById('editProductModal'));
    const editProductContent = document.getElementById('editProductContent');

    // Make refreshProductTable function globally available
    window.refreshProductTable = function () {
        // Re-fetch the products data
        fetch('assets/controllers/products/get_products.php')
            .then(response => {
                // Check if the response is ok
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                // Clone the response for debugging if needed
                const clonedResponse = response.clone();

                // Try to parse as JSON
                return response.json().catch(err => {
                    // If JSON parsing fails, log the actual response content
                    console.error('Error parsing JSON response:', err);
                    return clonedResponse.text().then(text => {
                        console.error('Raw response content:', text);
                        throw new Error('Invalid JSON in response');
                    });
                });
            })
            .then(data => {
                if (data.success) {
                    // Update the state with new data
                    state.allProducts = data.products;

                    // Apply any updates from local cache if needed
                    state.allProducts.forEach(product => {
                        // Ensure all status fields have numeric values
                        product.damageProduct = parseInt(product.damageProduct || 0);
                        product.soldProduct = parseInt(product.soldProduct || 0);
                        product.useProduct = parseInt(product.useProduct || 0);
                        product.returnedProduct = parseInt(product.returnedProduct || 0);
                    });

                    // Clone the products array to ensure the table re-renders
                    state.filteredProducts = [...state.allProducts];

                    // Re-apply current filters and sorting
                    applyFilters(
                        state,
                        () => renderTable(state, tableBody, editProductModal, editProductContent),
                        () => renderPagination(state, pagination, () => renderTable(state, tableBody, editProductModal, editProductContent))
                    );
                } else {
                    console.error('Failed to refresh products:', data.message);
                }
            })
            .catch(error => {
                console.error('Error refreshing products:', error);

                // Display user-friendly error message
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
                alertDiv.setAttribute('role', 'alert');
                alertDiv.innerHTML = `
                    <strong>Error!</strong> Failed to refresh products. Please try again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.body.appendChild(alertDiv);

                // Remove alert after 5 seconds
                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);
            });
    };

    // Add event listeners for filter controls
    filterControls.forEach(control => {
        control.addEventListener('input', () => applyFilters(
            state,
            () => renderTable(state, tableBody, editProductModal, editProductContent),
            () => renderPagination(state, pagination, () => renderTable(state, tableBody, editProductModal, editProductContent))
        ));

        control.addEventListener('change', () => applyFilters(
            state,
            () => renderTable(state, tableBody, editProductModal, editProductContent),
            () => renderPagination(state, pagination, () => renderTable(state, tableBody, editProductModal, editProductContent))
        ));
    });

    // Reset filters button
    resetButton.addEventListener('click', () => resetFilters(
        state,
        filterControls,
        () => renderTable(state, tableBody, editProductModal, editProductContent),
        () => renderPagination(state, pagination, () => renderTable(state, tableBody, editProductModal, editProductContent))
    ));

    // Add sorting functionality
    sortableHeaders.forEach(header => {
        header.addEventListener('click', () => handleSort(
            state,
            header,
            sortableHeaders,
            () => renderTable(state, tableBody, editProductModal, editProductContent),
            () => renderPagination(state, pagination, () => renderTable(state, tableBody, editProductModal, editProductContent))
        ));

        // Add pointer cursor to indicate sortable columns
        header.style.cursor = 'pointer';
    });

    // Initialize with URL parameters
    applyUrlParameters();

    // Initialize table rendering
    initializeTable();

    // Hide loading overlay
    loadingOverlay.style.display = 'none';

    /**
     * Apply URL parameters to the filters
     */
    function applyUrlParameters() {
        const params = getUrlParameters();

        // Add null checks for all elements
        const categoryEl = document.getElementById('category');
        const locationEl = document.getElementById('location');
        const genderEl = document.getElementById('gender');
        const searchEl = document.getElementById('search');
        const showVariationsEl = document.getElementById('showVariations');
        const showDamagedEl = document.getElementById('showDamaged');
        const showSoldEl = document.getElementById('showSold');
        const showReturnedEl = document.getElementById('showReturned');
        const showAvailableEl = document.getElementById('showAvailable');
        const showNewEl = document.getElementById('showNew');
        const entourageEl = document.getElementById('entourage');

        // Get measurement and attribute filter elements
        const sizeFilterEl = document.getElementById('sizeFilter');
        const colorFilterEl = document.getElementById('colorFilter');
        const bustFilterEl = document.getElementById('bustFilter');
        const waistFilterEl = document.getElementById('waistFilter');
        const lengthFilterEl = document.getElementById('lengthFilter');

        if (params.categoryFilter && categoryEl) {
            categoryEl.value = params.categoryFilter;
        }

        if (params.locationFilter && locationEl) {
            locationEl.value = params.locationFilter;
        }

        if (params.genderFilter && genderEl) {
            genderEl.value = params.genderFilter;
        }

        if (params.searchFilter && searchEl) {
            searchEl.value = params.searchFilter;
        }
        
        if (params.entourageFilter && entourageEl) {
            entourageEl.value = params.entourageFilter;
        }

        if (showVariationsEl) {
            showVariationsEl.checked = params.showVariations;
        }

        // Apply status filter parameters
        if (params.statusFilters) {
            console.log("URL status filters:", params.statusFilters);
            if (showDamagedEl) showDamagedEl.checked = params.statusFilters.damaged;
            if (showSoldEl) showSoldEl.checked = params.statusFilters.sold;
            if (showReturnedEl) showReturnedEl.checked = params.statusFilters.returned;
            if (showAvailableEl) showAvailableEl.checked = params.statusFilters.available;
            if (showNewEl) showNewEl.checked = params.statusFilters.new;
        }

        // Apply attribute filter parameters
        if (params.attributeFilters) {
            console.log("URL attribute filters:", params.attributeFilters);
            if (sizeFilterEl && params.attributeFilters.size) sizeFilterEl.value = params.attributeFilters.size;
            if (colorFilterEl && params.attributeFilters.color) colorFilterEl.value = params.attributeFilters.color;
            if (bustFilterEl && params.attributeFilters.bust) bustFilterEl.value = params.attributeFilters.bust;
            if (waistFilterEl && params.attributeFilters.waist) waistFilterEl.value = params.attributeFilters.waist;
            if (lengthFilterEl && params.attributeFilters.length) lengthFilterEl.value = params.attributeFilters.length;
        }

        state.currentPage = params.currentPage || 1;
        state.sortField = params.sortField || ProductConfig.defaultSortField;
        state.sortDirection = params.sortDirection || ProductConfig.defaultSortDirection;
    }

    /**
     * Initialize table with filters and sorting
     */
    function initializeTable() {
        // Get filter values with null checks
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
            attributeFilters,
            entourageFilter
        );

        console.log('Filtered products:', state.filteredProducts.length);

        renderTable(state, tableBody, editProductModal, editProductContent);
        renderPagination(state, pagination, () => renderTable(state, tableBody, editProductModal, editProductContent));
    }

    // Filter categories based on gender selection
    const genderSelect = document.getElementById('gender');
    const categorySelect = document.getElementById('category');

    // Store original category options
    const originalCategories = Array.from(categorySelect.options);

    // Function to filter categories based on selected gender
    function filterCategories() {
        const selectedGender = genderSelect.value;

        // Reset to original options first
        categorySelect.innerHTML = '';
        originalCategories.forEach(option => {
            categorySelect.add(option.cloneNode(true));
        });

        // If no gender selected, keep all categories
        if (!selectedGender) return;

        // Filter categories by selected gender
        const filteredCategories = window.categories.filter(cat =>
            cat.genderCategory === selectedGender
        );

        // Get IDs of categories that match the gender
        const matchingCategoryIds = new Set(filteredCategories.map(cat => cat.categoryID.toString()));

        // Remove options that don't match
        Array.from(categorySelect.options).forEach(option => {
            if (option.value && !matchingCategoryIds.has(option.value)) {
                categorySelect.removeChild(option);
            }
        });
    }

    // Apply filter when gender selection changes
    genderSelect.addEventListener('change', filterCategories);
}); 
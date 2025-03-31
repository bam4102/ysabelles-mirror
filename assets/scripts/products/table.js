// Table module for product management
import ProductConfig from './config.js';
import { getProductStatus, normalizeImageUrl } from './utils.js';
import { showProductDetails } from './product-details.js';

/**
 * Render the product table with the current page of products
 * @param {Object} state - Current application state
 * @param {HTMLElement} tableBodyElement - The table body element
 * @param {Object} bootstrapModal - The Bootstrap modal object
 * @param {HTMLElement} modalContentElement - The modal content element
 */
export function renderTable(state, tableBodyElement, bootstrapModal, modalContentElement) {
    tableBodyElement.innerHTML = '';
    
    console.log('Rendering table, state:', {
        filteredProductsCount: state.filteredProducts.length,
        currentPage: state.currentPage,
        sortField: state.sortField,
        sortDirection: state.sortDirection
    });
    
    const startIndex = (state.currentPage - 1) * ProductConfig.itemsPerPage;
    const endIndex = Math.min(startIndex + ProductConfig.itemsPerPage, state.filteredProducts.length);
    
    console.log(`Displaying products from index ${startIndex} to ${endIndex-1}`);
    
    if (state.filteredProducts.length === 0) {
        const noProductsRow = document.createElement('tr');
        noProductsRow.innerHTML = '<td colspan="11" class="text-center">No products found</td>';
        tableBodyElement.appendChild(noProductsRow);
        return;
    }
    
    for (let i = startIndex; i < endIndex; i++) {
        const product = state.filteredProducts[i];
        renderProductRow(product, tableBodyElement, bootstrapModal, modalContentElement, state);
    }

    // Log sample of first product to verify data structure
    if (state.filteredProducts.length > 0) {
        console.log('Sample product:', state.filteredProducts[0]);
    }
}

/**
 * Render a single product row in the table
 * @param {Object} product - The product to render
 * @param {HTMLElement} tableBodyElement - The table body element to append to
 * @param {Object} bootstrapModal - The Bootstrap modal object
 * @param {HTMLElement} modalContentElement - The modal content element
 * @param {Object} state - Current application state
 */
function renderProductRow(product, tableBodyElement, bootstrapModal, modalContentElement, state) {
    const status = getProductStatus(product);
    const imageUrl = normalizeImageUrl(product.imageUrl) || './assets/img/placeholder.jpg';
    const hasVariations = product.hasVariations && Array.isArray(product.variationProducts);
    
    // Create the main product row
    const row = document.createElement('tr');
    
    // Add a class if this product has variations
    if (hasVariations) {
        row.className = 'variation-group';
    }
    
    let nameDisplay = product.nameProduct || '';
    
    // Add size badge if product has variations
    if (hasVariations) {
        nameDisplay += ` <span class="size-badge">${product.sizeProduct || 'Various'}</span>`;
    }
    
    // Determine what to display in the size column
    let sizeDisplay = '';
    
    if (!product.sizeProduct || product.sizeProduct === '') {
        // If sizeProduct is null/empty and we have bust/waist/length measurements
        const hasBust = product.bustProduct && product.bustProduct !== '';
        const hasWaist = product.waistProduct && product.waistProduct !== '';
        const hasLength = product.lengthProduct && product.lengthProduct !== '';
        
        if (hasBust || hasWaist || hasLength) {
            let sizeLines = [];
            
            if (hasBust) {
                sizeLines.push(`B-${product.bustProduct}`);
            }
            
            if (hasWaist) {
                sizeLines.push(`W-${product.waistProduct}`);
            }
            
            if (hasLength) {
                sizeLines.push(`L-${product.lengthProduct}`);
            }
            
            sizeDisplay = sizeLines.join('<br>');
        } else {
            // If no measurements are available
            sizeDisplay = '';
        }
    } else {
        // Use the sizeProduct value if it exists
        sizeDisplay = product.sizeProduct;
    }
    
    row.innerHTML = `
        <td>
            <img src="${imageUrl}" 
                alt="${product.nameProduct || ''}" 
                class="product-thumbnail" 
                title="Click to view product images" 
                data-product-id="${product.productID}">
        </td>
        <td data-product-id="${product.productID}">${product.productID}</td>
        <td>${nameDisplay}</td>
        <td>${product.locationProduct || ''}</td>
        <td>${product.typeProduct || ''}</td>
        <td>${sizeDisplay}</td>
        <td>${product.colorProduct || ''}</td>
        <td>${status}</td>
        <td>
            <div class="action-buttons">
                <button class="action-btn edit-btn edit-product" data-product-id="${product.productID}" title="Edit Product">
                    <i class="bi bi-pencil-fill"></i>
                </button>
                <button class="action-btn delete-btn delete-product" data-product-id="${product.productID}" title="Delete Product">
                    <i class="bi bi-trash-fill"></i>
                </button>
                ${hasVariations ? `
                <button class="action-btn sizes-btn toggle-variations" title="Show Sizes">
                    <i class="bi bi-arrows-expand"></i>
                </button>` : ''}
            </div>
        </td>
    `;
    
    tableBodyElement.appendChild(row);
    
    // If this product has variations, handle the variations display
    if (hasVariations) {
        // Create a variations row
        const variationsRow = document.createElement('tr');
        variationsRow.className = 'expanded-row d-none';
        variationsRow.innerHTML = `
            <td colspan="11">
                <div class="variations-container">
                    <div class="variation-heading">Available Sizes:</div>
                    <div class="variation-sizes">
                        ${product.variationProducts.map(variant => `
                            <div class="variation-size${variant.productID === product.productID ? ' active' : ''}" 
                                data-product-id="${variant.productID}" 
                                title="Edit product with size ${variant.sizeProduct || 'Unknown'}">
                                ${variant.sizeProduct || 'Unknown Size'}
                            </div>
                        `).join('')}
                    </div>
                    <div class="mt-2 variation-tip">
                        <small class="text-muted">Click on a size to edit that product</small>
                    </div>
                </div>
            </td>
        `;
        tableBodyElement.appendChild(variationsRow);
        
        // Add event listener to the toggle button
        const toggleButton = row.querySelector('.toggle-variations');
        if (toggleButton) {
            toggleButton.addEventListener('click', function() {
                variationsRow.classList.toggle('d-none');
                
                // Animate the appearance of the size buttons when shown
                if (!variationsRow.classList.contains('d-none')) {
                    // Update icon to collapse
                    this.querySelector('i').className = 'bi bi-arrows-collapse';
                    this.setAttribute('title', 'Hide Sizes');
                    
                    const sizeButtons = variationsRow.querySelectorAll('.variation-size');
                    sizeButtons.forEach((btn, index) => {
                        btn.style.opacity = '0';
                        btn.style.transform = 'translateY(10px)';
                        
                        // Staggered animation
                        setTimeout(() => {
                            btn.style.opacity = '1';
                            btn.style.transform = 'translateY(0)';
                        }, 50 * index);
                    });
                } else {
                    // Update icon to expand
                    this.querySelector('i').className = 'bi bi-arrows-expand';
                    this.setAttribute('title', 'Show Sizes');
                }
            });
        }
        
        // Add event listeners to variation buttons
        const variationButtons = variationsRow.querySelectorAll('.variation-size');
        variationButtons.forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                if (productId) {
                    // Update the active state visually
                    variationsRow.querySelectorAll('.variation-size').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    this.classList.add('active');
                    
                    // Show edit modal
                    showProductDetails(
                        productId, 
                        state.allProducts, 
                        document.getElementById('editProductModal'), 
                        modalContentElement, 
                        bootstrapModal
                    );
                }
            });
        });
    }
    
    // Add event listener for the edit button
    const editButton = row.querySelector('.edit-product');
    if (editButton) {
        editButton.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            showProductDetails(
                productId, 
                state.allProducts, 
                document.getElementById('editProductModal'), 
                modalContentElement, 
                bootstrapModal
            );
        });
    }
    
    // Add event listener for the delete button
    const deleteButton = row.querySelector('.delete-product');
    if (deleteButton) {
        deleteButton.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const productName = product.nameProduct || `Product #${productId}`;
            
            if (confirm(`Are you sure you want to delete "${productName}"? This cannot be undone.`)) {
                deleteProduct(productId, state, tableBodyElement, bootstrapModal, modalContentElement);
            }
        });
    }
}

/**
 * Delete a product and refresh the table
 * @param {string|number} productId - The product ID to delete
 * @param {Object} state - Current application state
 * @param {HTMLElement} tableBodyElement - The table body element
 * @param {Object} bootstrapModal - The Bootstrap modal object
 * @param {HTMLElement} modalContentElement - The modal content element
 */
function deleteProduct(productId, state, tableBodyElement, bootstrapModal, modalContentElement) {
    // Show loading indicator
    document.getElementById('loadingOverlay').style.display = 'flex';
    
    // Send delete request to server
    fetch('assets/controllers/products/delete_product.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ productId: productId })
    })
    .then(response => response.json())
    .then(data => {
        // Hide loading indicator
        document.getElementById('loadingOverlay').style.display = 'none';
        
        if (data.success) {
            // Show success message
            alert('Product deleted successfully');
            
            // Remove the product from state
            state.allProducts = state.allProducts.filter(p => p.productID != productId);
            state.filteredProducts = state.filteredProducts.filter(p => p.productID != productId);
            
            // Re-render the table
            renderTable(state, tableBodyElement, bootstrapModal, modalContentElement);
            
            // Try to refresh pagination if it exists
            if (typeof renderPagination === 'function') {
                const pagination = document.getElementById('pagination');
                if (pagination) {
                    renderPagination(state, pagination, () => renderTable(state, tableBodyElement, bootstrapModal, modalContentElement));
                }
            }
        } else {
            // Show error message
            alert('Error: ' + (data.message || 'Failed to delete product'));
        }
    })
    .catch(error => {
        // Hide loading indicator
        document.getElementById('loadingOverlay').style.display = 'none';
        
        // Show error message
        console.error('Error:', error);
        alert('Error: Failed to delete product. Please try again.');
    });
} 
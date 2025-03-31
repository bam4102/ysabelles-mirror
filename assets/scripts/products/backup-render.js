// Backup rendering module for product table

/**
 * Initialize backup rendering functionality
 */
export function initBackupRender() {
    // Add a backup option to handle module loading issues
    window.addEventListener('load', function() {
        // If after 3 seconds, the table is still empty, try manual render
        setTimeout(function() {
            const tableBody = document.getElementById('productTableBody');
            if (tableBody && tableBody.children.length === 0) {
                console.warn('Table appears to be empty after page load, attempting manual render');
                debugRenderProducts();
            }
            
            // Hide loading overlay if it's still visible
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay && loadingOverlay.style.display !== 'none') {
                loadingOverlay.style.display = 'none';
            }
        }, 3000);
    });
}

/**
 * Backup function to render the product table if the module script fails
 */
export function debugRenderProducts() {
    const products = window.allProducts || [];
    const tableBody = document.getElementById('productTableBody');
    
    if (!tableBody || !products.length) {
        return;
    }
    
    tableBody.innerHTML = '';
    
    products.slice(0, 10).forEach(product => {
        const imageUrl = product.imageUrl || './assets/img/placeholder.jpg';
        const status = product.isActive === 1 ? 'Active' : 'Inactive';
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><img src="${imageUrl}" alt="${product.nameProduct || ''}" class="product-thumbnail"></td>
            <td>${product.productID}</td>
            <td>${product.nameProduct || ''}</td>
            <td>${product.productCategory || ''}</td>
            <td>${product.locationProduct || ''}</td>
            <td>${product.typeProduct || ''}</td>
            <td>${product.sizeProduct || ''}</td>
            <td>${product.colorProduct || ''}</td>
            <td>${product.genderProduct || ''}</td>
            <td>${status}</td>
            <td><button class="btn btn-sm btn-primary" data-product-id="${product.productID}">View Details</button></td>
        `;
        
        tableBody.appendChild(row);
    });
}

// Export to window for direct access if module system fails
window.debugRenderProducts = debugRenderProducts; 
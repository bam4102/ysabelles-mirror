// Debug script to help troubleshoot product data
console.log('Debug script loaded');

// Check if products data exists
if (typeof window.allProducts !== 'undefined') {
    console.log('window.allProducts exists with type:', typeof window.allProducts);
    
    if (Array.isArray(window.allProducts)) {
        console.log('window.allProducts is an array with length:', window.allProducts.length);
        
        if (window.allProducts.length > 0) {
            console.log('First product sample:', window.allProducts[0]);
        } else {
            console.error('window.allProducts array is empty');
        }
    } else {
        console.error('window.allProducts is not an array!');
    }
} else {
    console.error('window.allProducts is not defined!');
}

// Create a global function to manually render products
window.debugRenderProducts = function() {
    console.log('Manual debug render triggered');
    
    const tableBody = document.getElementById('productTableBody');
    if (!tableBody) {
        console.error('Could not find productTableBody element');
        return;
    }
    
    if (!window.allProducts || !Array.isArray(window.allProducts) || window.allProducts.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="10" class="text-center text-danger">No products available in window.allProducts</td></tr>';
        return;
    }
    
    tableBody.innerHTML = '';
    
    // Display first 10 products
    const productsToShow = window.allProducts.slice(0, 10);
    
    productsToShow.forEach(product => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${product.productID || 'N/A'}</td>
            <td>${product.nameProduct || 'N/A'}</td>
            <td>${product.productCategory || 'N/A'}</td>
            <td>${product.locationProduct || 'N/A'}</td>
            <td>${product.typeProduct || 'N/A'}</td>
            <td>${product.sizeProduct || 'N/A'}</td>
            <td>${product.colorProduct || 'N/A'}</td>
            <td>${product.genderProduct || 'N/A'}</td>
            <td>Status</td>
            <td>Actions</td>
        `;
        tableBody.appendChild(row);
    });
    
    console.log(`Manually rendered ${productsToShow.length} products`);
}; 
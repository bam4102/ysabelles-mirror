// Product data loader module

/**
 * Load product data via AJAX
 * @returns {Promise} Promise that resolves with product data
 */
export function loadProductData() {
    return new Promise((resolve, reject) => {
        // Check if data is already loaded into window (from PHP)
        if (window.allProducts && Array.isArray(window.allProducts)) {
            console.log('Using pre-loaded product data, count:', window.allProducts.length);
            resolve(window.allProducts);
            return;
        }

        // Fallback to AJAX loading if needed
        fetch('assets/controllers/load_product_data.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Loaded product data via AJAX, count:', data.length);
                window.allProducts = data;
                resolve(data);
            })
            .catch(error => {
                console.error('Error loading product data:', error);
                reject(error);
            });
    });
} 
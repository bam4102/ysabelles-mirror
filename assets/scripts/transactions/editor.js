/**
 * TransactionEditor module handles editing transaction details
 */
const TransactionEditor = (function() {
    // Private methods
    function formatDateForInput(dateString) {
        // Convert date string to YYYY-MM-DD format for input[type="date"]
        const date = new Date(dateString);
        return date.toISOString().split('T')[0];
    }
    
    // Public methods
    return {
        /**
         * Initialize the transaction editor module
         */
        init: function() {
            // No initialization needed for this module
        },
        
        /**
         * Display transaction edit modal
         * @param {number} transactionId - Transaction ID
         */
        showEditModal: function(transactionId) {
            // Fetch transaction details
            fetch(`assets/controllers/transactions2/get_transaction.php?id=${transactionId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    
                    // Populate form fields
                    document.getElementById('editTransactionId').value = data.transactionID;
                    document.getElementById('editClientName').value = data.clientName;
                    document.getElementById('editClientAddress').value = data.clientAddress;
                    document.getElementById('editClientContact').value = data.clientContact;
                    document.getElementById('editLocation').value = data.locationTransaction;
                    document.getElementById('editTransactionDate').value = formatDateForInput(data.dateTransaction);
                    document.getElementById('editPickupDate').value = formatDateForInput(data.datePickUp);
                    document.getElementById('editReturnDate').value = formatDateForInput(data.dateReturn);
                    document.getElementById('editDiscount').value = data.discountTransaction;
                    
                    // Populate new financial fields
                    document.getElementById('editCharge').value = data.chargeTransaction;
                    document.getElementById('editBondRequired').value = data.bondTransaction;
                    document.getElementById('editBondBalance').value = data.bondBalance;
                    document.getElementById('editBalance').value = data.balanceTransaction;
                    document.getElementById('editBondStatus').value = data.bondStatus;
                    
                    // Populate products table
                    const productsTableBody = document.getElementById('editProductsTable');
                    productsTableBody.innerHTML = '';
                    
                    const products = typeof data.products === 'string' ? JSON.parse(data.products) : data.products;
                    if (Array.isArray(products) && products.length > 0) {
                        products.forEach(product => {
                            if (product) {
                                // Show priceSold if soldPProduct is 1, otherwise show priceProduct
                                const price = product.soldPProduct === 1 ? product.priceSold : product.priceProduct;
                                let status = product.soldPProduct === 1 ? 'For Sale' : 'For Rent';
                                if (product.is_confirmed_sold) status = 'Sold';
                                
                                const row = `
                                    <tr>
                                        <td>${product.productID || 'N/A'}</td>
                                        <td>${product.nameProduct || 'N/A'}</td>
                                        <td>₱${parseFloat(price).toFixed(2)}</td>
                                        <td>${status}</td>
                                    </tr>
                                `;
                                productsTableBody.insertAdjacentHTML('beforeend', row);
                            }
                        });
                    } else {
                        productsTableBody.innerHTML = '<tr><td colspan="4" class="text-center">No products found</td></tr>';
                    }
                    
                    // Show the modal
                    new bootstrap.Modal(document.getElementById('editTransactionModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load transaction details. Please try again.');
                });
        },
        
        /**
         * Update transaction details
         */
        updateTransaction: function() {
            const form = document.getElementById('editTransactionForm');
            
            // Basic form validation
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const formData = new FormData(form);
            
            // Submit the form data
            fetch('assets/controllers/transactions2/update_transaction.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                // Close the modal
                bootstrap.Modal.getInstance(document.getElementById('editTransactionModal')).hide();
                
                // Show success message and reload the page
                alert('Transaction updated successfully!');
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating transaction: ' + error.message);
            });
        }
    };
})(); 
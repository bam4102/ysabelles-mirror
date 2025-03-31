/**
 * TransactionDetails module handles displaying transaction details and history
 */
const TransactionDetails = (function() {
    // Private methods
    function formatCurrency(amount) {
        return '₱ ' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
    
    function formatDateTime(datetime) {
        return datetime ? new Date(datetime).toLocaleString() : 'N/A';
    }
    
    function formatDate(date) {
        return new Date(date).toLocaleDateString();
    }
    
    function getPackageText(packageId) {
        switch (packageId) {
            case 1: return 'Package A';
            case 2: return 'Package B';
            default: return 'None';
        }
    }
    
    // Function to handle product images in the view modal
    function setupProductImages() {
        const imageContainers = document.querySelectorAll('#productViewModalContent .product-image-detail');
        
        // Add click handler to show images in larger view
        imageContainers.forEach(img => {
            img.addEventListener('click', function() {
                const imgSrc = this.getAttribute('src');
                const imgAlt = this.getAttribute('alt') || 'Product Image';
                
                // Create a full-screen overlay
                const overlay = document.createElement('div');
                overlay.className = 'image-overlay';
                overlay.style.position = 'fixed';
                overlay.style.top = '0';
                overlay.style.left = '0';
                overlay.style.width = '100%';
                overlay.style.height = '100%';
                overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.9)';
                overlay.style.display = 'flex';
                overlay.style.justifyContent = 'center';
                overlay.style.alignItems = 'center';
                overlay.style.zIndex = '9999';
                overlay.style.cursor = 'pointer';
                
                // Create image element
                const fullImg = document.createElement('img');
                fullImg.src = imgSrc;
                fullImg.alt = imgAlt;
                fullImg.style.maxWidth = '90%';
                fullImg.style.maxHeight = '90%';
                fullImg.style.objectFit = 'contain';
                fullImg.style.border = '2px solid white';
                fullImg.style.borderRadius = '4px';
                
                // Add close instruction
                const closeText = document.createElement('div');
                closeText.textContent = 'Click anywhere to close';
                closeText.style.position = 'absolute';
                closeText.style.bottom = '20px';
                closeText.style.color = 'white';
                closeText.style.textAlign = 'center';
                closeText.style.width = '100%';
                
                // Add elements to DOM
                overlay.appendChild(fullImg);
                overlay.appendChild(closeText);
                document.body.appendChild(overlay);
                
                // Close on click
                overlay.addEventListener('click', function() {
                    document.body.removeChild(overlay);
                });
            });
            
            // Add pointer cursor to indicate clickable
            img.style.cursor = 'pointer';
        });
    }
    
    // Function to enhance product view modal layout
    function enhanceProductViewLayout() {
        const modalContent = document.getElementById('productViewModalContent');
        const modalFooter = document.querySelector('#productViewModal .modal-footer');
        
        if (!modalContent || !modalFooter) return;
        
        // Check if the back button already exists
        const existingBackButton = modalFooter.querySelector('.btn-back-to-transaction');
        
        // Only create the button if it doesn't already exist
        if (!existingBackButton) {
            // Add a "Back to Transaction" button to easily return to transaction details
            const backButton = document.createElement('button');
            backButton.type = 'button';
            backButton.className = 'btn btn-primary me-2 btn-back-to-transaction';
            backButton.innerHTML = '<i class="fas fa-arrow-left me-1"></i> Back to Transaction';
            backButton.addEventListener('click', function() {
                // Hide product view modal and show transaction modal again
                const productViewModal = bootstrap.Modal.getInstance(document.getElementById('productViewModal'));
                if (productViewModal) {
                    productViewModal.hide();
                    
                    // Show transaction modal after a short delay
                    setTimeout(() => {
                        const transactionModal = new bootstrap.Modal(document.getElementById('transactionModal'));
                        transactionModal.show();
                    }, 150);
                }
            });
            
            // Insert back button as first child
            if (modalFooter.firstChild) {
                modalFooter.insertBefore(backButton, modalFooter.firstChild);
            } else {
                modalFooter.appendChild(backButton);
            }
        }
    }
    
    // Function to open product details modal
    function openProductDetailsModal(productId) {
        // Close any existing modals first to prevent modal stacking issues
        const existingModals = document.querySelectorAll('.modal.show');
        existingModals.forEach(modal => {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance && modal.id !== 'productViewModal') {
                modalInstance.hide();
            }
        });
        
        // Show loading indicator in the product view modal
        const modalContent = document.getElementById('productViewModalContent');
        if (modalContent) {
            modalContent.innerHTML = `
                <div class="text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading product #${productId}...</p>
                </div>
            `;
        }
        
        // Show the modal before loading content so user gets feedback
        const productViewModal = new bootstrap.Modal(document.getElementById('productViewModal'));
        productViewModal.show();
        
        // Fetch the product details
        fetch(`assets/controllers/products/load_product_form.php?productId=${productId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                // Set modal title and content
                const modalContent = document.getElementById('productViewModalContent');
                const modalTitle = document.getElementById('productViewModalLabel');
                
                if (!modalContent || !modalTitle) {
                    console.error('Product view modal elements not found');
                    return;
                }
                
                // Set the product details in the modal
                modalContent.innerHTML = html;
                modalTitle.innerHTML = `Product Details #${productId}`;
                
                // Disable all form inputs to make it view-only
                const form = modalContent.querySelector('form');
                if (form) {
                    const formElements = form.querySelectorAll('input, select, textarea, button');
                    formElements.forEach(element => {
                        element.disabled = true;
                    });
                    
                    // Remove variations container as requested
                    const variationsContainer = form.querySelector('.variations-container');
                    if (variationsContainer) {
                        variationsContainer.style.display = 'none';
                    }
                    
                    // Remove unnecessary elements for view-only mode
                    const dropZone = form.querySelector('.drop-zone');
                    if (dropZone) {
                        dropZone.style.display = 'none';
                    }
                    
                    // Display product information in a more readable format
                    const imageContainer = form.querySelector('.product-image-container');
                    if (imageContainer) {
                        imageContainer.classList.add('mb-4');
                        
                        // Make image display more prominent
                        const images = imageContainer.querySelectorAll('.product-image-detail');
                        images.forEach(img => {
                            img.style.width = '150px';
                            img.style.height = '150px';
                            img.style.margin = '5px';
                            img.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
                        });
                        
                        // If any delete buttons exist, hide them
                        const deleteButtons = imageContainer.querySelectorAll('.delete-image');
                        deleteButtons.forEach(btn => {
                            btn.style.display = 'none';
                        });
                    }
                }
                
                // Setup product images for viewing
                setTimeout(() => {
                    setupProductImages();
                    enhanceProductViewLayout();
                }, 100);
            })
            .catch(error => {
                console.error('Error loading product view:', error);
                
                // Show error message in the modal instead of alert
                const modalContent = document.getElementById('productViewModalContent');
                if (modalContent) {
                    modalContent.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Failed to load product details. Please try again.
                        </div>
                    `;
                }
            });
    }
    
    // Public methods
    return {
        /**
         * Initialize the details module
         */
        init: function() {
            // No initialization needed for this module
        },
        
        /**
         * Show transaction details in a modal
         * @param {Object} data - Transaction data object
         */
        showTransactionDetails: function(data) {
            $('#modalTransactionId').text(data.id);
            $('#modalClient').text(data.client);
            $('#modalCharge').text(formatCurrency(data.charge));
            $('#modalBond').text(formatCurrency(data.bond));
            $('#modalDiscount').text(formatCurrency(data.discount));
            
            const productsTableBody = $('#modalProductsTable');
            productsTableBody.empty();
            
            const products = typeof data.products === 'string' ? JSON.parse(data.products) : data.products;
            if (Array.isArray(products) && products.length > 0) {
                products.forEach(product => {
                    if (product) {
                        // Show priceSold if soldPProduct is 1, otherwise show priceProduct
                        const price = product.soldPProduct === 1 ? product.priceSold : product.priceProduct;
                        let soldBadge = '';
                        let newBadge = '';
                        let releaseDate = '';
                        let returnDate = '';
                        
                        if (product.soldPProduct && product.is_confirmed_sold) {
                            soldBadge = '<span class="badge bg-success ms-2">SOLD</span>';
                            releaseDate = formatDateTime(product.sold_date);
                            returnDate = 'N/A';
                        } else {
                            if (product.soldPProduct) {
                                soldBadge = '<span class="badge bg-warning ms-2">To Be Sold</span>';
                            }
                            releaseDate = formatDateTime(product.release_date);
                            returnDate = formatDateTime(product.return_date);
                        }
                        
                        // Add NEW badge if product is new
                        if (product.isNew === 1) {
                            newBadge = '<span class="badge bg-primary ms-2">NEW</span>';
                        }
                        
                        const row = `
                            <tr>
                                <td>${product.productID || 'N/A'}</td>
                                <td>
                                    <a href="javascript:void(0)" class="product-details-link" data-product-id="${product.productID}">
                                        ${product.nameProduct || 'N/A'}
                                    </a>
                                    ${soldBadge}${newBadge}
                                </td>
                                <td>${formatCurrency(price || 0)}</td>
                                <td>${releaseDate}</td>
                                <td>${returnDate}</td>
                                <td>${getPackageText(product.packagePurchase)}</td>
                            </tr>
                        `;
                        productsTableBody.append(row);
                    }
                });

                // Add click event listeners to product links
                $('.product-details-link').on('click', function() {
                    const productId = $(this).data('product-id');
                    openProductDetailsModal(productId);
                });
            } else {
                productsTableBody.append('<tr><td colspan="6" class="text-center">No products found</td></tr>');
            }
            
            new bootstrap.Modal(document.getElementById('transactionModal')).show();
        },
        
        /**
         * Show transaction history in a modal
         * @param {number} transactionId - Transaction ID
         */
        showHistory: function(transactionId) {
            fetch(`assets/controllers/transactions2/get_history.php?id=${transactionId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    
                    // Update payment history table
                    const paymentRows = data.payments.map(payment => `
                        <tr>
                            <td>${formatDate(payment.datePayment)}</td>
                            <td>${formatCurrency(payment.amountPayment)}</td>
                            <td>${payment.kindPayment}</td>
                            <td>${payment.notePayment || ''}</td>
                            <td>
                                <a href="./assets/controllers/transactions2/print_receipt.php?type=payment&id=${payment.paymentID}" 
                                   class="btn btn-sm btn-primary" target="_blank">
                                    <i class="fas fa-receipt"></i> Receipt
                                </a>
                            </td>
                        </tr>
                    `).join('') || '<tr><td colspan="5" class="text-center">No payment history found</td></tr>';
                    $('#paymentHistoryTable').html(paymentRows);
                    
                    // Update bond deposits table
                    const depositRows = data.bondDeposits.map(bond => `
                        <tr>
                            <td>${formatDate(bond.dateBond)}</td>
                            <td>${formatCurrency(bond.depositBond)}</td>
                            <td>${bond.noteBond || ''}</td>
                            <td>
                                <a href="assets/controllers/transactions2/print_bond_receipt.php?id=${bond.bondID}" 
                                   class="btn btn-sm btn-primary" target="_blank">
                                    <i class="fas fa-receipt"></i> Receipt
                                </a>
                            </td>
                        </tr>
                    `).join('') || '<tr><td colspan="4" class="text-center">No bond deposits found</td></tr>';
                    $('#bondHistoryTable').html(depositRows);
                    
                    // Update bond returns table
                    const returnRows = data.bondReturns.map(bond => `
                        <tr>
                            <td>${formatDate(bond.dateBond)}</td>
                            <td>${formatCurrency(bond.releaseBond)}</td>
                            <td>${bond.noteBond || ''}</td>
                        </tr>
                    `).join('') || '<tr><td colspan="5" class="text-center">No bond returns found</td></tr>';
                    $('#bondReturnTable').html(returnRows);
                    
                    new bootstrap.Modal(document.getElementById('historyModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load transaction history. Please try again.');
                });
        }
    };
})(); 
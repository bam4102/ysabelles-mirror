$(document).ready(function () {
    // Cache DOM elements for better performance
    const $searchQuery = $("#searchQuery");
    const $searchForm = $("#searchForm");
    const $searchResults = $("#searchResults");
    const $searchSection = $("#searchSection");
    const $scanSection = $("#scanSection");
    const $codeProduct = $("#codeProduct");
    const $returnForm = $("#returnForm");
    const $statusMessage = $("#resultContainer");
    const $backToSearch = $("#backToSearch");
    const $transactionInfo = $("#transactionInfo");
    const $selectedTransactionID = $("#selectedTransactionID");
    
    // Config
    const BARCODE_SCAN_DELAY = 100;
    
    // ===== Helper Functions =====
    
    /**
     * Formats a bond status value to display text
     */
    function getBondStatusText(statusValue) {
        const bondStatusValue = parseInt(statusValue);
        const statusMap = {
            0: 'Not Paid',
            1: 'Active',
            2: 'Completed'
        };
        return statusMap[bondStatusValue] || 'Unknown';
    }
    
    /**
     * Gets product status HTML display based on product attributes
     */
    function getProductStatusHTML(soldPProduct, isReleased, isReturned) {
        // Check if product is sold first
        if (parseInt(soldPProduct) === 1) {
            return '<span class="text-primary">SOLD</span>';
        }
        
        // For non-sold products, determine status based on release/return state
        const released = parseInt(isReleased) === 1;
        const returned = parseInt(isReturned) === 1;
        
        if (released && returned) return '<span class="text-success">Returned</span>';
        if (released) return '<span class="text-warning">Released, Not Returned</span>';
        return '<span class="text-danger">Pending</span>';
    }
    
    /**
     * Shows a message with appropriate styling
     */
    function showMessage($container, message, type = 'info') {
        $container.html(`<div class="alert alert-${type}">${message}</div>`);
    }
    
    // ===== Event Handlers =====
    
    // Search form submission
    $searchForm.on('submit', function (e) {
        e.preventDefault();
        
        const searchQuery = $searchQuery.val().trim();
        const searchType = $("#searchType").val();
        
        if (!searchQuery) {
            showMessage($searchResults, 'Please enter a search term.', 'danger');
            return;
        }
        
        $searchResults.html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Searching...</div>');
        
        $.ajax({
            url: "return.php",
            type: "POST",
            data: {
                action: 'searchTransaction',
                searchQuery: searchQuery,
                searchType: searchType
            },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    displaySearchResults(response.data);
                } else {
                    showMessage($searchResults, response.message || 'Error searching transactions', 'danger');
                }
            },
            error: function (xhr, status, error) {
                showMessage($searchResults, `Error: ${error}`, 'danger');
            }
        });
    });
    
    // Back to search button
    $backToSearch.on('click', function() {
        $statusMessage.empty();
        $scanSection.hide();
        $searchSection.show();
        $searchQuery.focus();
    });
    
    // Auto-submit for barcode scanner with debounce
    let scanTimer;
    $codeProduct.on('input', function () {
        const value = this.value.trim();
        if (value.length > 0) {
            clearTimeout(scanTimer);
            scanTimer = setTimeout(() => submitReturnForm(), BARCODE_SCAN_DELAY);
        }
    });

    // Return form submission handler
    $returnForm.on('submit', function (e) {
        e.preventDefault();
        submitReturnForm();
    });
    
    // Event delegation for damage forms with better handling
    $statusMessage.on('submit', '#damageCheckForm, #confirmReturnForm', function (e) {
        e.preventDefault();
        const formData = $(this).serialize();
        
        // Show loading indicator
        $statusMessage.html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Processing...</div>');
        
        $.ajax({
            url: "return.php",
            type: "POST",
            data: formData,
            success: function (response) {
                // Clean the response to remove any duplicates
                const cleanedResponse = cleanResponse(response);
                $statusMessage.html(cleanedResponse);
                $codeProduct.val('').focus();
                
                // If successful, refresh transaction details
                if (cleanedResponse.includes('alert-success')) {
                    refreshTransactionDetails($selectedTransactionID.val());
                }
            },
            error: function (xhr, status, error) {
                showMessage($statusMessage, `An error occurred: ${error || "Unknown error"}`, 'danger');
                $codeProduct.focus();
            }
        });
    });
    
    // Toggle damage description field
    $statusMessage.on('change', 'input[name="isDamaged"]', function () {
        const isDamaged = $(this).val() === "1";
        const $descContainer = $("#damageDescContainer");
        const $damageDesc = $("#damageDesc");
        
        if (isDamaged) {
            $descContainer.slideDown(200);
            $damageDesc.prop('required', true);
            setTimeout(() => $damageDesc.focus(), 250);
        } else {
            $descContainer.slideUp(200);
            $damageDesc.prop('required', false);
        }
    });
    
    // ===== Main Functions =====
    
    /**
     * Display search results table with transaction data
     */
    function displaySearchResults(transactions) {
        if (!transactions || transactions.length === 0) {
            showMessage($searchResults, 'No transactions found.', 'info');
            return;
        }
        
        const rows = transactions.map(transaction => `
            <tr data-transaction-id="${transaction.transactionID}">
                <td>${transaction.transactionID}</td>
                <td>${transaction.clientName}</td>
                <td>${transaction.clientContact || '-'}</td>
                <td>${transaction.dateTransaction}</td>
                <td>${getBondStatusText(transaction.bondStatus)}</td>
                <td>₱${parseFloat(transaction.balanceTransaction).toFixed(2)}</td>
            </tr>
        `).join('');
        
        const html = `
            <h4>Search Results</h4>
            <div class="table-responsive">
                <table id="transactionTable" class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Client Name</th>
                            <th>Contact</th>
                            <th>Transaction Date</th>
                            <th>Bond Status</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows}
                    </tbody>
                </table>
            </div>
            <p class="text-muted">Click on a transaction to select it for product return.</p>
        `;
        
        $searchResults.html(html);
        
        // Add click event to transaction rows
        $("#transactionTable tbody tr").on('click', function() {
            const transactionID = $(this).data('transaction-id');
            selectTransaction(transactionID);
        });
    }
    
    /**
     * Select a transaction and show transaction details
     */
    function selectTransaction(transactionID) {
        $.ajax({
            url: "return.php",
            type: "POST",
            data: {
                action: 'getTransactionDetails',
                transactionID: transactionID
            },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    // Store transaction ID for product scanning
                    $selectedTransactionID.val(transactionID);
                    
                    // Clear any previous status messages
                    $statusMessage.empty();
                    
                    // Display transaction details
                    displayTransactionDetails(response.transaction, response.products);
                    
                    // Switch from search to scan section
                    $searchSection.hide();
                    $scanSection.show();
                    
                    // Focus on product scan input
                    $codeProduct.focus();
                } else {
                    showMessage($searchResults, response.message || 'Error loading transaction details', 'danger');
                }
            },
            error: function (xhr, status, error) {
                showMessage($searchResults, `Error: ${error}`, 'danger');
            }
        });
    }
    
    /**
     * Display transaction details in scan section
     */
    function displayTransactionDetails(transaction, products) {
        // Count products needing return
        const pendingReturns = products.filter(p => 
            parseInt(p.is_released) === 1 && 
            parseInt(p.is_returned) === 0 && 
            parseInt(p.soldPProduct) === 0 &&
            parseInt(p.soldProduct) === 0
        ).length;
        
        // Generate product list rows
        const productRows = products.map(product => {
            const status = getProductStatusHTML(product.soldPProduct, product.is_released, product.is_returned);
            
            // Determine row class
            let rowClass = '';
            if (parseInt(product.soldPProduct) === 1) {
                rowClass = 'table-primary'; // Sold products - use primary color to match status
            } else if (parseInt(product.is_released) === 1 && parseInt(product.is_returned) === 0) {
                rowClass = 'table-warning'; // Released but not returned
            } else if (parseInt(product.is_released) === 1 && parseInt(product.is_returned) === 1) {
                rowClass = 'table-success'; // Returned products
            }
            
            return `
                <tr class="${rowClass}">
                    <td>${product.nameProduct}</td>
                    <td>${product.codeProduct}</td>
                    <td>${status}</td>
                </tr>
            `;
        }).join('');
        
        // Transaction info HTML
        const html = `
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Transaction #${transaction.transactionID}</h5>
                        <span class="badge bg-light text-dark">${getBondStatusText(transaction.bondStatus)}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Client Information</h6>
                            <p class="mb-0">
                                <strong>Name:</strong> ${transaction.clientName}<br>
                                <strong>Contact:</strong> ${transaction.clientContact || 'N/A'}<br>
                                <strong>Address:</strong> ${transaction.clientAddress || 'N/A'}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Transaction Details</h6>
                            <p class="mb-0">
                                <strong>Date:</strong> ${transaction.dateTransaction}<br>
                                <strong>Return Date:</strong> ${transaction.dateReturn}<br>
                                <strong>Bond Amount:</strong> ₱${parseFloat(transaction.bondTransaction).toFixed(2)}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><strong>Pending Returns:</strong> 
                            <span class="badge bg-${pendingReturns > 0 ? 'warning' : 'success'}">${pendingReturns}</span>
                        </span>
                        <button class="btn btn-sm btn-outline-primary" id="viewProductsBtn" type="button" data-bs-toggle="collapse" data-bs-target="#productListCollapse">
                            View Products
                        </button>
                    </div>
                </div>
            </div>

            <div class="collapse mt-3" id="productListCollapse">
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Product List</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th>Code</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${productRows}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $transactionInfo.html(html);
    }
    
    /**
     * Submit the return form
     */
    function submitReturnForm() {
        const codeProduct = $codeProduct.val().trim();
        const transactionID = $selectedTransactionID.val();
        
        if (!codeProduct) {
            showMessage($statusMessage, 'Please scan a product code.', 'warning');
            $codeProduct.focus();
            return;
        }
        
        if (!transactionID) {
            showMessage($statusMessage, 'No transaction selected.', 'danger');
            return;
        }
        
        // Show loading indicator
        $statusMessage.html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Processing...</div>');
        
        $.ajax({
            url: "return.php",
            type: "POST",
            data: {
                codeProduct: codeProduct,
                transactionID: transactionID
            },
            success: function (response) {
                // Clear the input field immediately
                $codeProduct.val('');
                
                // Remove any duplicate "Return Product" sections from the response
                const cleanedResponse = cleanResponse(response);
                
                // Process the response
                const formExists = cleanedResponse.includes('damageCheckForm');
                
                // Handle the response
                $statusMessage.html(cleanedResponse);
                
                // If it's a confirmation form, don't focus on the input
                // Otherwise focus back on the input for the next scan
                if (!formExists) {
                    $codeProduct.focus();
                    
                    // If successful, refresh transaction details to update counts
                    if (cleanedResponse.includes('alert-success')) {
                        refreshTransactionDetails(transactionID);
                    }
                }
            },
            error: function (xhr, status, error) {
                showMessage($statusMessage, `An error occurred: ${error || "Unknown error"}`, 'danger');
                $codeProduct.val('').focus();
            }
        });
    }
    
    /**
     * Refresh transaction details after a successful return
     */
    function refreshTransactionDetails(transactionID) {
        $.ajax({
            url: "return.php",
            type: "POST",
            data: {
                action: 'getTransactionDetails',
                transactionID: transactionID
            },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    displayTransactionDetails(response.transaction, response.products);
                }
            }
        });
    }
    
    /**
     * Clean response HTML to remove unwanted sections
     */
    function cleanResponse(html) {
        // Create a temporary div to parse HTML
        const $temp = $('<div>').html(html);
        
        // Remove any elements with class product-header
        $temp.find('.product-header').remove();
        
        // Remove any h1 with "Return Product" text
        $temp.find('h1:contains("Return Product")').remove();
        
        // Remove any divs containing only "Return Product" text
        $temp.find('div').each(function() {
            if ($(this).text().trim() === 'Return Product') {
                $(this).remove();
            }
        });
        
        // Remove any search form sections that might have been included
        $temp.find('#searchSection').remove();
        
        return $temp.html();
    }
});
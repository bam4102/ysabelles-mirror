$(document).ready(function () {
    // DOM element references
    const $searchQuery = $("#searchQuery");
    const $searchForm = $("#searchForm");
    const $searchResults = $("#searchResults");
    const $searchSection = $("#searchSection");
    const $scanSection = $("#scanSection");
    const $codeProduct = $("#codeProduct");
    const $releaseForm = $("#releaseForm");
    const $statusMessage = $("#statusMessage");
    const $backToSearch = $("#backToSearch");
    const $transactionInfo = $("#transactionInfo");
    const $selectedTransactionID = $("#selectedTransactionID");
    
    // ===== Helper Functions =====
    
    /**
     * Formats a bond status value to display text
     */
    function getBondStatusText(statusValue) {
        // Convert to number to handle both string and numeric types
        const bondStatusValue = parseInt(statusValue);
        switch (bondStatusValue) {
            case 0: return 'Not Paid';
            case 1: return 'Active';
            case 2: return 'Completed';
            default: return 'Unknown';
        }
    }
    
    /**
     * Gets product status HTML display based on product attributes
     */
    function getProductStatusHTML(soldPProduct, isReleased, isReturned) {
        // Convert to numbers for reliable comparison
        const isSold = parseInt(soldPProduct) === 1;
        const released = parseInt(isReleased) === 1;
        const returned = parseInt(isReturned) === 1;
        
        if (isSold) {
            return released ? 
                '<span class="text-success">Released</span>' : 
                '<span class="text-warning">Pending</span>';
        } else {
            if (released && returned) {
                return '<span class="text-success">Returned</span>';
            } else if (released) {
                return '<span class="text-warning">Released</span>';
            } else {
                return '<span class="text-danger">Pending</span>';
            }
        }
    }
    
    /**
     * Shows an error message in the search results container
     */
    function showSearchError(message) {
        $searchResults.html(`<div class="alert alert-danger">${message}</div>`);
    }
    
    /**
     * Shows a temporary status message
     */
    function showStatusMessage(message, type = 'info') {
        $statusMessage.html(`<div class="alert alert-${type}">${message}</div>`);
        
        // Auto-clear success and info messages after 5 seconds
        if (type === 'success' || type === 'info') {
            setTimeout(() => {
                if ($statusMessage.find('.alert').length > 0) {
                    $statusMessage.empty();
                }
            }, 5000);
        }
    }
    
    // ===== Event Handlers =====
    
    // Search form submission
    $searchForm.on('submit', function (e) {
        e.preventDefault();
        
        const searchQuery = $searchQuery.val().trim();
        const searchType = $("#searchType").val();
        
        if (!searchQuery) {
            showSearchError('Please enter a search term.');
            return;
        }
        
        $searchResults.html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Searching...</div>');
        
        $.ajax({
            url: "release.php",
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
                    showSearchError(response.message || 'Error searching transactions');
                }
            },
            error: function (xhr, status, error) {
                showSearchError(`Error: ${error}`);
            }
        });
    });
    
    // Back to search button
    $backToSearch.on('click', function() {
        // Clear any existing messages
        $statusMessage.empty();
        $scanSection.hide();
        $searchSection.show();
        $searchQuery.focus();
    });
    
    // Auto-submit when input changes (for barcode scanner)
    $codeProduct.on('input', function () {
        if (this.value.length > 0) {
            // Clear any pending submission
            clearTimeout(this.submitTimeout);
            
            // Set a short timeout to ensure complete barcode is captured
            this.submitTimeout = setTimeout(() => {
                submitReleaseForm();
            }, 100);
        }
    });

    // Release form submission handler
    $releaseForm.on('submit', function (e) {
        e.preventDefault();
        submitReleaseForm();
    });
    
    // ===== Main Functions =====
    
    /**
     * Display search results table with transaction data
     */
    function displaySearchResults(transactions) {
        if (!transactions || transactions.length === 0) {
            $searchResults.html('<div class="alert alert-info">No transactions found.</div>');
            return;
        }
        
        let html = `
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
        `;
        
        transactions.forEach(transaction => {
            html += `
                <tr data-transaction-id="${transaction.transactionID}">
                    <td>${transaction.transactionID}</td>
                    <td>${transaction.clientName}</td>
                    <td>${transaction.clientContact || '-'}</td>
                    <td>${transaction.dateTransaction}</td>
                    <td>${getBondStatusText(transaction.bondStatus)}</td>
                    <td>₱${parseFloat(transaction.balanceTransaction).toFixed(2)}</td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
            <p class="text-muted">Click on a transaction to select it for product release.</p>
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
            url: "release.php",
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
                    showSearchError(response.message || 'Error loading transaction details');
                }
            },
            error: function (xhr, status, error) {
                showSearchError(`Error: ${error}`);
            }
        });
    }
    
    /**
     * Display transaction details in scan section
     */
    function displayTransactionDetails(transaction, products) {
        // Count products that still need to be released
        const pendingProducts = products.filter(p => {
            return parseInt(p.is_released) !== 1;
        }).length;
        
        // Transaction info box
        let html = `
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <strong>Transaction: ${transaction.transactionID}</strong> - ${transaction.clientName}
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Client:</strong> ${transaction.clientName}</p>
                            <p><strong>Contact:</strong> ${transaction.clientContact || '-'}</p>
                            <p><strong>Address:</strong> ${transaction.clientAddress || '-'}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Transaction Date:</strong> ${transaction.dateTransaction}</p>
                            <p><strong>Pickup Date:</strong> ${transaction.datePickUp}</p>
                            <p><strong>Return Date:</strong> ${transaction.dateReturn || 'N/A'}</p>
                            <p><strong>Bond Status:</strong> ${getBondStatusText(transaction.bondStatus)}</p>
                        </div>
                    </div>
                    <div class="alert ${pendingProducts > 0 ? 'alert-info' : 'alert-success'} mt-2 mb-0">
                        <strong>${pendingProducts > 0 ? `${pendingProducts} product(s) pending` : 'All products released'}</strong>
                    </div>
                </div>
            </div>
            
            <h5>Products in Transaction</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        products.forEach(product => {
            const isSold = parseInt(product.soldPProduct) === 1;
            const isReleased = parseInt(product.is_released) === 1;
            
            html += `
                <tr class="${!isReleased ? 'table-warning' : ''}">
                    <td>${product.nameProduct}</td>
                    <td>${product.codeProduct}</td>
                    <td>${isSold ? 'Sale' : 'Rental'}</td>
                    <td>${getProductStatusHTML(product.soldPProduct, product.is_released, product.is_returned)}</td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
            <p class="text-muted">Scan products continuously to release them.</p>
        `;
        
        $transactionInfo.html(html);
    }
    
    /**
     * Handle release form submission
     */
    function submitReleaseForm() {
        const transactionID = $selectedTransactionID.val();
        if (!transactionID) {
            showStatusMessage('No transaction selected.', 'danger');
            return;
        }
        
        // Show loading message
        showStatusMessage('<i class="fas fa-spinner fa-spin"></i> Processing...', 'info');
        
        $.ajax({
            url: "release.php",
            type: "POST",
            data: $releaseForm.serialize(),
            success: function (response) {
                // Check if we have HTML response with alert
                if (response.includes('<div class="alert')) {
                    try {
                        // Extract just the alert div
                        const alertMatch = response.match(/<div class="alert alert-([^"]+)">([\s\S]*?)<\/div>/);
                        if (alertMatch && alertMatch.length >= 3) {
                            const alertType = alertMatch[1]; // success, danger, etc.
                            let alertText = alertMatch[2].trim();
                            
                            // Clean up any HTML tags within the alert text
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = alertText;
                            alertText = tempDiv.textContent || tempDiv.innerText || alertText;
                            
                            // Show the status message
                            showStatusMessage(alertText, alertType);
                            
                            // Refresh transaction details after successful release
                            if (alertType === 'success') {
                                refreshTransactionDetails(transactionID);
                            }
                            return;
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                    }
                }
                
                // Fallback: show generic message if we couldn't parse the response
                showStatusMessage('Operation completed, but response format was unexpected.', 'info');
                refreshTransactionDetails(transactionID);
            },
            error: function (xhr, status, error) {
                showStatusMessage(`An error occurred: ${error}`, 'danger');
            },
            complete: function () {
                // Clear only the input field for next scan, maintain the same transaction
                $codeProduct.val('').focus();
            }
        });
    }
    
    /**
     * Refresh transaction details after product release
     */
    function refreshTransactionDetails(transactionID) {
        $.ajax({
            url: "release.php",
            type: "POST",
            data: {
                action: 'getTransactionDetails',
                transactionID: transactionID
            },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    // Update transaction details display
                    displayTransactionDetails(response.transaction, response.products);
                }
            }
        });
    }
    
    // Initialize by focusing on the search input field
    $searchQuery.focus();
});
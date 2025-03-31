function initEventListeners() {
    // Product search
    $('#productSearch').on('keyup', function () {
        dataTable.search(this.value).draw();
    });

    // Select all products checkbox
    $('#selectAll').change(function () {
        var isChecked = $(this).is(':checked');
        var visibleRows = dataTable.rows({
            'search': 'applied'
        }).nodes();

        $(visibleRows).find('.product-select').each(function () {
            var productId = $(this).data('id');
            $(this).prop('checked', isChecked);
            if (isChecked) {
                checkedProducts.add(productId);
            } else {
                checkedProducts.delete(productId);
            }
        });
        updateSelectedProducts();
    });

    // Individual product select checkbox
    $(document).on('change', '.product-select', function () {
        var productId = $(this).data('id');
        if ($(this).is(':checked')) {
            checkedProducts.add(productId);
        } else {
            checkedProducts.delete(productId);
        }
        updateSelectAllState();
        updateSelectedProducts();
    });

    // Form submission handler
    $('#transmittalForm').submit(function (e) {
        e.preventDefault();

        var productIDs = Array.from(checkedProducts);
        if (productIDs.length === 0) {
            alert('Please select at least one product');
            return;
        }

        var formData = {
            productIDs: productIDs,
            fromLocation: $('#fromLocation').val(),
            toLocation: $('#toLocation').val(),
            noteTransmittal: $('#noteTransmittal').val()
        };

        $.ajax({
            type: "POST",
            url: "assets/controllers/transmittal/transmittal_controller.php",
            data: formData,
            success: function (response) {
                if (response.status === 'success') {
                    alert('Transmittal(s) created successfully');
                    // Clear the form
                    $('#transmittalForm')[0].reset();
                    checkedProducts.clear();
                    updateSelectedProducts();
                    // Refresh pending transmittals and highlight new ones
                    refreshPendingTransmittals(response.newTransmittalIds);
                    // Switch to pending tab
                    $('#pending-tab').tab('show');
                } else {
                    alert('Error creating transmittal: ' + response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error:', error);
                alert('Error submitting form');
            }
        });
    });

    // Handle receive button clicks
    $(document).on('click', '.receive-btn', function () {
        var transmittalId = $(this).data('id');
        if (confirm('Are you sure you want to receive this product?')) {
            $.ajax({
                type: "POST",
                url: "assets/controllers/transmittal/receive_transmittal.php",
                data: {
                    transmittalId: transmittalId
                },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        alert('Product received successfully');
                        // Refresh both pending and receive tables
                        refreshPendingTransmittals();
                        refreshReceiveProducts();
                    } else {
                        alert('Error receiving product: ' + response.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    alert('Error processing request. Check console for details.');
                }
            });
        }
    });

    // Handle receive all button clicks for a request group
    $(document).on('click', '.receive-all', function () {
        var requestId = $(this).data('request-id');
        if (confirm('Are you sure you want to receive all products from Request #' + requestId + '?')) {
            $.ajax({
                type: "POST",
                url: "assets/controllers/transmittal/receive_group_transmittal.php",
                data: {
                    requestId: requestId
                },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        alert('All products received successfully');
                        // Refresh both pending and receive tables
                        refreshPendingTransmittals();
                        refreshReceiveProducts();
                    } else {
                        alert('Error receiving products: ' + response.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    alert('Error processing request. Check console for details.');
                }
            });
        }
    });

    // Handle status change buttons with improved feedback
    $(document).on('click', '.status-btn', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var transmittalId = $(this).data('id');
        var newStatus = $(this).data('action');
        var requestId = $(this).closest('tr').data('request-id');

        console.log('Status button clicked', {
            transmittalId: transmittalId,
            newStatus: newStatus,
            requestId: requestId
        });

        if (!transmittalId || !newStatus) {
            console.error('Missing required data attributes on button');
            showErrorMessage('Error: Could not determine transmittal ID or action');
            return;
        }

        // If request ID is available and there are multiple transmittals in the request,
        // ask if user wants to update all of them
        if (requestId) {
            $('#transmittalId').val(transmittalId);
            $('#requestId').val(requestId);
            $('#newStatus').val(newStatus);
            var batchModal = new bootstrap.Modal(document.getElementById('batchUpdateModal'));
            batchModal.show();
            return;
        }

        processSingleTransmittalUpdate(transmittalId, newStatus);
    });

    // Handle group action buttons (Set All In Transit, Cancel All)
    $(document).on('click', '.group-action-btn', function(e) {
        e.preventDefault();
        
        var requestId = $(this).data('request-id');
        var action = $(this).data('action');
        
        console.log('Group action button clicked:', {
            requestId: requestId,
            action: action
        });
        
        if (!requestId || !action) {
            console.error('Missing required data attributes on button');
            showErrorMessage('Error: Could not determine request ID or action');
            return;
        }
        
        var confirmMsg = action === 'CANCELLED' ?
            'Are you sure you want to cancel all transmittals in this request?' :
            'Are you sure you want to mark all transmittals in this request as In Transit?';
            
        if (confirm(confirmMsg)) {
            // Show processing indicator
            showProcessingIndicator();
            
            $.ajax({
                url: 'assets/controllers/transmittal/update_group_transmittal_status.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    requestId: requestId,
                    status: action
                },
                success: function(response) {
                    hideProcessingIndicator();
                    
                    if (response.status === 'success') {
                        showSuccessMessage('All transmittals updated successfully');
                        refreshPendingTransmittals();
                    } else {
                        showErrorMessage('Error updating transmittals: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    hideProcessingIndicator();
                    
                    console.error('AJAX Error:', {
                        status: status, 
                        error: error,
                        responseText: xhr.responseText
                    });
                    
                    showErrorMessage('Error updating transmittals: ' + error);
                }
            });
        }
    });

    // Handle update all button in the batch update modal
    $(document).on('click', '#updateAllBtn', function() {
        var requestId = $('#requestId').val();
        var newStatus = $('#newStatus').val();
        
        var batchModal = bootstrap.Modal.getInstance(document.getElementById('batchUpdateModal'));
        batchModal.hide();
        
        // Show processing indicator
        showProcessingIndicator();
        
        console.log('Updating all transmittals for request', requestId, 'to', newStatus);
        
        $.ajax({
            url: 'assets/controllers/transmittal/update_group_transmittal_status.php',
            type: 'POST',
            dataType: 'json',
            data: {
                requestId: requestId,
                status: newStatus
            },
            success: function(response) {
                hideProcessingIndicator();
                
                if (response.status === 'success') {
                    showSuccessMessage('All transmittals updated successfully');
                    refreshPendingTransmittals();
                } else {
                    showErrorMessage('Error updating transmittals: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                hideProcessingIndicator();
                
                console.error('AJAX Error:', {
                    status: status, 
                    error: error,
                    responseText: xhr.responseText
                });
                
                showErrorMessage('Error updating transmittals: ' + error);
            }
        });
    });

    // Helper function to process a single transmittal update
    function processSingleTransmittalUpdate(transmittalId, newStatus) {
        var confirmMsg = newStatus === 'CANCELLED' ?
            'Are you sure you want to cancel this transmittal?' :
            'Are you sure you want to mark this as In Transit?';

        if (confirm(confirmMsg)) {
            showProcessingIndicator();

            $.ajax({
                url: 'assets/controllers/transmittal/update_transmittal_status.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    transmittalId: transmittalId,
                    status: newStatus
                },
                success: function (response) {
                    hideProcessingIndicator();
                    
                    console.log('Response from server:', response);
                    if (response && response.status === 'success') {
                        showSuccessMessage('Status updated successfully');
                        // Refresh both pending and receive tables
                        refreshPendingTransmittals();
                        refreshReceiveProducts();
                    } else {
                        showErrorMessage('Error: ' + (response.message || 'Unknown error occurred'));
                    }
                },
                error: function (xhr, status, error) {
                    hideProcessingIndicator();
                    
                    console.error('AJAX Error:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    
                    try {
                        var resp = JSON.parse(xhr.responseText);
                        showErrorMessage('Error: ' + (resp.message || 'Server error occurred'));
                    } catch (ex) {
                        showErrorMessage('Error: Could not process the request. See console for details.');
                    }
                }
            });
        }
    }

    // Helper functions for UI feedback
    function showProcessingIndicator() {
        if ($('#processingIndicator').length === 0) {
            $('body').append('<div id="processingIndicator" class="position-fixed top-50 start-50 translate-middle bg-white p-3 rounded shadow" style="z-index: 9999;"><div class="d-flex align-items-center"><div class="spinner-border text-primary me-3" role="status"></div><span>Processing...</span></div></div>');
        }
    }

    function hideProcessingIndicator() {
        $('#processingIndicator').remove();
    }

    function showSuccessMessage(message) {
        showToast(message, 'success');
    }

    function showErrorMessage(message) {
        showToast(message, 'danger');
    }

    function showToast(message, type) {
        const toastId = 'toast-' + Date.now();
        const toast = `
            <div id="${toastId}" class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
                <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(toast);
        const toastElement = new bootstrap.Toast(document.getElementById(toastId).querySelector('.toast'), {
            delay: 5000
        });
        toastElement.show();
        
        // Remove the toast element after it's hidden
        document.getElementById(toastId).addEventListener('hidden.bs.toast', function() {
            $(this).remove();
        });
    }

    // Filter by status for history tab
    $('#statusFilter').on('change', function () {
        var status = $(this).val();
        historyTable
            .column(6) // Status column
            .search(status ? status : '', true, false)
            .draw();
    });

    // Handle refresh button clicks
    $(document).on('click', '.refresh-table', function () {
        var tableType = $(this).data('table');
        var button = $(this);
        console.log('Refresh button clicked for', tableType, 'table');

        // Add spinning animation
        button.prop('disabled', true);
        button.find('i').addClass('fa-spin');

        // Load the appropriate content based on table type
        if (tableType === 'pending') {
            console.log('Calling refreshPendingTransmittals()');
            refreshPendingTransmittals();
        } else if (tableType === 'receive') {
            console.log('Calling refreshReceiveProducts()');
            refreshReceiveProducts();
        } else if (tableType === 'history') {
            console.log('Reloading page for history tab');
            // Simple page reload for history
            location.reload();
            return; // Exit early since we're reloading the page
        }

        // Remove spinning animation after appropriate time
        setTimeout(function () {
            button.prop('disabled', false);
            button.find('i').removeClass('fa-spin');
            console.log('Refresh animation stopped for', tableType, 'table');
        }, 1000);
    });

    // Add click handler for refresh products button
    $(document).on('click', '.refresh-products', function(e) {
        e.preventDefault();
        refreshProductList();
    });

    // Add click handler for clear choices button
    $(document).on('click', '.clear-choices', function(e) {
        e.preventDefault();
        // Clear all checkboxes
        $('.product-select').prop('checked', false);
        // Clear the checked products set
        checkedProducts.clear();
        // Update the select all checkbox
        $('#selectAll').prop('checked', false);
        // Update the selected products display
        updateSelectedProducts();
    });
    
    // Initialize the product scanning functionality
    initProductScanning();
} 
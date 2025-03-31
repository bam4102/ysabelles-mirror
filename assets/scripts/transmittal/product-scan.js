/**
 * Handles the product scanning functionality for receiving transmittals
 */

// Process the product code scanning
function processProductScan(productCode) {
    // Show processing indicator
    const messageDiv = $('#scanResultMessage');
    messageDiv.removeClass('d-none alert-success alert-danger').addClass('alert alert-info');
    messageDiv.html('<i class="fas fa-spinner fa-spin"></i> Processing product scan...');

    // Send AJAX request to scan product
    $.ajax({
        url: 'assets/controllers/transmittal/scan_product.php',
        type: 'POST',
        dataType: 'json',
        data: {
            productCode: productCode
        },
        success: function(response) {
            if (response.status === 'success') {
                // Display success message
                messageDiv.removeClass('alert-info alert-danger').addClass('alert-success');
                messageDiv.html(`<i class="fas fa-check-circle"></i> <strong>${response.productName}</strong> received successfully!`);
                
                // Clear the input field for next scan
                $('#productScanInput').val('').focus();
                
                // Highlight the row in the table if it exists
                const row = $(`#receiveTable tbody tr[data-code="${productCode}"]`);
                if (row.length) {
                    row.addClass('table-success');
                    setTimeout(() => {
                        row.fadeOut('slow', function() {
                            // Refresh the table after fade out
                            refreshReceiveProducts();
                        });
                    }, 2000);
                } else {
                    // Just refresh the table
                    setTimeout(() => {
                        refreshReceiveProducts();
                    }, 1500);
                }
            } else {
                // Display error message
                messageDiv.removeClass('alert-info alert-success').addClass('alert-danger');
                messageDiv.html(`<i class="fas fa-exclamation-triangle"></i> ${response.message}`);
                
                // Focus on input for next scan
                $('#productScanInput').select().focus();
            }
            
            // Setup timeout to clear the message
            setTimeout(() => {
                if (response.status !== 'success') {
                    messageDiv.addClass('d-none');
                }
            }, 5000);
        },
        error: function(xhr, status, error) {
            // Display error message
            messageDiv.removeClass('alert-info alert-success').addClass('alert-danger');
            messageDiv.html('<i class="fas fa-exclamation-triangle"></i> Error processing scan. Please try again.');
            console.error('Error scanning product:', error);
            
            // Focus on input for next scan
            $('#productScanInput').select().focus();
            
            // Setup timeout to clear the message
            setTimeout(() => {
                messageDiv.addClass('d-none');
            }, 5000);
        }
    });
}

// Initialize product scanning functionality
function initProductScanning() {
    // Handle button click for product scanning
    $('#productScanButton').on('click', function() {
        const productCode = $('#productScanInput').val().trim();
        if (productCode) {
            processProductScan(productCode);
        } else {
            // Alert if no code is entered
            const messageDiv = $('#scanResultMessage');
            messageDiv.removeClass('d-none alert-success alert-info').addClass('alert alert-danger');
            messageDiv.html('<i class="fas fa-exclamation-triangle"></i> Please enter a product code.');
            
            // Focus on input
            $('#productScanInput').focus();
            
            // Setup timeout to clear the message
            setTimeout(() => {
                messageDiv.addClass('d-none');
            }, 3000);
        }
    });
    
    // Handle enter key press in input field
    $('#productScanInput').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            const productCode = $(this).val().trim();
            if (productCode) {
                processProductScan(productCode);
            }
        }
    });
    
    // Focus the input field when the tab is shown
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        if ($(e.target).attr('id') === 'receive-tab') {
            $('#productScanInput').focus();
        }
    });
} 
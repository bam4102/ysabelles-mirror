// Function to refresh pending transmittals with improved error handling
function refreshPendingTransmittals(newTransmittalIds) {
    console.log("Refreshing pending transmittals...");
    
    // Show loading indicator
    $('#pendingTransmittalsContent').html('<div class="text-center my-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
    
    $.ajax({
        url: 'assets/controllers/transmittal/get_pending_transmittals.php',
        type: 'GET',
        dataType: 'json',
        cache: false, // Disable caching
        success: function (data) {
            console.log("Pending transmittals data received:", data);
            
            if (!data) {
                console.error("No data returned from server");
                $('#pendingTransmittalsContent').html('<div class="alert alert-danger">Error: No data received from server</div>');
                return;
            }
            
            if (data.error) {
                console.error("Error from server:", data.error);
                $('#pendingTransmittalsContent').html('<div class="alert alert-danger">Error: ' + data.error + '</div>');
                return;
            }

            try {
                renderPendingTransmittalsUI(data, newTransmittalIds);
            } catch (err) {
                console.error("Error rendering pending transmittals:", err);
                $('#pendingTransmittalsContent').html('<div class="alert alert-danger">Error rendering pending transmittals: ' + err.message + '</div>');
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX Error:", {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
            
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.error) {
                    $('#pendingTransmittalsContent').html('<div class="alert alert-danger">Error: ' + response.error + '</div>');
                } else {
                    $('#pendingTransmittalsContent').html('<div class="alert alert-danger">Error: ' + error + '</div>');
                }
            } catch (e) {
                $('#pendingTransmittalsContent').html('<div class="alert alert-danger">Error: ' + error + '</div>');
            }
        }
    });
}

// Function to render the pending transmittals UI with grouped data
function renderPendingTransmittalsUI(data, newTransmittalIds) {
    var content = '';
    var requestTemplate = $('#requestGroupTemplate').html();
    var individualTemplate = $('#individualGroupTemplate').html();
    var rowTemplate = $('#rowTemplate').html();
    var groupRowTemplate = $('#groupRowTemplate').html();
    
    // Render request groups
    if (data.grouped && data.grouped.length > 0) {
        data.grouped.forEach(function(group) {
            var rows = '';
            group.items.forEach(function(item) {
                rows += groupRowTemplate
                    .replace(/{{transmittalId}}/g, item.transmittalId)
                    .replace(/{{productId}}/g, item.productId)
                    .replace(/{{productName}}/g, item.productName)
                    .replace(/{{fromLocation}}/g, item.fromLocation)
                    .replace(/{{toLocation}}/g, item.toLocation)
                    .replace(/{{date}}/g, item.date)
                    .replace(/{{{statusBadge}}}/g, item.statusBadge)
                    .replace(/{{notes}}/g, item.notes || '');
            });
            
            // Get the template and replace rows
            var groupHtml = requestTemplate
                .replace(/{{requestId}}/g, group.requestId)
                .replace(/{{rows}}/g, rows);
            
            // If all items are in transit, remove the group action buttons
            if (group.allInTransit) {
                // Replace the buttons div with an empty div
                groupHtml = groupHtml.replace(
                    /<div class="btn-group">[\s\S]*?<\/div>/g, 
                    ''
                );
            }
            
            content += groupHtml;
        });
    }
    
    // Render individual transmittals
    if (data.individual && data.individual.length > 0) {
        var individualRows = '';
        data.individual.forEach(function(item) {
            individualRows += rowTemplate
                .replace(/{{transmittalId}}/g, item.transmittalId)
                .replace(/{{productId}}/g, item.productId)
                .replace(/{{productName}}/g, item.productName)
                .replace(/{{fromLocation}}/g, item.fromLocation)
                .replace(/{{toLocation}}/g, item.toLocation)
                .replace(/{{date}}/g, item.date)
                .replace(/{{{statusBadge}}}/g, item.statusBadge)
                .replace(/{{notes}}/g, item.notes || '')
                .replace(/{{{actionButton}}}/g, item.actionButton);
        });
        
        content += individualTemplate.replace(/{{rows}}/g, individualRows);
    }
    
    // If no content, show message
    if (!content) {
        content = '<div class="alert alert-info">No pending transmittals found</div>';
    }
    
    $('#pendingTransmittalsContent').html(content);
    
    // Initialize Bootstrap dropdowns
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    dropdownElementList.forEach(function(dropdownToggleEl) {
        new bootstrap.Dropdown(dropdownToggleEl);
    });
    
    // Highlight new transmittals if any
    if (newTransmittalIds && newTransmittalIds.length > 0) {
        console.log("Highlighting new transmittals:", newTransmittalIds);
        highlightNewTransmittals(newTransmittalIds);
    }
}

// Function to refresh receive products with improved error handling
function refreshReceiveProducts() {
    console.log("Refreshing receive products...");
    
    // Show loading indicator
    $('#receiveTableContainer').html('<div class="text-center my-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
    
    $.ajax({
        url: 'assets/controllers/transmittal/get_receive_products.php',
        type: 'GET',
        dataType: 'json',
        cache: false, // Disable caching
        success: function (data) {
            console.log("Receive products data received:", data);
            
            if (!data) {
                console.error("No data returned from server");
                $('#receiveTableContainer').html('<div class="alert alert-danger">Error: No data received from server</div>');
                return;
            }
            
            if (data.error) {
                console.error("Error from server:", data.error);
                $('#receiveTableContainer').html('<div class="alert alert-danger">Error: ' + data.error + '</div>');
                return;
            }

            try {
                renderReceiveProductsUI(data);
            } catch (err) {
                console.error("Error rendering receive products:", err);
                $('#receiveTableContainer').html('<div class="alert alert-danger">Error rendering receive products: ' + err.message + '</div>');
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX Error:", {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
            
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.error) {
                    $('#receiveTableContainer').html('<div class="alert alert-danger">Error: ' + response.error + '</div>');
                } else {
                    $('#receiveTableContainer').html('<div class="alert alert-danger">Error: ' + error + '</div>');
                }
            } catch (e) {
                $('#receiveTableContainer').html('<div class="alert alert-danger">Error: ' + error + '</div>');
            }
        }
    });
}

// Function to render the receive products UI with grouped data
function renderReceiveProductsUI(data) {
    var content = '';
    var requestTemplate = $('#receiveRequestGroupTemplate').html();
    var individualTemplate = $('#receiveIndividualGroupTemplate').html();
    var rowTemplate = $('#receiveRowTemplate').html();
    
    // Render request groups
    if (data.grouped && data.grouped.length > 0) {
        data.grouped.forEach(function(group) {
            var rows = '';
            group.items.forEach(function(item) {
                rows += rowTemplate
                    .replace(/{{transmittalId}}/g, item.transmittalId)
                    .replace(/{{productId}}/g, item.productId)
                    .replace(/{{productName}}/g, item.productName)
                    .replace(/{{fromLocation}}/g, item.fromLocation)
                    .replace(/{{toLocation}}/g, item.toLocation)
                    .replace(/{{date}}/g, item.date)
                    .replace(/{{{statusBadge}}}/g, item.statusBadge)
                    .replace(/{{notes}}/g, item.notes || '')
                    .replace(/{{{actionButton}}}/g, item.actionButton);
            });
            
            content += requestTemplate
                .replace(/{{requestId}}/g, group.requestId)
                .replace(/{{rows}}/g, rows);
        });
    }
    
    // Render individual transmittals
    if (data.individual && data.individual.length > 0) {
        var individualRows = '';
        data.individual.forEach(function(item) {
            individualRows += rowTemplate
                .replace(/{{transmittalId}}/g, item.transmittalId)
                .replace(/{{productId}}/g, item.productId)
                .replace(/{{productName}}/g, item.productName)
                .replace(/{{fromLocation}}/g, item.fromLocation)
                .replace(/{{toLocation}}/g, item.toLocation)
                .replace(/{{date}}/g, item.date)
                .replace(/{{{statusBadge}}}/g, item.statusBadge)
                .replace(/{{notes}}/g, item.notes || '')
                .replace(/{{{actionButton}}}/g, item.actionButton);
        });
        
        content += individualTemplate.replace(/{{rows}}/g, individualRows);
    }
    
    // If no content, show message
    if (!content) {
        content = '<div class="alert alert-info">No products to receive found</div>';
    }
    
    $('#receiveTableContainer').html(content);
    
    // Initialize Bootstrap dropdowns if any
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    dropdownElementList.forEach(function(dropdownToggleEl) {
        new bootstrap.Dropdown(dropdownToggleEl);
    });
}

// Function to refresh history data with improved error handling
function refreshHistoryData() {
    $.ajax({
        url: './assets/controllers/transmittal/get_history_data.php',
        type: 'GET',
        dataType: 'json',
        success: function (data) {
            if (!data || !Array.isArray(data)) {
                console.error("Invalid data returned from server:", data);
                alert('Error: Invalid data format received from server');
                return;
            }

            try {
                // Get the DataTable instance
                var table = $('#historyTable').DataTable();
                // Clear the table
                table.clear();
                // Add the new data safely
                table.rows.add(data).draw();

                // Update statistics if they exist
                if (data.stats) {
                    $('#totalTransmittals').text(data.stats.total);
                    $('#pendingCount').text(data.stats.pending);
                    $('#inTransitCount').text(data.stats.in_transit);
                    $('#deliveredCount').text(data.stats.delivered);
                    $('#cancelledCount').text(data.stats.cancelled);

                    // Update progress bars
                    $('#pendingProgress').css('width', (data.stats.pending / data.stats.total * 100) + '%');
                    $('#inTransitProgress').css('width', (data.stats.in_transit / data.stats.total * 100) + '%');
                    $('#deliveredProgress').css('width', (data.stats.delivered / data.stats.total * 100) + '%');
                    $('#cancelledProgress').css('width', (data.stats.cancelled / data.stats.total * 100) + '%');
                }
            } catch (err) {
                console.error("Error updating history table:", err);
                alert('Error refreshing transmittal history');
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX Error:", status, error);
            alert('Error refreshing transmittal history: ' + error);
        }
    });
}

// Function to highlight newly added transmittals
function highlightNewTransmittals(transmittalIds) {
    if (!transmittalIds || !Array.isArray(transmittalIds)) return;

    transmittalIds.forEach(function (id) {
        var row = $('tr[data-id="' + id + '"]');
        
        if (row.length) {
            row.addClass('highlight-new');
            setTimeout(function () {
                row.removeClass('highlight-new');
            }, 5000); // Remove highlight after 5 seconds
        }
    });
} 
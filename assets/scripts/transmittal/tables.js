// Declare global table variables
var dataTable, pendingTable, receiveTable, historyTable;

// Set of checked products for the new transmittal form
var checkedProducts = new Set();

function initTables() {
    initProductTable();
    // We now use a template-based approach for pending transmittals
    // initPendingTable(); 
    initReceiveTable();
    initHistoryTable();
    
    // Fix for DataTable width issues on tab change
    $('a[data-bs-toggle="tab"], button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        // Adjust column widths
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();

        // Get the target pane ID
        var targetPane = $(e.target).attr('data-bs-target');

        // Refresh data based on the target pane
        switch (targetPane) {
            case '#pending-pane':
                refreshPendingTransmittals();
                break;
            case '#receive-pane':
                refreshReceiveProducts();
                break;
            case '#history-pane':
                refreshHistoryData();
                break;
        }
    });
}

function initProductTable() {
    try {
        // Initialize DataTable for products
        dataTable = $('#productTable').DataTable({
            order: [
                [1, 'desc']
            ],
            pageLength: 10,
            language: {
                search: "",
                searchPlaceholder: "Search by ID or Name"
            },
            drawCallback: function () {
                // Restore checked state after table redraw
                $('.product-select').each(function () {
                    var productId = $(this).data('id');
                    if (checkedProducts.has(productId)) {
                        $(this).prop('checked', true);
                    }
                });
                updateSelectAllState();
            }
        });
        console.log("Product table initialized successfully");
    } catch (error) {
        console.error("Error initializing product table:", error);
    }
}

function initPendingTable() {
    try {
        // First check if the table exists in the DOM
        if ($("#pendingTable").length > 0) {
            console.log("Initializing pending table...");
            pendingTable = $('#pendingTable').DataTable({
                order: [[4, 'desc']], // Sort by date column
                pageLength: 10,
                language: {
                    search: "",
                    searchPlaceholder: "Search transmittals",
                    emptyTable: "No pending transmittals found"
                },
                scrollX: true,
                autoWidth: false,
                processing: true,
                columnDefs: [
                    { targets: 7, orderable: false }, // Make action column non-orderable
                    { targets: [0, 1], width: '15%' }, // Product ID and Name
                    { targets: [2, 3], width: '12%' }, // From and To
                    { targets: 4, width: '15%' }, // Date
                    { targets: 5, width: '10%' }, // Status
                    { targets: 6, width: '20%' }, // Notes
                    { targets: 7, width: '11%' }  // Action
                ],
                // Initialize with empty data
                data: [],
                // Define columns to match API response format
                columns: [
                    { data: 0 }, // Product ID
                    { data: 1 }, // Product Name
                    { data: 2 }, // From
                    { data: 3 }, // To
                    { data: 4 }, // Date
                    { data: 5 }, // Status
                    { data: 6 }, // Notes
                    { data: 7 }  // Action
                ],
                drawCallback: function() {
                    // Re-initialize dropdown functionality after table draw
                    if (typeof bootstrap !== 'undefined') {
                        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
                        dropdownElementList.map(function (dropdownToggleEl) {
                            return new bootstrap.Dropdown(dropdownToggleEl);
                        });
                    }
                }
            });
            console.log("Pending table initialized successfully");
            
            // Load initial data
            refreshPendingTransmittals();
        } else {
            console.log("Pending table element not found in DOM");
        }
    } catch (error) {
        console.error("Error initializing pending table:", error);
    }
}

function initReceiveTable() {
    try {
        if ($("#receiveTable").length > 0) {
            receiveTable = $('#receiveTable').DataTable({
                pageLength: 10,
                language: {
                    emptyTable: "No products to receive"
                },
                scrollX: true,
                autoWidth: false,
                order: [[4, 'desc']], // Sort by date column
                columnDefs: [
                    { targets: 7, orderable: false } // Make action column non-orderable
                ],
                // Set columns explicitly to match server-side structure
                columns: [
                    { data: 0 }, // Product ID
                    { data: 1 }, // Product Name
                    { data: 2 }, // From
                    { data: 3 }, // To
                    { data: 4 }, // Date
                    { data: 5 }, // Status
                    { data: 6 }, // Notes
                    { data: 7 }  // Action
                ]
            });
            console.log("Receive table initialized successfully");
        }
    } catch (error) {
        console.error("Error initializing receive table:", error);
    }
}

function initHistoryTable() {
    try {
        if ($("#historyTable").length > 0) {
            historyTable = $('#historyTable').DataTable({
                order: [[4, 'desc']], // Sort by date created
                pageLength: 15,
                language: {
                    search: "",
                    searchPlaceholder: "Search transmittals"
                }
            });
            console.log("History table initialized successfully");
        }
    } catch (error) {
        console.error("Error initializing history table:", error);
    }
} 
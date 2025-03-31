$(document).ready(function () {
    // Initialize Select2 for better dropdown experience
    $('#destinationBranch').select2();

    // Handle URL hash for tabs
    function activateTabFromHash() {
        const hash = window.location.hash;
        if (hash) {
            const tab = $(`a[href="${hash}"]`);
            if (tab.length) {
                tab.tab('show');
            }
        }
    }

    // Update URL hash when tab is clicked
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const hash = $(e.target).attr('href');
        if (hash) {
            history.replaceState(null, null, hash);
        }
    });

    // Activate tab based on URL hash when page loads
    activateTabFromHash();

    // Listen for hash changes (browser back/forward buttons)
    $(window).on('hashchange', function () {
        activateTabFromHash();
    });

    // Initialize DataTables
    var requestsTable = $('#requestsTable').DataTable({
        responsive: true,
        ajax: {
            url: 'assets/controllers/branch_requests/get_requests.php',
            dataSrc: ''
        },
        columns: [
            { data: 'requestID' },
            { data: 'dateRequested' },
            { data: 'sourceBranch' },
            { data: 'destinationBranch' },
            {
                data: 'products',
                render: function (data) {
                    let products = JSON.parse(data);
                    let productList = '';

                    products.forEach(function (product, index) {
                        productList += product.name;
                        if (index < products.length - 1) {
                            productList += '<br>';
                        }
                    });

                    return productList;
                }
            },
            { data: 'requiredDate' },
            {
                data: 'status',
                render: function (data) {
                    let statusClass = '';
                    switch (data.toLowerCase()) {
                        case 'pending':
                            statusClass = 'status-pending';
                            break;
                        case 'approved':
                            statusClass = 'status-approved';
                            break;
                        case 'declined':
                            statusClass = 'status-declined';
                            break;
                        case 'completed':
                            statusClass = 'status-completed';
                            break;
                    }
                    return `<span class="status-badge ${statusClass}">${data}</span>`;
                }
            },
            {
                data: null,
                render: function (data) {
                    let buttons = `<button class="action-btn view-btn" data-id="${data.requestID}">View</button>`;

                    if (data.status.toLowerCase() === 'pending') {
                        buttons += `<button class="action-btn cancel-btn" data-id="${data.requestID}">Cancel</button>`;
                    }

                    return buttons;
                }
            }
        ],
        order: [[1, 'desc']] // Order by date requested descending
    });

    var incomingRequestsTable = $('#incomingRequestsTable').DataTable({
        responsive: true,
        ajax: {
            url: 'assets/controllers/branch_requests/get_incoming_requests.php',
            dataSrc: ''
        },
        columns: [
            { data: 'requestID' },
            { data: 'dateRequested' },
            { data: 'sourceBranch' },
            {
                data: 'products',
                render: function (data) {
                    let products = JSON.parse(data);
                    let productList = '';

                    products.forEach(function (product, index) {
                        productList += product.name;
                        if (index < products.length - 1) {
                            productList += '<br>';
                        }
                    });

                    return productList;
                }
            },
            { data: 'requiredDate' },
            {
                data: 'status',
                render: function (data) {
                    let statusClass = '';
                    switch (data.toLowerCase()) {
                        case 'pending':
                            statusClass = 'status-pending';
                            break;
                        case 'approved':
                            statusClass = 'status-approved';
                            break;
                        case 'declined':
                            statusClass = 'status-declined';
                            break;
                        case 'completed':
                            statusClass = 'status-completed';
                            break;
                    }
                    return `<span class="status-badge ${statusClass}">${data}</span>`;
                }
            },
            {
                data: null,
                render: function (data) {
                    let buttons = `<button class="action-btn view-btn" data-id="${data.requestID}">View</button>`;

                    if (data.status.toLowerCase() === 'pending') {
                        buttons += `<button class="action-btn approve-btn" data-id="${data.requestID}">Approve</button>`;
                        buttons += `<button class="action-btn decline-btn" data-id="${data.requestID}">Decline</button>`;
                    }

                    // Removed the "Mark Completed" button since Approved is now final

                    return buttons;
                }
            }
        ],
        order: [[1, 'desc']] // Order by date requested descending
    });

    // Filter functionality
    $('#statusFilter').change(function () {
        requestsTable.column(6).search($(this).val()).draw();
    });

    $('#branchFilter').change(function () {
        requestsTable.column(3).search($(this).val()).draw();
    });

    $('#startDateFilter, #endDateFilter').change(function () {
        $.fn.dataTable.ext.search.push(
            function (settings, data, dataIndex) {
                let startDate = $('#startDateFilter').val();
                let endDate = $('#endDateFilter').val();
                let dateRequested = data[1]; // Date requested is in the second column

                if (startDate === '' && endDate === '') {
                    return true;
                }

                if (startDate === '') {
                    return dateRequested <= endDate;
                }

                if (endDate === '') {
                    return dateRequested >= startDate;
                }

                return dateRequested >= startDate && dateRequested <= endDate;
            }
        );

        requestsTable.draw();
    });

    // Initialize variables for pagination and sorting
    let currentPage = 1;
    let itemsPerPage = 10;
    let totalItems = 0;
    let filteredItems = [];
    let allProducts = [];
    let selectedProductIds = new Set(); // Track selected product IDs
    let currentSort = {
        field: 'productID',
        direction: 'asc'
    };

    // Function to update pagination controls
    function updatePaginationControls() {
        const totalPages = Math.ceil(filteredItems.length / itemsPerPage);
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = Math.min(startIndex + itemsPerPage, filteredItems.length);

        $('#startEntry').text(startIndex + 1);
        $('#endEntry').text(endIndex);
        $('#totalEntries').text(filteredItems.length);
        $('#currentPage').text(currentPage);
        $('#totalPages').text(totalPages);

        $('#prevPage').prop('disabled', currentPage === 1);
        $('#nextPage').prop('disabled', currentPage === totalPages);
    }

    // Function to sort products
    function sortProducts(products, field, direction) {
        return [...products].sort((a, b) => {
            let aValue = a[field] || '';
            let bValue = b[field] || '';

            // Special handling for productID (numeric sorting)
            if (field === 'productID') {
                aValue = parseInt(aValue) || 0;
                bValue = parseInt(bValue) || 0;
            } else {
                // String sorting for other fields
                aValue = String(aValue).toLowerCase();
                bValue = String(bValue).toLowerCase();
            }

            if (direction === 'asc') {
                return aValue > bValue ? 1 : -1;
            } else {
                return aValue < bValue ? 1 : -1;
            }
        });
    }

    // Handle table header clicks for sorting
    $(document).on('click', '.sortable', function () {
        const field = $(this).data('sort');

        // Update sort direction
        if (currentSort.field === field) {
            currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
        } else {
            currentSort.field = field;
            currentSort.direction = 'asc';
        }

        // Update sort icons
        $('.sortable i').removeClass('fa-sort-up fa-sort-down').addClass('fa-sort');
        $(this).find('i').removeClass('fa-sort').addClass(`fa-sort-${currentSort.direction === 'asc' ? 'up' : 'down'}`);

        // Sort and display products
        filteredItems = sortProducts(filteredItems, currentSort.field, currentSort.direction);
        currentPage = 1;
        displayProducts();
    });

    // Function to display products for current page
    function displayProducts() {
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = Math.min(startIndex + itemsPerPage, filteredItems.length);
        const pageProducts = filteredItems.slice(startIndex, endIndex);

        let tableHtml = '';
        if (pageProducts.length === 0) {
            tableHtml = `
                <tr>
                    <td colspan="4" class="text-center">No products found matching "${$('#productSearchInput').val()}"</td>
                </tr>
            `;
        } else {
            pageProducts.forEach(product => {
                const isDamaged = product.damageProduct == 1;
                const damageDescription = product.descProduct || "No damage description available";
                const rowClass = isDamaged ? 'damaged-product' : '';
                const damagedBadge = isDamaged ?
                    `<span class="damaged-badge damage-tooltip">
                        DAMAGED
                        <span class="tooltip-text">${damageDescription}</span>
                    </span>` : '';

                // Check if product is in selected set
                const isChecked = selectedProductIds.has(product.productID.toString()) ? 'checked' : '';

                tableHtml += `
                    <tr class="${rowClass}">
                        <td>
                            <input type="checkbox" class="product-checkbox" 
                                   value="${product.productID}" 
                                   data-name="${product.nameProduct}"
                                   ${isDamaged ? 'disabled' : ''}
                                   ${isChecked}>
                        </td>
                        <td>${product.productID}</td>
                        <td>${product.nameProduct} ${damagedBadge}</td>
                        <td>${product.typeProduct || 'N/A'}</td>
                    </tr>
                `;
            });
        }

        $('#productsTable tbody').html(tableHtml);
        updatePaginationControls();
    }

    // Handle show limit change
    $('#showLimit').change(function () {
        itemsPerPage = parseInt($(this).val());
        currentPage = 1;
        displayProducts();
    });

    // Handle pagination buttons
    $('#prevPage').click(function () {
        if (currentPage > 1) {
            currentPage--;
            displayProducts();
        }
    });

    $('#nextPage').click(function () {
        const totalPages = Math.ceil(filteredItems.length / itemsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            displayProducts();
        }
    });

    // Update product search functionality
    $("#productSearchInput").on("keyup", function () {
        const searchValue = $(this).val().toLowerCase();

        // Filter products based on search value
        filteredItems = allProducts.filter(product => {
            const productName = String(product.nameProduct || '').toLowerCase();
            const productId = String(product.productID || '').toLowerCase();
            const productType = String(product.typeProduct || '').toLowerCase();

            return productName.includes(searchValue) ||
                productId.includes(searchValue) ||
                productType.includes(searchValue);
        });

        // Apply current sort to filtered items
        filteredItems = sortProducts(filteredItems, currentSort.field, currentSort.direction);

        // Reset to first page when searching
        currentPage = 1;

        // Update the display
        displayProducts();
    });

    // Select all products checkbox
    $("#selectAllProducts").click(function () {
        const isChecked = $(this).prop("checked");

        // Update all visible checkboxes
        $(".product-checkbox:visible:not(:disabled)").prop("checked", isChecked).each(function () {
            const productId = $(this).val().toString();
            if (isChecked) {
                selectedProductIds.add(productId);
            } else {
                selectedProductIds.delete(productId);
            }
        });

        updateSelectedCount();
    });

    // Update selected count when individual checkboxes change
    $(document).on("change", ".product-checkbox", function () {
        const productId = $(this).val().toString();

        if ($(this).prop("checked")) {
            selectedProductIds.add(productId);
        } else {
            selectedProductIds.delete(productId);
        }

        updateSelectedCount();

        // If not all checkboxes are checked, uncheck the "select all" checkbox
        if (!$(this).prop("checked")) {
            $("#selectAllProducts").prop("checked", false);
        } else {
            // Check if all visible checkboxes are checked
            const allVisibleChecked = $(".product-checkbox:visible:not(:disabled)").length ===
                $(".product-checkbox:visible:checked").length;
            $("#selectAllProducts").prop("checked", allVisibleChecked);
        }
    });

    // Show selected products in modal
    $("#viewSelectedBtn").click(function () {
        let selectedProducts = [];
        $(".product-checkbox:checked").each(function () {
            let productId = $(this).val();
            let productName = $(this).data("name");
            let productCode = $(this).val(); // Using productID instead of code
            selectedProducts.push(`<li>${productName} (ID: ${productCode})</li>`);
        });

        if (selectedProducts.length === 0) {
            $("#selectedProductsList").html("<li>No products selected</li>");
        } else {
            $("#selectedProductsList").html(selectedProducts.join(""));
        }

        var selectedProductsModal = new bootstrap.Modal(document.getElementById('selectedProductsModal'));
        selectedProductsModal.show();
    });

    // Function to update the selected products count and list
    function updateSelectedCount() {
        let count = selectedProductIds.size;
        $("#selectedProductsCount").text(count + " products selected");

        // Update the selected products list
        let selectedProducts = [];
        selectedProductIds.forEach(productId => {
            // Find product info from allProducts
            const product = allProducts.find(p => p.productID.toString() === productId);
            if (product) {
                selectedProducts.push(`
                    <span class="badge bg-primary me-2 mb-2">
                        ${product.nameProduct}
                        <button type="button" class="btn-close btn-close-white ms-2 remove-product" data-id="${productId}"></button>
                    </span>
                `);
            }
        });

        if (selectedProducts.length === 0) {
            $("#selectedProductsList").html('<div class="text-muted">No products selected</div>');
        } else {
            $("#selectedProductsList").html(selectedProducts.join(""));
        }
    }

    // Handle removing individual products from selection
    $(document).on("click", ".remove-product", function () {
        let productId = $(this).data("id").toString();
        selectedProductIds.delete(productId);
        $(`input.product-checkbox[value="${productId}"]`).prop("checked", false);
        updateSelectedCount();
    });

    // Clear all selected products
    $('#clearChoices').click(function () {
        selectedProductIds.clear();
        $(".product-checkbox").prop("checked", false);
        $("#selectAllProducts").prop("checked", false);
        updateSelectedCount();
    });

    // Populate products table based on selected branch
    $('#destinationBranch').change(function () {
        let branch = $(this).val();
        let requiredDate = $('#requiredDate').val();

        if (branch) {
            if (!requiredDate) {
                alert('Please select a required date first');
                $(this).val('').trigger('change');
                return;
            }

            $.ajax({
                url: 'assets/controllers/branch_requests/get_branch_products.php',
                type: 'POST',
                data: { 
                    branch: branch,
                    requiredDate: requiredDate
                },
                dataType: 'json',
                success: function (data) {
                    allProducts = data;
                    filteredItems = data;
                    currentPage = 1;
                    displayProducts();
                }
            });
        } else {
            allProducts = [];
            filteredItems = [];
            currentPage = 1;
            displayProducts();
        }
    });

    // Update products when required date changes
    $('#requiredDate').change(function() {
        let branch = $('#destinationBranch').val();
        if (branch) {
            $.ajax({
                url: 'assets/controllers/branch_requests/get_branch_products.php',
                type: 'POST',
                data: { 
                    branch: branch,
                    requiredDate: $(this).val()
                },
                dataType: 'json',
                success: function (data) {
                    allProducts = data;
                    filteredItems = data;
                    currentPage = 1;
                    displayProducts();
                }
            });
        }
    });

    // Form submission
    $('#requestForm').submit(function (e) {
        e.preventDefault();

        let selectedProducts = [];
        $(".product-checkbox:checked").each(function () {
            selectedProducts.push($(this).val());
        });

        if (selectedProducts.length === 0) {
            alert('Please select at least one product.');
            return;
        }

        let formData = {
            sourceBranch: $('#sourceBranch').val(),
            destinationBranch: $('#destinationBranch').val(),
            products: selectedProducts,
            notes: $('#requestNotes').val(),
            requiredDate: $('#requiredDate').val()
        };

        $.ajax({
            url: 'assets/controllers/branch_requests/create_request.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    alert('Request created successfully!');

                    // Clear the form
                    $('#requestForm')[0].reset();

                    // Clear selected products
                    selectedProductIds.clear();
                    $(".product-checkbox").prop("checked", false);
                    $("#selectAllProducts").prop("checked", false);
                    updateSelectedCount();

                    // Clear product table
                    $('#productsTable tbody').html('');

                    // Reset branch selection
                    $('#destinationBranch').val('').trigger('change');

                    // Refresh the tables
                    requestsTable.ajax.reload();
                    incomingRequestsTable.ajax.reload();

                    // Switch to My Request tab
                    $('#myRequest-tab').tab('show');
                    // Update URL hash to reflect the tab change
                    history.replaceState(null, null, '#myRequest-pane');
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error creating request:', error);
                alert('Failed to create request. Please try again.');
            }
        });
    });

    // View request details
    $(document).on('click', '.view-btn', function () {
        let requestId = $(this).data('id');

        $.ajax({
            url: 'assets/controllers/branch_requests/get_request_details.php',
            type: 'POST',
            data: { requestId: requestId },
            dataType: 'json',
            success: function (data) {
                let productsHtml = '';
                let products = JSON.parse(data.products);

                products.forEach(function (product) {
                    productsHtml += `<tr>
                        <td>${product.id}</td>
                        <td>${product.name}</td>
                        <td>${product.typeProduct || 'N/A'}</td>
                    </tr>`;
                });

                let html = `
                    <div class="request-details card">
                        <div class="card-header">
                            <h5 class="modal-title">Request Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="detail-row">
                                <div class="detail-label">Request ID:</div>
                                <div class="detail-value">${data.requestID}</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Date Requested:</div>
                                <div class="detail-value">${data.dateRequested}</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">From Branch:</div>
                                <div class="detail-value">${data.sourceBranch}</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">To Branch:</div>
                                <div class="detail-value">${data.destinationBranch}</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Required By:</div>
                                <div class="detail-value">${data.requiredDate}</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Status:</div>
                                <div class="detail-value">
                                    <span class="status-badge status-${data.status.toLowerCase()}">${data.status}</span>
                                </div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Notes:</div>
                                <div class="detail-value">${data.notes || 'No notes provided'}</div>
                            </div>
                        </div>
                        <div class="card-header mt-1">
                            <h5 class="modal-title">Requested Products</h5>
                        </div>
                        <div class="card-body border-top">
                            <div class="table-responsive">
                                <table class="products-table table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Product ID</th>
                                            <th>Product Name</th>
                                            <th>Product Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${productsHtml}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;

                $('#requestDetailsContent').html(html);
                $('#requestDetailsModal').modal('show');
            },
            error: function (xhr, status, error) {
                console.error('Error getting request details:', error);
                alert('Failed to get request details. Please try again.');
            }
        });
    });

    // Refresh products button
    $('#refreshProducts').click(function () {
        let branch = $('#destinationBranch').val();

        if (branch) {
            $(this).find('i').addClass('fa-spin');

            $.ajax({
                url: 'assets/controllers/branch_requests/get_branch_products.php',
                type: 'POST',
                data: { branch: branch },
                dataType: 'json',
                success: function (data) {
                    allProducts = data;
                    filteredItems = data;
                    currentPage = 1;
                    displayProducts();

                    // Stop spinning after load
                    setTimeout(() => {
                        $('#refreshProducts').find('i').removeClass('fa-spin');
                    }, 500);
                },
                error: function () {
                    $('#refreshProducts').find('i').removeClass('fa-spin');
                }
            });
        } else {
            alert('Please select a branch first');
        }
    });

    // Handle refresh buttons
    $('#refreshMyRequests').click(function () {
        requestsTable.ajax.reload();
        $(this).find('i').addClass('fa-spin');
        setTimeout(() => {
            $(this).find('i').removeClass('fa-spin');
        }, 1000);
    });

    $('#refreshIncomingRequests').click(function () {
        incomingRequestsTable.ajax.reload();
        $(this).find('i').addClass('fa-spin');
        setTimeout(() => {
            $(this).find('i').removeClass('fa-spin');
        }, 1000);
    });

    // Handle request actions
    $(document).on('click', '.cancel-btn', function () {
        if (confirm('Are you sure you want to cancel this request?')) {
            let requestId = $(this).data('id');
            updateRequestStatus(requestId, 'canceled');
        }
    });

    $(document).on('click', '.approve-btn', function () {
        if (confirm('Are you sure you want to approve this request?')) {
            let requestId = $(this).data('id');
            updateRequestStatus(requestId, 'approved');
        }
    });

    $(document).on('click', '.decline-btn', function () {
        if (confirm('Are you sure you want to decline this request?')) {
            let requestId = $(this).data('id');
            updateRequestStatus(requestId, 'declined');
        }
    });

    // Removed the complete-btn click handler

    // Function to update request status
    function updateRequestStatus(requestId, status) {
        $.ajax({
            url: 'assets/controllers/branch_requests/update_request_status.php',
            type: 'POST',
            data: {
                requestId: requestId,
                status: status
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    alert('Request status updated successfully!');

                    // Check if we should redirect to transmittal page
                    if (response.redirect) {
                        window.location.href = response.redirect;
                        return;
                    }

                    // Refresh the tables
                    requestsTable.ajax.reload();
                    incomingRequestsTable.ajax.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error updating request status:', error);
                alert('Failed to update request status. Please try again.');
            }
        });
    }

    // Set minimum date for required by date to today
    const today = new Date();
    const formattedDate = today.toISOString().split('T')[0];
    document.getElementById('requiredDate').setAttribute('min', formattedDate);
});
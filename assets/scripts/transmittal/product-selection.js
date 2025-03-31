function updateSelectAllState() {
    if (!dataTable) return; // Guard clause
    var visibleRows = dataTable.rows({
        'search': 'applied'
    }).nodes();
    var visibleCheckboxes = $(visibleRows).find('.product-select');
    var allChecked = visibleCheckboxes.length > 0 &&
        visibleCheckboxes.filter(':checked').length === visibleCheckboxes.length;
    $('#selectAll').prop('checked', allChecked);
}

function updateSelectedProducts() {
    if (!dataTable) return;
    var selectedProducts = [];
    var fromLocation = '';

    // Get all rows, not just visible ones
    var allRows = dataTable.rows().nodes();
    $(allRows).each(function () {
        var checkbox = $(this).find('.product-select');
        var productId = parseInt(checkbox.data('id')); // Convert to number for proper comparison

        if (checkedProducts.has(productId)) {
            var name = $(this).find('td:eq(2)').text().trim();
            var location = checkbox.data('location');

            selectedProducts.push({
                id: productId,
                name: name
            });

            if (!fromLocation) {
                fromLocation = location;
            }
        }
    });

    // Create badges for selected products
    var html = '';
    if (selectedProducts.length > 0) {
        html = selectedProducts.map(function (product) {
            return `<div class="badge bg-primary me-2 mb-2">
                ${product.name}
                <input type="hidden" name="productIDs[]" value="${product.id}">
            </div>`;
        }).join('');
    } else {
        html = 'No products selected';
    }

    $('#selectedProducts').html(html);
    $('#fromLocation').val(fromLocation);

    // Debug output
    console.log('Selected Products:', selectedProducts);
    console.log('Checked Products Set:', Array.from(checkedProducts));
}

function refreshProductList() {
    $.ajax({
        url: 'assets/controllers/transmittal/get_product_list.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                var table = $('#productTable').DataTable();
                table.clear();
                
                response.data.forEach(function(product) {
                    table.row.add([
                        `<input type="checkbox" class="product-select" data-id="${product.id}" data-location="${product.location}">`,
                        product.id,
                        product.name,
                        product.type,
                        product.location,
                        product.status
                    ]);
                });
                
                table.draw();
                
                // Restore checked state after refresh
                $('.product-select').each(function() {
                    var productId = $(this).data('id');
                    if (checkedProducts.has(productId)) {
                        $(this).prop('checked', true);
                    }
                });
                updateSelectAllState();
            } else {
                alert('Error refreshing product list');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error refreshing product list:', error);
            alert('Error refreshing product list');
        }
    });
} 
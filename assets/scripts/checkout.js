$(document).ready(function () {

    // Call updateCart on page load to populate the table from get_cart_checkout.php
    updateCart();

    // Function to update cart display and total.
    function updateCart() {
        $.ajax({
            url: './assets/controllers/checkout/get_cart_checkout.php',
            method: 'GET',
            success: function (cartHtml) {
                $('#cartItems').html(cartHtml);
                updateTotal();
            }
        });
    }

    // Function to update the total based on packages.
    function updateTotal() {
        $.ajax({
            url: './assets/controllers/checkout/get_cart_total.php',
            method: 'GET',
            success: function (total) {
                var normalTotal = parseFloat(total) || 0;
                var pkgASelected = 0;
                var pkgBSelected = 0;
                // Iterate over each cart row.
                $('#cartItems tr').each(function () {
                    var row = $(this);
                    // Only process if row has checkboxes.
                    var pkgA = row.find('.pkgA');
                    var pkgB = row.find('.pkgB');
                    if (pkgA.length > 0 && pkgA.is(':checked')) {
                        pkgASelected++;
                        normalTotal -= parseFloat(row.data('price')) || 0;
                    } else if (pkgB.length > 0 && pkgB.is(':checked')) {
                        pkgBSelected++;
                        normalTotal -= parseFloat(row.data('price')) || 0;
                    }
                });
                if (pkgASelected > 0) {
                    normalTotal += 5800;
                }
                if (pkgBSelected > 0) {
                    normalTotal += 12800;
                }

                // Get and validate discount
                var discountInput = $('#discountTransaction');
                var discount = parseFloat(discountInput.val()) || 0;

                // Ensure discount doesn't exceed total
                if (discount > normalTotal) {
                    discount = normalTotal;
                    discountInput.val(normalTotal);
                    alert('Discount cannot exceed total charge!');
                }

                var finalTotal = normalTotal - discount;

                // Update both displays
                $('#chargeTransaction').val(finalTotal);
                $('#totalDisplay').text('Total: ₱' + finalTotal.toFixed(2));

                // Update discount max attribute
                discountInput.attr('max', normalTotal);
            }
        });
    }

    // Handle changes to price-sold input fields
    $(document).on('input', '.price-sold-input', function() {
        var index = $(this).data('index');
        var newPrice = parseFloat($(this).val()) || 0;
        
        // Update the row's data-price attribute
        $(this).closest('tr').data('price', newPrice);
        
        // Update price in the session via AJAX
        $.ajax({
            url: './assets/controllers/checkout/update_price_sold.php',
            method: 'POST',
            data: {
                index: index,
                priceSold: newPrice
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update the total
                    updateTotal();
                } else {
                    alert('Error updating price: ' + response.error);
                }
            },
            error: function() {
                alert('Error updating price. Please try again.');
            }
        });
    });

    $(document).on('click', '.remove-item', function () {
        var index = $(this).data('index');
        console.log("Removing item at index:", index);
        $.ajax({
            url: './assets/controllers/checkout/remove_from_cart.php',
            method: 'POST',
            data: {
                index: index
            },
            dataType: 'json',
            success: function (response) {
                console.log("Remove response:", response);
                if (response.success) {
                    alert('Product removed from cart!');
                    updateCart();
                } else {
                    alert('Error: ' + response.error);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error("AJAX Error:", textStatus, errorThrown);
                alert('Error removing product.');
            }
        });
    });

    // Clear Cart functionality.
    $('#clearCart').click(function () {
        if (confirm('Are you sure you want to clear the entire cart?')) {
            $.ajax({
                url: './assets/controllers/checkout/clear_cart.php',
                method: 'POST',
                success: function () {
                    alert('Cart cleared!');
                    updateCart();
                },
                error: function () {
                    alert('Error clearing cart.');
                }
            });
        }
    });

    // Update total when discount changes.
    $('#discountTransaction').on('input', function () {
        var discount = parseFloat($(this).val()) || 0;
        var currentTotal = parseFloat($('#chargeTransaction').val()) || 0;
        var maxTotal = parseFloat($(this).attr('max')) || 0;

        if (discount > maxTotal) {
            $(this).val(maxTotal);
            alert('Discount cannot exceed total charge!');
        }
        updateTotal();
    });

    // Update total when package checkboxes change (Pkg A/B).
    $(document).on('change', '.pkgA, .pkgB', function () {
        var row = $(this).closest('tr');
        if ($(this).hasClass('pkgA') && $(this).is(':checked')) {
            row.find('.pkgB').prop('checked', false);
        } else if ($(this).hasClass('pkgB') && $(this).is(':checked')) {
            row.find('.pkgA').prop('checked', false);
        }
        updateTotal();
    });

    // Handle Add New Product form submission via AJAX.
    $('#addProductForm').on('submit', function (e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.ajax({
            url: './assets/controllers/checkout/firstuse_product.php',
            method: 'POST',
            data: formData,
            success: function (response) {
                if (response.trim() === "success") {
                    alert('New product added to cart!');
                    var modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
                    modal.hide();
                    updateCart();
                } else {
                    alert('Error: ' + response);
                }
            },
            error: function () {
                alert('Error adding new product.');
            }
        });
    });

    // On checkout form submission, collect package selections and price changes
    $('#checkoutForm').on('submit', function () {
        var selections = {};
        var priceChanges = {};
        
        $('#cartItems tr').each(function () {
            var index = $(this).data('index');
            var pkgA = $(this).find('.pkgA');
            var pkgB = $(this).find('.pkgB');
            
            if (pkgA.length && pkgA.is(':checked')) {
                selections[index] = 1;
            } else if (pkgB.length && pkgB.is(':checked')) {
                selections[index] = 2;
            } else {
                selections[index] = 0;
            }
            
            // Collect price changes for "to buy" products
            var priceSoldInput = $(this).find('.price-sold-input');
            if (priceSoldInput.length) {
                priceChanges[index] = priceSoldInput.val();
            }
        });
        
        $('#packageSelections').val(JSON.stringify(selections));
        $('#priceChanges').val(JSON.stringify(priceChanges));
    });

    // Handle importing an existing transaction
    $('#importTransactionForm').on('submit', function (e) {
        e.preventDefault();
        var transactionID = $('#transactionID').val();
        $.ajax({
            url: './assets/controllers/checkout/import_transaction.php',
            method: 'POST',
            data: {
                transactionID: transactionID
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    alert(response.message);
                    var details = response.transactionDetails;
                    // Populate standard transaction details:
                    $('#locationTransaction').val(details.locationTransaction);
                    $('#clientName').val(details.clientName);
                    $('#clientAddress').val(details.clientAddress);
                    $('#clientContact').val(details.clientContact);
                    $('#datePickUp').val(details.datePickUp);
                    $('#dateReturn').val(details.dateReturn);
                    // Populate additional fields:
                    $('#bondTransaction').val(details.bondTransaction);
                    $('#discountTransaction').val(details.discountTransaction);

                    // Set the hidden field so checkout knows to update the transaction.
                    $('#importedTransactionID').val(transactionID);

                    // Refresh the cart display
                    updateCart();
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function () {
                alert("Error importing transaction.");
            }
        });
    });
});
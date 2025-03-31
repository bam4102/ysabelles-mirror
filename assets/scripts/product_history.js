$(document).ready(function () {
    // Initialize bootstrap modal
    const historyModal = new bootstrap.Modal(document.getElementById('ph_historyModal'));
    const transactionModal = new bootstrap.Modal(document.getElementById('ph_transactionModal'));

    let productsTable = $('#productsTable').DataTable({
        order: [
            [3, 'desc']
        ], // Sort by Times Used column by default
        pageLength: 25,
        // Add these options to ensure Bootstrap 5 styling is applied
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        renderer: 'bootstrap',
        pagingType: 'full_numbers',
        language: {
            paginate: {
                previous: '&laquo;',
                next: '&raquo;',
                first: '&laquo;&laquo;',
                last: '&raquo;&raquo;'
            }
        },
        columnDefs: [{
            targets: 0, // Code column (now showing ProductID)
            render: function (data, type, row) {
                if (type === 'display') {
                    return `<code>${data}</code>`;
                }
                return data;
            }
        }, {
            targets: 3, // Times Used column
            render: function (data, type, row) {
                if (type === 'display') {
                    return '<span class="badge bg-info">' + data + ' times</span>';
                }
                return parseInt(data);
            }
        }]
    });

    // Update filter functionality for new nav-pills style with fade effect
    $('.filter-btn').click(function () {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');

        const filter = $(this).data('filter');
        
        // First fade out all rows
        $('#productsTable tbody tr').css('opacity', '0');
        
        // Small timeout to allow fade out before filtering
        setTimeout(() => {
            $.fn.dataTable.ext.search.pop(); // Remove previous filter

            if (filter !== 'all') {
                $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                    const $row = $(productsTable.row(dataIndex).node());
                    const status = $row.find('td:eq(4)').text().trim();
                    switch (filter) {
                        case 'available':
                            return status.includes('Available');
                        case 'released':
                            return status.includes('Released');
                        case 'damaged':
                            return status.includes('Damaged');
                        case 'overdue':
                            return status.includes('Delayed');
                        case 'new':
                            return $row.hasClass('new-product');
                        case 'sold':
                            return status.includes('Sold');
                        default:
                            return true;
                    }
                });
            }

            productsTable.draw();
            
            // After drawing, fade in the visible rows
            $('#productsTable tbody tr').each(function() {
                $(this).removeClass('fade-in');
                // Trigger reflow for animation to work
                void this.offsetWidth;
                $(this).addClass('fade-in');
            });
            
            // Reset opacity
            $('#productsTable tbody tr').css('opacity', '1');
            
        }, 200); // 200ms timeout for fade-out effect
    });

    // Handle history view button clicks
    $('.view-history').click(function (e) {
        e.preventDefault();
        const productId = $(this).data('product-id');

        // Show loading state
        $('#ph_historyModalContent').html('<div class="text-center py-4"><div class="spinner-border"></div></div>');
        historyModal.show();

        $.ajax({
            url: 'product_history.php',
            type: 'GET',
            data: {
                id: productId
            },
            success: function (response) {
                $('#ph_historyModalContent').html(response);
            },
            error: function () {
                $('#ph_historyModalContent').html(
                    '<div class="alert alert-danger">Error loading history details.</div>'
                );
            }
        });
    });

    // Handle transaction view clicks
    $(document).on('click', '.view-transaction', function (e) {
        e.preventDefault();
        const transactionId = $(this).data('transaction-id');

        // Show loading state
        $('#ph_transactionModalContent').html('<div class="text-center py-4"><div class="spinner-border"></div></div>');
        transactionModal.show();

        // Fetch transaction details
        $.ajax({
            url: 'product_history.php',
            type: 'GET',
            data: {
                transactionId: transactionId
            },
            success: function (response) {
                $('#ph_transactionModalContent').html(response);
            },
            error: function () {
                $('#ph_transactionModalContent').html(
                    '<div class="alert alert-danger">Error loading transaction details.</div>'
                );
            }
        });
    });

    // Add click handler for transaction headers
    $(document).on('click', '.transaction-header', function () {
        const $header = $(this);
        const $actionList = $header.siblings('.action-list');

        // Toggle active class for rotation
        $header.toggleClass('active');

        // Slide toggle the action list
        $actionList.slideToggle(300);

        // Close other open transactions
        $('.transaction-header').not($header).removeClass('active');
        $('.action-list').not($actionList).slideUp(300);
    });

    // Function to calculate delay status
    function updateDelayStatus() {
        const today = new Date();

        $('#productsTable tbody tr').each(function () {
            const $row = $(this);
            const returnDate = new Date($row.data('return-date'));
            const $statusCell = $row.find('.status-cell');

            // Remove any existing delay badge
            $statusCell.find('.delay-badge').remove();

            // Only check for delay if there's a return date and the item is released
            if (!isNaN(returnDate.getTime()) && $statusCell.find('.badge-warning').length) {
                const daysLate = Math.floor((today - returnDate) / (1000 * 60 * 60 * 24));

                if (daysLate > 7) {
                    $statusCell.append(
                        `<span class="badge bg-danger delay-badge">Delayed (${daysLate} days past return date)</span>`
                    );
                }
            }
        });
    }

    // Update delay status initially and every minute
    updateDelayStatus();
    setInterval(updateDelayStatus, 60000);
});
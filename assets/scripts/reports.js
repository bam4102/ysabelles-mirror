// Add fade animation when switching tabs
document.addEventListener('DOMContentLoaded', function () {
    // Get all tab buttons
    const triggerTabList = document.querySelectorAll('button[data-bs-toggle="tab"]');
    const filterForm = document.getElementById('reportFilterForm');
    const filterButton = document.getElementById('filterButton');
    const dateSelect = document.getElementById('dateSelect');
    const locationSelect = document.getElementById('locationSelect');

    // Setup loading indicator
    const loadingIndicator = document.createElement('div');
    loadingIndicator.className = 'loading-indicator d-none';
    loadingIndicator.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
    document.querySelector('.container').appendChild(loadingIndicator);
    
    // Function to activate tab based on hash
    function activateTabFromHash() {
        const hash = window.location.hash;
        if (hash) {
            const tabId = hash.replace('#', '');
            const tabToActivate = document.querySelector(`button[data-bs-target="#${tabId}"]`);
            if (tabToActivate) {
                const tab = new bootstrap.Tab(tabToActivate);
                tab.show();
                
                // Update the current tab in form data attribute
                filterForm.dataset.currentTab = tabId;
            }
        }
    }
    
    // Function to fetch reports via AJAX
    function fetchReportData() {
        // Show loading indicator
        loadingIndicator.classList.remove('d-none');
        
        // Get current active tab
        const currentTab = filterForm.dataset.currentTab;
        
        // Create form data for the AJAX request
        const formData = new FormData();
        formData.append('date', dateSelect.value);
        
        // Even if locationSelect is disabled, we still need to get its value
        if (locationSelect.disabled) {
            // If the select is disabled, the user is ADMIN and we use the selected value
            formData.append('location', locationSelect.value);
        } else {
            // Normal case for SUPERADMIN
            formData.append('location', locationSelect.value);
        }
        
        // Fix tab ID difference between front-end and back-end
        let ajaxTabId = currentTab;
        if (currentTab === 'new-products') {
            ajaxTabId = 'new_products';
        }
        
        console.log('Current tab:', currentTab);
        console.log('AJAX tab ID:', ajaxTabId);
        
        formData.append('tab', ajaxTabId);
        
        // Send AJAX request
        fetch('./assets/controllers/reports/ajax_reports.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }
            
            console.log('Received data for', currentTab, ':', data);
            
            if (currentTab === 'releasing') {
                console.log('Releasing tab data structure:', data.data);
                if (data.data.length > 0) {
                    console.log('First item keys:', Object.keys(data.data[0]));
                    console.log('First item values:', Object.values(data.data[0]));
                }
            }
            
            // Update the tab content with the new data
            updateTabContent(currentTab, data.data);
            
            // Equalize card heights if on alltime tab
            if (currentTab === 'alltime') {
                equalizeCardHeights();
            }
        })
        .catch(error => {
            console.error('Error fetching report data:', error);
        })
        .finally(() => {
            // Hide loading indicator
            loadingIndicator.classList.add('d-none');
        });
    }
    
    // Function to update tab content based on AJAX response
    function updateTabContent(tabId, data) {
        console.log('Updating tab content for:', tabId);
        
        const tabPane = document.getElementById(tabId);
        if (!tabPane) {
            console.error(`Tab pane with ID "${tabId}" not found`);
            return;
        }
        
        // Different update logic based on tab type
        switch(tabId) {
            case 'daily':
                updateDailyTab(tabPane, data);
                break;
            case 'alltime':
                updateAlltimeTab(tabPane, data);
                break;
            case 'unreturned':
                updateUnreturnedTab(tabPane, data);
                break;
            case 'releasing':
                updateReleasingTab(tabPane, data);
                break;
            case 'employees':
                updateEmployeesTab(tabPane, data);
                break;
            case 'new-products':
                updateNewProductsTab(tabPane, data);
                break;
            default:
                console.error('Unknown tab ID:', tabId);
        }
    }
    
    // Helper functions for updating specific tabs
    // These functions would need to be customized based on each tab's HTML structure
    function updateDailyTab(tabPane, data) {
        if (!data.sales || !data.bonds) return;
        
        // Update sales section
        tabPane.querySelector('.daily-total-sales').textContent = formatCurrency(data.sales.totalSales);
        tabPane.querySelector('.daily-total-discounts').textContent = formatCurrency(data.sales.totalDiscounts);
        tabPane.querySelector('.daily-total-income').textContent = formatCurrency(data.sales.totalIncome);
        tabPane.querySelector('.daily-cash-on-hand').textContent = formatCurrency(data.sales.cashOnHand);
        
        // Update payment methods
        const paymentMethodsContainer = tabPane.querySelector('.daily-payment-methods');
        let paymentMethodsHTML = '';
        if (data.sales.payments && data.sales.payments.length > 0) {
            paymentMethodsHTML += '<div class="indent-1">Payment Methods:</div>';
            data.sales.payments.forEach(payment => {
                paymentMethodsHTML += `
                    <div class="indent-2 amount-row">
                        <span>${payment.kindPayment} - ${payment.clientName}</span>
                        <span class="amount-value">${formatCurrency(payment.amountPayment)}</span>
                    </div>
                `;
            });
        }
        paymentMethodsContainer.innerHTML = paymentMethodsHTML;
        
        // Update bond section
        tabPane.querySelector('.daily-bond-beginning').textContent = formatCurrency(data.bonds.beginningBalance);
        tabPane.querySelector('.daily-bond-income').textContent = formatCurrency(data.bonds.bondIncome);
        tabPane.querySelector('.daily-bond-refund').textContent = `(${formatCurrency(data.bonds.bondRefund)})`;
        tabPane.querySelector('.daily-bond-ending').textContent = formatCurrency(data.bonds.endingBalance);
        
        // Update bond deposits
        const depositContainer = tabPane.querySelector('.daily-bond-deposits');
        let depositsHTML = '';
        if (data.bonds.deposits && data.bonds.deposits.length > 0) {
            depositsHTML += '<div class="indent-1">Bond Deposits:</div>';
            data.bonds.deposits.forEach(deposit => {
                depositsHTML += `
                    <div class="indent-2 amount-row">
                        <span>${deposit.clientName}</span>
                        <span class="amount-value">${formatCurrency(deposit.amount)}</span>
                    </div>
                `;
            });
        }
        depositContainer.innerHTML = depositsHTML;
        
        // Update bond refunds
        const refundsContainer = tabPane.querySelector('.daily-bond-refunds');
        let refundsHTML = '';
        if (data.bonds.refunds && data.bonds.refunds.length > 0) {
            data.bonds.refunds.forEach(refund => {
                refundsHTML += `
                    <div class="indent-2 amount-row">
                        <span>${refund.clientName}</span>
                        <span class="amount-value">(${formatCurrency(refund.amount)})</span>
                    </div>
                `;
            });
        }
        refundsContainer.innerHTML = refundsHTML;
        
        // Update the report date in the heading
        const dateObj = new Date(dateSelect.value);
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        const formattedDate = dateObj.toLocaleDateString('en-US', options);
        tabPane.querySelector('h4').textContent = formattedDate;
    }
    
    function updateAlltimeTab(tabPane, data) {
        if (!data || !data.report || !data.sales || !data.bonds) return;
        
        // Update summary cards
        const cards = tabPane.querySelectorAll('.alltime-card');
        if (cards.length >= 4) {
            // Total Sales
            cards[0].querySelector('.card-text').textContent = formatCurrency(data.report.totalSales);
            // Cash on Hand
            cards[1].querySelector('.card-text').textContent = formatCurrency(data.sales.cashOnHand);
            // Current Bond Balance
            cards[2].querySelector('.card-text').textContent = formatCurrency(data.bonds.currentBalance);
            // Total Transactions
            cards[3].querySelector('.card-text').textContent = data.report.totalTransactions.toLocaleString();
        }
        
        // Update Sales Summary
        const salesSummary = tabPane.querySelector('.card-body:nth-of-type(1)');
        if (salesSummary) {
            const amountRows = salesSummary.querySelectorAll('.amount-row');
            if (amountRows.length >= 2) {
                // Total Sales
                amountRows[0].querySelector('.amount-value').textContent = formatCurrency(data.sales.totalSales);
                // Total Discounts
                amountRows[1].querySelector('.amount-value').textContent = formatCurrency(data.report.totalDiscounts);
            }
            
            // Update payment methods
            const paymentMethodsContainer = salesSummary.querySelector('.indent-1')?.parentElement;
            if (paymentMethodsContainer) {
                let paymentMethodsHTML = '';
                if (data.sales.payments && data.sales.payments.length > 0) {
                    paymentMethodsHTML += '<div class="indent-1">Payment Methods:</div>';
                    
                    // Group payments by method
                    const paymentMethods = {};
                    data.sales.payments.forEach(payment => {
                        if (!paymentMethods[payment.kindPayment]) {
                            paymentMethods[payment.kindPayment] = 0;
                        }
                        paymentMethods[payment.kindPayment] += parseFloat(payment.amountPayment);
                    });
                    
                    // Add each payment method
                    for (const [method, amount] of Object.entries(paymentMethods)) {
                        paymentMethodsHTML += `
                            <div class="indent-2 amount-row">
                                <span>${method}</span>
                                <span class="amount-value">${formatCurrency(amount)}</span>
                            </div>
                        `;
                    }
                }
                
                // Replace the payment methods content
                const oldIndent = paymentMethodsContainer.querySelector('.indent-1');
                if (oldIndent) {
                    const parent = oldIndent.parentElement;
                    const nextSibling = paymentMethodsContainer.querySelector('.total-line');
                    
                    // Remove old payment methods
                    while (parent.firstChild !== nextSibling) {
                        if (parent.firstChild) {
                            parent.removeChild(parent.firstChild);
                        } else {
                            break;
                        }
                    }
                    
                    // Insert new payment methods
                    if (paymentMethodsHTML) {
                        const temp = document.createElement('div');
                        temp.innerHTML = paymentMethodsHTML;
                        while (temp.firstChild) {
                            parent.insertBefore(temp.firstChild, nextSibling);
                        }
                    }
                }
                
                // Update Cash on Hand
                const totalLine = salesSummary.querySelector('.total-line');
                if (totalLine) {
                    totalLine.querySelector('.amount-value').textContent = formatCurrency(data.sales.cashOnHand);
                }
            }
        }
        
        // Update Bond Summary
        const bondSummary = tabPane.querySelector('.card-body:nth-of-type(2)');
        if (bondSummary) {
            const amountRows = bondSummary.querySelectorAll('.amount-row');
            if (amountRows.length >= 3) {
                // Total Bond Deposits
                amountRows[0].querySelector('.amount-value').textContent = formatCurrency(data.bonds.totalDeposits);
                // Total Bond Refunds
                amountRows[1].querySelector('.amount-value').textContent = '(' + formatCurrency(data.bonds.totalRefunds) + ')';
                // Current Bond Balance
                amountRows[2].querySelector('.amount-value').textContent = formatCurrency(data.bonds.currentBalance);
            }
        }
        
        // Update Payment Methods table
        const paymentMethodsTable = tabPane.querySelectorAll('table')[0];
        if (paymentMethodsTable && data.report.paymentsByType) {
            const tableBody = paymentMethodsTable.querySelector('tbody');
            if (tableBody) {
                tableBody.innerHTML = '';
                data.report.paymentsByType.forEach(payment => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${payment.kindPayment}</td>
                        <td>${payment.count}</td>
                        <td>${formatCurrency(payment.total)}</td>
                    `;
                    tableBody.appendChild(row);
                });
            }
        }
        
        // Update Top Products table
        const topProductsTable = tabPane.querySelectorAll('table')[1];
        if (topProductsTable && data.report.topProducts) {
            const tableBody = topProductsTable.querySelector('tbody');
            if (tableBody) {
                tableBody.innerHTML = '';
                data.report.topProducts.forEach(product => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${product.productID}</td>
                        <td>${product.nameProduct}</td>
                        <td>${product.typeProduct}</td>
                        <td>${product.rentCount}</td>
                        <td>${formatCurrency(product.totalRevenue)}</td>
                    `;
                    tableBody.appendChild(row);
                });
            }
        }
        
        // Equalize card heights after updating
        setTimeout(equalizeCardHeights, 50);
    }
    
    function updateUnreturnedTab(tabPane, data) {
        if (!data || !Array.isArray(data)) {
            console.error('Invalid data received for unreturned tab:', data);
            return;
        }
        
        // Get the table body
        const tableBody = tabPane.querySelector('tbody');
        if (!tableBody) {
            console.error('Table body not found in unreturned tab');
            return;
        }
        
        // Clear existing rows
        tableBody.innerHTML = '';
        
        // Populate with new data
        if (data.length === 0) {
            // No unreturned items
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center">No unreturned items found</td>
                </tr>
            `;
        } else {
            // Add rows for each unreturned item
            data.forEach(item => {
                // Format date
                const dueDate = item.dateReturn ? new Date(item.dateReturn).toLocaleDateString() : '';
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.clientName || ''}</td>
                    <td>${item.clientContact || ''}</td>
                    <td>${item.items || ''}</td>
                    <td>${dueDate}</td>
                    <td>${formatCurrency(item.bondPaid || 0)}</td>
                `;
                tableBody.appendChild(row);
            });
        }
    }
    
    function updateReleasingTab(tabPane, data) {
        if (!data || !Array.isArray(data)) {
            console.error('Invalid data received for releasing tab:', data);
            return;
        }
        
        // Get the table body
        const tableBody = tabPane.querySelector('tbody');
        if (!tableBody) {
            console.error('Table body not found in releasing tab');
            return;
        }
        
        // Clear existing rows
        tableBody.innerHTML = '';
        
        // Populate with new data
        if (data.length === 0) {
            // No items due for release
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center">No products due for release</td>
                </tr>
            `;
        } else {
            // Add rows for each item due for release
            data.forEach(item => {
                // Format pick up date
                const pickUpDate = item.datePickUp ? new Date(item.datePickUp).toLocaleDateString() : '';
                
                // Determine badge class based on status
                const badgeClass = item.status === 'Ready for Release' ? 'bg-success' : 'bg-warning text-dark';
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.transactionID || ''}</td>
                    <td>${item.clientName || ''}</td>
                    <td>${item.clientContact || ''}</td>
                    <td>${item.items || ''}</td>
                    <td>${pickUpDate}</td>
                    <td>
                        <span class="badge ${badgeClass}">
                            ${item.status || ''}
                        </span>
                    </td>
                `;
                tableBody.appendChild(row);
            });
        }
    }
    
    function updateEmployeesTab(tabPane, data) {
        if (!data || !Array.isArray(data)) {
            console.error('Invalid data received for employees tab:', data);
            return;
        }
        
        // Get the table body
        const tableBody = tabPane.querySelector('tbody');
        if (!tableBody) {
            console.error('Table body not found in employees tab');
            return;
        }
        
        // Clear existing rows
        tableBody.innerHTML = '';
        
        // Populate with new data
        if (data.length === 0) {
            // No employee stats
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center">No employee transaction data available</td>
                </tr>
            `;
        } else {
            // Add rows for each employee
            data.forEach(employee => {
                // Get badge class for position
                const badgeClass = getPositionBadgeClass(employee.positionEmployee);
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${employee.nameEmployee || ''}</td>
                    <td>
                        <span class="badge ${badgeClass}">
                            ${employee.positionEmployee || ''}
                        </span>
                    </td>
                    <td>${formatNumber(employee.transactionCount || 0)}</td>
                    <td>${formatCurrency(employee.totalSales || 0)}</td>
                    <td>${formatNumber(employee.paymentCount || 0)}</td>
                    <td>${formatNumber(employee.bondCount || 0)}</td>
                `;
                tableBody.appendChild(row);
            });
        }
    }
    
    // Helper function to determine badge class for employee positions
    function getPositionBadgeClass(position) {
        if (!position) return 'bg-dark';
        
        switch (position.toUpperCase()) {
            case 'SUPERADMIN':
                return 'bg-dark';
            case 'ADMIN':
                return 'bg-danger';
            case 'INVENTORY':
                return 'bg-warning text-dark';
            case 'SALES':
                return 'bg-primary';
            case 'CASHIER':
                return 'bg-success';
            case 'COMPUTER':
                return 'bg-info';
            default:
                return 'bg-dark';
        }
    }
    
    // Helper function to format numbers
    function formatNumber(value) {
        if (!value) return '0';
        
        return Number(value).toLocaleString('en-PH');
    }
    
    function updateNewProductsTab(tabPane, data) {
        if (!data || !Array.isArray(data)) {
            console.error('Invalid data received for new products tab:', data);
            return;
        }
        
        // Get the table body
        const tableBody = tabPane.querySelector('tbody');
        if (!tableBody) {
            console.error('Table body not found in new products tab');
            return;
        }
        
        // Clear existing rows
        tableBody.innerHTML = '';
        
        // Populate with new data
        if (data.length === 0) {
            // No new products
            tableBody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center">No first-time products found</td>
                </tr>
            `;
        } else {
            // Add rows for each new product
            data.forEach(product => {
                // Format date values if they exist
                const pickUpDate = product.datePickUp ? new Date(product.datePickUp).toLocaleDateString() : '-';
                const returnDate = product.dateReturn ? new Date(product.dateReturn).toLocaleDateString() : '-';
                
                // Format badge class based on bond status
                let badgeClass = 'bg-secondary';
                let bondStatusText = 'Unknown';
                
                if (product.bondStatus !== null) {
                    if (product.bondStatus == 1) {
                        badgeClass = 'bg-success';
                        bondStatusText = 'Bond Posted';
                    } else if (product.bondStatus == 0) {
                        badgeClass = 'bg-warning text-dark';
                        bondStatusText = 'Pending Bond';
                    }
                }
                
                // Use bondStatusText from server if available
                if (product.bondStatusText) {
                    bondStatusText = product.bondStatusText;
                }
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${product.productID || ''}</td>
                    <td>${product.nameProduct || ''}</td>
                    <td>${product.typeProduct || ''}</td>
                    <td>${product.locationProduct || ''}</td>
                    <td>${product.transactionID || 'Not Rented'}</td>
                    <td>${product.clientName || '-'}</td>
                    <td>${pickUpDate}</td>
                    <td>${returnDate}</td>
                    <td>
                        <span class="badge ${badgeClass}">
                            ${bondStatusText}
                        </span>
                    </td>
                `;
                
                tableBody.appendChild(row);
            });
        }
    }
    
    // Helper function to format currency
    function formatCurrency(value) {
        if (!value) return '₱0.00';
        
        // Handle string values
        if (typeof value === 'string') {
            value = parseFloat(value);
        }
        
        return '₱' + value.toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    triggerTabList.forEach(triggerEl => {
        // Add fade animation when tab is shown
        triggerEl.addEventListener('shown.bs.tab', event => {
            const activePane = document.querySelector('.tab-pane.active');
            activePane.classList.add('animate-fade');

            // Equalize card heights when alltime tab is shown
            if (event.target.id === 'alltime-tab') {
                equalizeCardHeights();
            }
            
            // Update URL hash when tab is changed
            const targetId = event.target.getAttribute('data-bs-target').substring(1);
            window.location.hash = targetId;
            
            // Update current tab in form data attribute
            filterForm.dataset.currentTab = targetId;
        });
    });
    
    // Also run on initial page load to set the correct initial tab
    const initialActiveTab = document.querySelector('.nav-link.active');
    if (initialActiveTab) {
        const targetId = initialActiveTab.getAttribute('data-bs-target').substring(1);
        filterForm.dataset.currentTab = targetId;
        
        // Equalize card heights if alltime tab is active
        if (initialActiveTab.id === 'alltime-tab') {
            equalizeCardHeights();
        }
    }

    // Also run on window resize
    window.addEventListener('resize', function () {
        if (document.querySelector('#alltime-tab').classList.contains('active')) {
            equalizeCardHeights();
        }
    });
    
    // Add event listener for filter button click
    filterButton.addEventListener('click', function(e) {
        e.preventDefault();
        fetchReportData();
    });
    
    // Activate the correct tab based on URL hash when page loads
    activateTabFromHash();
    
    // Handle back/forward navigation
    window.addEventListener('hashchange', activateTabFromHash);

    // Function to equalize card heights in the same row
    function equalizeCardHeights() {
        // First row - summary cards
        equalizeHeightsForClass('.alltime-card');
        // Second row - sales and bond summaries
        equalizeHeightsForClass('.alltime-card-row2');
        // Third row - payment methods and top products
        equalizeHeightsForClass('.alltime-card-row3');
    }

    function equalizeHeightsForClass(selector) {
        const cards = document.querySelectorAll(selector);
        if (cards.length > 0) {
            // Reset heights to auto first to get their natural height
            cards.forEach(card => card.style.height = 'auto');

            // Get the maximum height
            let maxHeight = 0;
            cards.forEach(card => {
                const height = card.offsetHeight;
                if (height > maxHeight) {
                    maxHeight = height;
                }
            });

            // Set all cards to the maximum height
            cards.forEach(card => card.style.height = maxHeight + 'px');
        }
    }
});
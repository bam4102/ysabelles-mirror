// Initialize modal instance globally
let transactionDetailModal;
let productDetailModal;
let imageLightbox;

// Function to show product details
function showProductDetails(productId) {
    // Show loading state in modal
    const modalContent = document.getElementById('productDetailContent');
    if (modalContent) {
        modalContent.innerHTML = `
            <div class="text-center p-4">
                <div class="spinner-border text-danger" role="status"></div>
                <p class="mt-2">Loading product details...</p>
            </div>`;
        
        // Show modal
        if (productDetailModal) {
            productDetailModal.show();
            
            // Fetch product details
            fetch(`./assets/controllers/products/load_product_form.php?productId=${productId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    // Set modal title
                    const modalTitle = document.getElementById('productDetailModalLabel');
                    modalTitle.textContent = 'Product Details';
                    
                    // Insert the form HTML
                    modalContent.innerHTML = html;
                    
                    // Disable all form inputs to make it view-only
                    const form = modalContent.querySelector('form');
                    if (form) {
                        const formElements = form.querySelectorAll('input, select, textarea, button');
                        formElements.forEach(element => {
                            element.disabled = true;
                        });
                    }
                    
                    // Hide all delete image buttons
                    const deleteButtons = modalContent.querySelectorAll('.delete-image');
                    deleteButtons.forEach(button => {
                        button.style.display = 'none';
                    });
                    
                    // Hide the file upload area and disclaimer text
                    const uploadArea = modalContent.querySelector('input[type="file"]');
                    if (uploadArea) {
                        const uploadContainer = uploadArea.closest('.mb-3');
                        if (uploadContainer) {
                            uploadContainer.style.display = 'none';
                        }
                    }
                    
                    // Hide any "Add Photo" buttons
                    const addPhotoBtn = modalContent.querySelector('#addImageBtn');
                    if (addPhotoBtn) {
                        const btnContainer = addPhotoBtn.closest('.mb-3');
                        if (btnContainer) {
                            btnContainer.style.display = 'none';
                        }
                    }
                    
                    // Hide any alert messages about maximum images
                    const imageAlerts = modalContent.querySelectorAll('.alert-info');
                    imageAlerts.forEach(alert => {
                        if (alert.textContent.includes('Maximum number of images')) {
                            alert.style.display = 'none';
                        }
                    });

                    // Add click handlers to product images
                    modalContent.querySelectorAll('.product-image-detail').forEach(img => {
                        img.addEventListener('click', function() {
                            const originalUrl = this.getAttribute('data-original-url');
                            if (originalUrl) {
                                showImageLightbox(originalUrl);
                            }
                        });
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalContent.innerHTML = `
                        <div class="alert alert-danger m-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-circle me-3 fs-3"></i>
                                <div>
                                    <h5 class="mb-1">Error Loading Product</h5>
                                    <p class="mb-0">An error occurred while loading product details. Please try again.</p>
                                </div>
                            </div>
                        </div>`;
                });
        }
    }
}

// Function to show image in lightbox
function showImageLightbox(imageUrl) {
    if (!imageLightbox) {
        // Create lightbox if it doesn't exist
        imageLightbox = document.createElement('div');
        imageLightbox.className = 'image-lightbox';
        imageLightbox.innerHTML = `<img src="${imageUrl}" alt="Product Image">`;
        document.body.appendChild(imageLightbox);

        // Add click handler to close lightbox
        imageLightbox.addEventListener('click', function() {
            this.classList.remove('active');
        });
    } else {
        // Update existing lightbox image
        imageLightbox.querySelector('img').src = imageUrl;
    }

    // Show lightbox
    imageLightbox.classList.add('active');
}

// Function to update calendar content
function updateCalendar() {
    const formData = new FormData();
    
    // Get current filter from URL or default to 'all'
    const urlParams = new URLSearchParams(window.location.search);
    const filter = urlParams.get('filter') || 'all';
    
    // Get date range values
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;
    
    // Get month/year value from the month picker
    const monthYearInput = document.querySelector('input[name="month_year"]');
    
    // Add values to form data
    formData.append('filter', filter);
    if (startDate && endDate) {
        formData.append('start_date', startDate);
        formData.append('end_date', endDate);
        formData.append('type', 'range');
    } else if (monthYearInput && monthYearInput.value) {
        // Extract month and year from the month picker value (format: YYYY-MM)
        const [year, month] = monthYearInput.value.split('-');
        formData.append('month', parseInt(month));
        formData.append('year', parseInt(year));
        formData.append('type', 'month');
    }
    
    // Show loading state
    const calendarBody = document.querySelector('.calendar-table tbody');
    if (calendarBody) {
        calendarBody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center p-5">
                    <div class="d-flex flex-column align-items-center">
                        <div class="spinner-border text-danger mb-3" role="status"></div>
                        <p>Loading calendar data...</p>
                    </div>
                </td>
            </tr>`;
    }
    
    // Make AJAX request
    fetch('./assets/controllers/calendar/get_calendar_data.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update calendar title
            const titleElement = document.querySelector('.calendar-header h1');
            if (titleElement) {
                titleElement.textContent = data.title;
            }
            
            // Update calendar body
            if (calendarBody) {
                calendarBody.innerHTML = data.calendarHtml;
            }
            
            // Reattach event listeners to transaction detail links
            attachTransactionDetailListeners();
        } else {
            throw new Error(data.error || 'Failed to update calendar');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (calendarBody) {
            calendarBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center p-4">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            An error occurred while updating the calendar. Please try again.
                        </div>
                    </td>
                </tr>`;
        }
    });
}

// Function to attach event listeners to transaction detail links
function attachTransactionDetailListeners() {
    document.querySelectorAll('.transaction-detail').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const transactionId = this.dataset.transactionid;
            const type = this.dataset.type;
            
            // Show loading state in modal
            const modalContent = document.getElementById('transactionDetailContent');
            if (modalContent) {
                modalContent.innerHTML = `
                    <div class="text-center p-4">
                        <div class="spinner-border text-danger" role="status"></div>
                        <p class="mt-2">Loading transaction details...</p>
                    </div>`;
                
                // Show modal
                if (transactionDetailModal) {
                    transactionDetailModal.show();
                    
                    // Fetch transaction details
                    fetch(`./assets/controllers/calendar/calendar_transaction.php?transactionID=${transactionId}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                modalContent.innerHTML = data.html;
                                
                                // Add click handlers to product links
                                modalContent.querySelectorAll('.product-link').forEach(link => {
                                    link.addEventListener('click', function(e) {
                                        e.preventDefault();
                                        const productId = this.dataset.productid;
                                        showProductDetails(productId);
                                    });
                                });
                            } else {
                                modalContent.innerHTML = `
                                    <div class="alert alert-danger m-3">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-exclamation-circle me-3 fs-3"></i>
                                            <div>
                                                <h5 class="mb-1">Error</h5>
                                                <p class="mb-0">${data.error || 'Failed to load transaction details'}</p>
                                            </div>
                                        </div>
                                    </div>`;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            modalContent.innerHTML = `
                                <div class="alert alert-danger m-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-exclamation-circle me-3 fs-3"></i>
                                        <div>
                                            <h5 class="mb-1">Connection Error</h5>
                                            <p class="mb-0">An error occurred while loading transaction details. Please try again.</p>
                                        </div>
                                    </div>
                                </div>`;
                        });
                }
            }
        });
    });
}

// Function to handle view mode switching
function switchViewMode(mode) {
    // Update URL to reflect the current view mode
    const url = new URL(window.location.href);
    
    if (mode === 'range') {
        // Check if we have dates to use
        const startDate = document.querySelector('#range-view input[name="start_date"]').value;
        const endDate = document.querySelector('#range-view input[name="end_date"]').value;
        
        if (startDate && endDate) {
            url.searchParams.set('start_date', startDate);
            url.searchParams.set('end_date', endDate);
            // Clear month/year params if they exist
            url.searchParams.delete('month');
            url.searchParams.delete('year');
        }
    } else if (mode === 'month') {
        // Use month/year value from the month picker
        const monthYearInput = document.querySelector('#month-view input[name="month_year"]');
        if (monthYearInput && monthYearInput.value) {
            // Extract month and year from the month picker value (format: YYYY-MM)
            const [year, month] = monthYearInput.value.split('-');
            url.searchParams.set('month', parseInt(month));
            url.searchParams.set('year', parseInt(year));
            // Clear date range params if they exist
            url.searchParams.delete('start_date');
            url.searchParams.delete('end_date');
        }
    }
    
    // Update URL without page refresh
    window.history.pushState({}, '', url);
    
    // Update calendar
    updateCalendar();
}

// Add event listeners when document is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modals
    const transactionModalElement = document.getElementById('transactionDetailModal');
    const productModalElement = document.getElementById('productDetailModal');
    if (transactionModalElement) {
        transactionDetailModal = new bootstrap.Modal(transactionModalElement);
    }
    if (productModalElement) {
        productDetailModal = new bootstrap.Modal(productModalElement);
    }
    
    // Attach event listeners to filter buttons
    document.querySelectorAll('.btn-group .btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const filter = this.dataset.filter;
            
            // Update URL without page refresh
            const url = new URL(window.location.href);
            url.searchParams.set('filter', filter);
            window.history.pushState({}, '', url);
            
            // Update active button state in all filter groups
            document.querySelectorAll('.btn-group .btn').forEach(b => b.classList.remove('btn-custom'));
            document.querySelectorAll(`.btn-group .btn[data-filter="${filter}"]`).forEach(b => b.classList.add('btn-custom'));
            
            // Update calendar
            updateCalendar();
        });
    });
    
    // Attach event listeners to tab navigation
    document.querySelectorAll('.nav-pills .utility-nav-link').forEach(tab => {
        tab.addEventListener('click', function(e) {
            // Get the target view mode from the data-bs-target attribute
            const target = this.getAttribute('data-bs-target');
            const mode = target === '#range-view' ? 'range' : 'month';
            
            // Switch view mode
            switchViewMode(mode);
        });
    });
    
    // Attach event listener to month/year form
    const monthYearForm = document.getElementById('monthYearForm');
    if (monthYearForm) {
        monthYearForm.addEventListener('submit', function(e) {
            e.preventDefault();
            switchViewMode('month');
        });
    }
    
    // Attach event listener to date range form
    const dateRangeForm = document.getElementById('dateRangeForm');
    if (dateRangeForm) {
        dateRangeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            switchViewMode('range');
        });
    }
    
    // Attach event listeners to navigation buttons
    document.querySelectorAll('.d-flex.gap-2 .btn').forEach(btn => {
        if (btn.getAttribute('href')) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                const url = new URL(href, window.location.href);
                
                // Update URL without page refresh
                window.history.pushState({}, '', url);
                
                // Update calendar
                updateCalendar();
            });
        }
    });
    
    // Attach initial transaction detail listeners
    attachTransactionDetailListeners();
});

// Function to export transactions
function exportTransactions() {
    // Get current filter from URL or default to 'all'
    const urlParams = new URLSearchParams(window.location.search);
    const filter = urlParams.get('filter') || 'all';
    
    // Get date range values
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;
    
    // Get month/year value from the month picker
    const monthYearInput = document.querySelector('input[name="month_year"]');
    
    // Build export URL
    let exportUrl = './assets/controllers/calendar/export_transactions.php?filter=' + filter;
    
    if (startDate && endDate) {
        // Date range view
        exportUrl += '&start_date=' + startDate + '&end_date=' + endDate + '&type=range';
    } else if (monthYearInput && monthYearInput.value) {
        // Month view - Get month and year from the input
        const [year, month] = monthYearInput.value.split('-');
        const lastDay = new Date(year, month, 0).getDate(); // Get last day of month
        
        const startDateStr = `${year}-${month}-01`;
        const endDateStr = `${year}-${month}-${lastDay}`;
        
        exportUrl += '&start_date=' + startDateStr + '&end_date=' + endDateStr + '&type=month';
    }
    
    // Redirect to export URL
    window.location.href = exportUrl;
}

// Function to reset calendar to current month
function resetCalendar() {
    // Get current date
    const today = new Date();
    const currentMonth = today.getMonth() + 1; // JavaScript months are 0-11
    const currentYear = today.getFullYear();

    // Reset month picker input
    const monthYearInput = document.querySelector('input[name="month_year"]');
    if (monthYearInput) {
        // Format: YYYY-MM
        monthYearInput.value = `${currentYear}-${currentMonth.toString().padStart(2, '0')}`;
    }

    // Clear date range inputs
    const startDate = document.querySelector('input[name="start_date"]');
    const endDate = document.querySelector('input[name="end_date"]');
    if (startDate) startDate.value = '';
    if (endDate) endDate.value = '';

    // Reset filter buttons
    document.querySelectorAll('.btn-group .btn').forEach(btn => {
        btn.classList.remove('btn-custom');
        if (btn.dataset.filter === 'all') {
            btn.classList.add('btn-custom');
        }
    });

    // Update URL without page refresh
    const url = new URL(window.location.href);
    url.searchParams.delete('start_date');
    url.searchParams.delete('end_date');
    url.searchParams.set('month', currentMonth);
    url.searchParams.set('year', currentYear);
    url.searchParams.set('filter', 'all');
    window.history.pushState({}, '', url);

    // Update calendar
    updateCalendar();
}

// Function to navigate calendar
function navigateCalendar(direction) {
    // Get the current month-year value
    const monthYearInput = document.querySelector('input[name="month_year"]');
    if (!monthYearInput || !monthYearInput.value) return;
    
    // Parse the current value (format: YYYY-MM)
    const [currentYear, currentMonth] = monthYearInput.value.split('-').map(Number);
    
    // Calculate the new month and year
    let newMonth = currentMonth;
    let newYear = currentYear;
    
    if (direction === 'prev') {
        newMonth--;
        if (newMonth < 1) {
            newMonth = 12;
            newYear--;
        }
    } else {
        newMonth++;
        if (newMonth > 12) {
            newMonth = 1;
            newYear++;
        }
    }
    
    // Update the month picker with the new value
    monthYearInput.value = `${newYear}-${newMonth.toString().padStart(2, '0')}`;
    
    // Update calendar
    updateCalendar();
}
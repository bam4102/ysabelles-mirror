/**
 * TransactionFilters module manages the filtering logic for transactions
 */
const TransactionFilters = (function() {
    // Private variables
    let canModifyLocation = false;
    
    // Private methods
    function saveFiltersToLocalStorage() {
        const pickupDateStart = $('#pickupDateStart').val();
        const pickupDateEnd = $('#pickupDateEnd').val();
        const returnDateStart = $('#returnDateStart').val();
        const returnDateEnd = $('#returnDateEnd').val();
        const location = $('#locationFilter').val();
        const status = $('#statusFilter').val();
        
        localStorage.setItem('pickupDateStart', pickupDateStart);
        localStorage.setItem('pickupDateEnd', pickupDateEnd);
        localStorage.setItem('returnDateStart', returnDateStart);
        localStorage.setItem('returnDateEnd', returnDateEnd);
        localStorage.setItem('locationFilter', location);
        localStorage.setItem('statusFilter', status);
    }
    
    function createFilterFunction() {
        const pickupDateStart = $('#pickupDateStart').val();
        const pickupDateEnd = $('#pickupDateEnd').val();
        const returnDateStart = $('#returnDateStart').val();
        const returnDateEnd = $('#returnDateEnd').val();
        const location = $('#locationFilter').val();
        const status = $('#statusFilter').val();
        
        return function(settings, data, dataIndex) {
            // Get pickup and return dates from the table row data
            // Column indices: Pick-up date is column 4, Return date is column 5, Location is column 2
            const rowPickupDate = new Date(data[4]);
            const rowReturnDate = new Date(data[5]);
            const rowLocation = data[2];
            
            // Get the row's bond status
            const $row = $(settings.aoData[dataIndex].nTr);
            const rowStatus = $row.attr('data-bond-status');
            
            let matchesPickupFilter = true;
            let matchesReturnFilter = true;
            let matchesLocationFilter = true;
            let matchesStatusFilter = true;
            
            // Check pickup date range
            if (pickupDateStart && pickupDateEnd) {
                const startDate = new Date(pickupDateStart);
                const endDate = new Date(pickupDateEnd);
                endDate.setHours(23, 59, 59); // Set to end of day for the end date
                matchesPickupFilter = rowPickupDate >= startDate && rowPickupDate <= endDate;
            } else if (pickupDateStart) {
                const startDate = new Date(pickupDateStart);
                matchesPickupFilter = rowPickupDate >= startDate;
            } else if (pickupDateEnd) {
                const endDate = new Date(pickupDateEnd);
                endDate.setHours(23, 59, 59);
                matchesPickupFilter = rowPickupDate <= endDate;
            }
            
            // Check return date range
            if (returnDateStart && returnDateEnd) {
                const startDate = new Date(returnDateStart);
                const endDate = new Date(returnDateEnd);
                endDate.setHours(23, 59, 59);
                matchesReturnFilter = rowReturnDate >= startDate && rowReturnDate <= endDate;
            } else if (returnDateStart) {
                const startDate = new Date(returnDateStart);
                matchesReturnFilter = rowReturnDate >= startDate;
            } else if (returnDateEnd) {
                const endDate = new Date(returnDateEnd);
                endDate.setHours(23, 59, 59);
                matchesReturnFilter = rowReturnDate <= endDate;
            }
            
            // Check location
            if (location) {
                matchesLocationFilter = rowLocation === location;
            }
            
            // Check status
            if (status) {
                // If a specific status is selected, only show rows with that status
                matchesStatusFilter = (rowStatus === status);
            } else {
                // If "All Statuses" is selected (empty value), hide status 3 (inactive) transactions
                matchesStatusFilter = (rowStatus !== '3');
            }
            
            // Return true if all active filters match
            return matchesPickupFilter && matchesReturnFilter && matchesLocationFilter && matchesStatusFilter;
        };
    }
    
    // Public methods
    return {
        /**
         * Initialize the filters module
         */
        init: function() {
            // Check if user can modify location
            canModifyLocation = !$('#locationFilter').prop('disabled');
            
            // Initialize date filters with values from localStorage or empty
            $('#pickupDateStart').val(localStorage.getItem('pickupDateStart') || '');
            $('#pickupDateEnd').val(localStorage.getItem('pickupDateEnd') || '');
            $('#returnDateStart').val(localStorage.getItem('returnDateStart') || '');
            $('#returnDateEnd').val(localStorage.getItem('returnDateEnd') || '');
            $('#statusFilter').val(localStorage.getItem('statusFilter') || '');
            
            // For location filter, if user can modify it, use localStorage
            // Otherwise, the default selected value will be used and should be kept
            if (canModifyLocation) {
                $('#locationFilter').val(localStorage.getItem('locationFilter') || '');
            } else {
                // For non-SUPERADMIN users, save their fixed location to localStorage
                localStorage.setItem('locationFilter', $('#locationFilter').val());
            }
            
            // Apply filters from localStorage on page load if they exist
            if (localStorage.getItem('filtersActive') === 'true') {
                this.applyFilters();
            }
            
            // Set up event handlers
            this.setupEventHandlers();
        },
        
        /**
         * Set up event handlers for filter buttons
         */
        setupEventHandlers: function() {
            // Apply filters button click
            $('#applyFilters').click(() => {
                this.applyFilters();
                localStorage.setItem('filtersActive', 'true');
            });
            
            // Reset filters button click
            $('#resetFilters').click(() => {
                $('#transactionFilters')[0].reset();
                localStorage.removeItem('pickupDateStart');
                localStorage.removeItem('pickupDateEnd');
                localStorage.removeItem('returnDateStart');
                localStorage.removeItem('returnDateEnd');
                localStorage.removeItem('statusFilter');
                
                // Only clear location filter if user can modify it
                if (canModifyLocation) {
                    localStorage.removeItem('locationFilter');
                    $('#locationFilter').val('');
                } else {
                    // For restricted users, restore their location
                    $('#locationFilter').val($('#locationFilter option:first').val());
                }
                
                localStorage.setItem('filtersActive', 'false');
                
                // Clear all filters and redraw the table
                TransactionTable.getTable().search('').columns().search('').draw();
            });
        },
        
        /**
         * Apply filters to the transactions table
         */
        applyFilters: function() {
            saveFiltersToLocalStorage();
            TransactionTable.applyFilter(createFilterFunction());
        }
    };
})(); 
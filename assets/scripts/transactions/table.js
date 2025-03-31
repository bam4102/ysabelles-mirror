/**
 * TransactionTable module handles DataTable initialization and filtering logic
 */
const TransactionTable = (function() {
    // Private variables
    let transactionsTable;
    
    // Public methods
    return {
        /**
         * Initialize the transactions DataTable
         */
        init: function() {
            // Initialize DataTable
            transactionsTable = $('#transactionsTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                responsive: true
            });
        },
        
        /**
         * Get reference to the DataTable instance
         */
        getTable: function() {
            return transactionsTable;
        },
        
        /**
         * Apply custom filter function to the DataTable
         * @param {Function} filterFunction - Custom filtering function
         */
        applyFilter: function(filterFunction) {
            $.fn.dataTable.ext.search.push(filterFunction);
            transactionsTable.draw();
            $.fn.dataTable.ext.search.pop();
        }
    };
})(); 
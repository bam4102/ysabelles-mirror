/**
 * TransactionUtils module contains common utility functions for transactions
 */
const TransactionUtils = (function() {
    // Public methods
    return {
        /**
         * Format currency values with proper symbolization
         * @param {number} amount - The amount to format
         * @returns {string} Formatted currency string
         */
        formatCurrency: function(amount) {
            return '₱ ' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        },
        
        /**
         * Format date time for display
         * @param {string} datetime - Date time string
         * @returns {string} Formatted date time string
         */
        formatDateTime: function(datetime) {
            return datetime ? new Date(datetime).toLocaleString() : 'N/A';
        },
        
        /**
         * Format date for display
         * @param {string} date - Date string
         * @returns {string} Formatted date string
         */
        formatDate: function(date) {
            return new Date(date).toLocaleDateString();
        },
        
        /**
         * Format date for HTML input element
         * @param {string} dateString - Date string
         * @returns {string} Formatted date in YYYY-MM-DD format
         */
        formatDateForInput: function(dateString) {
            const date = new Date(dateString);
            return date.toISOString().split('T')[0];
        },
        
        /**
         * Get text representation of package type
         * @param {number} packageId - Package ID
         * @returns {string} Package text representation
         */
        getPackageText: function(packageId) {
            switch (packageId) {
                case 1: return 'Package A';
                case 2: return 'Package B';
                default: return 'None';
            }
        }
    };
})(); 
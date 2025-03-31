/**
 * Main entry point for transactions module
 * Loads all required modules in the correct order
 * 
 * Include files in your HTML in this order:
 * 1. utils.js
 * 2. table.js
 * 3. filters.js
 * 4. details.js
 * 5. payment-handler.js
 * 6. bond-handler.js
 * 7. editor.js
 * 8. special-actions.js
 * 9. global-handlers.js
 * 10. index.js
 */
$(document).ready(function() {
    // Initialize all modules in correct order
    TransactionTable.init();
    TransactionFilters.init();
    TransactionDetails.init();
    PaymentHandler.init();
    BondHandler.init();
    TransactionEditor.init();
    SpecialActions.init();
    
    // Log initialization
    console.log('Transaction modules initialized');
}); 
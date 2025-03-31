/**
 * Special Actions Module for Transactions
 * Handles special bulk operations on transactions
 */
const SpecialActions = (function() {
    /**
     * Updates transactions without payments to inactive status
     * Changes bondStatus to 3 for transactions with no payment records
     */
    function updatePastTransactions() {
        if (!confirm('Are you sure you want to mark all transactions with no payment records as inactive (Status 3)?')) {
            return;
        }
        
        $.ajax({
            url: './assets/controllers/transactions2/update_past_transactions.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(`Success! ${response.count} transactions have been updated.`);
                    // Reload the page to show updated statuses
                    location.reload();
                } else {
                    alert('Error: ' + response.error);
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'An error occurred.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.error || errorMessage;
                } catch (e) {
                    errorMessage = `${error}: ${xhr.responseText}`;
                }
                alert('Error: ' + errorMessage);
            }
        });
    }
    
    /**
     * Initialize event listeners for special action buttons
     */
    function init() {
        $('#updatePastTransactions').on('click', updatePastTransactions);
        
        console.log('Special Actions module initialized');
    }
    
    // Public API
    return {
        init: init,
        updatePastTransactions: updatePastTransactions
    };
})(); 
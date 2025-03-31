/**
 * BondHandler module handles bond deposit and release functionality
 */
const BondHandler = (function() {
    // Public methods
    return {
        /**
         * Initialize the bond handler module
         */
        init: function() {
            // Add event listener for bond release button
            const submitBondReleaseBtn = document.getElementById('submitBondRelease');
            if (submitBondReleaseBtn) {
                submitBondReleaseBtn.addEventListener('click', this.submitBondRelease);
            }
        },
        
        /**
         * Display bond deposit modal
         * @param {number} transactionId - Transaction ID
         * @param {number} requiredBond - Required bond amount
         * @param {number} paymentId - Payment ID (optional)
         */
        showBondModal: function(transactionId, requiredBond, paymentId) {
            // Set today's date as default
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('bondDate').value = today;
            
            // Set transaction ID and bond amount
            document.getElementById('bondTransactionId').value = transactionId;
            document.getElementById('bondPaymentId').value = paymentId || '';
            document.getElementById('requiredBond').textContent = '₱ ' + parseFloat(requiredBond).toFixed(2);
            
            // Set and configure bond amount input
            const bondAmount = document.getElementById('bondAmount');
            bondAmount.value = requiredBond; // Pre-populate with required bond
            bondAmount.max = requiredBond;
            bondAmount.min = requiredBond; // Force exact amount
            
            // Clear note field
            document.getElementById('bondNote').value = '';
            
            // Show modal
            new bootstrap.Modal(document.getElementById('bondModal')).show();
        },
        
        /**
         * Process bond deposit form submission
         */
        submitBond: function() {
            const form = document.getElementById('bondForm');
            const formData = new FormData(form);
            
            const bondData = {
                transactionId: formData.get('transactionId'),
                date: formData.get('date'),
                amount: formData.get('amount'),
                note: formData.get('note')
            };
            
            // Close bond modal
            bootstrap.Modal.getInstance(document.getElementById('bondModal')).hide();
            
            // Submit payment and bond data
            PaymentHandler.submitPaymentAndBond(bondData);
        },
        
        /**
         * Display bond release modal
         * @param {number} transactionId - Transaction ID
         * @param {number} bondBalance - Current bond balance
         */
        showBondReleaseModal: function(transactionId, bondBalance) {
            // Set today's date as default
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('bondReleaseDate').value = today;
            
            // Set transaction ID and current bond balance
            document.getElementById('bondReleaseTransactionId').value = transactionId;
            
            // Format bond balance for display
            const formattedBondBalance = '₱ ' + parseFloat(bondBalance).toFixed(2);
            document.getElementById('bondReleaseBalance').value = bondBalance;
            document.getElementById('bondReleaseBalanceDisplay').textContent = formattedBondBalance;
            
            const amountInput = document.getElementById('bondReleaseAmount');
            amountInput.max = bondBalance;
            amountInput.min = 0;
            
            // Clear other fields
            amountInput.value = '';
            document.getElementById('bondReleaseNote').value = '';
            
            // Add event listeners for amount input validation
            amountInput.addEventListener('input', function(e) {
                if (parseFloat(e.target.value) > bondBalance) {
                    e.target.value = bondBalance;
                    document.getElementById('bondReleaseAmountError').textContent = 'Amount cannot exceed bond balance';
                    document.getElementById('bondReleaseAmountError').style.display = 'block';
                } else {
                    document.getElementById('bondReleaseAmountError').style.display = 'none';
                }
                
                // Prevent negative numbers
                if (parseFloat(e.target.value) < 0) {
                    e.target.value = 0;
                }
            });
            
            // Show the modal
            new bootstrap.Modal(document.getElementById('bondReleaseModal')).show();
        },
        
        /**
         * Process bond release form submission
         */
        submitBondRelease: function() {
            const form = document.getElementById('bondReleaseForm');
            
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const formData = new FormData(form);
            
            // Get the values for validation
            const transactionId = document.getElementById('bondReleaseTransactionId').value;
            const date = document.getElementById('bondReleaseDate').value;
            const amount = document.getElementById('bondReleaseAmount').value;
            
            // Validate required fields
            if (!transactionId || !date || !amount) {
                alert('Please fill all required fields.');
                return;
            }
            
            const bondBalance = parseFloat(document.getElementById('bondReleaseBalance').value);
            if (parseFloat(amount) > bondBalance) {
                document.getElementById('bondReleaseAmountError').textContent = 'Amount cannot exceed bond balance';
                document.getElementById('bondReleaseAmountError').style.display = 'block';
                return;
            }
            
            // Submit the form
            fetch('assets/controllers/transactions2/process_bond_release.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Format the released amount for display
                    const formattedAmount = '₱' + parseFloat(amount).toFixed(2);
                    
                    // Show success alert with the released amount
                    alert(`Bond release of ${formattedAmount} processed successfully!`);
                    
                    // Hide the modal
                    bootstrap.Modal.getInstance(document.getElementById('bondReleaseModal')).hide();
                    
                    // Reload the page to refresh the data
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your request.');
            });
        }
    };
})();
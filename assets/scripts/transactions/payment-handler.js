/**
 * PaymentHandler module handles payment functionality for transactions
 */
const PaymentHandler = (function() {
    // Private variables
    let tempPaymentData = null;
    
    // Public methods
    return {
        /**
         * Initialize the payment handler module
         */
        init: function() {
            // No initialization needed for this module
        },
        
        /**
         * Display payment modal for a transaction
         * @param {number} transactionId - Transaction ID
         * @param {number} balance - Remaining balance
         */
        showPaymentModal: function(transactionId, balance) {
            // Set today's date as default
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('paymentDate').value = today;
            
            // Set transaction ID and remaining balance
            document.getElementById('paymentTransactionId').value = transactionId;
            const formattedBalance = '₱ ' + parseFloat(balance).toFixed(2);
            document.getElementById('remainingBalance').textContent = formattedBalance;
            
            const amountInput = document.getElementById('paymentAmount');
            amountInput.max = balance;
            amountInput.min = 0;
            
            // Clear other fields
            amountInput.value = '';
            document.getElementById('paymentNote').value = '';
            
            // Add event listeners for amount input validation
            amountInput.addEventListener('input', function(e) {
                const amount = parseFloat(e.target.value) || 0;
                if (amount > balance) {
                    e.target.value = balance;
                }
                if (amount < 0) {
                    e.target.value = 0;
                }
                const newBalance = Math.max(0, balance - parseFloat(e.target.value));
                document.getElementById('remainingBalance').textContent = '₱ ' + newBalance.toFixed(2);
            });
            
            // Prevent typing negative numbers
            amountInput.addEventListener('keydown', function(e) {
                if (e.key === '-' || e.key === 'e') {
                    e.preventDefault();
                }
            });
            
            // Show modal
            new bootstrap.Modal(document.getElementById('paymentModal')).show();
        },
        
        /**
         * Process payment form submission
         */
        submitPayment: function() {
            const form = document.getElementById('paymentForm');
            const formData = new FormData(form);
            
            // Basic validation
            const amount = parseFloat(formData.get('amount'));
            const balance = parseFloat(document.getElementById('paymentAmount').max);
            
            if (amount <= 0) {
                alert('Amount must be greater than 0');
                return;
            }
            
            if (amount > balance) {
                alert('Amount cannot exceed the remaining balance');
                return;
            }
            
            // Store payment data for both partial and full payments
            tempPaymentData = {
                transactionId: formData.get('transactionId'),
                date: formData.get('date'),
                mode: formData.get('mode'),
                amount: formData.get('amount'),
                note: formData.get('note')
            };
            
            // Close payment modal
            bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
            
            // If this is a full payment, check for bond requirement
            if (amount === balance) {
                fetch(`assets/controllers/transactions2/get_bond_requirement.php?id=${formData.get('transactionId')}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.bondRequired > 0) {
                            BondHandler.showBondModal(formData.get('transactionId'), data.bondRequired);
                        } else {
                            this.submitPaymentAndBond(null);
                        }
                    })
                    .catch(error => {
                        alert('Error checking bond requirement: ' + error.message);
                    });
            } else {
                // For partial payments, submit directly
                this.submitPaymentAndBond(null);
            }
        },
        
        /**
         * Submit payment and bond data to the server
         * @param {Object} bondData - Bond data (optional)
         */
        submitPaymentAndBond: function(bondData) {
            if (!tempPaymentData) {
                alert('No payment data found');
                return;
            }
            
            const submitData = {
                payment: tempPaymentData,
                bond: bondData
            };
            
            fetch('assets/controllers/transactions2/process_payment_and_bond.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(submitData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                // Format the payment amount
                const formattedAmount = '₱' + parseFloat(tempPaymentData.amount).toFixed(2);
                
                // Show success alert with payment amount
                if (bondData) {
                    // If both payment and bond deposit
                    const formattedBondAmount = '₱' + parseFloat(bondData.amount).toFixed(2);
                    alert(`Payment of ${formattedAmount} and bond deposit of ${formattedBondAmount} processed successfully!`);
                } else {
                    // If payment only
                    alert(`Payment of ${formattedAmount} processed successfully!`);
                }
                
                // Show payment receipt
                if (data.paymentId) {
                    window.open(`assets/controllers/transactions2/print_receipt.php?type=payment&id=${data.paymentId}`, '_blank');
                }
                
                // Show bond receipt if bond was submitted
                if (data.bondId) {
                    window.open(`assets/controllers/transactions2/print_bond_receipt.php?id=${data.bondId}`, '_blank');
                }
                
                // Clear temporary data
                tempPaymentData = null;
                
                // Refresh page
                location.reload();
            })
            .catch(error => {
                alert('Error processing payment: ' + error.message);
                tempPaymentData = null;
            });
        }
    };
})();
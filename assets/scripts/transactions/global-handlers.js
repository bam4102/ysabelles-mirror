/**
 * This file exposes transaction functions globally for use in HTML event handlers
 */

// Transaction details function
function showTransactionDetails(data) {
    TransactionDetails.showTransactionDetails(data);
}

// Transaction history function
function showHistory(transactionId) {
    TransactionDetails.showHistory(transactionId);
}

// Payment related functions
function showPaymentModal(transactionId, balance) {
    PaymentHandler.showPaymentModal(transactionId, balance);
}

function submitPayment() {
    PaymentHandler.submitPayment();
}

// Bond related functions
function showBondModal(transactionId, requiredBond, paymentId) {
    BondHandler.showBondModal(transactionId, requiredBond, paymentId);
}

function submitBond() {
    BondHandler.submitBond();
}

function showBondReleaseModal(transactionId, bondBalance) {
    BondHandler.showBondReleaseModal(transactionId, bondBalance);
}

// Transaction editing functions
function showEditModal(transactionId) {
    TransactionEditor.showEditModal(transactionId);
}

function updateTransaction() {
    TransactionEditor.updateTransaction();
}

// Special action functions
function updatePastTransactions() {
    SpecialActions.updatePastTransactions();
} 
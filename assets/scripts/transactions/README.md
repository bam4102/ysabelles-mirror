# Transaction Modules

This directory contains modular JavaScript files for handling transaction functionality.

## Structure

- `index.js` - Main entry point, initializes all modules
- `utils.js` - Common utility functions 
- `table.js` - DataTable initialization and management
- `filters.js` - Filtering functionality for transactions
- `details.js` - Transaction details and history viewing
- `payment-handler.js` - Payment processing functionality
- `bond-handler.js` - Bond deposit and release functionality
- `editor.js` - Transaction editing functionality
- `global-handlers.js` - Globally exposed functions for HTML event binding

## Implementation Details

Each module follows the revealing module pattern for proper encapsulation and scoping.
The modules expose a public API through a namespace object.

## Usage

Include the JavaScript files in the following order:

```html
<!-- Third-party dependencies -->
<script src="path/to/jquery.min.js"></script>
<script src="path/to/bootstrap.bundle.min.js"></script>
<script src="path/to/jquery.dataTables.min.js"></script>

<!-- Transaction modules -->
<script src="assets/scripts/transactions/utils.js"></script>
<script src="assets/scripts/transactions/table.js"></script>
<script src="assets/scripts/transactions/filters.js"></script>
<script src="assets/scripts/transactions/details.js"></script>
<script src="assets/scripts/transactions/payment-handler.js"></script>
<script src="assets/scripts/transactions/bond-handler.js"></script>
<script src="assets/scripts/transactions/editor.js"></script>
<script src="assets/scripts/transactions/global-handlers.js"></script>
<script src="assets/scripts/transactions/index.js"></script>
```

## Global Functions

The following functions are exposed globally for direct use in HTML:

- `showTransactionDetails(data)` - Show transaction details
- `showHistory(transactionId)` - Show transaction history
- `showPaymentModal(transactionId, balance)` - Display payment modal
- `submitPayment()` - Process payment submission
- `showBondModal(transactionId, requiredBond, paymentId)` - Display bond modal
- `submitBond()` - Process bond submission
- `showBondReleaseModal(transactionId, bondBalance)` - Display bond release modal
- `showEditModal(transactionId)` - Display transaction edit modal
- `updateTransaction()` - Process transaction update 
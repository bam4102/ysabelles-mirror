<?php
session_start();
include '../db.php';

// Add CSRF validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token validation failed');
}

// Add input validation
function validate_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Ensure the session cart is not empty.
    if (empty($_SESSION['cart'])) {
        echo "Cart is empty.";
        exit;
    }

    try {
        // Validate all inputs
        $locationTransaction = validate_input($_POST['locationTransaction'] ?? '');
        $agent = validate_input($_POST['agent'] ?? ''); // Sales Agent
        $clientName = validate_input($_POST['clientName'] ?? '');
        $clientAddress = validate_input($_POST['clientAddress'] ?? '');
        $clientContact = validate_input($_POST['clientContact'] ?? '');
        $datePickUp = validate_input($_POST['datePickUp'] ?? '');
        $dateReturn = validate_input($_POST['dateReturn'] ?? '');

        // New fields from the form.
        $depositTransaction = isset($_POST['depositTransaction']) ? floatval($_POST['depositTransaction']) : 0;
        $bondTransaction = isset($_POST['bondTransaction']) ? floatval($_POST['bondTransaction']) : 0;
        $discountTransaction = isset($_POST['discountTransaction']) ? floatval($_POST['discountTransaction']) : 0;
        $bondStatus = isset($_POST['bondStatus']) ? 1 : 0;
        $modeTransaction = ''; // Set as needed.

        // Set timezone to UTC+8 (Philippines)
        date_default_timezone_set('Asia/Manila');

        // Use today's date for the transaction date with UTC+8 timezone
        $dateTransaction = date('Y-m-d');
        $currentTimestamp = date('Y-m-d H:i:s');

        // Decode package selections JSON from the hidden field.
        $packageSelections = isset($_POST['packageSelections']) ? json_decode($_POST['packageSelections'], true) : [];

        // Decode price changes JSON from the hidden field
        $priceChanges = isset($_POST['priceChanges']) ? json_decode($_POST['priceChanges'], true) : [];

        // Initialize totals.
        $totalNormal = 0;
        $pkgA_count = 0;
        $pkgB_count = 0;

        // Loop over each item in the cart.
        foreach ($_SESSION['cart'] as $index => $item) {
            // Check if this is a "to buy" item
            $toBuy = isset($item['toBuy']) && $item['toBuy'];

            // Get the modified price if available
            $price = $item['priceProduct'];
            $priceSold = null;
            if ($toBuy && isset($priceChanges[$index])) {
                $priceSold = floatval($priceChanges[$index]);
                $price = $priceSold; // Use this price for total calculation
            }

            // Determine package for this item (1 = Package A, 2 = Package B, 0 = none).
            $pkg = isset($packageSelections[$index]) ? intval($packageSelections[$index]) : 0;

            if ($toBuy) {
                // Add to total directly for "to buy" items
                $totalNormal += $price;
            } else if ($pkg === 1) {
                $pkgA_count++;
            } else if ($pkg === 2) {
                $pkgB_count++;
            } else {
                $totalNormal += $price;
            }
        }

        // For Package A items, add fixed cost 5800 if one or more exist.
        if ($pkgA_count > 0) {
            $totalNormal += 5800;
        }
        // For Package B items, add fixed cost 12800 if one or more exist.
        if ($pkgB_count > 0) {
            $totalNormal += 12800;
        }

        $chargeTransaction = $totalNormal;
        $balanceTransaction = $chargeTransaction - $discountTransaction;
        $bondBalance = 0;

        // Get importedTransactionID (if any)
        $importedTransactionID = $_POST['importedTransactionID'] ?? '';

        $pdo->beginTransaction();

        if (!empty($importedTransactionID)) {
            // UPDATE existing transaction record.
            $transactionID = $importedTransactionID;
            $sqlTransaction = "UPDATE transaction SET 
                employeeID = ?,
                dateTransaction = ?,
                locationTransaction = ?,
                clientName = ?,
                clientAddress = ?,
                clientContact = ?,
                datePickUp = ?,
                dateReturn = ?,
                chargeTransaction = ?,
                depositTransaction = ?,
                balanceTransaction = ?,
                bondTransaction = ?,
                bondStatus = ?,
                modeTransaction = ?,
                discountTransaction = ?,
                bondBalance = ?
                WHERE transactionID = ?";
            $stmtTrans = $pdo->prepare($sqlTransaction);
            if (!$stmtTrans->execute([
                $agent,
                $dateTransaction,
                $locationTransaction,
                $clientName,
                $clientAddress,
                $clientContact,
                $datePickUp,
                $dateReturn,
                $chargeTransaction,
                $depositTransaction,
                $balanceTransaction,
                $bondTransaction,
                $bondStatus,
                $modeTransaction,
                $discountTransaction,
                $bondBalance,
                $transactionID
            ])) {
                throw new Exception("Error updating transaction: " . implode(" ", $stmtTrans->errorInfo()));
            }

            // Remove all existing purchases for this transaction.
            $stmtDelete = $pdo->prepare("DELETE FROM purchase WHERE transactionID = ?");
            $stmtDelete->execute([$transactionID]);
        } else {
            // INSERT new transaction without purchaseID (will be updated later)
            $sqlTransaction = "INSERT INTO transaction 
                (employeeID, dateTransaction, locationTransaction, 
                clientName, clientAddress, clientContact, datePickUp, dateReturn, 
                chargeTransaction, depositTransaction, balanceTransaction, bondTransaction, 
                bondStatus, modeTransaction, discountTransaction, bondBalance)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtTrans = $pdo->prepare($sqlTransaction);
            if (!$stmtTrans->execute([
                $agent,
                $dateTransaction,
                $locationTransaction,
                $clientName,
                $clientAddress,
                $clientContact,
                $datePickUp,
                $dateReturn,
                $chargeTransaction,
                $depositTransaction,
                $balanceTransaction,
                $bondTransaction,
                $bondStatus,
                $modeTransaction,
                $discountTransaction,
                $bondBalance
            ])) {
                throw new Exception("Error inserting transaction: " . implode(" ", $stmtTrans->errorInfo()));
            }
            $transactionID = $pdo->lastInsertId();
        }

        // Prepare statements for inserting purchase records and updating product counters.
        $sqlPurchase = "INSERT INTO purchase (productID, transactionID, dateCreated, soldPProduct, packagePurchase) VALUES (?, ?, ?, ?, ?)";
        $stmtPurchase = $pdo->prepare($sqlPurchase);
        $sqlUpdateCounter = "UPDATE product SET counterProduct = CAST(IFNULL(counterProduct, '0') AS UNSIGNED) + 0 WHERE productID = ?";
        $stmtUpdateCounter = $pdo->prepare($sqlUpdateCounter);

        foreach ($_SESSION['cart'] as $index => $cartItem) {
            $packagePurchase = isset($packageSelections[$index]) ? intval($packageSelections[$index]) : 0;
            $toBuy = isset($cartItem['toBuy']) && $cartItem['toBuy'];

            // Get the modified price if available
            $priceSold = null;
            if ($toBuy && isset($priceChanges[$index])) {
                $priceSold = floatval($priceChanges[$index]);
            }

            if (strpos($cartItem['productID'], "new_") === 0) {
                // For new products, insert them into the product table.
                $sqlCat = "SELECT categoryCode, genderCategory FROM productcategory WHERE categoryID = ?";
                $stmtCat = $pdo->prepare($sqlCat);
                $stmtCat->execute([$cartItem['categoryID']]);
                $catRow = $stmtCat->fetch(PDO::FETCH_ASSOC);
                $categoryCode = $catRow ? $catRow['categoryCode'] : '';
                $genderCategory = $catRow ? $catRow['genderCategory'] : '';

                // Add priceSold if this is a "to buy" product
                if ($toBuy && $priceSold !== null) {
                    $sqlInsertProduct = "INSERT INTO product (categoryID, nameProduct, priceProduct, priceSold, typeProduct, genderProduct, isNew) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmtInsertProduct = $pdo->prepare($sqlInsertProduct);
                    $stmtInsertProduct->execute([
                        $cartItem['categoryID'],
                        $cartItem['nameProduct'],
                        $cartItem['priceProduct'],
                        $priceSold,
                        $categoryCode,
                        $genderCategory,
                        1  // Set isNew to 1 for new products
                    ]);
                } else {
                    // Original code without priceSold
                    $sqlInsertProduct = "INSERT INTO product (categoryID, nameProduct, priceProduct, typeProduct, genderProduct, isNew) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmtInsertProduct = $pdo->prepare($sqlInsertProduct);
                    $stmtInsertProduct->execute([
                        $cartItem['categoryID'],
                        $cartItem['nameProduct'],
                        $cartItem['priceProduct'],
                        $categoryCode,
                        $genderCategory,
                        1  // Set isNew to 1 for new products
                    ]);
                }
                $newProductID = $pdo->lastInsertId();
                $stmtPurchase->execute([
                    $newProductID,
                    $transactionID,
                    $currentTimestamp,
                    $toBuy ? 1 : null, // Set soldPProduct to 1 for "to buy" products
                    $packagePurchase
                ]);
                $purchaseID = $pdo->lastInsertId();
            } else {
                // For existing products, update priceSold if it's a "to buy" product
                if ($toBuy && $priceSold !== null) {
                    $sqlUpdateProduct = "UPDATE product SET priceSold = ? WHERE productID = ?";
                    $stmtUpdateProduct = $pdo->prepare($sqlUpdateProduct);
                    $stmtUpdateProduct->execute([$priceSold, $cartItem['productID']]);
                }

                // Insert purchase record with soldPProduct flag
                $stmtPurchase->execute([
                    $cartItem['productID'],
                    $transactionID,
                    $currentTimestamp,
                    $toBuy ? 1 : null, // Set soldPProduct to 1 for "to buy" products
                    $packagePurchase
                ]);
                $purchaseID = $pdo->lastInsertId();
            }

            // Update transaction with the first purchaseID (if not already set)
            if ($index === 0) {
                $sqlUpdateTransaction = "UPDATE transaction SET purchaseID = ? WHERE transactionID = ?";
                $stmtUpdateTransaction = $pdo->prepare($sqlUpdateTransaction);
                $stmtUpdateTransaction->execute([$purchaseID, $transactionID]);
            }
        }

        $pdo->commit();

        // Clear the session cart.
        $_SESSION['cart'] = array();

        echo "Transaction successfully processed. Transaction ID: " . $transactionID;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Checkout Error: " . $e->getMessage());
        echo "An error occurred: " . $e->getMessage();
        exit;
    }
} else {
    echo "Invalid request.";
}

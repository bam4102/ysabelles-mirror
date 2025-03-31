<?php
session_start();
include 'cart_functions.php';

$cartItems = getCartItems($pdo);

if (!empty($cartItems)):
    foreach ($cartItems as $index => $item):
        // Use the key as the index if not already defined in the item.
        $itemIndex = isset($item['index']) ? $item['index'] : $index;
        $productID = htmlspecialchars($item['productID']);
        $nameProduct = htmlspecialchars($item['nameProduct']);
        $productCategory = isset($item['productCategory']) ? htmlspecialchars($item['productCategory']) : "N/A";
        $priceProduct = htmlspecialchars($item['priceProduct']);
        // Determine package selection: 0 = none, 1 = Package A, 2 = Package B.
        $pkgSelection = isset($item['packagePurchase']) ? (int)$item['packagePurchase'] : 0;
        // Check if product is flagged to buy
        $toBuy = isset($item['toBuy']) && $item['toBuy'] ? true : false;
        // For "to buy" products, use priceSold if set, otherwise use priceProduct
        $priceSold = isset($item['priceSold']) && $item['priceSold'] ? $item['priceSold'] : $priceProduct;
        
        // Check if this is a new product (starts with "new_")
        $isNewProduct = isset($productID) && is_string($productID) && substr($productID, 0, 4) === "new_";
?>
        <tr data-index="<?= $itemIndex; ?>" data-price="<?= $toBuy ? $priceSold : $priceProduct; ?>" class="align-middle">
            <td class="product-id"><?= $productID; ?></td>
            <td class="product-name">
                <?= $nameProduct; ?>
                <?php if ($toBuy): ?>
                    <span class="badge bg-success">To Buy</span>
                <?php endif; ?>
            </td>
            <td class="product-category"><span class="badge bg-info"><?= $productCategory; ?></span></td>
            <td class="product-price">
                <?php if ($toBuy): ?>
                    <input type="number" class="form-control price-sold-input" 
                        data-index="<?= $itemIndex; ?>" 
                        value="<?= $priceSold; ?>" 
                        min="0" step="0.01">
                <?php else: ?>
                    ₱<?= number_format($priceProduct, 2); ?>
                <?php endif; ?>
            </td>
            <!-- Package A Checkbox -->
            <td class="package-checkbox">
                <?php if ($isNewProduct || $toBuy): ?>
                    <span class="text-muted">-</span>
                <?php else: ?>
                    <div class="form-check d-flex justify-content-center">
                        <input type="checkbox" class="form-check-input pkgA" data-index="<?= $itemIndex; ?>" <?= ($pkgSelection === 1) ? 'checked' : ''; ?>>
                    </div>
                <?php endif; ?>
            </td>
            <!-- Package B Checkbox -->
            <td class="package-checkbox">
                <?php if ($isNewProduct || $toBuy): ?>
                    <span class="text-muted">-</span>
                <?php else: ?>
                    <div class="form-check d-flex justify-content-center">
                        <input type="checkbox" class="form-check-input pkgB" data-index="<?= $itemIndex; ?>" <?= ($pkgSelection === 2) ? 'checked' : ''; ?>>
                    </div>
                <?php endif; ?>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm remove-item" data-index="<?= $itemIndex; ?>">
                    <i class="fas fa-trash-alt"></i> Remove
                </button>
            </td>
        </tr>
<?php
    endforeach;
else:
    echo '<tr><td colspan="7" class="text-center">Cart is empty.</td></tr>';
endif;
?>

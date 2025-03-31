<?php
session_start();
include 'cart_functions.php';

$cartItems = getCartItems($pdo);

if (!empty($cartItems)):
    foreach ($cartItems as $item):
?>
        <tr data-index="<?= $item['index']; ?>" data-price="<?= $item['priceProduct']; ?>">
            <td><?= $item['productID']; ?></td>
            <td><?= $item['nameProduct']; ?></td>
            <td><?= $item['productCategory']; ?></td>
            <td><?= $item['priceProduct']; ?></td>
            <td>
                <button type="button" class="btn btn-danger btn-sm remove-item" data-index="<?= $item['index']; ?>">Remove</button>
            </td>
        </tr>
<?php
    endforeach;
else:
    echo '<tr><td colspan="5" class="text-center">Cart is empty.</td></tr>';
endif;
?>
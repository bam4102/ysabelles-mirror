<?php
session_start();
$total = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        // If it's a "to buy" item with a custom price, use that price
        if (isset($item['toBuy']) && $item['toBuy'] && isset($item['priceSold']) && $item['priceSold'] > 0) {
            $total += $item['priceSold'];
        } else {
            $total += $item['priceProduct'];
        }
    }
}
echo $total;
?>

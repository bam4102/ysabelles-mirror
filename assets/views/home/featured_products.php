<?php
/**
 * Featured Products View
 * Displays the top 10 most viewed products in a carousel
 */
?>
<section class="featured-products">
    <div class="container">
        <h2>FEATURED PRODUCTS</h2>
        
        <!-- Debug info -->
        <?php if (empty($allProductData)): ?>
        <div class="alert alert-warning">No products found. Please check the database connection.</div>
        <?php endif; ?>
        
        <div class="product-carousel">
            <!-- Products will be populated via JavaScript -->
        </div>
    </div>
</section> 
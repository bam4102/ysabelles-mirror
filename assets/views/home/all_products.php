<?php
/**
 * All Products View
 * Displays a grid of all available products
 */
?>
<section class="all-products">
    <div class="container-fluid px-0">
        <h2>ALL PRODUCTS</h2>
        
        <!-- Debug info -->
        <?php if (empty($allProductsData)): ?>
        <div class="alert alert-warning">No products found. Please check the database connection.</div>
        <?php endif; ?>
        
        <div class="row position-relative products-row">
           
            <!-- Product Grid -->
            <div class="col-12 products-container" id="products-container">
                <div class="product-grid">
                    <!-- Products will be populated via JavaScript -->
                </div>
                
                <button id="load-more-btn" class="load-more-btn">Load More</button>
            </div>
        </div>
    </div>
</section>
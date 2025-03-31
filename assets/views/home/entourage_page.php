<?php
/**
 * Entourage Products View
 * Displays a grid of entourage sets and their products
 */
?>
<!-- Load entourage page styles -->
<link rel="stylesheet" href="assets/css/home/entourage_page.css">

<section class="entourage-products">
    <div class="container-fluid px-0">
        <h2>ENTOURAGE COLLECTION</h2>
        
        <div class="row position-relative entourage-row">
            <!-- Entourage Grid -->
            <div class="col-12 entourage-container" id="entourage-products-container">
                <!-- Content will be loaded by JavaScript -->
            </div>
        </div>
    </div>
</section>

<!-- Entourage Details Modal -->
<div id="entourage-details-popup" class="product-details-popup">
    <div class="popup-content">
        <span class="close-popup">&times;</span>
        <div class="popup-container">
            <div class="popup-image-container">
                <img id="popup-entourage-image" src="" alt="Entourage Image" class="popup-image">
                <div class="popup-thumbnails" id="popup-entourage-thumbnails">
                    <!-- Thumbnails will be added dynamically -->
                </div>
            </div>
            <div class="popup-details">
                <div class="popup-details-content">
                    <h2 id="popup-entourage-name"></h2>
                    
                    <div class="product-info-row">
                        <div class="detail-value" id="popup-entourage-count"></div>
                        <div class="popup-category" id="popup-entourage-status"></div>
                    </div>
                    
                    <!-- Back to entourage button - initially hidden -->
                    <button id="back-to-entourage" class="btn btn-outline-secondary mb-3" style="display: none;">
                        ← Back to Entourage
                    </button>
                    
                    <!-- Entourage Products Grid -->
                    <div class="popup-section" id="popup-entourage-products">
                        <h3>Products in Set</h3>
                        <div class="entourage-products-grid" id="entourage-products-grid">
                            <!-- Products will be added dynamically -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .entourage-container {
        padding: 20px 0;
    }
    
    .section-title {
        font-size: 2rem;
        margin-bottom: 10px;
        color: #333;
    }
    
    .section-description {
        color: #666;
        margin-bottom: 30px;
    }
    
    .entourage-products {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .loading-indicator {
        padding: 40px 0;
    }
</style>

<!-- Script to preload all entourage data on page initialization -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Preload all entourage data on page load
        if (typeof fetchComprehensiveEntourageData === 'function') {
            console.log('Initializing single-connection data fetch for entourage page');
            // This will ensure all data is fetched with a single database connection
            fetchComprehensiveEntourageData().then(() => {
                console.log('Entourage data preloaded successfully on page initialization');
            }).catch(error => {
                console.error('Error preloading entourage data on initialization:', error);
            });
        }
    });
</script> 
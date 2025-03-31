<?php
/**
 * Product Details Popup View
 * Displays a modal popup with detailed product information when a product card is clicked
 */
?>
<div id="product-details-popup" class="product-details-popup">
    <div class="popup-content">
        <span class="close-popup">&times;</span>
        <div class="popup-container">
            <div class="popup-image-container">
                <img id="popup-product-image" src="" alt="Product Image" class="popup-image">
                <div class="popup-thumbnails" id="popup-thumbnails">
                    <!-- Thumbnails will be added dynamically -->
                </div>
            </div>
            <div class="popup-details">
                <div class="popup-details-content">
                    <h2 id="popup-product-name"></h2>
                    
                    <div class="product-info-row">
                        <div class="detail-value" id="popup-product-gender"></div>
                        <div class="popup-category" id="popup-product-category"></div>
                    </div>
                    
                    <div class="popup-location"><span class="location-label">Location:</span> <span id="popup-product-location"></span></div>
                    
                    <!-- Product Specifications - Will be populated by JavaScript -->
                    <div class="popup-section" id="popup-product-specs">
                        <h3>Specifications</h3>
                        <div class="popup-details-table">
                            <!-- Basic specifications -->
                       
                            
                            <!-- Color specification -->
                            <div class="detail-row">
                                <div class="detail-label">Color</div>
                                <div class="detail-value" id="popup-product-color"></div>
                            </div>
                            
                            <!-- Product condition -->
                            <div class="detail-row">
                                <div class="detail-label">Condition</div>
                                <div class="detail-value condition-container">
                                    <span id="popup-product-condition"></span>
                                    <!-- Product Status Badges -->
                                    <div class="product-status-badges" id="product-status-badges">
                                        <!-- Status badges will be added dynamically -->
                                    </div>
                                </div>
                            </div>
                            <div class="popup-details-table" id="measurements-container">
                            <!-- Measurements will be added dynamically by JavaScript -->
                            <!-- Either size OR bust/waist/length will be shown -->
                        </div>
                        </div>
                    </div>
                    
                    <!-- Measurements Section - New dedicated section -->
                </div>
                
                <!-- Action Buttons -->
                <div class="popup-actions">
                    <div class="popup-price" id="popup-product-price"></div>
                    <button id="popup-add-to-cart" class="popup-btn add-to-cart-btn">Add to Cart</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add condition info modal -->
<div id="condition-info-modal" class="condition-info-modal">
    <div class="condition-modal-content">
        <span class="close-condition-modal">&times;</span>
        <h3 id="condition-modal-title">Product Condition</h3>
        <p id="condition-modal-description"></p>
    </div>
</div> 
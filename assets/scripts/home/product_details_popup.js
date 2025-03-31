// Product Details Popup
$(document).ready(function() {
    console.log("Product Details Popup JS loaded");
    
    // Global variables
    let currentProductId = null;
    const productsMap = {};  // Change to plain object instead of Map for more reliable lookups
    const productGroups = {}; // Store product variations by group_id
    
    // Process available data
    processProductData();
    
    // Setup event listeners
    setupProductCardListeners();
    
    // Listen for thumbnail clicks to switch images
    $(document).on('click', '.popup-thumbnail', function() {
        const thumbnails = $('.popup-thumbnail');
        thumbnails.removeClass('active');
        $(this).addClass('active');
        
        const imageSrc = $(this).find('img').attr('src');
        $('#popup-product-image').attr('src', imageSrc);
    });
    
    // Close popup when the X is clicked
    $('.close-popup').on('click', function() {
        $('#product-details-popup').fadeOut(300);
    });
    
    // Close popup when clicking outside the content
    $('#product-details-popup').on('click', function(e) {
        if ($(e.target).is('#product-details-popup')) {
            $(this).fadeOut(300);
        }
    });
    
    // Process and organize product data
    function processProductData() {
        const processedIds = new Set(); // Track which product IDs we've processed
        
        // Process featured products data first (since these are higher priority)
        if (window.allProductData && window.allProductData.length > 0) {
            console.log("Processing featured products for popup:", window.allProductData.length);
            window.allProductData.forEach(item => {
                // Skip sold products but show damaged ones
                if (item.soldProduct == 1) {
                    console.log(`Skipping sold product ${item.productID} (${item.nameProduct}) from featured products`);
                    return;
                }
                
                const productId = item.productID.toString();
                processedIds.add(productId); // Mark this ID as processed
                organizeProductData(item);
            });
        }
        
        // Process all products data, skipping duplicates that were already in featured products
        if (window.allProductsData && window.allProductsData.length > 0) {
            console.log("Processing all products for popup:", window.allProductsData.length);
            
            // Count how many duplicates we're skipping
            let duplicateCount = 0;
            let soldProductCount = 0;
            
            window.allProductsData.forEach(item => {
                // Skip sold products but show damaged ones
                if (item.soldProduct == 1) {
                    soldProductCount++;
                    console.log(`Skipping sold product ${item.productID} (${item.nameProduct}) from all products`);
                    return;
                }
                
                const productId = item.productID.toString();
                
                // Check if this is a duplicate from featured products
                if (processedIds.has(productId)) {
                    duplicateCount++;
                    // Only update the image if needed (to avoid duplicating images)
                    if (item.pictureLocation) {
                        organizeProductData(item);
                    }
                } else {
                    processedIds.add(productId);
                    organizeProductData(item);
                }
            });
            
            console.log(`Skipped ${duplicateCount} duplicate products from allProductsData`);
            console.log(`Skipped ${soldProductCount} sold products from allProductsData`);
        }
        
        console.log(`Processed ${Object.keys(productsMap).length} unique products for popup`);
        console.log("Sample product IDs:", Object.keys(productsMap).slice(0, 5));
        
        // Organize products into variation groups
        organizeProductGroups();
        
        // Log image counts for debugging
        let productsWithMultipleImages = 0;
        for (const productId in productsMap) {
            const imageCount = productsMap[productId].images.length;
            if (imageCount > 1) {
                productsWithMultipleImages++;
                console.log(`Product ${productId} (${productsMap[productId].nameProduct}) has ${imageCount} images`);
            }
        }
        console.log(`${productsWithMultipleImages} products have multiple images`);
    }
    
    // Organize products into variation groups based on group_id
    function organizeProductGroups() {
        // Reset product groups
        Object.keys(productGroups).forEach(key => delete productGroups[key]);
        
        // Group products by group_id
        for (const productId in productsMap) {
            const product = productsMap[productId];
            
            // Skip sold products from variation groups, but include damaged ones
            if (product.soldProduct == 1) {
                console.log(`Skipping sold product ${productId} from variation groups`);
                continue;
            }
            
            if (product.group_id) {
                const groupId = product.group_id.toString();
                
                // Initialize group if it doesn't exist
                if (!productGroups[groupId]) {
                    productGroups[groupId] = [];
                }
                
                // Add product to group if not already included
                if (!productGroups[groupId].some(p => p.productID === product.productID)) {
                    productGroups[groupId].push(product);
                }
            }
        }
        
        // Clean up empty groups
        for (const groupId in productGroups) {
            // If a group has no active products, delete it
            if (productGroups[groupId].length === 0) {
                delete productGroups[groupId];
            }
        }
        
        // Log product groups
        console.log(`Organized ${Object.keys(productGroups).length} product variation groups`);
        for (const groupId in productGroups) {
            console.log(`Group ${groupId} has ${productGroups[groupId].length} variations`);
        }
    }
    
    // Helper function to organize product data
    function organizeProductData(item) {
        // Make sure productID is treated as a string consistently
        const productId = item.productID.toString();
        
        // Log the raw product data for debugging
        console.log(`Processing product ${productId}:`, {
            productID: productId,
            name: item.nameProduct,
            sizeRaw: item.sizeProduct,
            bustRaw: item.bustProduct,
            waistRaw: item.waistProduct,
            lengthRaw: item.lengthProduct,
            group_id: item.group_id
        });
        
        if (!productsMap[productId]) {
            // Initialize product entry with all available fields from database schema
            productsMap[productId] = {
                productID: productId,
                nameProduct: item.nameProduct,
                priceProduct: item.priceProduct || 0,
                productCategory: item.productCategory || 'Uncategorized',
                counterProduct: item.counterProduct || 0,
                description: item.descProduct || 'No description available for this product.',
                colorProduct: item.colorProduct || 'N/A',
                // Ensure size fields use consistent fallback values
                sizeProduct: (item.sizeProduct && item.sizeProduct !== '') ? item.sizeProduct : 'N/A',
                bustProduct: (item.bustProduct && item.bustProduct !== '') ? item.bustProduct : 'N/A',
                waistProduct: (item.waistProduct && item.waistProduct !== '') ? item.waistProduct : 'N/A',
                lengthProduct: (item.lengthProduct && item.lengthProduct !== '') ? item.lengthProduct : 'N/A',
                typeProduct: item.typeProduct || 'N/A',
                locationProduct: item.locationProduct || 'BACOLOD CITY',
                genderProduct: item.genderProduct || 'N/A',
                damageProduct: item.damageProduct || 0,
                returnedProduct: item.returnedProduct || 0,
                useProduct: item.useProduct || 0,
                soldProduct: item.soldProduct || 0,
                isNew: item.isNew || 0,
                codeProduct: item.codeProduct || 'N/A',
                dateAdded: item.createdAt || new Date().toISOString().split('T')[0],
                dateModified: item.updatedAt || new Date().toISOString().split('T')[0],
                images: [],
                processedImageIds: new Set(), // Track processed image IDs to prevent duplicates
                group_id: item.group_id || null,
                variation_id: item.variation_id || null
            };
        } else {
            // Update variation data if it exists in this record but not in the existing one
            if (item.group_id && !productsMap[productId].group_id) {
                productsMap[productId].group_id = item.group_id;
                productsMap[productId].variation_id = item.variation_id;
            }
        }
        
        // Add image to product if it exists
        if (item.pictureLocation) {
            const product = productsMap[productId];
            const pictureId = item.pictureID || item.pictureLocation; // Use pictureID or location as identifier
            
            // Check if this image has already been added
            if (!product.processedImageIds.has(pictureId)) {
                product.processedImageIds.add(pictureId);
                
                product.images.push({
                    location: item.pictureLocation,
                    isPrimary: item.isPrimary,
                    pictureID: item.pictureID || '',
                    captionPicture: item.captionPicture || '',
                    altPicture: item.altPicture || item.nameProduct
                });
                
                // Sort images to ensure primary image is first
                product.images.sort((a, b) => (b.isPrimary || 0) - (a.isPrimary || 0));
                
                console.log(`Added image for product ${productId}, image count: ${product.images.length}`);
            } else {
                console.log(`Skipped duplicate image for product ${productId}`);
            }
        }
    }
    
    // Initialize event listeners for product cards
    function setupProductCardListeners() {
        // For featured products
        $(document).on('click', '.product-card', function(e) {
            e.preventDefault();
            // Get product ID directly from the data attribute
            const productId = $(this).data('id');
            console.log("Product card clicked, ID:", productId);
            
            if (productId) {
                openProductPopup(productId);
            } else {
                console.error("No product ID found on this card");
                // Fallback to carousel index method
                const carouselIndex = $(this).closest('.slick-slide').data('slick-index');
                console.log("Trying carousel index method, index:", carouselIndex);
                
                if (typeof carouselIndex !== 'undefined' && window.allProductData) {
                    // Create a sorted array of featured products
                    const processedProducts = Object.values(productsMap)
                        .sort((a, b) => (b.counterProduct || 0) - (a.counterProduct || 0))
                        .slice(0, 10);
                    
                    if (processedProducts[carouselIndex]) {
                        const productIdFromIndex = processedProducts[carouselIndex].productID;
                        console.log("Found product ID from index:", productIdFromIndex);
                        openProductPopup(productIdFromIndex);
                    }
                }
            }
        });
        
        // For all products grid
        $(document).on('click', '.product-item', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent default navigation
            const productId = $(this).data('id');
            console.log("Product item clicked, ID:", productId);
            
            if (productId) {
                openProductPopup(productId);
            }
        });
    }
    
    // Open product popup by product ID
    function openProductPopup(productId) {
        console.log("Opening product popup for ID:", productId);
        
        // Make sure we have the product data
        if (!productsMap[productId]) {
            console.error("Product data not found for ID:", productId);
            return;
        }
        
        // Store current product ID
        currentProductId = productId;
        
        // Update popup content
        updatePopupContent(productsMap[productId]);
        
        // Show popup with animation
        $('#product-details-popup').fadeIn(300);
    }
    
    // Update popup content with product data
    function updatePopupContent(product) {
        console.log("Updating popup content for product:", product.nameProduct);
        
        // Set current product data attributes
        $('#popup-product-name').text(product.nameProduct);
        $('#popup-product-category').text(product.productCategory || 'Uncategorized');
        
        // Format price with commas
        const price = parseFloat(product.priceProduct || 0);
        $('#popup-product-price').text('₱' + price.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        
        $('#popup-product-location').text(product.locationProduct || 'BACOLOD CITY');
        
        // Handle gender display - hide if N/A
        const genderElement = $('#popup-product-gender');
        if (product.genderProduct && product.genderProduct !== 'N/A') {
            genderElement.text(product.genderProduct).show();
        } else {
            genderElement.hide();
        }
        
        $('#popup-product-color').text(product.colorProduct || 'N/A');
        
        // Set product condition based on flags
        let conditionText = 'New';
        let conditionClass = 'condition-new';
        
        if (product.damageProduct == 1) {
            conditionText = 'Damaged';
            conditionClass = 'condition-damaged';
        } else if (product.returnedProduct == 1) {
            conditionText = 'Not Returned';
            conditionClass = 'condition-not-returned';
        } else if (product.useProduct == 1) {
            conditionText = 'Available';
            conditionClass = 'condition-available';
        } else if (product.isNew == 1) {
            conditionText = 'New';
            conditionClass = 'condition-brand-new';
        }
        
        // Remove any existing condition classes before adding the new one
        $('#popup-product-condition')
            .removeClass('condition-new condition-brand-new condition-damaged condition-not-returned condition-available condition-used condition-returned')
            .text(conditionText)
            .addClass(conditionClass);
        
        // Set primary image with fallback
        const primaryImagePath = (product.images && product.images.length > 0) 
            ? product.images[0].location 
            : 'assets/img/placeholder.jpg';
        
        $('#popup-product-image').attr('src', primaryImagePath);
        
        // Set product ID for the "Add to Cart" button
        $('#popup-add-to-cart').data('product-id', product.productID);
        
        // Clear existing thumbnails
        $('#popup-thumbnails').empty();
        
        // Add thumbnails if there are multiple images
        if (product.images && product.images.length > 1) {
            product.images.forEach((image, index) => {
                const thumbnailHtml = `
                    <div class="popup-thumbnail${index === 0 ? ' active' : ''}">
                        <img src="${image.location}" alt="${image.altPicture || product.nameProduct}" 
                             onerror="this.src='assets/img/placeholder.jpg';">
                    </div>
                `;
                $('#popup-thumbnails').append(thumbnailHtml);
            });
        }
        
        // HANDLE MEASUREMENTS - Check if we should show size OR bust/waist/length
        const hasSize = product.sizeProduct && product.sizeProduct !== 'N/A' && product.sizeProduct !== '';
        const hasBustWaistLength = (
            (product.bustProduct && product.bustProduct !== 'N/A' && product.bustProduct !== '') ||
            (product.waistProduct && product.waistProduct !== 'N/A' && product.waistProduct !== '') ||
            (product.lengthProduct && product.lengthProduct !== 'N/A' && product.lengthProduct !== '')
        );
        
        // Get measurements container
        const measurementsContainer = $('#measurements-container');
        measurementsContainer.empty();
        
        // Debug measurements data
        console.log("Product measurements data:", {
            productID: product.productID,
            name: product.nameProduct,
            hasSize: hasSize,
            hasBustWaistLength: hasBustWaistLength,
            size: product.sizeProduct,
            bust: product.bustProduct,
            waist: product.waistProduct,
            length: product.lengthProduct
        });
        
        // Decide which measurements to show
        if (hasSize && !hasBustWaistLength) {
            // Show ONLY size
            measurementsContainer.append(`
                <div class="detail-row">
                    <div class="detail-label">Size</div>
                    <div class="detail-value">${product.sizeProduct}</div>
                </div>
            `);
        } else if (!hasSize && hasBustWaistLength) {
            // Create a single row for all BWL measurements
            const bustValue = (product.bustProduct && product.bustProduct !== 'N/A' && product.bustProduct !== '') ? product.bustProduct : '-';
            const waistValue = (product.waistProduct && product.waistProduct !== 'N/A' && product.waistProduct !== '') ? product.waistProduct : '-';
            const lengthValue = (product.lengthProduct && product.lengthProduct !== 'N/A' && product.lengthProduct !== '') ? product.lengthProduct : '-';
            
            measurementsContainer.append(`
                <div class="detail-row measurements-row">
                    <div class="detail-label">Measurements</div>
                    <div class="detail-value measurements-value">
                        <span class="measurement-item"><strong>Bust:</strong> ${bustValue}</span>
                        <span class="measurement-item"><strong>Waist:</strong> ${waistValue}</span>
                        <span class="measurement-item"><strong>Length:</strong> ${lengthValue}</span>
                    </div>
                </div>
            `);
        } else if (hasSize && hasBustWaistLength) {
            // Show ONLY size if both are available (prioritize size)
            measurementsContainer.append(`
                <div class="detail-row">
                    <div class="detail-label">Size</div>
                    <div class="detail-value">${product.sizeProduct}</div>
                </div>
            `);
        } else {
            // No measurements available
            measurementsContainer.append(`
                <div class="detail-row">
                    <div class="detail-label">Size</div>
                    <div class="detail-value">Not Available</div>
                </div>
            `);
        }
        
        // Add product variations if available
        if (product.group_id) {
            const groupId = product.group_id.toString();
            if (productGroups[groupId] && productGroups[groupId].length > 1) {
                // Create variations section
                measurementsContainer.append(`
                    <div class="detail-row variations-header">
                        <div class="detail-label">Select Sizes</div>
                    </div>
                    <div class="product-variations"></div>
                `);
                
                // Add variation buttons
                const variationsContainer = measurementsContainer.find('.product-variations');
                productGroups[groupId].forEach(variation => {
                    const isActive = variation.productID === product.productID;
                    // Add 'damaged' class to variation button if product is damaged
                    const isDamaged = variation.damageProduct == 1;
                    const variationClass = isActive 
                        ? `variation-btn active${isDamaged ? ' damaged' : ''}` 
                        : `variation-btn${isDamaged ? ' damaged' : ''}`;
                    
                    const sizeText = variation.sizeProduct !== 'N/A' ? variation.sizeProduct : 'Default';
                    
                    // Add a 'D' indicator for damaged products in variation buttons
                    
                    variationsContainer.append(`
                        <button class="${variationClass}" data-product-id="${variation.productID}">
                            ${sizeText}
                        </button>
                    `);
                });
            }
        }
        
        // Image handling
        console.log(`Product ${product.productID} has ${product.images.length} images:`, product.images);
        
        // Create status badges
        const badgesContainer = $('#product-status-badges');
        badgesContainer.empty();
        
        // Add new badge if product is new
        if (product.isNew == 1) {
            badgesContainer.append('<span class="status-badge new-badge">NEW</span>');
        }
        
        // Add damage badge with "D" letter
        if (product.damageProduct == 1) {
            badgesContainer.append('<span class="status-badge damaged-badge">i</span>');
        }
        
        // Add returned badge if product is returned
        if (product.returnedProduct == 1) {
            badgesContainer.append('<span class="status-badge returned-badge">i</span>');
        }
        
        // Enable/disable add to cart button based on product status
        if (product.soldProduct == 1) {
            $('#popup-add-to-cart').prop('disabled', true).addClass('disabled');
        } else {
            $('#popup-add-to-cart').prop('disabled', false).removeClass('disabled');
        }
    }
    
    // Handle variation button click
    $(document).on('click', '.variation-btn', function() {
        // Don't do anything if already active
        if ($(this).hasClass('active')) {
            return;
        }
        
        const productId = $(this).data('product-id');
        if (productId) {
            currentProductId = productId.toString();
            const product = productsMap[currentProductId];
            if (product) {
                // Preserve original gender if it was displayed
                const originalGenderElement = $('#popup-product-gender');
                const wasGenderVisible = originalGenderElement.is(':visible');
                const originalGender = wasGenderVisible ? originalGenderElement.text() : null;
                
                // Update popup content
                updatePopupContent(product);
                
                // If we had gender before but not after update, restore it
                if (wasGenderVisible && !$('#popup-product-gender').is(':visible') && originalGender) {
                    $('#popup-product-gender').text(originalGender).show();
                }
            }
        }
    });
    
    // Close popup with escape key
    $(document).keydown(function(e) {
        if (e.key === "Escape" && $('#product-details-popup').is(':visible')) {
            $('#product-details-popup').fadeOut(300);
            $('body').removeClass('popup-open');
        }
    });
    
    // Function to show condition info modal
    function showConditionModal(conditionType) {
        const modal = $('#condition-info-modal');
        const title = $('#condition-modal-title');
        const description = $('#condition-modal-description');
        
        // Get the current product description
        const product = productsMap[currentProductId];
        const productDesc = product.description || 'No description available for this product.';
        
        // If this is a not-returned condition, check availability status
        if (conditionType === 'not-returned') {
            // Check bond status through availability API
            $.ajax({
                url: 'assets/controllers/check_product_availability.php',
                type: 'GET',
                data: {
                    productID: currentProductId,
                    check_all: 1
                },
                dataType: 'json',
                success: function(response) {
                    let notReturnedMessage = 'This item has not been returned yet.';
                    
                    if (response.status && response.transactions.length > 0) {
                        // Look for transactions with bondStatus = 1 (reserved)
                        const reservedTransactions = response.transactions.filter(t => t.bondStatus == 1);
                        
                        if (reservedTransactions.length > 0) {
                            const latestReservation = reservedTransactions[0];
                            notReturnedMessage = `This item is currently reserved and not yet returned. It was scheduled to be returned by ${latestReservation.dateReturn}.`;
                        }
                    }
                    
                    // Update modal content
                    title.text('Not Returned Product');
                    description.html(notReturnedMessage + '<br><br>' + productDesc);
                    
                    // Show modal if not already visible
                    if (!modal.is(':visible')) {
                        modal.fadeIn(300);
                    }
                },
                error: function() {
                    // Fallback to regular description on error
                    title.text('Not Returned Product');
                    description.text(productDesc);
                    modal.fadeIn(300);
                }
            });
        } else {
            // For other condition types, set title and show immediately
            switch(conditionType) {
                case 'new':
                    title.text('New Product');
                    break;
                case 'damaged':
                    title.text('Damaged Product');
                    break;
                case 'available':
                    title.text('Available Product');
                    break;
                default:
                    title.text('Product Condition');
            }
            
            // Display the product's actual description
            description.text(productDesc);
            
            // Show modal
            modal.fadeIn(300);
        }
    }
    
    // Handle clicks on status badges
    $(document).on('click', '.status-badge', function() {
        let conditionType = '';
        
        if ($(this).hasClass('new-badge')) {
            conditionType = 'new';
        } else if ($(this).hasClass('damaged-badge')) {
            conditionType = 'damaged';
        } else if ($(this).hasClass('returned-badge')) {
            conditionType = 'not-returned';
        }
        
        showConditionModal(conditionType);
    });
    
    // Close condition modal
    $('.close-condition-modal').on('click', function() {
        $('#condition-info-modal').fadeOut(300);
    });
    
    // Close condition modal when clicking outside content
    $('#condition-info-modal').on('click', function(e) {
        if ($(e.target).is('#condition-info-modal')) {
            $(this).fadeOut(300);
        }
    });
});
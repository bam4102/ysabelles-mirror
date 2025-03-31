// Wait for the document to be fully loaded
$(document).ready(function() {
    console.log("jQuery is loaded");
    
    if (typeof $.fn.slick === 'undefined') {
        console.error("Slick carousel is not loaded");
        alert("Slick carousel library is not loaded. Check your network connection.");
    } else {
        console.log("Slick carousel is loaded");
        
        // Process product data on client side
        if (window.allProductData && window.allProductData.length > 0) {
            // Create a map to organize products and their images
            const productsMap = new Map();
            
            // Process all products to organize data
            window.allProductData.forEach(item => {
                const productId = item.productID;
                
                if (!productsMap.has(productId)) {
                    // Initialize product entry without image
                    productsMap.set(productId, {
                        productID: productId,
                        nameProduct: item.nameProduct,
                        priceProduct: item.priceProduct,
                        productCategory: item.productCategory,
                        counterProduct: item.counterProduct || 0,
                        damageProduct: item.damageProduct || 0,
                        images: [],
                        createdAt: item.createdAt
                    });
                }
                
                // Add image to product if it exists
                if (item.pictureLocation) {
                    const product = productsMap.get(productId);
                    product.images.push({
                        location: item.pictureLocation,
                        isPrimary: item.isPrimary
                    });
                    
                    // Sort images to ensure primary image is first
                    product.images.sort((a, b) => (b.isPrimary || 0) - (a.isPrimary || 0));
                }
                
                // Add damage status if present
                if (item.damageProduct == 1) {
                    productsMap.get(productId).damageProduct = 1;
                }
            });
            
            // Sort products by counterProduct (view count) and take top 10
            const processedProducts = Array.from(productsMap.values())
                .sort((a, b) => (b.counterProduct || 0) - (a.counterProduct || 0))
                .slice(0, 10);
            
            // Debug sorting
            console.log("Products sorted by view count (counterProduct):");
            processedProducts.forEach((product, index) => {
                console.log(`${index + 1}. ${product.nameProduct} - ${product.counterProduct} views - ID: ${product.productID}`);
            });
            
            // Populate carousel with processed data
            const carouselContainer = $('.product-carousel');
            carouselContainer.empty();
            
            processedProducts.forEach(product => {
                // Check for images and use placeholder if none available
                let imageUrl = './assets/img/placeholder.jpg'; //DONT CHANGE THE PLACEHOLDER IMAGE LOCATION
                
                if (product.images && product.images.length > 0 && product.images[0].location) {
                    imageUrl = product.images[0].location;
                }
                
                // Add damage badge if product is damaged
                let damageBadge = '';
                if (product.damageProduct === '1') {
                    damageBadge = '<div class="damage-badge">DAMAGED</div>';
                }
                    
                //DONT CHANGE THE PLACEHOLDER IMAGE LOCATION
                const productCard = $(`
                    <div class="product-card" data-id="${product.productID}">
                        <div class="product-image-container">
                            <img src="${imageUrl}" alt="${product.nameProduct}" class="product-image" 
                                 onerror="this.src='./assets/img/placeholder.jpg'; this.onerror='';">
                            ${damageBadge}
                            <div class="product-name-overlay">
                                ${product.nameProduct}
                            </div>
                        </div>
                    </div>
                `);
                
                // Debug: Verify data-id attribute
                console.log(`Added featured product card with ID: ${product.productID}`);
                
                carouselContainer.append(productCard);
            });
            
            // Initialize the carousel if not already initialized
            if (!carouselContainer.hasClass('slick-initialized')) {
                carouselContainer.slick({
                    slidesToShow: 5,
                    slidesToScroll: 1,
                    autoplay: true,
                    autoplaySpeed: 3000,
                    arrows: true,
                    prevArrow: '<button class="slick-prev"><i class="fas fa-chevron-left"></i></button>',
                    nextArrow: '<button class="slick-next"><i class="fas fa-chevron-right"></i></button>',
                    dots: false,
                    infinite: true,
                    swipe: true,
                    touchMove: true,
                    swipeToSlide: true,
                    touchThreshold: 10,
                    responsive: [
                        {
                            breakpoint: 1400,
                            settings: {
                                slidesToShow: 4
                            }
                        },
                        {
                            breakpoint: 1200,
                            settings: {
                                slidesToShow: 3
                            }
                        },
                        {
                            breakpoint: 992,
                            settings: {
                                slidesToShow: 2
                            }
                        },
                        {
                            breakpoint: 576,
                            settings: {
                                slidesToShow: 1
                            }
                        }
                    ]
                });
                console.log("Slick carousel initialized");
            }
        } else {
            console.error("No product data available");
        }
    }
}); 
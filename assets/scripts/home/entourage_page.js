/**
 * Entourage page content loader
 * This script handles loading entourage sets and displaying them in the home page
 */

document.addEventListener('DOMContentLoaded', function() {
    // Cache for all entourage data to prevent repeated database connections
    window.entourageCache = {
        sets: {},      // Store entourage sets by ID
        images: {},    // Store entourage images by ID
        products: {},  // Store products by ID
        productImages: {}, // Store product images by product ID
        isPreloaded: false // Flag to track if preloading is complete
    };
    
    // Check if we have data in sessionStorage from previous page
    const cachedPreloadedData = sessionStorage.getItem('entouragePreloadedData');
    if (cachedPreloadedData) {
        try {
            console.log('Found entourage preloaded data in sessionStorage');
            window.entouragePreloadedData = JSON.parse(cachedPreloadedData);
            console.log(`Restored ${window.entouragePreloadedData.sets.length} entourage sets from sessionStorage`);
            
            // Clear from sessionStorage to prevent memory issues on future navigations
            // sessionStorage.removeItem('entouragePreloadedData');
        } catch (error) {
            console.error('Error restoring entourage preloaded data from sessionStorage:', error);
        }
    }
    
    // Check if we have entourage cache directly
    const cachedEntourageData = sessionStorage.getItem('entourageCache');
    if (cachedEntourageData) {
        try {
            console.log('Found entourage cache in sessionStorage');
            window.entourageCache = JSON.parse(cachedEntourageData);
            console.log(`Restored ${Object.keys(window.entourageCache.sets).length} entourage sets from cache`);
            
            // Clear from sessionStorage to prevent memory issues on future navigations
            // sessionStorage.removeItem('entourageCache');
        } catch (error) {
            console.error('Error restoring entourage cache from sessionStorage:', error);
        }
    }
    
    // Check URL parameters - if we have page=entourage, load entourage page immediately
    const urlParams = new URLSearchParams(window.location.search);
    const pageParam = urlParams.get('page');
    
    if (pageParam === 'entourage') {
        // Slight delay to ensure all other scripts are loaded first
        setTimeout(() => {
            loadEntouragePage();
        }, 100);
    }
    
    // Function to load entourage page content
    window.loadEntouragePage = function() {
        // Show loading indicator in main content
        const mainContent = document.getElementById('mainContent');
        if (!mainContent) return;
        
        // Save current scroll position
        const scrollPosition = window.pageYOffset;
        
        // First, load the entourage view template
        fetch('assets/views/home/entourage_page.php')
            .then(response => response.text())
            .then(html => {
                // Replace main content with entourage view
                mainContent.innerHTML = html;
                
                // Hide sidebar and expand main content
                const sidebarColumn = document.getElementById('sidebarColumn');
                if (sidebarColumn) {
                    sidebarColumn.style.display = 'none';
                }
                
                // Expand main content to full width
                mainContent.classList.remove('col-lg-9');
                mainContent.classList.add('col-lg-12');
                mainContent.classList.add('expanded');
                
                // Initialize entourage page
                initializeEntouragePage();
                
                // Restore scroll position
                window.scrollTo(0, 0);
                
                // Update page URL without refreshing (for browser history)
                history.pushState(
                    { page: 'entourage' }, 
                    'Entourage Collection - Ysabelles', 
                    '?page=entourage'
                );
                
                // Update document title
                document.title = 'Entourage Collection - Ysabelles';
            })
            .catch(error => {
                console.error('Error loading entourage page:', error);
                displayError('Failed to load entourage page');
            });
    };
    
    // Initialize entourage page functionality
    function initializeEntouragePage() {
        const container = document.getElementById('entourage-products-container');
        if (!container) {
            console.error('Container entourage-products-container not found');
            return;
        }
        
        // Add loading indicator
        container.innerHTML = `
            <div class="entourage-grid">
                <div class="col-12 text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <button id="load-more-btn" class="load-more-btn" style="display: none;">Load More</button>
        `;
        
        // Check if entourage data was preloaded in home.php
        if (window.entouragePreloadedData && window.entouragePreloadedData.isPreloaded) {
            console.log("Initializing with preloaded entourage data from home.php");
            
            // Initialize entourageCache with the preloaded data
            if (!window.entourageCache.isPreloaded) {
                // This will copy the preloaded data into our cache
                fetchComprehensiveEntourageData().then(() => {
                    // After preloading, display the entourage sets
                    displayEntourageSets(Object.values(window.entourageCache.sets), true);
                    
                    // Hide load more button since we have all data
                    const loadMoreBtn = document.getElementById('load-more-btn');
                    if (loadMoreBtn) {
                        loadMoreBtn.style.display = 'none';
                    }
                    
                    console.log("All entourage data initialized from home.php preloaded data - NO database connection needed!");
                });
            } else {
                // If already preloaded, display directly
                displayEntourageSets(Object.values(window.entourageCache.sets), true);
                
                // Hide load more button
                const loadMoreBtn = document.getElementById('load-more-btn');
                if (loadMoreBtn) {
                    loadMoreBtn.style.display = 'none';
                }
            }
            return;
        }
        
        // If no preloaded data, fetch all entourage data in a single request
        fetchComprehensiveEntourageData().then(() => {
            // After preloading, display the entourage sets
            displayEntourageSets(Object.values(window.entourageCache.sets), true);
            
            // Setup load more button (now mostly for show since we've preloaded everything)
            const loadMoreBtn = document.getElementById('load-more-btn');
            if (loadMoreBtn) {
                loadMoreBtn.style.display = 'none'; // Hide since we preloaded everything
            }
            
            console.log("All entourage data loaded successfully in a single connection!");
        }).catch(error => {
            console.error('Error loading entourage data:', error);
            // Fall back to regular loading if comprehensive fetch fails
            fetchEntourageSets();
            
            // Setup load more button
            const loadMoreBtn = document.getElementById('load-more-btn');
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', loadMoreEntourageSets);
            }
        });
    }
    
    // Function to fetch all entourage data in a single request
    async function fetchComprehensiveEntourageData() {
        // If already preloaded, return immediately
        if (window.entourageCache.isPreloaded) {
            console.log("Using cached entourage data");
            return Promise.resolve();
        }
        
        // Check if entourage data was preloaded in home.php
        if (window.entouragePreloadedData && window.entouragePreloadedData.isPreloaded) {
            console.log("Using preloaded entourage data from home.php");
            
            try {
                // Store all sets in cache
                if (window.entouragePreloadedData.sets && Array.isArray(window.entouragePreloadedData.sets)) {
                    window.entouragePreloadedData.sets.forEach(set => {
                        window.entourageCache.sets[set.entourageID] = set;
                    });
                    
                    console.log(`Loaded ${window.entouragePreloadedData.sets.length} entourage sets from preloaded data`);
                }
                
                // Store all images in cache
                if (window.entouragePreloadedData.imagesMap) {
                    window.entourageCache.images = window.entouragePreloadedData.imagesMap;
                    console.log(`Loaded images for ${Object.keys(window.entouragePreloadedData.imagesMap).length} entourage sets from preloaded data`);
                }
                
                // Store all product images in cache
                if (window.entouragePreloadedData.productImagesMap) {
                    window.entourageCache.productImages = window.entouragePreloadedData.productImagesMap;
                    console.log(`Loaded images for ${Object.keys(window.entouragePreloadedData.productImagesMap).length} products from preloaded data`);
                }
                
                // Store all products in cache
                if (window.entouragePreloadedData.sets && Array.isArray(window.entouragePreloadedData.sets)) {
                    window.entouragePreloadedData.sets.forEach(set => {
                        if (set.products && Array.isArray(set.products)) {
                            set.products.forEach(product => {
                                window.entourageCache.products[product.productID] = product;
                            });
                        }
                    });
                    
                    console.log(`Loaded ${Object.keys(window.entourageCache.products).length} products from preloaded data`);
                }
                
                // Enhance each product with preloaded images and data if possible
                Object.values(window.entourageCache.products).forEach(product => {
                    enhanceProductWithPreloadedData(product);
                });
                
                // Mark preloading as complete
                window.entourageCache.isPreloaded = true;
                console.log("All entourage data loaded from home.php preloaded data!");
                
                return Promise.resolve();
            } catch (error) {
                console.error("Error processing preloaded entourage data:", error);
                // Fall back to API call if processing preloaded data fails
            }
        }
        
        try {
            console.log("Preloaded data not found, fetching all entourage data via API call...");
            
            // Add timestamp to prevent caching
            const timestamp = new Date().getTime();
            const response = await fetch(`assets/controllers/entourage_page/entourage_controller.php?comprehensive=true&_=${timestamp}`);
            
            if (!response.ok) {
                throw new Error(`Network response was not ok: ${response.status}`);
            }
            
            const data = await response.json();
            
            // Validate the response data
            if (!data.sets || !Array.isArray(data.sets)) {
                throw new Error('Invalid data format received from server');
            }
            
            // Store all sets in cache
            data.sets.forEach(set => {
                window.entourageCache.sets[set.entourageID] = set;
            });
            
            // Store all images in cache
            if (data.imagesMap) {
                window.entourageCache.images = data.imagesMap;
            }
            
            // Store all product images in cache
            if (data.productImagesMap) {
                window.entourageCache.productImages = data.productImagesMap;
            }
            
            // Store all products in cache
            data.sets.forEach(set => {
                if (set.products && Array.isArray(set.products)) {
                    set.products.forEach(product => {
                        window.entourageCache.products[product.productID] = product;
                    });
                }
            });
            
            // Enhance each product with preloaded images and data if possible
            Object.values(window.entourageCache.products).forEach(product => {
                enhanceProductWithPreloadedData(product);
            });
            
            // Mark preloading as complete
            window.entourageCache.isPreloaded = true;
            console.log("All entourage data loaded in a single request!");
            console.log(`Loaded ${data.sets.length} entourage sets`);
            console.log(`Loaded ${Object.keys(window.entourageCache.products).length} products`);
            console.log(`Loaded images for ${Object.keys(window.entourageCache.images).length} entourage sets`);
            console.log(`Loaded images for ${Object.keys(window.entourageCache.productImages).length} products`);
            
            return Promise.resolve();
            
        } catch (error) {
            console.error("Error fetching comprehensive entourage data:", error);
            return Promise.reject(error);
        }
    }
    
    // Function to enhance products with images from preloaded data
    function enhanceProductWithPreloadedData(product) {
        // First, check if we have images in the entourage cache
        const productId = product.productID;
        if (window.entourageCache.productImages && window.entourageCache.productImages[productId]) {
            const images = window.entourageCache.productImages[productId];
            product.images = images;
            
            // Set primary image for product
            if (images.length > 0) {
                const primaryImage = images.find(img => img.isPrimary == 1);
                product.pictureLocation = primaryImage ? primaryImage.pictureLocation : images[0].pictureLocation;
            }
            
            console.log(`Enhanced product ${productId} with ${images.length} preloaded images from cache`);
            return product;
        }
        
        // If not in cache, try from global preloaded data
        if (window.allProductsData && Array.isArray(window.allProductsData)) {
            // Find all matching records for this product in the preloaded data
            const productRecords = window.allProductsData.filter(p => p.productID == productId);
            
            if (productRecords.length > 0) {
                // Collect all images from preloaded data
                const images = [];
                productRecords.forEach(record => {
                    if (record.pictureLocation) {
                        // Check if this image is already in the array
                        const exists = images.some(img => img.pictureLocation === record.pictureLocation);
                        if (!exists) {
                            images.push({
                                pictureID: record.pictureID || 0,
                                pictureLocation: record.pictureLocation,
                                isPrimary: record.isPrimary || 0,
                                isActive: 1,
                                fromPreloaded: true
                            });
                        }
                    }
                });
                
                // If we found images, add them to the product
                if (images.length > 0) {
                    console.log(`Enhanced product ${productId} with ${images.length} preloaded images from allProductsData`);
                    product.images = images;
                    
                    // Set main product image from primary or first image
                    const primaryImage = images.find(img => img.isPrimary == 1);
                    product.pictureLocation = primaryImage ? primaryImage.pictureLocation : images[0].pictureLocation;
                }
            }
        }
        
        return product;
    }
    
    // Function to get product images from preloaded data or fetch from server if needed
    function getProductImages(productId) {
        // First check in our comprehensive cache
        if (window.entourageCache.productImages && window.entourageCache.productImages[productId]) {
            console.log(`Using cached images for product ${productId}`);
            return Promise.resolve(window.entourageCache.productImages[productId]);
        }
        
        // Then check in our regular product cache if it exists
        if (window.productImagesCache && window.productImagesCache[productId]) {
            console.log(`Using cached images for product ${productId}`);
            return Promise.resolve(window.productImagesCache[productId]);
        }
        
        // If we have the product in window.allProductsData, extract images
        if (window.allProductsData && Array.isArray(window.allProductsData)) {
            const productRecords = window.allProductsData.filter(p => p.productID == productId);
            if (productRecords.length > 0) {
                // Use enhanceProductWithPreloadedData function to extract images
                const tempProduct = { productID: productId };
                enhanceProductWithPreloadedData(tempProduct);
                
                if (tempProduct.images && tempProduct.images.length > 0) {
                    // Store in cache
                    if (!window.productImagesCache) {
                        window.productImagesCache = {};
                    }
                    window.productImagesCache[productId] = tempProduct.images;
                    
                    console.log(`Extracted ${tempProduct.images.length} images for product ${productId} from allProductsData`);
                    return Promise.resolve(tempProduct.images);
                }
            }
        }
        
        // If we couldn't find images in any cache, fetch from server
        return fetchProductImages(productId);
    }
    
    // Function to fetch product images from server
    function fetchProductImages(productId) {
        const timestamp = new Date().getTime();
        return fetch(`assets/controllers/get_product_images.php?id=${productId}&_=${timestamp}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Network response was not ok: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Store in cache
                if (!window.productImagesCache) {
                    window.productImagesCache = {};
                }
                window.productImagesCache[productId] = data;
                
                return data;
            })
            .catch(error => {
                console.error(`Error fetching images for product ${productId}:`, error);
                return []; // Return empty array on error
            });
    }
    
    // Variables for pagination
    let currentPage = 1;
    let isLoading = false;
    let hasMore = true;
    const itemsPerPage = 12;
    
    // Function to fetch entourage sets
    function fetchEntourageSets(page = 1) {
        if (isLoading || !hasMore) return;
        
        // If we already have preloaded data, use it instead of making an API call
        if (window.entourageCache.isPreloaded) {
            console.log("Using preloaded entourage data from cache");
            const allSets = Object.values(window.entourageCache.sets);
            
            // Apply pagination manually
            const offset = (page - 1) * itemsPerPage;
            const pagedSets = allSets.slice(offset, offset + itemsPerPage);
            
            // Display the data
            displayEntourageSets(pagedSets, page === 1);
            
            // Update pagination state
            currentPage = page;
            hasMore = offset + itemsPerPage < allSets.length;
            
            // Update UI based on whether there are more items
            const loadMoreBtn = document.getElementById('load-more-btn');
            if (loadMoreBtn) {
                loadMoreBtn.style.display = hasMore ? 'block' : 'none';
                loadMoreBtn.textContent = 'Load More';
                loadMoreBtn.disabled = false;
            }
            
            return;
        }
        
        // Check if we can initialize from window.entouragePreloadedData
        if (!window.entourageCache.isPreloaded && window.entouragePreloadedData && window.entouragePreloadedData.isPreloaded) {
            console.log("Initializing cache from home.php preloaded data before fetching");
            
            // Initialize cache from preloaded data
            fetchComprehensiveEntourageData().then(() => {
                // Call this function again now that cache is initialized
                fetchEntourageSets(page);
            });
            return;
        }
        
        isLoading = true;
        const offset = (page - 1) * itemsPerPage;
        
        // Update loading state UI
        const container = document.getElementById('entourage-products-container');
        const loadMoreBtn = document.getElementById('load-more-btn');
        if (loadMoreBtn) {
            loadMoreBtn.textContent = 'Loading...';
            loadMoreBtn.disabled = true;
        }
        
        // Add timestamp to prevent caching
        const timestamp = new Date().getTime();
        
        // Fetch entourage data with pagination
        fetch(`assets/controllers/entourage_page/entourage_controller.php?offset=${offset}&limit=${itemsPerPage}&_=${timestamp}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Network response was not ok: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Entourage data received:', data); // Debug data
                
                if (data.error) {
                    throw new Error(data.message || 'Failed to load entourage sets');
                }
                
                // Check if we have data as an array
                if (!Array.isArray(data)) {
                    console.error('Received data is not an array:', data);
                    throw new Error('Invalid data format received from server');
                }
                
                // Store in cache
                data.forEach(set => {
                    window.entourageCache.sets[set.entourageID] = set;
                });
                
                // Display the entourage sets
                displayEntourageSets(data, page === 1);
                
                // Update pagination state
                currentPage = page;
                hasMore = data.length === itemsPerPage;
                
                // Update UI based on whether there are more items
                if (loadMoreBtn) {
                    loadMoreBtn.style.display = hasMore ? 'block' : 'none';
                    loadMoreBtn.textContent = 'Load More';
                    loadMoreBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error fetching entourage sets:', error);
                displayDiagnosticError(error.message || 'Failed to load entourage sets');
                
                // Reset UI
                if (loadMoreBtn) {
                    loadMoreBtn.textContent = 'Try Again';
                    loadMoreBtn.disabled = false;
                }
            })
            .finally(() => {
                isLoading = false;
            });
    }
    
    // Function to load more entourage sets
    function loadMoreEntourageSets() {
        if (!isLoading && hasMore) {
            fetchEntourageSets(currentPage + 1);
        }
    }
    
    // Function to display entourage sets
    function displayEntourageSets(entourageSets, clearContainer = false) {
        const container = document.querySelector('.entourage-grid');
        console.log('Displaying entourage sets:', entourageSets);
        console.log('Container found:', !!container);
        
        if (!container) {
            console.error('Container .entourage-grid not found');
            return;
        }
        
        if (clearContainer) {
            container.innerHTML = '';
        }
        
        if (!entourageSets || entourageSets.length === 0) {
            if (clearContainer) {
                container.innerHTML = `
                    <div class="col-12 text-center">
                        <p class="my-5">No entourage sets found.</p>
                    </div>
                `;
            }
            return;
        }
        
        // Add grid layout to container if needed
        if (!container.classList.contains('row')) {
            container.classList.add('row', 'row-cols-1', 'row-cols-md-3', 'row-cols-lg-4', 'g-4');
        }
        
        // Ensure each entourage set has an empty products array if missing
        entourageSets.forEach(set => {
            if (!set.products) set.products = [];
            if (!set.pictures) set.pictures = [];
            
            console.log('Creating card for set:', set.nameEntourage, 'ID:', set.entourageID);
            const setCard = createEntourageSetCard(set);
            container.appendChild(setCard);
        });
    }
    
    // Function to create an entourage set card
    function createEntourageSetCard(set) {
        const card = document.createElement('div');
        card.className = 'col-sm-6 col-md-4 col-lg-3 mb-4';
        
        // Get primary picture or first picture or use default
        const primaryPicture = set.pictures && set.pictures.length > 0 ? 
            (set.pictures.find(p => p.isPrimary == 1) || set.pictures[0]) : null;
        const pictureUrl = primaryPicture ? primaryPicture.pictureLocation : 'assets/img/default.jpg';
        
        // Count only active products
        const productCount = set.products ? set.products.length : 0;
        const activeProductsCount = set.products ? 
            set.products.filter(p => p.soldProduct == 0 && p.damageProduct == 0).length : 0;
        
        card.innerHTML = `
            <div class="card entourage-card" data-entourage-id="${set.entourageID}">
                <div class="card-img-container">
                    <img src="${pictureUrl}" 
                         class="card-img-top" 
                         alt="${set.nameEntourage}"
                         onerror="this.src='assets/img/default.jpg'">
                </div>
                <div class="card-body">
                    <h5 class="card-title">${set.nameEntourage}</h5>
                    <p class="card-text">${productCount} items in set</p>
                    <p class="card-text text-muted">${activeProductsCount} available</p>
                </div>
                <div class="card-footer">
                    <button class="btn btn-outline-primary btn-sm view-set" 
                            data-entourage-id="${set.entourageID}">
                        View Set
                    </button>
                </div>
            </div>
        `;
        
        // Add click handler to the entire card
        card.querySelector('.entourage-card').addEventListener('click', () => {
            showEntourageDetails(set);
        });
        
        return card;
    }
    
    // Function to show entourage details in modal
    function showEntourageDetails(set) {
        const modal = document.getElementById('entourage-details-popup');
        if (!modal) return;
        
        // Try to get the entourage from cache first for the most complete data
        if (window.entourageCache && window.entourageCache.sets && window.entourageCache.sets[set.entourageID]) {
            set = window.entourageCache.sets[set.entourageID];
            console.log("Using cached entourage data for ID:", set.entourageID);
        }

        // Track current image index
        let currentImageIndex = 0;
        
        // Variable to track selected product
        let selectedProduct = null;

        // Override any existing document click handlers for entourage product cards
        function overrideGlobalProductCardHandlers() {
            // Only add this if jQuery is available (used by product_details_popup.js)
            if (typeof jQuery !== 'undefined') {
                jQuery(document).off('click', '.entourage-product-card').on('click', '.entourage-product-card', function(e) {
                    // Stop the event from propagating to the product_details_popup handlers
                    e.stopPropagation();
                    e.preventDefault();
                    return false;
                });
            }
        }
        
        // Call the override function
        overrideGlobalProductCardHandlers();

        // Store original entourage data to use with back button
        const originalEntourage = {
            name: set.nameEntourage,
            image: null,
            count: `${set.products.length} items in set`,
            status: `${set.products.filter(p => p.soldProduct == 0 && p.damageProduct == 0).length} available`,
            thumbnails: [],
            currentIndex: 0
        };

        // Function to get entourage images from cache or fetch if needed
        function getEntourageImages(entourageId) {
            return new Promise((resolve, reject) => {
                // First check the cache
                if (window.entourageCache && window.entourageCache.images && window.entourageCache.images[entourageId]) {
                    console.log(`Using cached images for entourage ${entourageId}`);
                    return resolve(window.entourageCache.images[entourageId]);
                }

                // If not cached, fall back to fetching
                fetchEntourageImages(entourageId).then(images => {
                    // Store in cache for future use
                    if (!window.entourageCache.images) {
                        window.entourageCache.images = {};
                    }
                    window.entourageCache.images[entourageId] = images;
                    resolve(images);
                });
            });
        }

        // Function to fetch entourage images as fallback
        function fetchEntourageImages(entourageId) {
            return new Promise((resolve, reject) => {
                // Add timestamp to prevent caching
                const timestamp = new Date().getTime();
                
                // Make an AJAX call to fetch entourage images
                fetch(`assets/controllers/entourage_page/entourage_controller.php?images=true&id=${entourageId}&_=${timestamp}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error('Error fetching entourage images:', data.message);
                            resolve([]);
                            return;
                        }
                        console.log('Entourage images loaded:', data);
                        resolve(data.images || []);
                    })
                    .catch(error => {
                        console.error('Failed to load entourage images:', error);
                        resolve([]);
                    });
            });
        }

        // Get primary picture or first picture as a placeholder
        const primaryPicture = set.pictures && set.pictures.length > 0 ? 
            (set.pictures.find(p => p.isPrimary == 1) || set.pictures[0]) : null;
        const pictureUrl = primaryPicture ? primaryPicture.pictureLocation : 'assets/img/default.jpg';
        originalEntourage.image = pictureUrl;

        // Update modal content with basic info
        document.getElementById('popup-entourage-image').src = pictureUrl;
        document.getElementById('popup-entourage-name').textContent = set.nameEntourage;
        document.getElementById('popup-entourage-count').textContent = originalEntourage.count;
        document.getElementById('popup-entourage-status').textContent = originalEntourage.status;

        // Hide back button initially
        const backButton = document.getElementById('back-to-entourage');
        backButton.style.display = 'none';

        // Clear thumbnails container
        const thumbnailsContainer = document.getElementById('popup-entourage-thumbnails');
        thumbnailsContainer.innerHTML = '';
        
        // Show the modal first for better user experience
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';

        // Show loading indicator for images
        const imageContainer = document.querySelector('.popup-image-container');
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'loading-indicator';
        loadingIndicator.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading images...</span></div>';
        imageContainer.appendChild(loadingIndicator);

        // Create image navigation controls
        const createNavButtons = () => {
            // Remove old navigation buttons if they exist
            const oldNavButtons = modal.querySelectorAll('.image-nav-btn');
            oldNavButtons.forEach(btn => btn.remove());
            
            // Remove old image counter if it exists
            const oldCounter = modal.querySelector('.image-counter');
            if (oldCounter) oldCounter.remove();
            
            // Use the right set of images depending on context
            const currentImages = selectedProduct ? 
                selectedProduct.images : 
                (set.displayImages || set.images);
                
            // Only show nav buttons if we have multiple images
            if (currentImages && currentImages.length > 1) {
                // Create previous button
                const prevBtn = document.createElement('button');
                prevBtn.className = 'image-nav-btn prev-btn';
                prevBtn.innerHTML = '&lt;';
                prevBtn.onclick = (e) => {
                    e.stopPropagation();
                    if (currentImageIndex > 0) {
                        currentImageIndex--;
                    } else {
                        currentImageIndex = currentImages.length - 1;
                    }
                    updateMainImage();
                };
                
                // Create next button
                const nextBtn = document.createElement('button');
                nextBtn.className = 'image-nav-btn next-btn';
                nextBtn.innerHTML = '&gt;';
                nextBtn.onclick = (e) => {
                    e.stopPropagation();
                    if (currentImageIndex < currentImages.length - 1) {
                        currentImageIndex++;
                    } else {
                        currentImageIndex = 0;
                    }
                    updateMainImage();
                };
                
                const imageContainer = document.querySelector('.popup-image-container');
                imageContainer.appendChild(prevBtn);
                imageContainer.appendChild(nextBtn);
                
                // Add image counter
                const counter = document.createElement('div');
                counter.className = 'image-counter';
                counter.innerHTML = `<span>${currentImageIndex + 1}</span>/<span>${currentImages.length}</span>`;
                imageContainer.appendChild(counter);
            }
        };
        
        // Function to update the main image
        const updateMainImage = () => {
            const mainImage = document.getElementById('popup-entourage-image');
            // Use the right set of images depending on context
            const currentImages = selectedProduct ? 
                selectedProduct.images : 
                (set.displayImages || set.images);
                
            if (currentImages && currentImages.length > 0) {
                const selectedImage = currentImages[currentImageIndex];
                mainImage.src = selectedImage.pictureLocation;
                
                // Update image counter if it exists
                const counter = modal.querySelector('.image-counter');
                if (counter) {
                    counter.innerHTML = `<span>${currentImageIndex + 1}</span>/<span>${currentImages.length}</span>`;
                }
                
                // Update active thumbnail
                const thumbnails = thumbnailsContainer.querySelectorAll('img');
                thumbnails.forEach((thumb, index) => {
                    if (index === currentImageIndex) {
                        thumb.classList.add('active');
                    } else {
                        thumb.classList.remove('active');
                    }
                });
            }
        };
        
        // Use images from the set object if already available, otherwise fetch them
        let displayPromise;
        
        if (set.images && set.displayImages) {
            console.log("Using already loaded images for entourage");
            displayPromise = Promise.resolve({
                images: set.images,
                displayImages: set.displayImages
            });
        } else {
            // Fetch or get from cache
            displayPromise = getEntourageImages(set.entourageID).then(images => {
                // Filter images to only include direct entourage images
                const entourageOnlyImages = images.filter(img => !img.fromProduct);
                
                // If we have direct entourage images, use those; otherwise fall back to all images
                const displayImages = entourageOnlyImages.length > 0 ? entourageOnlyImages : images;
                
                return {
                    images: images,
                    displayImages: displayImages
                };
            });
        }
        
        // Process and display images
        displayPromise.then(({images, displayImages}) => {
            // Remove loading indicator
            const loadingIndicator = modal.querySelector('.loading-indicator');
            if (loadingIndicator) loadingIndicator.remove();
            
            // Store fetched images in the set object
            set.images = images;
            set.displayImages = displayImages;
            
            // Reset current index
            currentImageIndex = 0;
            
            // Clear thumbnails container
            thumbnailsContainer.innerHTML = '';
            
            // Populate thumbnails with filtered images only
            if (displayImages.length > 0) {
                displayImages.forEach((image, index) => {
                    const thumb = document.createElement('img');
                    thumb.src = image.pictureLocation;
                    thumb.alt = 'Thumbnail';
                    thumb.classList.toggle('active', index === currentImageIndex);
                    thumb.onclick = () => {
                        currentImageIndex = index;
                        updateMainImage();
                    };
                    thumbnailsContainer.appendChild(thumb);
                    
                    // Store original thumbnail data
                    originalEntourage.thumbnails.push({
                        src: image.pictureLocation
                    });
                });
                
                // Update main image
                const mainImage = document.getElementById('popup-entourage-image');
                const primaryImage = displayImages.find(img => img.isPrimary == 1);
                mainImage.src = primaryImage ? primaryImage.pictureLocation : displayImages[0].pictureLocation;
                
                // Create navigation buttons
                createNavButtons();
            }
        });

        // Populate products grid
        const productsGrid = document.getElementById('entourage-products-grid');
        productsGrid.innerHTML = '';
        if (set.products && set.products.length > 0) {
            // Enhance products with preloaded data
            set.products.forEach(product => {
                // Enhance product with preloaded data including images
                product = enhanceProductWithPreloadedData(product);
                
                const productCard = document.createElement('div');
                productCard.className = 'product-card entourage-product-card';
                // Add data-id attribute for compatibility with product_details_popup.js
                productCard.setAttribute('data-id', product.productID);
                
                // Determine product status
                const statusClass = product.soldProduct ? 'sold' : product.damageProduct ? 'damaged' : 'available';
                const statusText = product.soldProduct ? 'Sold' : product.damageProduct ? 'Damaged' : 'Available';
                
                productCard.innerHTML = `
                    <div class="product-image-wrapper">
                        <img src="${product.pictureLocation || 'assets/img/default.jpg'}" 
                             alt="${product.nameProduct}"
                             onerror="this.src='assets/img/default.jpg'">
                    </div>
                    <div class="product-info">
                        <h4>${product.nameProduct}</h4>
                        <p class="price">₱${parseFloat(product.priceProduct).toFixed(2)}</p>
                        <p class="status ${statusClass}">${statusText}</p>
                        <div class="product-details">
                            <span class="product-id">ID: ${product.productID}</span>
                            ${product.sizeProduct ? `<span class="product-size">Size: ${product.sizeProduct}</span>` : ''}
                            ${product.colorProduct ? `<span class="product-color">Color: ${product.colorProduct}</span>` : ''}
                </div>
            </div>
        `;
        
                // Add click handler to product card
                productCard.addEventListener('click', (e) => {
                    // Prevent the event from bubbling to avoid triggering product_details_popup.js handler
                    e.stopPropagation();
                    e.preventDefault();
                    
                    // If already selected, don't do anything
                    if (productCard.classList.contains('selected')) {
                        return;
                    }
                    
                    // Show loading indicator for product images if we need to fetch them
                    if (!product.images || product.images.length === 0) {
                        const loadingIndicator = document.createElement('div');
                        loadingIndicator.className = 'loading-indicator';
                        loadingIndicator.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading product images...</span></div>';
                        imageContainer.appendChild(loadingIndicator);
                    }
                    
                    // Mark this product as selected
                    document.querySelectorAll('.product-card').forEach(card => {
                        card.classList.remove('selected');
                    });
                    productCard.classList.add('selected');
                    
                    // Update selected product reference
                    selectedProduct = product;
                    
                    // Use a debounce mechanism to prevent multiple rapid API calls
                    if (window.productImageFetchTimeout) {
                        clearTimeout(window.productImageFetchTimeout);
                    }
                    
                    window.productImageFetchTimeout = setTimeout(() => {
                        // If we already have preloaded images, use them directly
                        if (product.images && product.images.length > 0) {
                            displayProductImages(product, product.images);
                        } else {
                            // Otherwise fetch product images from server
                            getProductImages(product.productID)
                                .then(images => {
                                    // Store and display the images
                                    displayProductImages(product, images);
                                });
                        }
                    }, 300); // 300ms debounce to prevent multiple rapid calls
                });
                
                productsGrid.appendChild(productCard);
            });
        }
        
        // Function to display product images after fetching or from preloaded data
        function displayProductImages(product, images) {
            // Remove loading indicator if it exists
            const loadingIndicator = modal.querySelector('.loading-indicator');
            if (loadingIndicator) loadingIndicator.remove();
            
            // Store images in product object
            product.images = images;
            
            // Reset current index
            currentImageIndex = 0;
            
            // Clear and populate thumbnails
            thumbnailsContainer.innerHTML = '';
            
            // If we have product images, display them
            if (images.length > 0) {
                images.forEach((image, index) => {
                    const thumb = document.createElement('img');
                    thumb.src = image.pictureLocation;
                    thumb.alt = 'Product Thumbnail';
                    thumb.classList.toggle('active', index === 0);
                    thumb.onclick = (e) => {
                        e.stopPropagation();
                        currentImageIndex = index;
                        updateMainImage();
                    };
                    thumbnailsContainer.appendChild(thumb);
                });
                
                // Set main image to first product image or primary image if available
                const mainImage = document.getElementById('popup-entourage-image');
                const primaryImage = images.find(img => img.isPrimary == 1);
                mainImage.src = primaryImage ? primaryImage.pictureLocation : images[0].pictureLocation;
            } else {
                // Fallback to product's main image if no images found
                const mainImage = document.getElementById('popup-entourage-image');
                mainImage.src = product.pictureLocation || 'assets/img/default.jpg';
                
                // Create a thumbnail for the main image
                const thumb = document.createElement('img');
                thumb.src = product.pictureLocation || 'assets/img/default.jpg';
                thumb.alt = 'Product Thumbnail';
                thumb.classList.add('active');
                thumbnailsContainer.appendChild(thumb);
                
                // Create a single image object for the product
                product.images = [{
                    pictureLocation: product.pictureLocation || 'assets/img/default.jpg'
                }];
            }
            
            // Update product name in modal title
            document.getElementById('popup-entourage-name').textContent = product.nameProduct;
            
            // Show back button
            backButton.style.display = 'block';
            
            // Update navigation buttons for product images
            createNavButtons();
            
            // Update image counter
            updateMainImage();
        }
        
        // Add back button functionality
        backButton.onclick = (e) => {
            e.stopPropagation();
            
            // Unselect any selected product
            document.querySelectorAll('.product-card').forEach(card => {
                card.classList.remove('selected');
            });
            selectedProduct = null;
            currentImageIndex = originalEntourage.currentIndex;
            
            // Get direct entourage images only
            const entourageOnlyImages = set.images.filter(img => !img.fromProduct);
            const displayImages = entourageOnlyImages.length > 0 ? entourageOnlyImages : set.images;
            set.displayImages = displayImages;
            
            // Restore entourage information
            document.getElementById('popup-entourage-image').src = originalEntourage.image;
            document.getElementById('popup-entourage-name').textContent = originalEntourage.name;
            document.getElementById('popup-entourage-count').textContent = originalEntourage.count;
            document.getElementById('popup-entourage-status').textContent = originalEntourage.status;
            
            // Restore thumbnails - but only for direct entourage images
            thumbnailsContainer.innerHTML = '';
            displayImages.forEach((image, index) => {
                const thumb = document.createElement('img');
                thumb.src = image.pictureLocation;
                thumb.alt = 'Thumbnail';
                thumb.classList.toggle('active', index === currentImageIndex);
                thumb.onclick = () => {
                    currentImageIndex = index;
                    updateMainImage();
                };
                thumbnailsContainer.appendChild(thumb);
            });
            
            // Hide back button
            backButton.style.display = 'none';
            
            // Update navigation buttons
            createNavButtons();
            
            // Update image counter
            updateMainImage();
        };

        // Add close button functionality
        const closeBtn = modal.querySelector('.close-popup');
        closeBtn.onclick = () => {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        };

        // Close modal when clicking outside
        window.onclick = (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        };
    }
    
    // Function to display error messages with diagnostic options
    function displayDiagnosticError(message) {
        const container = document.getElementById('entourage-products-container');
        if (container) {
            container.innerHTML = `
                <div class="col-12 text-center">
                    <div class="alert alert-danger" role="alert">
                        <p>${message}</p>
                        <hr>
                        <p class="mb-0">Possible issues:</p>
                        <ul class="list-unstyled">
                            <li>- Database connection error</li>
                            <li>- No entourage data in database</li>
                            <li>- SQL query error</li>
                        </ul>
                        <div class="mt-3">
                            <a href="assets/controllers/test_entourage_data.php" target="_blank" class="btn btn-sm btn-info">
                                Run Diagnostic Test
                            </a>
                            <button onclick="fetchEntourageSets(1)" class="btn btn-sm btn-primary ms-2">
                                Try Again
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
    }
    
    // Function to display simple error messages (used by other functions)
    function displayError(message) {
        const container = document.getElementById('entourage-products-container');
        if (container) {
            container.innerHTML = `
                <div class="col-12 text-center">
                    <div class="alert alert-danger" role="alert">
                        ${message}
                    </div>
                </div>
            `;
        }
    }
    
    // Handle browser back/forward navigation
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.page === 'entourage') {
            loadEntouragePage();
        } else {
            // Navigate to home without reloading to preserve our preloaded data
            // Store entourage cache in sessionStorage before navigating
            if (window.entourageCache && window.entourageCache.isPreloaded) {
                sessionStorage.setItem('entourageCache', JSON.stringify(window.entourageCache));
                console.log('Stored entourage cache in sessionStorage before navigation');
            }
            
            // Navigate to home
            window.location.href = 'http://localhost:3000/home.php';
        }
    });
}); 

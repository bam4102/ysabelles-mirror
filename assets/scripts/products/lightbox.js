// Lightbox module for product images
import { normalizeImageUrl } from './utils.js';

// State variables for lightbox
let currentLightboxImages = [];
let currentImageIndex = 0;
let currentProduct = null;

/**
 * Initialize lightbox listeners and setup
 */
export function initLightbox() {
    // Set up click listener for product thumbnails
    document.addEventListener('click', function(e) {
        if (e.target && e.target.matches('.product-thumbnail')) {
            const productId = e.target.getAttribute('data-product-id');
            
            if (productId) {
                openProductLightbox(productId);
            } else {
                // Fallback to just showing the clicked image
                const imageUrl = e.target.src;
                const productName = e.target.alt;
                showSingleImageLightbox(imageUrl, productName);
            }
        }
    });
    
    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('imageLightboxModal').classList.contains('show')) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('imageLightboxModal'));
            if (modal) modal.hide();
        }
    });
}

/**
 * Opens the lightbox with all images for a specific product
 * @param {string|number} productId - The product ID to show images for
 */
export function openProductLightbox(productId) {
    // Show loading state in modal
    const lightboxModal = new bootstrap.Modal(document.getElementById('imageLightboxModal'));
    document.getElementById('imageLightboxModalLabel').textContent = 'Loading...';
    document.getElementById('lightboxImage').src = './assets/img/placeholder.jpg';
    lightboxModal.show();
    
    // First try to get from cached products in window.allProducts
    const cachedProduct = window.allProducts?.find(p => p.productID == productId);
    
    if (cachedProduct) {
        displayProductInLightbox(cachedProduct);
    } else {
        // Fetch product details from the server for most up-to-date data
        fetch(`assets/controllers/products/get_product_image_modal.php?product_id=${productId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.product) {
                    displayProductInLightbox(data.product);
                } else {
                    // Display error
                    document.getElementById('imageLightboxModalLabel').textContent = 'Error loading product';
                    document.getElementById('lightboxImage').src = './assets/img/placeholder.jpg';
                }
            })
            .catch(error => {
                console.error('Error fetching product details:', error);
                document.getElementById('imageLightboxModalLabel').textContent = 'Error loading product';
                document.getElementById('lightboxImage').src = './assets/img/placeholder.jpg';
            });
    }
}

/**
 * Displays a product in the lightbox
 * @param {Object} product - The product to display
 */
function displayProductInLightbox(product) {
    if (!product) {
        document.getElementById('imageLightboxModalLabel').textContent = 'Product not found';
        return;
    }
    
    // Store the current product
    currentProduct = product;
    
    // Normalize image URLs and set up the lightbox state with images
    if (product.images && product.images.length > 0) {
        // Create a new array with normalized URLs
        currentLightboxImages = product.images.map(img => ({
            ...img,
            url: normalizeImageUrl(img.url)
        }));
    } else {
        currentLightboxImages = [{id: 0, url: './assets/img/placeholder.jpg', isPrimary: 1}];
    }
    
    currentImageIndex = 0;
    
    // Find the primary image or default to first image
    if (Array.isArray(currentLightboxImages)) {
        const primaryIndex = currentLightboxImages.findIndex(img => 
            img.isPrimary === 1 || img.isPrimary === '1');
            
        if (primaryIndex !== -1) {
            currentImageIndex = primaryIndex;
        }
    }
    
    updateLightboxImage();
    
    // Set the title
    document.getElementById('imageLightboxModalLabel').textContent = product.nameProduct || 'Product Images';
    
    // Generate thumbnails if there are multiple images
    updateLightboxThumbnails();
    
    // Show the navigation buttons if needed
    updateNavigationVisibility();
}

/**
 * Shows a single image in the lightbox
 * @param {string} imageUrl - URL of the image to show
 * @param {string} title - Title to display in the lightbox header
 */
export function showSingleImageLightbox(imageUrl, title) {
    // Normalize the image URL before setting it
    const normalizedUrl = normalizeImageUrl(imageUrl);
    
    currentLightboxImages = [{id: 0, url: normalizedUrl, isPrimary: 1}];
    currentImageIndex = 0;
    currentProduct = null;
    
    updateLightboxImage();
    
    // Set the title
    document.getElementById('imageLightboxModalLabel').textContent = title || 'Product Image';
    
    // Hide thumbnails and navigation for single image
    document.getElementById('imageThumbnails').innerHTML = '';
    updateNavigationVisibility();
    
    // Show the modal
    const lightboxModal = new bootstrap.Modal(document.getElementById('imageLightboxModal'));
    lightboxModal.show();
}

/**
 * Updates the main image in the lightbox
 */
function updateLightboxImage() {
    if (!currentLightboxImages || currentLightboxImages.length === 0) return;
    
    const lightboxImage = document.getElementById('lightboxImage');
    const currentImage = currentLightboxImages[currentImageIndex];
    
    // Use normalized image URL
    lightboxImage.src = currentImage.url;
    lightboxImage.alt = document.getElementById('imageLightboxModalLabel').textContent;
    
    // Update counter
    document.getElementById('imageCounter').textContent = `${currentImageIndex + 1} / ${currentLightboxImages.length}`;
    
    // Highlight current thumbnail if thumbnails exist
    const thumbnails = document.querySelectorAll('.image-thumbnail');
    thumbnails.forEach((thumb, index) => {
        if (index === currentImageIndex) {
            thumb.classList.add('active');
        } else {
            thumb.classList.remove('active');
        }
    });
    
    // Adjust modal size after image loads
    lightboxImage.onload = function() {
        // Image has loaded, now we can adjust if needed
        const modalDialog = document.querySelector('#imageLightboxModal .modal-dialog');
        const modalContent = document.querySelector('#imageLightboxModal .modal-content');
        
        // Reset any previous custom sizing
        modalContent.style.maxHeight = '';
        modalContent.style.width = '';
    };
}

/**
 * Generates and renders the thumbnail images in the lightbox footer
 */
function updateLightboxThumbnails() {
    const thumbnailContainer = document.getElementById('imageThumbnails');
    thumbnailContainer.innerHTML = '';
    
    if (currentLightboxImages.length <= 1) {
        return;
    }
    
    currentLightboxImages.forEach((image, index) => {
        const thumb = document.createElement('div');
        thumb.className = 'image-thumbnail' + (index === currentImageIndex ? ' active' : '');
        // Use the normalized URL
        thumb.innerHTML = `<img src="${image.url}" alt="Thumbnail ${index + 1}">`;
        thumb.setAttribute('data-index', index);
        
        thumb.addEventListener('click', function() {
            currentImageIndex = parseInt(this.getAttribute('data-index'));
            updateLightboxImage();
        });
        
        thumbnailContainer.appendChild(thumb);
    });
}

/**
 * Updates the visibility of navigation controls based on the number of images
 */
function updateNavigationVisibility() {
    const prevBtn = document.getElementById('prevImage');
    const nextBtn = document.getElementById('nextImage');
    const counter = document.getElementById('imageCounter');
    
    if (currentLightboxImages.length <= 1) {
        prevBtn.style.display = 'none';
        nextBtn.style.display = 'none';
        counter.style.display = 'none';
    } else {
        prevBtn.style.display = 'block';
        nextBtn.style.display = 'block';
        counter.style.display = 'block';
        
        // Add click handlers for navigation
        prevBtn.onclick = function() {
            currentImageIndex = (currentImageIndex - 1 + currentLightboxImages.length) % currentLightboxImages.length;
            updateLightboxImage();
        };
        
        nextBtn.onclick = function() {
            currentImageIndex = (currentImageIndex + 1) % currentLightboxImages.length;
            updateLightboxImage();
        };
        
        // Add keyboard navigation
        document.onkeydown = function(e) {
            if (!document.getElementById('imageLightboxModal').classList.contains('show')) {
                return;
            }
            
            if (e.key === 'ArrowLeft') {
                prevBtn.click();
            } else if (e.key === 'ArrowRight') {
                nextBtn.click();
            }
        };
    }
} 
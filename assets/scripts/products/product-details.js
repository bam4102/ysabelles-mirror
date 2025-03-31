// Product details module
import { getProductStatus, normalizeImageUrl } from './utils.js';
import { initializeImageHandlers } from './modules/image-handlers.js';
import { initializeFormHandlers } from './modules/form-handlers.js';
import { initializeCategoryHandlers } from './modules/category-handlers.js';

/**
 * Show product edit form in a modal
 * @param {string|number} productId - The product ID
 * @param {Array} allProducts - All products array
 * @param {HTMLElement} modalElement - The modal element
 * @param {HTMLElement} contentElement - The content container element
 * @param {Object} bootstrapModal - The Bootstrap modal object
 */
export function showProductDetails(productId, allProducts, modalElement, contentElement, bootstrapModal) {
    const product = allProducts.find(p => p.productID == productId);
    
    if (!product) {
        contentElement.innerHTML = '<div class="alert alert-danger">Product not found</div>';
        bootstrapModal.show();
        return;
    }
    
    // Normalize image URLs in product object before rendering
    if (product.images && Array.isArray(product.images)) {
        product.images = product.images.map(img => ({
            ...img,
            url: normalizeImageUrl(img.url)
        }));
    }
    
    // Set modal title with status badges
    const modalTitle = document.getElementById('editProductModalLabel');
    const statusBadges = getProductStatus(product);
    
    modalTitle.innerHTML = `Edit Product: ${product.nameProduct || 'Details'} <div class="product-status-badges ms-2">${statusBadges}</div>`;
    
    // Apply flex styling to the modal title to align badges properly
    modalTitle.style.display = 'flex';
    modalTitle.style.alignItems = 'center';
    modalTitle.style.flexWrap = 'wrap';
    modalTitle.style.gap = '0.5rem';
    
    // Build the size variations section if applicable
    let variationsHtml = '';
    if (product.variationGroupId !== null && Array.isArray(product.variations) && product.variations.length > 1) {
        variationsHtml = `
            <div class="row mt-3">
                <div class="col-12">
                    <div class="variations-container">
                        <h5 class="variation-heading">Available Sizes</h5>
                        <div class="variation-sizes">
                            ${product.variations.map(variation => {
                                const isActive = variation.product_id == productId;
                                return `
                                    <div class="variation-size${isActive ? ' active' : ''}" 
                                        data-product-id="${variation.product_id}"
                                        title="Edit product with size ${variation.sizeProduct || 'Unknown'}">
                                        ${variation.sizeProduct || 'Unknown Size'}
                                    </div>
                                `;
                            }).join('')}
                        </div>
                        <div class="mt-2 variation-tip">
                            <small class="text-muted">Click on a size to edit that product</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Load the form from the PHP view
    fetch(`assets/controllers/products/load_product_form.php?productId=${productId}`)
        .then(response => response.text())
        .then(html => {
            contentElement.innerHTML = html;
            
            // Show the modal
            bootstrapModal.show();
            
            // Initialize all handlers
            initializeImageHandlers(product, contentElement, productId);
            initializeFormHandlers(product, allProducts, contentElement, bootstrapModal);
            initializeCategoryHandlers(product, contentElement);
            
            // Add click handlers for size variation buttons
            const variationButtons = contentElement.querySelectorAll('.variation-size');
            variationButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const variantId = this.getAttribute('data-product-id');
                    if (variantId) {
                        // Update the active state visually
                        contentElement.querySelectorAll('.variation-size').forEach(btn => {
                            btn.classList.remove('active');
                        });
                        this.classList.add('active');
                        
                        // If it's the current product, no need to reload
                        if (variantId == productId) {
                            return;
                        }
                        
                        // Reload the modal with the selected variation
                        showProductDetails(variantId, allProducts, modalElement, contentElement, bootstrapModal);
                    }
                });
            });
        })
        .catch(error => {
            console.error('Error loading form:', error);
            contentElement.innerHTML = '<div class="alert alert-danger">Failed to load product edit form</div>';
            bootstrapModal.show();
        });
}

function generateFormHtml(product, variationsHtml) {
    return `
        <form id="editProductForm" class="needs-validation" novalidate>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="product-image-container">
                        <div class="current-images mb-3">
                            <h6>Current Photos (${product.images.length}/5)</h6>
                            <div class="d-flex flex-wrap gap-2">
                                ${product.images.map((image, index) => `
                                    <div class="position-relative product-image-wrapper">
                                        <img src="${image.url}" alt="${product.nameProduct || 'Product'}" 
                                             class="img-thumbnail product-image-detail" style="width: 100px; height: 100px; object-fit: cover;">
                                        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 delete-image" 
                                                data-image-id="${image.id}" ${image.isPrimary ? 'disabled' : ''}>
                                            <i class="bi bi-x"></i>
                                        </button>
                                        ${image.isPrimary ? '<span class="badge bg-primary position-absolute bottom-0 start-0 m-1">Primary</span>' : ''}
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        ${product.images.length < 5 ? `
                            <div class="mb-3">
                                <div class="d-flex align-items-start gap-2 mb-2">
                                    <div class="flex-grow-1">
                                        <div class="input-group">
                                            <input type="file" class="form-control" id="newImages" name="newImages[]" 
                                                accept="image/*" style="display: none;" data-max-files="${5 - product.images.length}">
                                            <button type="button" class="btn btn-primary" id="addImageBtn">
                                                <i class="bi bi-plus-lg"></i> Add Photo
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div id="imagePreviewContainer" class="d-flex flex-wrap gap-2 mt-2"></div>
                            </div>
                        ` : ''}
                    </div>
                </div>
                <div class="col-md-4">
                    <h5>Basic Information</h5>
                    <div class="mb-3">
                        <label for="productName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="productName" name="nameProduct" value="${product.nameProduct || ''}" required>
                    </div>
                    <div class="mb-3">
                        <label for="productGender" class="form-label">Type</label>
                        <select class="form-select" id="productGender" name="genderProduct" required>
                            <option value="">Select Type</option>
                            <option value="MENSWEAR" ${product.genderProduct === 'MENSWEAR' ? 'selected' : ''}>MENSWEAR</option>
                            <option value="WOMENSWEAR" ${product.genderProduct === 'WOMENSWEAR' ? 'selected' : ''}>WOMENSWEAR</option>
                            <option value="BOYS" ${product.genderProduct === 'BOYS' ? 'selected' : ''}>BOYS</option>
                            <option value="GIRLS" ${product.genderProduct === 'GIRLS' ? 'selected' : ''}>GIRLS</option>
                            <option value="WEDDING" ${product.genderProduct === 'WEDDING' ? 'selected' : ''}>WEDDING</option>
                            <option value="ACCESSORIES" ${product.genderProduct === 'ACCESSORIES' ? 'selected' : ''}>ACCESSORIES</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="productCategory" class="form-label">Category</label>
                        <select class="form-select" id="productCategory" name="categoryID" required>
                            <option value="">Select Category</option>
                            ${window.categories ? window.categories
                                .filter(cat => !product.genderProduct || cat.genderCategory === product.genderProduct)
                                .map(cat => `
                                    <option value="${cat.categoryID}" 
                                            ${cat.categoryID == product.categoryID ? 'selected' : ''}
                                            data-code="${cat.categoryCode}">
                                        ${cat.productCategory} (${cat.categoryCode})
                                    </option>
                                `).join('') : ''}
                         </select>
                        <input type="hidden" name="typeProduct" value="${product.typeProduct || ''}">
                    </div>
                    <div class="mb-3">
                        <label for="productLocation" class="form-label">Location</label>
                        <select class="form-select" id="productLocation" name="locationProduct" required>
                            <option value="">Select Location</option>
                            <option value="BACOLOD CITY" ${product.locationProduct === 'BACOLOD CITY' ? 'selected' : ''}>BACOLOD CITY</option>
                            <option value="DUMAGUETE CITY" ${product.locationProduct === 'DUMAGUETE CITY' ? 'selected' : ''}>DUMAGUETE CITY</option>
                            <option value="ILOILO CITY" ${product.locationProduct === 'ILOILO CITY' ? 'selected' : ''}>ILOILO CITY</option>
                            <option value="SAN CARLOS CITY" ${product.locationProduct === 'SAN CARLOS CITY' ? 'selected' : ''}>SAN CARLOS CITY</option>
                            <option value="CEBU CITY" ${product.locationProduct === 'CEBU CITY' ? 'selected' : ''}>CEBU CITY</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <h5>Additional Details</h5>
                    <div class="mb-3">
                        <label for="productCode" class="form-label">Code</label>
                        <input type="text" class="form-control" id="productCode" name="codeProduct" value="${product.codeProduct || ''}">
                    </div>
                    <div class="mb-3">
                        <label for="productPrice" class="form-label">Price</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="productPrice" name="priceProduct" value="${product.priceProduct || ''}" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="productSize" class="form-label">Size</label>
                        <input type="text" class="form-control" id="productSize" name="sizeProduct" value="${product.sizeProduct || ''}">
                    </div>
                    <div class="mb-3">
                        <label for="bustProduct" class="form-label">Bust</label>
                        <input type="text" class="form-control" id="bustProduct" name="bustProduct" value="${product.bustProduct || ''}">
                    </div>
                    <div class="mb-3">
                        <label for="waistProduct" class="form-label">Waist</label>
                        <input type="text" class="form-control" id="waistProduct" name="waistProduct" value="${product.waistProduct || ''}">
                    </div>
                    <div class="mb-3">
                        <label for="lengthProduct" class="form-label">Length</label>
                        <input type="text" class="form-control" id="lengthProduct" name="lengthProduct" value="${product.lengthProduct || ''}">
                    </div>
                    <div class="mb-3">
                        <label for="productColor" class="form-label">Color</label>
                        <input type="text" class="form-control" id="productColor" name="colorProduct" value="${product.colorProduct || ''}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="statusDamaged" name="damageProduct" value="1" ${product.damageProduct == 1 ? 'checked' : ''}>
                            <label class="form-check-label" for="statusDamaged">
                                Damaged
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="statusSold" name="soldProduct" value="1" ${product.soldProduct == 1 ? 'checked' : ''}>
                            <label class="form-check-label" for="statusSold">
                                Sold
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="statusReturned" name="returnedProduct" value="1" ${product.returnedProduct == 1 ? 'checked' : ''}>
                            <label class="form-check-label" for="statusReturned">
                                Released
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="statusNew" name="isNew" value="1" ${product.isNew == 1 ? 'checked' : ''}>
                            <label class="form-check-label" for="statusNew">
                                New
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            ${variationsHtml}
            <div class="row mt-3">
                <div class="col-12">
                    <div class="mb-3">
                        <label for="productDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="productDescription" name="descProduct" rows="3">${product.descProduct || ''}</textarea>
                    </div>
                </div>
            </div>
            <input type="hidden" name="productID" value="${product.productID}">
        </form>
    `;
} 
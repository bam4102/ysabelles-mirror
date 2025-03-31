import { normalizeImageUrl } from '../utils.js';

/**
 * Converts a data URL to a File object
 * @param {string} dataURL - The data URL to convert
 * @param {string} fileName - The name to give the file
 * @returns {File} A File object created from the data URL
 */
export function dataURLtoFile(dataURL, fileName) {
    // Convert base64/URLEncoded data component to raw binary data
    let arr = dataURL.split(',');
    let mime = arr[0].match(/:(.*?);/)[1];
    let bstr = atob(arr[1]);
    let n = bstr.length;
    let u8arr = new Uint8Array(n);
    
    // Convert binary string to Uint8Array
    while (n--) {
        u8arr[n] = bstr.charCodeAt(n);
    }
    
    // Create File object
    return new File([u8arr], fileName, { type: mime });
}

/**
 * Initialize all image-related handlers
 * @param {Object} product - The product being edited
 * @param {HTMLElement} contentElement - The modal content element
 * @param {string|number} productId - The product ID
 */
export function initializeImageHandlers(product, contentElement, productId) {
    initializeImageLightbox(product, contentElement, productId);
    initializeImageDeletion(product, contentElement);
    initializeImageUpload(product, contentElement);
}

function initializeImageLightbox(product, contentElement, productId) {
    const detailImages = contentElement.querySelectorAll('.product-image-detail');
    if (detailImages && detailImages.length > 0) {
        detailImages.forEach(img => {
            // Make sure the image src is properly normalized
            img.src = normalizeImageUrl(img.src);
            
            img.addEventListener('click', function() {
                if (typeof window.openProductLightbox === 'function') {
                    window.openProductLightbox(productId);
                } else {
                    // Fallback to just showing the clicked image
                    const lightboxImage = document.getElementById('lightboxImage');
                    if (lightboxImage) {
                        lightboxImage.src = normalizeImageUrl(this.src);
                        lightboxImage.alt = this.alt;
                        
                        document.getElementById('imageLightboxModalLabel').textContent = product.nameProduct || 'Product Image';
                        
                        const lightboxModal = new bootstrap.Modal(document.getElementById('imageLightboxModal'));
                        lightboxModal.show();
                    }
                }
            });
        });
    }
}

function initializeImageDeletion(product, contentElement) {
    const deleteButtons = contentElement.querySelectorAll('.delete-image');
    deleteButtons.forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            const imageId = this.getAttribute('data-image-id');
            
            if (confirm('Are you sure you want to delete this image?')) {
                try {
                    const response = await fetch('./assets/controllers/products/delete_product_image.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            pictureID: imageId,
                            productID: product.productID
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        // Instead of full page reload, update UI or refresh product data
                        if (typeof window.refreshProductTable === 'function') {
                            window.refreshProductTable();
                            // Remove the image from the current display
                            const imageContainer = button.closest('.product-image-wrapper');
                            if (imageContainer) imageContainer.remove();
                        } else {
                            // Fallback to reload if refreshProductTable is unavailable
                            window.location.reload();
                        }
                    } else {
                        showNotification('error', 'Failed to delete image: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showNotification('error', 'Failed to delete image');
                }
            }
        });
    });
}

/**
 * Shows a notification message to the user
 * @param {string} type - The type of notification (success, error, warning)
 * @param {string} message - The message to display
 */
function showNotification(type, message) {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alertDiv.setAttribute('role', 'alert');
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        <strong>${type === 'success' ? 'Success!' : type === 'error' ? 'Error!' : 'Warning!'}</strong> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.body.appendChild(alertDiv);

    // Remove alert after 3 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

function initializeImageUpload(product, contentElement) {
    const addImageBtn = contentElement.querySelector('#addImageBtn');
    const imageInput = contentElement.querySelector('#newImages');
    const previewContainer = document.getElementById('imagePreviewContainer');
    
    if (addImageBtn && imageInput) {
        // Create a Set to track filenames that have already been added
        const addedFiles = new Set();
        
        // Update the button text with remaining count initially
        const maxFiles = parseInt(imageInput.getAttribute('data-max-files')) || (5 - product.images.length);
        addImageBtn.innerHTML = `<i class="bi bi-plus-lg"></i> Add Photo (${maxFiles} remaining)`;
        
        // Create drop zone for drag and drop
        createDropZone(previewContainer, addedFiles, product, addImageBtn, maxFiles);
        
        addImageBtn.addEventListener('click', function() {
            imageInput.click();
        });

        imageInput.addEventListener('change', function() {
            // Skip if no files selected
            if (!this.files || this.files.length === 0) {
                return;
            }
            
            console.log(`File input change event fired with ${this.files.length} files`);
            
            const currentPreviews = document.querySelectorAll('#imagePreviewContainer .preview-wrapper').length;
            const remainingSlots = maxFiles - currentPreviews;
            
            if (remainingSlots <= 0) {
                showNotification('warning', 'Maximum number of images reached');
                this.value = '';
                return;
            }
            
            // Process files (up to the remaining slot count)
            const filesToProcess = Math.min(this.files.length, remainingSlots);
            for (let i = 0; i < filesToProcess; i++) {
                processImageFile(this.files[i], addedFiles, previewContainer, product, addImageBtn, maxFiles);
            }
            
            // Clear the input for the next selection
            this.value = '';
        });
    }
}

/**
 * Creates a drop zone for drag and drop file uploads
 */
function createDropZone(container, addedFiles, product, addImageBtn, maxFiles) {
    const dropZone = document.createElement('div');
    dropZone.className = 'drop-zone mt-2 mb-3';
    dropZone.innerHTML = '<div class="drop-zone-prompt">Drag & drop images here</div>';
    dropZone.style.cssText = 'border: 2px dashed #ccc; border-radius: 5px; padding: 25px; text-align: center; background-color: #f8f9fa; transition: all 0.3s ease;';
    
    // Insert before the preview container
    container.parentNode.insertBefore(dropZone, container);
    
    // Add event listeners for drag and drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    // Handle visual feedback
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight() {
        dropZone.style.borderColor = '#0d6efd';
        dropZone.style.backgroundColor = '#e9f0ff';
    }
    
    function unhighlight() {
        dropZone.style.borderColor = '#ccc';
        dropZone.style.backgroundColor = '#f8f9fa';
    }
    
    // Handle the drop event
    dropZone.addEventListener('drop', function(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        if (files.length > 0) {
            const currentPreviews = document.querySelectorAll('#imagePreviewContainer .preview-wrapper').length;
            const remainingSlots = maxFiles - currentPreviews;
            
            if (remainingSlots <= 0) {
                showNotification('warning', 'Maximum number of images reached');
                return;
            }
            
            // Process files (up to the remaining slot count)
            const filesToProcess = Math.min(files.length, remainingSlots);
            for (let i = 0; i < filesToProcess; i++) {
                processImageFile(files[i], addedFiles, container, product, addImageBtn, maxFiles);
            }
        }
    });
}

/**
 * Process an image file and add it to the preview
 */
async function processImageFile(file, addedFiles, previewContainer, product, addImageBtn, maxFiles) {
    // Check file size and type
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (file.size > maxSize) {
        showNotification('warning', `File "${file.name}" is too large. Maximum size is 5MB.`);
        return;
    }
    
    if (!allowedTypes.includes(file.type)) {
        showNotification('warning', `File "${file.name}" is not a supported image type. Please use JPEG, PNG, GIF, or WebP.`);
        return;
    }
    
    // Check if this file has already been added
    if (addedFiles.has(file.name)) {
        console.log(`Skipping duplicate file: ${file.name}`);
        return;
    }
    
    console.log(`Processing file: ${file.name}`);
    
    // Add loading indicator
    const loadingIndicator = document.createElement('div');
    loadingIndicator.className = 'spinner-border text-primary spinner-border-sm';
    loadingIndicator.setAttribute('role', 'status');
    loadingIndicator.innerHTML = '<span class="visually-hidden">Loading...</span>';
    previewContainer.appendChild(loadingIndicator);
    
    try {
        // Compress image before preview
        const compressedFile = await compressImage(file);
        const compressInfo = compressedFile.size < file.size ? 
            `(Compressed: ${formatFileSize(file.size)} → ${formatFileSize(compressedFile.size)})` : '';
        
        // Add to tracking set
        addedFiles.add(file.name);
        
        // Create preview for the selected file
        const previewWrapper = document.createElement('div');
        previewWrapper.className = 'position-relative preview-wrapper';
        previewWrapper.style.cssText = 'margin: 5px; display: inline-block;';
        
        // Create preview image container
        const preview = document.createElement('img');
        preview.className = 'img-thumbnail';
        preview.style.width = '100px';
        preview.style.height = '100px';
        preview.style.objectFit = 'cover';
        
        // Create remove button
        const removeBtn = document.createElement('button');
        removeBtn.className = 'btn btn-danger btn-sm position-absolute top-0 end-0';
        removeBtn.innerHTML = '<i class="bi bi-x"></i>';
        removeBtn.onclick = function(e) {
            e.preventDefault();
            // Remove from tracking set
            addedFiles.delete(file.name);
            previewWrapper.remove();
            
            // Update remaining count
            const remaining = maxFiles - document.querySelectorAll('#imagePreviewContainer .preview-wrapper').length;
            if (remaining > 0) {
                addImageBtn.disabled = false;
                addImageBtn.innerHTML = `<i class="bi bi-plus-lg"></i> Add Photo (${remaining} remaining)`;
            }
        };
        
        // Create info label
        const infoLabel = document.createElement('div');
        infoLabel.className = 'position-absolute bottom-0 start-0 w-100 text-white bg-dark bg-opacity-50 small px-1';
        infoLabel.style.fontSize = '8px';
        infoLabel.textContent = compressInfo;
        
        // Read and display the compressed image
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
        };
        reader.readAsDataURL(compressedFile);
        
        // Add elements to the preview wrapper
        previewWrapper.appendChild(preview);
        previewWrapper.appendChild(removeBtn);
        if (compressInfo) {
            previewWrapper.appendChild(infoLabel);
        }
        
        // Store the compressed file data with the preview
        previewWrapper.dataset.fileName = file.name;
        previewWrapper.file = compressedFile;
        
        // Remove loading indicator and add the preview
        loadingIndicator.remove();
        previewContainer.appendChild(previewWrapper);
        
        // Update remaining count
        const remaining = maxFiles - document.querySelectorAll('#imagePreviewContainer .preview-wrapper').length;
        if (remaining > 0) {
            addImageBtn.disabled = false;
            addImageBtn.innerHTML = `<i class="bi bi-plus-lg"></i> Add Photo (${remaining} remaining)`;
        } else {
            addImageBtn.disabled = true;
            addImageBtn.innerHTML = '<i class="bi bi-x"></i> Maximum Photos Reached';
        }
    } catch (error) {
        console.error('Error processing image:', error);
        loadingIndicator.remove();
        showNotification('error', `Error processing image "${file.name}"`);
    }
}

/**
 * Compresses an image file to reduce its size while maintaining quality
 * @param {File} file - The image file to compress
 * @returns {Promise<Blob>} A promise that resolves to the compressed image blob
 */
export function compressImage(file) {
    return new Promise((resolve, reject) => {
        // If file is already small (< 1MB), don't compress
        if (file.size < 1024 * 1024) {
            console.log(`Skipping compression for small file: ${file.name} (${formatFileSize(file.size)})`);
            resolve(file);
            return;
        }

        // Target max dimensions
        const maxWidth = 1200;
        const maxHeight = 1200;
        
        // Create image element
        const img = new Image();
        
        // Set up error handling with timeout
        const timeoutId = setTimeout(() => {
            img.src = ''; // Stop loading the image
            reject(new Error('Image loading timeout'));
        }, 10000); // 10-second timeout
        
        img.onload = function() {
            clearTimeout(timeoutId);
            
            try {
                // Calculate new dimensions
                let width = img.width;
                let height = img.height;
                
                console.log(`Original image dimensions: ${width}x${height}`);
                
                if (width > height) {
                    if (width > maxWidth) {
                        height *= maxWidth / width;
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width *= maxHeight / height;
                        height = maxHeight;
                    }
                }
                
                width = Math.floor(width);
                height = Math.floor(height);
                
                console.log(`Resizing to: ${width}x${height}`);
                
                // Create canvas for resizing
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                
                // Draw and compress
                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    throw new Error('Failed to get canvas context');
                }
                
                // Draw image with a white background to handle transparency properly
                ctx.fillStyle = '#FFFFFF';
                ctx.fillRect(0, 0, width, height);
                ctx.drawImage(img, 0, 0, width, height);
                
                // Get the image type from the original file
                const type = file.type;
                
                // Quality settings based on file type
                let quality = 0.85;
                if (type === 'image/jpeg' || type === 'image/webp') {
                    // JPEG and WebP can use quality parameter
                    quality = 0.7; // 70% quality is usually a good balance
                }
                
                try {
                    canvas.toBlob(
                        blob => {
                            if (!blob) {
                                reject(new Error('Failed to create image blob'));
                                return;
                            }
                            
                            // Create a new file from the blob with the original name
                            const compressedFile = new File([blob], file.name, { 
                                type: file.type,
                                lastModified: file.lastModified
                            });
                            
                            console.log(`Compression successful: ${formatFileSize(file.size)} → ${formatFileSize(compressedFile.size)}`);
                            resolve(compressedFile);
                        },
                        type,
                        quality
                    );
                } catch (blobError) {
                    console.error('Error creating blob:', blobError);
                    
                    // Fallback to DataURL approach if toBlob fails
                    try {
                        const dataURL = canvas.toDataURL(type, quality);
                        fetch(dataURL)
                            .then(res => res.blob())
                            .then(blob => {
                                const compressedFile = new File([blob], file.name, { 
                                    type: file.type,
                                    lastModified: file.lastModified
                                });
                                
                                console.log(`Fallback compression: ${formatFileSize(file.size)} → ${formatFileSize(compressedFile.size)}`);
                                resolve(compressedFile);
                            })
                            .catch(fetchErr => {
                                console.error('Fallback compression failed:', fetchErr);
                                // If all compression fails, just use the original file
                                resolve(file);
                            });
                    } catch (dataUrlError) {
                        console.error('Both compression methods failed:', dataUrlError);
                        // If all compression attempts fail, use the original
                        resolve(file);
                    }
                }
            } catch (drawError) {
                console.error('Error during canvas operations:', drawError);
                // If compression fails for any reason, return the original file
                resolve(file);
            }
        };
        
        img.onerror = function(error) {
            clearTimeout(timeoutId);
            console.error('Error loading image for compression:', error);
            // If image loading fails, use the original file
            resolve(file);
        };
        
        // Set crossOrigin to anonymous if the image is from another domain
        if (file.name.startsWith('http')) {
            img.crossOrigin = 'anonymous';
        }
        
        // Load image from file using a safe approach
        try {
            // Using createObjectURL to load the image from the file
            const objectUrl = URL.createObjectURL(file);
            img.src = objectUrl;
            
            // Clean up object URL on load or error
            const cleanup = () => {
                URL.revokeObjectURL(objectUrl);
            };
            
            img.onload = function() {
                cleanup();
                img.onload(arguments[0]); // Call the original onload
            };
            
            img.onerror = function() {
                cleanup();
                img.onerror(arguments[0]); // Call the original onerror
            };
        } catch (urlError) {
            console.error('Error creating object URL:', urlError);
            reject(urlError);
        }
    });
}

/**
 * Formats a file size in bytes to a human-readable string (KB, MB)
 * @param {number} bytes - The file size in bytes
 * @returns {string} Formatted file size with units
 */
export function formatFileSize(bytes) {
    if (bytes < 1024) {
        return bytes + ' bytes';
    } else if (bytes < 1024 * 1024) {
        return (bytes / 1024).toFixed(1) + ' KB';
    } else {
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }
} 
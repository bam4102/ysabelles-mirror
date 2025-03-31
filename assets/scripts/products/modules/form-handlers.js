// Import needed functions from image-handlers.js
import { dataURLtoFile, compressImage, formatFileSize } from './image-handlers.js';

/**
 * Initialize form-related handlers
 * @param {Object} product - The product being edited
 * @param {Array} allProducts - All products array
 * @param {HTMLElement} contentElement - The modal content element
 * @param {Object} bootstrapModal - The Bootstrap modal object
 */
export function initializeFormHandlers(product, allProducts, contentElement, bootstrapModal) {
    const saveButton = document.getElementById('saveProductChanges');
    if (saveButton) {
        saveButton.addEventListener('click', async function() {
            const form = document.getElementById('editProductForm');
            if (!form) return;

            // Check form validity
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Disable save button and show loading state
            saveButton.disabled = true;
            saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

            try {
                await handleFormSubmission(product, allProducts, form, bootstrapModal);
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to update product: ' + error.message);
            } finally {
                // Re-enable save button
                saveButton.disabled = false;
                saveButton.textContent = 'Save Changes';
            }
        });
    }
}

async function handleFormSubmission(product, allProducts, form, bootstrapModal) {
    // Handle the regular form data
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // Set status flags
    // Ensure boolean values for checkbox fields (if not checked, value will be undefined)
    data.damageProduct = formData.has('damageProduct') ? 1 : 0;
    data.soldProduct = formData.has('soldProduct') ? 1 : 0;
    data.useProduct = formData.has('useProduct') ? 1 : 0;
    data.returnedProduct = formData.has('returnedProduct') ? 1 : 0;
    
    try {
        // Update product details
        const response = await fetch('./assets/controllers/products/update_product.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        // Check for image previews first
        const previewContainer = document.getElementById('imagePreviewContainer') || document.getElementById('image-previews');
        const previews = previewContainer ? previewContainer.querySelectorAll('.preview-wrapper, .image-preview') : [];
        
        if (previews.length > 0) {
            // Handle image uploads if any
            console.log(`Found ${previews.length} images to upload`);
            await handleImageUploads(data.productID);
        } else {
            console.log('No images to upload');
        }

        if (result.success) {
            // Find and update the product in the allProducts array to reflect changes immediately
            const productIndex = allProducts.findIndex(p => p.productID == data.productID);
            if (productIndex !== -1) {
                // Update all the fields including status
                allProducts[productIndex] = {
                    ...allProducts[productIndex],
                    ...data,
                    damageProduct: parseInt(data.damageProduct),
                    soldProduct: parseInt(data.soldProduct),
                    useProduct: parseInt(data.useProduct),
                    returnedProduct: parseInt(data.returnedProduct)
                };
            }
            
            // Now handle the successful update
            await handleSuccessfulUpdate(data, allProducts, bootstrapModal);
        } else {
            throw new Error(result.message || 'Failed to update product');
        }
    } catch (error) {
        console.error('Error updating product:', error);
        throw error; // Re-throw to be caught in the calling function
    }
}

/**
 * Handles the image uploads for a product
 * @param {number} productID - The ID of the product to upload images for
 * @returns {Promise} A promise that resolves when all uploads are complete
 */
async function handleImageUploads(productID) {
    return new Promise((resolve, reject) => {
        // Get all image previews - try both possible container IDs
        let previewContainer = document.getElementById('image-previews');
        if (!previewContainer) {
            // Try the alternative ID used in image-handlers.js
            previewContainer = document.getElementById('imagePreviewContainer');
            if (!previewContainer) {
                console.error('Preview container not found - checked both "image-previews" and "imagePreviewContainer"');
                reject(new Error('Preview container not found'));
                return;
            }
        }
        
        const previews = previewContainer.querySelectorAll('.image-preview, .preview-wrapper');
        if (previews.length === 0) {
            console.log('No images to upload');
            resolve([]);
            return;
        }
        
        // Create progress container if it doesn't exist
        let progressContainer = document.getElementById('upload-progress-container');
        if (!progressContainer) {
            progressContainer = document.createElement('div');
            progressContainer.id = 'upload-progress-container';
            progressContainer.className = 'my-3 p-3 border rounded';
            previewContainer.insertAdjacentElement('afterend', progressContainer);
        }
        
        progressContainer.innerHTML = `
            <div class="d-flex justify-content-between mb-2">
                <span>Uploading images...</span>
                <span id="upload-progress-text">0%</span>
            </div>
            <div class="progress mb-3">
                <div id="upload-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" 
                     role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div id="upload-status" class="small text-muted"></div>
        `;
        
        // Create form data for upload
        const formData = new FormData();
        formData.append('product_id', productID);
        
        console.log('Creating upload with product ID:', productID);
        
        // Track unique filenames to prevent duplicates
        const fileNames = new Set();
        let totalSize = 0;
        let fileCount = 0;
        
        // Compress and add each file to form data
        const compressPromises = [];
        
        previews.forEach(preview => {
            let file = null;
            
            // Try to get file data from different storage methods
            if (preview.dataset.file) {
                // Method 1: Stored as JSON string in data-file attribute
                try {
                    const fileData = JSON.parse(preview.dataset.file);
                    file = dataURLtoFile(fileData.dataURL, fileData.name);
                } catch (error) {
                    console.error('Error parsing file data:', error);
                }
            } else if (preview.file) {
                // Method 2: Direct file property (used in image-handlers.js)
                file = preview.file;
            } else if (preview.querySelector('img')) {
                // Method 3: Get from image src if all else fails
                const img = preview.querySelector('img');
                const fileName = preview.dataset.fileName || `image_${Date.now()}.jpg`;
                try {
                    // Only try to extract if it's a data URL
                    if (img.src.startsWith('data:')) {
                        file = dataURLtoFile(img.src, fileName);
                    }
                } catch (error) {
                    console.error('Error extracting file from image:', error);
                }
            }
            
            if (file) {
                // Check if this filename is already being uploaded
                if (fileNames.has(file.name)) {
                    console.warn(`Duplicate filename: ${file.name} - skipping`);
                    return;
                }
                
                fileNames.add(file.name);
                totalSize += file.size;
                fileCount++;
                
                // Compress the image before uploading
                compressPromises.push(
                    compressImage(file)
                        .then(compressedFile => {
                            formData.append('images[]', compressedFile);
                            return compressedFile;
                        })
                        .catch(err => {
                            console.error(`Error compressing ${file.name}:`, err);
                            // Use original file if compression fails
                            formData.append('images[]', file);
                            return file;
                        })
                );
            }
        });
        
        // Proceed with upload after all files are processed
        Promise.all(compressPromises)
            .then(files => {
                if (files.length === 0) {
                    throw new Error('No valid files to upload');
                }
                
                // Update status with file info
                const statusElem = document.getElementById('upload-status');
                if (statusElem) {
                    statusElem.textContent = `Uploading ${files.length} files (${formatFileSize(totalSize)})`;
                }
                
                // Create and configure XHR for upload
                const xhr = new XMLHttpRequest();
                
                // Set up progress tracking
                xhr.upload.addEventListener('progress', (event) => {
                    if (event.lengthComputable) {
                        const percentComplete = Math.round((event.loaded / event.total) * 100);
                        const progressBar = document.getElementById('upload-progress-bar');
                        const progressText = document.getElementById('upload-progress-text');
                        
                        if (progressBar) progressBar.style.width = percentComplete + '%';
                        if (progressBar) progressBar.setAttribute('aria-valuenow', percentComplete);
                        if (progressText) progressText.textContent = percentComplete + '%';
                        
                        if (statusElem && event.loaded < event.total) {
                            statusElem.textContent = `Uploaded ${formatFileSize(event.loaded)} of ${formatFileSize(event.total)}`;
                        } else if (statusElem) {
                            statusElem.textContent = 'Processing upload...';
                        }
                    }
                });
                
                // Set up completion handler
                xhr.addEventListener('load', () => {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        let response;
                        
                        try {
                            // Attempt to parse the response as JSON
                            const responseText = xhr.responseText;
                            
                            // Log the raw response for debugging
                            console.log('Server response:', responseText);
                            
                            // Reject if empty response
                            if (!responseText || responseText.trim() === '') {
                                // Update UI to show error
                                progressContainer.innerHTML = `
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-circle"></i> Server returned an empty response
                                        <button class="btn btn-sm btn-outline-danger ms-3" id="retry-upload">
                                            <i class="fas fa-redo"></i> Retry
                                        </button>
                                    </div>
                                    <div class="small text-danger mt-2">
                                        The server did not return a properly formatted response. This may indicate a server-side error.
                                    </div>
                                `;
                                
                                // Add retry button handler
                                const retryBtn = document.getElementById('retry-upload');
                                if (retryBtn) {
                                    retryBtn.addEventListener('click', () => {
                                        handleImageUploads(productID)
                                            .then(resolve)
                                            .catch(reject);
                                    });
                                }
                                
                                reject(new Error('Server returned an empty response'));
                                return;
                            }
                            
                            response = JSON.parse(responseText);
                        } catch (error) {
                            console.error('Error parsing server response:', error);
                            console.log('Raw server response:', xhr.responseText);
                            
                            // Update UI to show parse error
                            progressContainer.innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle"></i> Failed to parse server response
                                    <button class="btn btn-sm btn-outline-danger ms-3" id="retry-upload">
                                        <i class="fas fa-redo"></i> Retry
                                    </button>
                                </div>
                                <div class="small text-danger mt-2">
                                    The server response could not be processed. Response: ${xhr.responseText ? xhr.responseText.substring(0, 100) + '...' : 'Empty response'}
                                </div>
                            `;
                            
                            // Add retry button handler
                            const retryBtn = document.getElementById('retry-upload');
                            if (retryBtn) {
                                retryBtn.addEventListener('click', () => {
                                    handleImageUploads(productID)
                                        .then(resolve)
                                        .catch(reject);
                                });
                            }
                            
                            reject(new Error('Invalid response format from server'));
                            return;
                        }
                        
                        // Update UI based on response
                        if (response.success) {
                            // Show success message
                            showAlert('success', response.message);
                            
                            // Clean up previews and progress
                            clearImagePreviews();
                            progressContainer.innerHTML = `
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> ${response.message}
                                </div>
                            `;
                            
                            // Enable the add image button
                            const addButton = document.querySelector('button[data-action="add-image"]');
                            if (addButton) {
                                addButton.disabled = false;
                            }
                            
                            // Resolve with uploaded files
                            resolve(response.uploaded || []);
                        } else {
                            // Show error message for failed uploads
                            const errorMsg = response.message || 'Upload failed';
                            
                            progressContainer.innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle"></i> ${errorMsg}
                                    <button class="btn btn-sm btn-outline-danger ms-3" id="retry-upload">
                                        <i class="fas fa-redo"></i> Retry
                                    </button>
                                </div>
                                <div class="small text-danger mt-2">
                                    ${response.failed ? response.failed.map(f => `${f.name}: ${f.error}`).join('<br>') : ''}
                                </div>
                            `;
                            
                            // Add retry button handler
                            const retryBtn = document.getElementById('retry-upload');
                            if (retryBtn) {
                                retryBtn.addEventListener('click', () => {
                                    handleImageUploads(productID)
                                        .then(resolve)
                                        .catch(reject);
                                });
                            }
                            
                            reject(new Error(errorMsg));
                        }
                    } else {
                        // Handle HTTP errors
                        let errorMessage = `Server error (${xhr.status})`;
                        let response = null;
                        
                        try {
                            response = JSON.parse(xhr.responseText);
                            errorMessage = response.message || errorMessage;
                        } catch (e) {
                            console.error('Error parsing error response:', e);
                            console.log('Raw error response:', xhr.responseText);
                        }
                        
                        progressContainer.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> ${errorMessage}
                                <button class="btn btn-sm btn-outline-danger ms-3" id="retry-upload">
                                    <i class="fas fa-redo"></i> Retry
                                </button>
                            </div>
                        `;
                        
                        // Add retry button handler
                        const retryBtn = document.getElementById('retry-upload');
                        if (retryBtn) {
                            retryBtn.addEventListener('click', () => {
                                handleImageUploads(productID)
                                    .then(resolve)
                                    .catch(reject);
                            });
                        }
                        
                        reject(new Error(errorMessage));
                    }
                });
                
                // Set up error handler
                xhr.addEventListener('error', () => {
                    progressContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> Network error during upload
                            <button class="btn btn-sm btn-outline-danger ms-3" id="retry-upload">
                                <i class="fas fa-redo"></i> Retry
                            </button>
                        </div>
                        <div class="small text-danger mt-2">
                            The connection to the server was lost or failed. Please check your internet connection and try again.
                        </div>
                    `;
                    
                    // Add retry button handler
                    const retryBtn = document.getElementById('retry-upload');
                    if (retryBtn) {
                        retryBtn.addEventListener('click', () => {
                            handleImageUploads(productID)
                                .then(resolve)
                                .catch(reject);
                        });
                    }
                    
                    reject(new Error('Network error during upload'));
                });
                
                // Add timeout handler
                xhr.timeout = 60000; // 60 seconds timeout
                xhr.addEventListener('timeout', () => {
                    progressContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> Upload timed out
                            <button class="btn btn-sm btn-outline-danger ms-3" id="retry-upload">
                                <i class="fas fa-redo"></i> Retry
                            </button>
                        </div>
                        <div class="small text-danger mt-2">
                            The server took too long to respond. This may indicate network issues or server overload.
                        </div>
                    `;
                    
                    // Add retry button handler
                    const retryBtn = document.getElementById('retry-upload');
                    if (retryBtn) {
                        retryBtn.addEventListener('click', () => {
                            handleImageUploads(productID)
                                .then(resolve)
                                .catch(reject);
                        });
                    }
                    
                    reject(new Error('Upload request timed out'));
                });
                
                // Send the request
                xhr.open('POST', 'assets/controllers/products/upload_product_images.php');
                // Add specific header to identify XHR requests
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send(formData);
            })
            .catch(error => {
                progressContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> ${error.message}
                    </div>
                `;
                reject(error);
            });
    });
}

async function handleSuccessfulUpdate(data, allProducts, bootstrapModal) {
    // Update the product in the local data is now done in handleFormSubmission
    // We don't need to update it here again

    // Close modal and refresh table
    bootstrapModal.hide();
    
    // Show success message
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
    alertDiv.setAttribute('role', 'alert');
    alertDiv.innerHTML = `
        <strong>Success!</strong> Product has been updated.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.body.appendChild(alertDiv);

    // Remove alert after 3 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);

    // Try to refresh the table using the global function
    try {
        if (typeof window.refreshProductTable === 'function') {
            window.refreshProductTable();
        } else {
            // If the function doesn't exist, try to refresh manually
            console.warn('refreshProductTable function not available, attempting manual refresh');
            if (typeof window.renderTable === 'function') {
                // This assumes we can access the state object and other required elements
                const state = { 
                    allProducts: allProducts,
                    filteredProducts: [...allProducts]
                };
                const tableBody = document.getElementById('productTableBody');
                if (tableBody) {
                    window.renderTable(state, tableBody);
                }
            }
        }
    } catch (error) {
        console.error('Error refreshing product table:', error);
        // Try to force a page reload as last resort
        // location.reload();
    }
}

/**
 * Displays an alert message to the user
 * @param {string} type - The type of alert (success, danger, warning, info)
 * @param {string} message - The message to display
 * @param {number} [timeout=5000] - Time in milliseconds before the alert auto-dismisses
 */
function showAlert(type, message, timeout = 5000) {
    // Create alert container if it doesn't exist
    let alertContainer = document.getElementById('alert-container');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'alert-container';
        alertContainer.className = 'position-fixed top-0 end-0 p-3';
        alertContainer.style.zIndex = '1050';
        document.body.appendChild(alertContainer);
    }
    
    // Create a unique ID for this alert
    const alertId = 'alert-' + Date.now();
    
    // Create the alert element
    const alertHTML = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Add the alert to the container
    alertContainer.insertAdjacentHTML('beforeend', alertHTML);
    
    // Set up auto-dismiss
    if (timeout > 0) {
        setTimeout(() => {
            const alertElement = document.getElementById(alertId);
            if (alertElement) {
                // Remove the alert after fading out
                alertElement.classList.remove('show');
                setTimeout(() => alertElement.remove(), 150);
            }
        }, timeout);
    }
    
    return alertId;
}

/**
 * Clears all image previews from the preview container
 */
function clearImagePreviews() {
    const previewContainer = document.getElementById('image-previews');
    if (previewContainer) {
        // Save the container's innerHTML for later animation
        const originalHTML = previewContainer.innerHTML;
        
        // Add fade-out animation
        previewContainer.style.opacity = '1';
        previewContainer.style.transition = 'opacity 0.3s ease';
        previewContainer.style.opacity = '0';
        
        // Clear the container after the animation completes
        setTimeout(() => {
            previewContainer.innerHTML = '';
            previewContainer.style.opacity = '1';
        }, 300);
    }
} 
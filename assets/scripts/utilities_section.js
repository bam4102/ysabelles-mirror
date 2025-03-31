// Add event listener to category selection
const categorySelect = document.getElementById('productCategory');
const genderSelect = document.getElementById('productGender');

categorySelect.addEventListener('change', function () {
    const selectedOption = this.options[this.selectedIndex];
    const genderCategory = selectedOption.getAttribute('data-gender');

    // Enable/disable and set the gender select value
    if (genderCategory) {
        genderSelect.value = genderCategory;
        genderSelect.disabled = true;
    } else {
        genderSelect.value = '';
        genderSelect.disabled = true;
    }
});

// Initialize on page load
window.addEventListener('load', function () {
    if (categorySelect.value) {
        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        const genderCategory = selectedOption.getAttribute('data-gender');
        if (genderCategory) {
            genderSelect.value = genderCategory;
            genderSelect.disabled = true;
        }
    } else {
        genderSelect.disabled = true;
    }
});

// Initialize Bootstrap tabs
var triggerTabList = [].slice.call(document.querySelectorAll('#utilityTabs button'))
triggerTabList.forEach(function (triggerEl) {
    var tabTrigger = new bootstrap.Tab(triggerEl)
    triggerEl.addEventListener('click', function (event) {
        event.preventDefault()
        tabTrigger.show()
    })
})

// Form validation for category code
document.querySelector('input[name="category_code"]').addEventListener('input', function (e) {
    this.value = this.value.replace(/[^A-Za-z]/g, '').toUpperCase();
});

// Bootstrap form validation
(function () {
    'use strict'

    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
// Image preview functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add Entourage Image Preview functionality
    const entourageImagesInput = document.querySelector('input[name="entourage_images[]"]');
    const entouragePreviewContainers = document.querySelectorAll('#entourage .image-preview');
    
    if (entourageImagesInput && entouragePreviewContainers.length > 0) {
        entourageImagesInput.addEventListener('change', function(e) {
            // Clear all preview containers
            entouragePreviewContainers.forEach(container => {
                container.innerHTML = '';
                container.style.display = 'none';
            });
            
            // Loop through selected files (up to the number of preview containers)
            const maxPreview = Math.min(this.files.length, entouragePreviewContainers.length);
            
            for (let i = 0; i < maxPreview; i++) {
                const file = this.files[i];
                if (!file.type.startsWith('image/')) continue;
                
                const reader = new FileReader();
                const previewContainer = entouragePreviewContainers[i];
                
                reader.onload = function(e) {
                    // Create image and add to preview container
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'cover';
                    
                    // Style the preview container
                    previewContainer.style.width = '120px';
                    previewContainer.style.height = '120px';
                    previewContainer.style.border = '1px solid #ddd';
                    previewContainer.style.borderRadius = '4px';
                    previewContainer.style.overflow = 'hidden';
                    previewContainer.style.display = 'block';
                    
                    // Clear and append
                    previewContainer.innerHTML = '';
                    previewContainer.appendChild(img);
                };
                
                reader.readAsDataURL(file);
            }
        });
    }
    
    const addProductFileInput = document.getElementById('addProductFileInput');
    const addProductPreviewContainer = document.getElementById('addProductPreviewContainer');
    const MAX_IMAGES = 5;
    let selectedFiles = new DataTransfer(); // Store selected files

    function createImagePreview(file) {
        const reader = new FileReader();
        const container = document.createElement('div');
        container.className = 'preview-container position-relative d-inline-block me-2 mb-2';
        
        const img = document.createElement('img');
        img.className = 'img-thumbnail';
        img.style.width = '150px';
        img.style.height = '150px';
        img.style.objectFit = 'cover';
        
        const removeBtn = document.createElement('button');
        removeBtn.innerHTML = '×';
        removeBtn.className = 'btn btn-danger btn-sm position-absolute top-0 end-0';
        removeBtn.onclick = (e) => {
            e.preventDefault();
            container.remove();
            
            // Remove file from selectedFiles
            const newFiles = new DataTransfer();
            for (let i = 0; i < selectedFiles.files.length; i++) {
                if (selectedFiles.files[i] !== file) {
                    newFiles.items.add(selectedFiles.files[i]);
                }
            }
            selectedFiles = newFiles;
            addProductFileInput.files = selectedFiles.files;
            updateAddImageButton();
        };

        reader.onload = (e) => {
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);

        container.appendChild(img);
        container.appendChild(removeBtn);
        return container;
    }

    function updateAddImageButton() {
        const currentImages = addProductPreviewContainer.querySelectorAll('.preview-container').length;
        const addButton = addProductPreviewContainer.querySelector('.add-image');
        
        if (currentImages >= MAX_IMAGES && addButton) {
            addButton.remove();
        } else if (currentImages < MAX_IMAGES && !addButton) {
            const newAddButton = document.createElement('div');
            newAddButton.className = 'add-image btn btn-outline-secondary';
            newAddButton.innerHTML = '+ Add Image';
            newAddButton.onclick = () => addProductFileInput.click();
            addProductPreviewContainer.appendChild(newAddButton);
        }
    }

    addProductFileInput.addEventListener('change', function(e) {
        const files = Array.from(this.files);
        const currentImages = selectedFiles.files.length;
        
        if (currentImages + files.length > MAX_IMAGES) {
            alert(`Maximum ${MAX_IMAGES} images allowed.`);
            return;
        }

        files.forEach(file => {
            if (!file.type.startsWith('image/')) {
                alert('Only image files are allowed.');
                return;
            }
            
            // Add file to selectedFiles
            selectedFiles.items.add(file);
            
            const previewContainer = createImagePreview(file);
            const addButton = addProductPreviewContainer.querySelector('.add-image');
            if (addButton) {
                addProductPreviewContainer.insertBefore(previewContainer, addButton);
            } else {
                addProductPreviewContainer.appendChild(previewContainer);
            }
        });
        
        // Update the file input with all selected files
        this.files = selectedFiles.files;
        updateAddImageButton();
    });

    // Initialize add button
    updateAddImageButton();
});

// Form validation for measurements
document.querySelectorAll('input[name$="_bust"], input[name$="_waist"], input[name$="_length"]').forEach(input => {
    input.addEventListener('input', function (e) {
        const measurementPattern = /^\d+(\s+\d+\/\d+)?$/;
        if (this.value && !measurementPattern.test(this.value)) {
            this.setCustomValidity('Please enter a valid measurement (e.g., "55" or "55 1/2")');
        } else {
            this.setCustomValidity('');
        }
    });
});

// Form validation for category code
document.querySelector('input[name="category_code"]').addEventListener('input', function (e) {
    this.value = this.value.replace(/[^A-Za-z]/g, '').toUpperCase();
});

// Bootstrap form validation
(function () {
    'use strict'

    // Fetch all forms that need validation
    var forms = document.querySelectorAll('.needs-validation')

    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }

                form.classList.add('was-validated')
            }, false)
        })
})()

document.addEventListener("DOMContentLoaded", function () {
    // When an edit button is clicked…
    document.querySelectorAll(".edit-entourage-btn").forEach(function (btn) {
        btn.addEventListener("click", function () {
            // Retrieve data attributes
            const entourageId = this.getAttribute("data-id");
            const entourageName = this.getAttribute("data-name");
            const imagesData = JSON.parse(this.getAttribute("data-images") || "[]");

            // Set the form fields
            document.getElementById("edit_entourage_id").value = entourageId;
            document.getElementById("edit_entourage_name").value = entourageName;
            document.getElementById("removed_images").value = JSON.stringify([]);

            // Clear any existing thumbnails
            const existingImagesContainer = document.getElementById("existingImages");
            existingImagesContainer.innerHTML = "";

            // Render each existing image thumbnail with a remove button
            imagesData.forEach(function (img) {
                const imgWrapper = document.createElement("div");
                imgWrapper.style.position = "relative";
                imgWrapper.style.display = "inline-block";

                const imageEl = document.createElement("img");
                imageEl.src = img.pictureLocation;
                imageEl.alt = "Image " + img.pictureID;
                imageEl.style.width = "120px";
                imageEl.style.height = "120px";
                imageEl.style.objectFit = "cover";
                imageEl.classList.add("rounded");

                // Remove button overlay
                const removeBtn = document.createElement("button");
                removeBtn.type = "button";
                removeBtn.textContent = "×";
                removeBtn.classList.add("btn", "btn-sm", "btn-danger");
                removeBtn.style.position = "absolute";
                removeBtn.style.top = "2px";
                removeBtn.style.right = "2px";
                removeBtn.addEventListener("click", function () {
                    // Mark this image as removed by adding its id to the hidden input
                    let removedImages = JSON.parse(document.getElementById("removed_images").value);
                    removedImages.push(img.pictureID);
                    document.getElementById("removed_images").value = JSON.stringify(removedImages);
                    // Remove thumbnail from DOM
                    imgWrapper.remove();
                });

                imgWrapper.appendChild(imageEl);
                imgWrapper.appendChild(removeBtn);
                existingImagesContainer.appendChild(imgWrapper);
            });

            // Clear any new image previews and file input
            document.getElementById("newImagePreview").innerHTML = "";
            document.getElementById("edit_entourage_images").value = "";

            // Show the modal using Bootstrap’s API
            const editModal = new bootstrap.Modal(document.getElementById("editEntourageModal"));
            editModal.show();
        });
    });

    // New image preview when files are selected
    document.getElementById("edit_entourage_images").addEventListener("change", function (e) {
        const previewContainer = document.getElementById("newImagePreview");
        previewContainer.innerHTML = ""; // clear old previews
        const files = this.files;
        // Check the total count (existing images not removed + new ones) if needed
        Array.from(files).forEach(function (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const imgPreview = document.createElement("img");
                imgPreview.src = e.target.result;
                imgPreview.style.width = "120px";
                imgPreview.style.height = "120px";
                imgPreview.style.objectFit = "cover";
                imgPreview.classList.add("rounded", "border");
                previewContainer.appendChild(imgPreview);
            };
            reader.readAsDataURL(file);
        });
    });
});

// Category edit functionality
document.addEventListener("DOMContentLoaded", function() {
    // Listen for clicks on the edit-category buttons
    document.querySelectorAll(".edit-category-btn").forEach(function(button) {
        button.addEventListener("click", function() {
            // Retrieve category details from data attributes
            const categoryId = this.getAttribute("data-id");
            const categoryName = this.getAttribute("data-name");
            const categoryCode = this.getAttribute("data-code");
            const classification = this.getAttribute("data-classification");

            // Populate the modal fields
            document.getElementById("edit_category_id").value = categoryId;
            document.getElementById("edit_category_name").value = categoryName;
            document.getElementById("edit_category_code").value = categoryCode;
            document.getElementById("edit_genderCat").value = classification;

            // Create and show the Bootstrap modal
            const editModalEl = document.getElementById("editCategoryModal");
            const editModal = new bootstrap.Modal(editModalEl);
            editModal.show();
        });
    });
});

// Size Variations functionality
document.addEventListener('DOMContentLoaded', function() {
    const addSizeBtn = document.getElementById('addSizeBtn');
    const sizeVariationsContainer = document.getElementById('sizeVariationsContainer');
    const sizeVariationsJson = document.getElementById('sizeVariationsJson');
    const enableMultipleSizes = document.getElementById('enableMultipleSizes');
    const multipleSizesSection = document.querySelector('.multiple-sizes-section');
    const singleSizeInput = document.querySelector('.single-size-input');
    const singlePriceInput = document.querySelector('.single-price-input');
    const singleScanCodeInput = document.querySelector('.single-scan-code');
    const measurementFields = document.querySelector('.measurement-fields');
    
    // Handle the toggle switch for enabling multiple sizes
    if (enableMultipleSizes) {
        enableMultipleSizes.addEventListener('change', function() {
            // Toggle display of multiple sizes section
            if (multipleSizesSection) {
                multipleSizesSection.style.display = this.checked ? 'block' : 'none';
            }
            
            // Toggle display of single size/price/scan inputs and measurements
            if (singleSizeInput && singlePriceInput && singleScanCodeInput && measurementFields) {
                singleSizeInput.style.display = this.checked ? 'none' : 'block';
                singlePriceInput.style.display = this.checked ? 'none' : 'block';
                singleScanCodeInput.style.display = this.checked ? 'none' : 'block';
                measurementFields.style.display = this.checked ? 'none' : 'flex';
            }
            
            // If enabling multiple sizes, initialize with a row and make fields required
            if (this.checked) {
                if (sizeVariationsContainer && !sizeVariationsContainer.querySelector('.size-variation-row')) {
                    addSizeVariationRow();
                }
                
                // Clear single inputs and make them not required
                if (document.querySelector('input[name="product_size"]')) {
                    document.querySelector('input[name="product_size"]').value = '';
                    document.querySelector('input[name="product_size"]').required = false;
                }
                if (document.querySelector('input[name="product_price"]')) {
                    document.querySelector('input[name="product_price"]').value = '';
                    document.querySelector('input[name="product_price"]').required = false;
                }
                if (document.querySelector('input[name="product_scan"]')) {
                    document.querySelector('input[name="product_scan"]').value = '';
                    document.querySelector('input[name="product_scan"]').required = false;
                }
                
                // Clear measurement fields and make them not required
                document.querySelectorAll('input[name="product_bust"], input[name="product_waist"], input[name="product_length"]').forEach(input => {
                    input.value = '';
                    input.required = false;
                });
                
                // Make size variation fields required
                document.querySelectorAll('.size-variation-row input').forEach(input => {
                    input.required = true;
                });
            } else {
                // Clear size variations when toggling off and make fields not required
                if (sizeVariationsContainer) {
                    const variationInputs = sizeVariationsContainer.querySelectorAll('input');
                    variationInputs.forEach(input => {
                        input.required = false;
                    });
                    sizeVariationsContainer.innerHTML = '';
                }
                if (sizeVariationsJson) {
                    sizeVariationsJson.value = JSON.stringify([]);
                }
                
                // Re-enable required on single inputs
                if (document.querySelector('input[name="product_price"]')) {
                    document.querySelector('input[name="product_price"]').required = true;
                }
                if (document.querySelector('input[name="product_scan"]')) {
                    document.querySelector('input[name="product_scan"]').required = true;
                }
                // Size field is optional, so we don't set it as required
            }
        });
    }
    
    if (addSizeBtn && sizeVariationsContainer) {
        // Initialize with empty array
        sizeVariationsJson.value = JSON.stringify([]);
        
        // Add a size row when clicked
        addSizeBtn.addEventListener('click', function() {
            addSizeVariationRow();
        });
        
        // Add first row by default
        addSizeVariationRow();
        
        // Function to add a new size variation row
        function addSizeVariationRow() {
            const rowId = 'size-row-' + Date.now();
            const row = document.createElement('div');
            row.className = 'size-variation-row mb-3 border-bottom pb-3';
            row.id = rowId;
            
            row.innerHTML = `
                <div class="row g-2 mb-2">
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="text" class="form-control size-input" placeholder="Size" ${enableMultipleSizes && enableMultipleSizes.checked ? 'required' : ''}>
                            <label>Size</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="number" class="form-control price-input" placeholder="Price" min="0" step="0.01" ${enableMultipleSizes && enableMultipleSizes.checked ? 'required' : ''}>
                            <label>Price</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="number" class="form-control quantity-input" placeholder="Quantity" min="1" value="1" ${enableMultipleSizes && enableMultipleSizes.checked ? 'required' : ''}>
                            <label>Quantity</label>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-center">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-size-btn">
                            <i class="bi bi-trash"></i> Remove
                        </button>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label">Scan Code 1:</label>
                    <div class="scan-codes-container">
                        <div class="input-group mb-2">
                            <input type="text" class="form-control scan-code-input" placeholder="Scan Code 1" ${enableMultipleSizes && enableMultipleSizes.checked ? 'required' : ''}>
                        </div>
                    </div>
                </div>
            `;
            
            // Add remove button functionality
            row.querySelector('.remove-size-btn').addEventListener('click', function() {
                row.remove();
                updateSizeVariationsJson();
            });
            
            // Add input change listeners
            row.querySelectorAll('input').forEach(input => {
                input.addEventListener('input', function() {
                    if (this.classList.contains('quantity-input')) {
                        updateScanCodeFields(row);
                    }
                    updateSizeVariationsJson();
                });
            });
            
            sizeVariationsContainer.appendChild(row);
            updateSizeVariationsJson();
        }
        
        // Function to update scan code fields based on quantity
        function updateScanCodeFields(row) {
            const quantityInput = row.querySelector('.quantity-input');
            const scanCodesContainer = row.querySelector('.scan-codes-container');
            const quantity = parseInt(quantityInput.value) || 1;
            
            // Get current scan code inputs
            const currentInputs = scanCodesContainer.querySelectorAll('.input-group');
            const currentCount = currentInputs.length;
            
            const isRequired = enableMultipleSizes && enableMultipleSizes.checked;
            
            // If we need more inputs
            if (quantity > currentCount) {
                for (let i = currentCount; i < quantity; i++) {
                    const inputGroup = document.createElement('div');
                    inputGroup.className = 'input-group mb-2';
                    inputGroup.innerHTML = `
                        <input type="text" class="form-control scan-code-input" placeholder="Scan Code ${i + 1}" ${isRequired ? 'required' : ''}>
                    `;
                    scanCodesContainer.appendChild(inputGroup);
                    
                    // Add change listener to new input
                    inputGroup.querySelector('input').addEventListener('input', updateSizeVariationsJson);
                }
            }
            // If we need fewer inputs
            else if (quantity < currentCount) {
                // Remove excess inputs (keep the first 'quantity' inputs)
                for (let i = currentCount - 1; i >= quantity; i--) {
                    currentInputs[i].remove();
                }
            }
            
            updateSizeVariationsJson();
        }
        
        // Function to update the hidden JSON input
        function updateSizeVariationsJson() {
            const variations = [];
            document.querySelectorAll('.size-variation-row').forEach(row => {
                const sizeInput = row.querySelector('.size-input').value.trim();
                const priceInput = row.querySelector('.price-input').value.trim();
                const quantityInput = row.querySelector('.quantity-input').value.trim();
                const scanCodeInputs = row.querySelectorAll('.scan-code-input');
                
                if (sizeInput && priceInput && quantityInput) {
                    const scanCodes = [];
                    scanCodeInputs.forEach(input => {
                        if (input.value.trim()) {
                            scanCodes.push(input.value.trim());
                        }
                    });
                    
                    variations.push({
                        size: sizeInput,
                        price: parseFloat(priceInput),
                        quantity: parseInt(quantityInput, 10),
                        scanCodes: scanCodes
                    });
                }
            });
            
            sizeVariationsJson.value = JSON.stringify(variations);
        }
        
        // Add first row by default
        addSizeVariationRow();
    }
});

// Also add a form submission validation to only validate visible fields
document.addEventListener('DOMContentLoaded', function() {
    const productForm = document.querySelector('form[name="add_product"]');
    if (productForm) {
        productForm.addEventListener('submit', function(event) {
            // Check if multiple sizes is enabled
            const enableMultipleSizes = document.getElementById('enableMultipleSizes');
            const isMultipleSizesEnabled = enableMultipleSizes && enableMultipleSizes.checked;
            
            // If using multiple sizes, validate those fields instead of single fields
            if (isMultipleSizesEnabled) {
                const sizeInputs = document.querySelectorAll('.size-variation-row .size-input');
                const priceInputs = document.querySelectorAll('.size-variation-row .price-input');
                const scanCodeInputs = document.querySelectorAll('.size-variation-row .scan-code-input');
                
                // Check if at least one complete row exists
                let hasValidRow = false;
                for (let i = 0; i < sizeInputs.length; i++) {
                    if (sizeInputs[i].value && priceInputs[i].value && scanCodeInputs[i].value) {
                        hasValidRow = true;
                        break;
                    }
                }
                
                if (!hasValidRow) {
                    alert('Please complete at least one size variation with size, price, and scan code.');
                    event.preventDefault();
                    return false;
                }
            } else {
                // If using single size, make sure price and scan code are filled
                const priceInput = document.querySelector('input[name="product_price"]');
                const scanCodeInput = document.querySelector('input[name="product_scan"]');
                
                if (!priceInput.value) {
                    alert('Please enter a price for the product.');
                    priceInput.focus();
                    event.preventDefault();
                    return false;
                }
                
                if (!scanCodeInput.value) {
                    alert('Please enter a scan code for the product.');
                    scanCodeInput.focus();
                    event.preventDefault();
                    return false;
                }
            }
        });
    }
});

// Optimized product validation with debounce
const validateProductInput = (() => {
    let timer = null;
    return function(input) {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const datalist = document.getElementById('productNamesDatalist');
            
            // Skip validation for empty inputs
            if (!input.value.trim()) {
                input.setCustomValidity('');
                return;
            }
            
            // Create an efficient lookup map instead of iterating each time
            const validOptions = new Set(
                Array.from(datalist.querySelectorAll('option'))
                    .map(option => option.value)
            );
            
            // Only validate when input loses focus or after typing pauses
            input.setCustomValidity(validOptions.has(input.value) ? '' : 
                'Please select a valid product from the list.');
            
            // Trigger validation UI update
            input.reportValidity();
        }, 300); // 300ms debounce delay
    };
})();

// Add event listener for variation product type selection to update product names datalist
document.addEventListener('DOMContentLoaded', function() {
    const variationTypeSelect = document.querySelector('[name="variation_typeProduct"]');
    const productNamesDatalist = document.getElementById('productNamesDatalist');
    
    if (variationTypeSelect && productNamesDatalist) {
        variationTypeSelect.addEventListener('change', function() {
            const categoryCode = this.value;
            if (!categoryCode) return;
            
            // Fetch product names for this category
            fetch(`./assets/controllers/utilities_section/get_product_names.php?category=${categoryCode}`)
                .then(response => response.json())
                .then(data => {
                    // Clear existing options
                    productNamesDatalist.innerHTML = '';
                    
                    // Add new options based on returned data
                    if (data.names && data.names.length) {
                        data.names.forEach(name => {
                            const option = document.createElement('option');
                            option.value = name;
                            productNamesDatalist.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error fetching product names:', error));
        });
    }
});

// Improve the category selection event handler with better error handling
document.addEventListener('DOMContentLoaded', function() {
    const variationTypeSelect = document.querySelector('[name="variation_typeProduct"]');
    const productNamesDatalist = document.getElementById('productNamesDatalist');
    const variationNameInput = document.getElementById('variation_nameProduct');
    
    if (variationTypeSelect && productNamesDatalist) {
        variationTypeSelect.addEventListener('change', function() {
            const categoryCode = this.value;
            console.log('Category changed to:', categoryCode);
            
            if (!categoryCode) {
                return;
            }
            
            // Clear the product name input when category changes
            if (variationNameInput) {
                variationNameInput.value = '';
            }
            
            // Show loading indicator
            if (variationNameInput) {
                variationNameInput.placeholder = "Loading suggestions...";
            }
            
            // Build the URL with a timestamp to prevent caching
            const timestamp = new Date().getTime();
            const url = `./assets/controllers/utilities_section/get_product_names.php?category=${categoryCode}&_=${timestamp}`;
            
            console.log('Fetching product names from:', url);
            
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Got product names:', data);
                    
                    // Clear existing options
                    productNamesDatalist.innerHTML = '';
                    
                    // Add new options based on returned data
                    if (data.names && data.names.length) {
                        data.names.forEach(name => {
                            const option = document.createElement('option');
                            option.value = name;
                            productNamesDatalist.appendChild(option);
                        });
                        // Update placeholder
                        if (variationNameInput) {
                            variationNameInput.placeholder = `${data.names.length} suggestions available`;
                        }
                    } else {
                        // Handle empty results
                        if (variationNameInput) {
                            variationNameInput.placeholder = "No suggestions found - type a new name";
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching product names:', error);
                    // Provide a user-friendly fallback
                    if (variationNameInput) {
                        variationNameInput.placeholder = "Type a product name";
                    }
                });
        });
    }
});
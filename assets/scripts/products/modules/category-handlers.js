/**
 * Initialize category and gender related handlers
 * @param {Object} product - The product being edited
 * @param {HTMLElement} contentElement - The modal content element
 */
export function initializeCategoryHandlers(product, contentElement) {
    const genderSelect = contentElement.querySelector('#productGender');
    const categorySelect = contentElement.querySelector('#productCategory');
    
    if (genderSelect && categorySelect) {
        // Add event listener for gender change
        genderSelect.addEventListener('change', function() {
            handleGenderChange(this.value, categorySelect);
        });

        // Add event listener for category change
        categorySelect.addEventListener('change', function() {
            handleCategoryChange(this, contentElement);
        });
    }
}

/**
 * Handle gender selection change
 * @param {string} selectedGender - The selected gender value
 * @param {HTMLElement} categorySelect - The category select element
 */
function handleGenderChange(selectedGender, categorySelect) {
    const currentCategoryId = categorySelect.value;
    
    // Filter categories based on selected gender
    const filteredCategories = window.categories
        .filter(cat => !selectedGender || cat.genderCategory === selectedGender);
    
    // Update category options
    categorySelect.innerHTML = `
        <option value="">Select Category</option>
        ${filteredCategories.map(cat => `
            <option value="${cat.categoryID}" 
                    ${cat.categoryID == currentCategoryId ? 'selected' : ''}
                    data-code="${cat.categoryCode}">
                ${cat.productCategory} (${cat.categoryCode})
            </option>
        `).join('')}
    `;
    
    // Reset category selection if current category doesn't match gender
    if (!filteredCategories.some(cat => cat.categoryID == currentCategoryId)) {
        categorySelect.value = '';
    }
}

/**
 * Handle category selection change
 * @param {HTMLElement} categorySelect - The category select element
 * @param {HTMLElement} contentElement - The modal content element
 */
function handleCategoryChange(categorySelect, contentElement) {
    const selectedOption = categorySelect.options[categorySelect.selectedIndex];
    if (selectedOption) {
        const categoryCode = selectedOption.getAttribute('data-code');
        // Find and update the hidden typeProduct input
        const typeProductInput = contentElement.querySelector('input[name="typeProduct"]');
        if (typeProductInput) {
            typeProductInput.value = categoryCode;
        }
    }
} 
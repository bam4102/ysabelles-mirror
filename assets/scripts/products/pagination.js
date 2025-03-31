// Pagination module for product management
import ProductConfig from './config.js';
import { updateUrl } from './utils.js';

/**
 * Render pagination controls
 * @param {Object} state - Current application state
 * @param {HTMLElement} paginationElement - The pagination container element
 * @param {Function} renderTable - Function to render the table
 */
export function renderPagination(state, paginationElement, renderTable) {
    paginationElement.innerHTML = '';
    
    const totalPages = Math.ceil(state.filteredProducts.length / ProductConfig.itemsPerPage);
    
    if (totalPages <= 1) {
        return;
    }
    
    // First and Previous buttons
    if (state.currentPage > 1) {
        appendPaginationItem('«', 1, 'First', paginationElement, state, renderTable);
        appendPaginationItem('‹', state.currentPage - 1, 'Previous', paginationElement, state, renderTable);
    }
    
    // Page numbers
    const range = 2;
    const startPage = Math.max(1, state.currentPage - range);
    const endPage = Math.min(totalPages, state.currentPage + range);
    
    for (let i = startPage; i <= endPage; i++) {
        appendPaginationItem(i, i, null, paginationElement, state, renderTable, i === state.currentPage);
    }
    
    // Next and Last buttons
    if (state.currentPage < totalPages) {
        appendPaginationItem('›', state.currentPage + 1, 'Next', paginationElement, state, renderTable);
        appendPaginationItem('»', totalPages, 'Last', paginationElement, state, renderTable);
    }
}

/**
 * Helper function to append pagination items
 * @param {string|number} text - The text to display
 * @param {number} page - The page number
 * @param {string|null} label - The aria-label
 * @param {HTMLElement} paginationElement - The pagination container element
 * @param {Object} state - Current application state
 * @param {Function} renderTable - Function to render the table
 * @param {boolean} isActive - Whether this is the active page
 */
function appendPaginationItem(text, page, label, paginationElement, state, renderTable, isActive = false) {
    const li = document.createElement('li');
    li.className = `page-item${isActive ? ' active' : ''}`;
    
    const a = document.createElement('a');
    a.className = 'page-link';
    a.href = '#';
    a.textContent = text;
    if (label) {
        a.setAttribute('aria-label', label);
    }
    
    a.addEventListener('click', function(e) {
        e.preventDefault();
        state.currentPage = page;
        renderTable();
        renderPagination(state, paginationElement, renderTable);
        
        // Get current filters
        const categoryFilter = document.getElementById('category').value;
        const locationFilter = document.getElementById('location').value;
        const genderFilter = document.getElementById('gender').value;
        const searchFilter = document.getElementById('search').value;
        
        // Update URL
        updateUrl(
            categoryFilter, 
            locationFilter, 
            genderFilter, 
            searchFilter, 
            state.currentPage, 
            state.sortField, 
            state.sortDirection
        );
        
        window.scrollTo(0, 0);
    });
    
    li.appendChild(a);
    paginationElement.appendChild(li);
} 
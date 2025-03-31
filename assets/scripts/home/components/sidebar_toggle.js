/**
 * Sidebar Toggle Functionality
 * Handles the collapsing and expanding of the filter sidebar
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elements cache
    const elements = {
        sidebarColumn: document.getElementById('sidebarColumn'),
        mainContent: document.getElementById('mainContent'),
        sidebarToggle: document.getElementById('sidebarToggle'),
        openSidebarBtn: document.getElementById('openSidebarBtn'),
        productGrids: document.querySelectorAll('.product-grid')
    };
    
    // Initialize sidebar state from localStorage
    const initSidebar = () => {
        const isSidebarOpen = localStorage.getItem('isSidebarOpen') !== 'false';
        
        if (!isSidebarOpen) {
            elements.sidebarColumn.classList.add('collapsed');
            elements.mainContent.classList.add('expanded');
            elements.openSidebarBtn.classList.remove('d-none');
            
            elements.productGrids.forEach(grid => {
                grid.classList.add('expanded-grid');
            });
        }
    };
    
    // Toggle sidebar state
    const toggleSidebar = (event) => {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        const isNowCollapsed = elements.sidebarColumn.classList.toggle('collapsed');
        elements.mainContent.classList.toggle('expanded');
        
        // Update localStorage
        localStorage.setItem('isSidebarOpen', !isNowCollapsed);
        
        // Toggle open button visibility
        elements.openSidebarBtn.classList.toggle('d-none', !isNowCollapsed);
        
        // Toggle grid classes
        elements.productGrids.forEach(grid => {
            grid.classList.toggle('expanded-grid', isNowCollapsed);
        });
    };
    
    // Attach event listeners
    const attachEvents = () => {
        if (elements.sidebarToggle) {
            elements.sidebarToggle.addEventListener('click', toggleSidebar);
        }
        
        if (elements.openSidebarBtn) {
            elements.openSidebarBtn.addEventListener('click', toggleSidebar);
        }
    };
    
    // Initialize
    initSidebar();
    attachEvents();
}); 
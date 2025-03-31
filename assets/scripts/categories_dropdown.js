document.addEventListener('DOMContentLoaded', function() {
    const dropdownToggle = document.querySelector('#navbarDropdown');
    const categoriesDropdown = document.querySelector('#categoriesDropdown');
    let isOpen = false;

    // Toggle dropdown when clicking the "More" button
    dropdownToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (!isOpen) {
            // Open dropdown
            categoriesDropdown.classList.add('show');
            dropdownToggle.setAttribute('aria-expanded', 'true');
            isOpen = true;
        } else {
            // Close dropdown
            categoriesDropdown.classList.remove('show');
            dropdownToggle.setAttribute('aria-expanded', 'false');
            isOpen = false;
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!categoriesDropdown.contains(e.target) && !dropdownToggle.contains(e.target)) {
            categoriesDropdown.classList.remove('show');
            dropdownToggle.setAttribute('aria-expanded', 'false');
            isOpen = false;
        }
    });
});

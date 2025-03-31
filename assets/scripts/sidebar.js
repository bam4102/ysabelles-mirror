document.addEventListener('DOMContentLoaded', function() {
    // DateTime update
    function updateDateTime() {
        const now = new Date();
        const options = { 
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        };
        const datetimeElement = document.getElementById('datetime');
        if (datetimeElement) {
            datetimeElement.textContent = now.toLocaleString('en-US', options);
        }
    }

    // Update immediately and then every second
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // Create overlay element
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    // Sidebar toggle
    const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');

    function showSidebar() {
        sidebar.classList.add('active');
        overlay.classList.add('active');
        mainContent.classList.add('shifted');
    }

    function hideSidebar() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        mainContent.classList.remove('shifted');
    }

    function toggleSidebar() {
        if (sidebar.classList.contains('active')) {
            hideSidebar();
        } else {
            showSidebar();
        }
    }

    sidebarToggleBtn.addEventListener('click', toggleSidebar);
    
    // Only hide sidebar when clicking the overlay itself
    overlay.addEventListener('click', hideSidebar);

    // Dropdown toggle
    const dropdownToggle = document.getElementById('reportsDropdown');
    if (dropdownToggle) {
        const dropdownMenu = document.getElementById('reportsMenu');
        
        dropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            dropdownMenu.classList.toggle('show');
        });
    }
}); 
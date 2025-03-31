function handleHashAndTabs() {
    // Handle URL hash changes and set initial active tab
    function handleHashChange() {
        var hash = window.location.hash || '#new';
        var tabId = hash.substring(1); // Remove the # from the hash
        var tab = $('#' + tabId + '-tab');
        if (tab.length) {
            tab.tab('show');
        }
    }

    // Listen for hash changes
    $(window).on('hashchange', handleHashChange);

    // Handle tab change events
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        var targetId = $(e.target).attr('id');
        var hash = targetId.replace('-tab', '');
        window.location.hash = hash;
    });

    // Set initial tab based on URL hash
    handleHashChange();
} 
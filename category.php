<?php
// Special handling for entourage category to use our new dynamic entourage page
if (isset($_GET['cat']) && $_GET['cat'] === 'entourage') {
    header('Location: home.php?page=entourage');
    exit;
}

// Rest of the category page code
// ... existing code ... 
<?php
$protected_pages = [
    'main_products.php',
    'utilities_section.php',
    'release.php',
    'return.php',
    'transmittal.php',
    'branch_requests.php',
    'product_history.php',
    'calendar.php',
    'home.php',
    'transactions2.php',
    'reports.php',
    'manage_employees.php'
];

$session_check_code = "<?php\ninclude './assets/controllers/login/session_check.php';\n";

foreach ($protected_pages as $page) {
    $file_path = "../../../$page";
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        
        // Check if session check is already included
        if (strpos($content, 'session_check.php') === false) {
            // If the file starts with PHP tag, replace it
            if (strpos($content, '<?php') === 0) {
                $new_content = preg_replace('/^<\?php/', $session_check_code, $content, 1);
            } else {
                // If no PHP tag at start, add it at the beginning
                $new_content = $session_check_code . $content;
            }
            
            file_put_contents($file_path, $new_content);
            echo "Added session check to $page\n";
        } else {
            echo "Session check already exists in $page\n";
        }
    } else {
        echo "File not found: $page\n";
    }
}

echo "\nSession check addition complete!\n";
?> 
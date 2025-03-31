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

$auth_include = "<?php\nrequire_once 'auth.php';\n";

foreach ($protected_pages as $page) {
    $file_path = "../../../$page";
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        
        // Check if auth.php is already included
        if (strpos($content, 'auth.php') === false) {
            // If the file starts with PHP tag, replace it
            if (strpos($content, '<?php') === 0) {
                $new_content = preg_replace('/^<\?php/', $auth_include, $content, 1);
            } else {
                // If no PHP tag at start, add it at the beginning
                $new_content = $auth_include . $content;
            }
            
            file_put_contents($file_path, $new_content);
            echo "Added auth check to $page\n";
        } else {
            echo "Auth check already exists in $page\n";
        }
    } else {
        echo "File not found: $page\n";
    }
}

echo "\nAuth check addition complete!\n";
?> 
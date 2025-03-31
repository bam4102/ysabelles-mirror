<?php
// This script fixes the duplicate function issue in upload_product_images.php

// Read the original file
$fileContent = file_get_contents('upload_product_images.php');

// Make a backup
file_put_contents('upload_product_images.php.bak2', $fileContent);

// Remove duplicate debug_log function
$fileContent = preg_replace('/\/\/ Debug log function with file output for persistent logging.*?function debug_log\(.*?\}(\r?\n)+/s', '', $fileContent);

// Remove duplicate send_json_response function
$fileContent = preg_replace('/\/\*\*\s*\* Sends a formatted JSON response and ends script execution.*?function send_json_response\(.*?exit;\s*\}(\r?\n)+/s', '', $fileContent);

// Write back the fixed file
file_put_contents('upload_product_images.php', $fileContent);

echo "Fixed upload_product_images.php by removing duplicate function definitions.\n";
?> 
<?php
// delete_category.php

// Include your database connection
require_once '../db.php';

// Check if a valid category ID is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $category_id = (int) $_GET['id'];

    try {
        // Prepare and execute the deletion statement
        $stmt = $pdo->prepare("DELETE FROM productcategory WHERE categoryID = :id");
        $stmt->execute([':id' => $category_id]);

        // Optionally, check if the deletion affected any rows
        if ($stmt->rowCount() > 0) {
            // Redirect back with a success message
            header("Location: ../../../utilities_section.php?msg=CategoryDeleted");
        } else {
            // Redirect back with an error message if no rows were deleted
            header("Location: ../../../utilities_section.php?msg=CategoryNotFound");
        }
        exit;
    } catch (Exception $e) {
        // Log error details if needed
        // Redirect back with an error message
        header("Location: ../../../utilities_section.php?msg=ErrorDeletingCategory");
        exit;
    }
} else {
    // If no valid category ID is provided, redirect back with an error message
    header("Location: ../../../utilities_section.php?msg=InvalidCategoryID");
    exit;
}

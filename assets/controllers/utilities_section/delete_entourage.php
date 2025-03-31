<?php
include '../db.php'; // Ensure this file sets up your $pdo PDO connection

if (!isset($_GET['id'])) {
    die("No entourage ID provided.");
}

$entourage_id = intval($_GET['id']);

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Fetch associated pictures for the given entourage
    $stmt = $pdo->prepare("SELECT pictureID, pictureLocation FROM picture WHERE entourageID = :id");
    $stmt->execute([':id' => $entourage_id]);
    $pictures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($pictures as $pic) {
        // Delete the file from the file system if it exists
        if (file_exists($pic['pictureLocation'])) {
            unlink($pic['pictureLocation']);
        }
        
        // Remove the picture record from the database
        $delStmt = $pdo->prepare("DELETE FROM picture WHERE pictureID = :pic_id");
        $delStmt->execute([':pic_id' => $pic['pictureID']]);
    }
    
    // Delete the entourage record itself
    $stmt = $pdo->prepare("DELETE FROM entourage WHERE entourageID = :id");
    $stmt->execute([':id' => $entourage_id]);
    
    // Commit transaction
    $pdo->commit();
    
    // Redirect back to the utilities page with a success message
    header("Location: ../../../utilities_section.php?message=Entourage deleted successfully!");
    exit;
    
} catch (Exception $e) {
    $pdo->rollBack();
    die("Error deleting entourage: " . $e->getMessage());
}

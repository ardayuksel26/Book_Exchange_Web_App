<?php
/**
 * BOOK DELETION SCRIPT
 * Handles the permanent removal of a book listing.
 * * Purpose: Validates ownership, deletes the physical image file, 
 * and removes the database record.
 */

require_once 'config.php';
require_once 'auth.php';

// Security Check: User must be logged in to delete anything
require_login();

// Get the specific book ID to delete
$book_id = $_GET['book_id'] ?? 0;
$user_id = get_user_id();

// ---------------------------------------------------------
// 1. OWNERSHIP VERIFICATION (Security Critical)
// ---------------------------------------------------------
// We must verify that the user trying to delete the book is actually the owner.
// We do this by adding "AND user_id = ?" to the query.
// If this returns no results, it means either the book doesn't exist 
// OR this user doesn't own it.
$stmt = $pdo->prepare("SELECT b.*, 
                       (SELECT image_path FROM book_images WHERE book_id = b.book_id LIMIT 1) as image_path
                       FROM books b 
                       WHERE book_id = ? AND user_id = ?");
$stmt->execute([$book_id, $user_id]);
$book = $stmt->fetch();

// If verification fails, redirect immediately (Access Control)
if (!$book) {
    header("Location: my_books.php");
    exit();
}

// ---------------------------------------------------------
// 2. DELETION PROCESS
// ---------------------------------------------------------
try {
    // Step A: File System Cleanup
    // Before deleting the database entry, we must delete the actual image file 
    // from the server's disk to save space and prevent orphaned files.
    if ($book['image_path'] && file_exists($book['image_path'])) {
        unlink($book['image_path']); // 'unlink' deletes the file
    }
    
    // Step B: Database Record Deletion
    // We execute the delete query. 
    // Note: The database schema is set up with ON DELETE CASCADE, 
    // so deleting this book will automatically clean up related rows 
    // in the 'book_images', 'rentals', and 'swap_requests' tables.
    $stmt = $pdo->prepare("DELETE FROM books WHERE book_id = ? AND user_id = ?");
    $stmt->execute([$book_id, $user_id]);
    
    // Redirect with success flag
    header("Location: my_books.php?deleted=1");
    exit();
} catch (PDOException $e) {
    // Handle database errors gracefully
    header("Location: my_books.php?error=delete_failed");
    exit();
}
?>
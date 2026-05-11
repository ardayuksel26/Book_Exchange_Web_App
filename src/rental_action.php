<?php
/**
 * RENTAL ACTION PROCESSOR
 * Handles the logic for accepting or declining a rental request.
 * * Purpose: Updates the database based on the owner's decision.
 * If accepted, it locks the book and automatically declines competing requests.
 */

require_once 'config.php';
require_once 'auth.php';
require_login(); // Security: Only logged-in users can execute actions

// Retrieve parameters from URL
$rental_id = $_GET['rental_id'] ?? 0;
$action = $_GET['action'] ?? '';
$user_id = get_user_id();

// ---------------------------------------------------------
// 1. SECURITY & VALIDATION
// ---------------------------------------------------------
// Fetch rental details to ensure:
// 1. The rental request actually exists.
// 2. The CURRENT USER is the OWNER of the book (Security Check).
// 3. The request is still 'pending' (prevent re-accepting already processed items).
$stmt = $pdo->prepare("SELECT r.*, b.title, b.book_id, 
                       u1.first_name as renter_first, u1.last_name as renter_last,
                       u2.first_name as owner_first, u2.last_name as owner_last
                       FROM rentals r
                       JOIN books b ON r.book_id = b.book_id
                       JOIN users u1 ON r.renter_id = u1.user_id
                       JOIN users u2 ON r.owner_id = u2.user_id
                       WHERE r.rental_id = ? AND r.owner_id = ? AND r.status = 'pending'");
$stmt->execute([$rental_id, $user_id]);
$rental = $stmt->fetch();

// If validation fails, redirect back
if (!$rental) {
    header("Location: " . url('notifications.php'));
    exit();
}

// Ensure the action is a valid keyword
if (!in_array($action, ['accept', 'decline'])) {
    header("Location: " . url('notifications.php'));
    exit();
}

// 

try {
    // ---------------------------------------------------------
    // 2. DATABASE TRANSACTION
    // ---------------------------------------------------------
    // We use a transaction because 'Accept' involves multiple steps.
    // If one fails, we want to roll back everything to prevent data inconsistency.
    $pdo->beginTransaction();

    if ($action === 'accept') {
        // STEP A: Mark the specific rental request as 'accepted'
        $stmt = $pdo->prepare("UPDATE rentals SET status = 'accepted' WHERE rental_id = ?");
        $stmt->execute([$rental_id]);
        
        // STEP B: Mark the book as 'rented' so it no longer appears in search results
        $stmt = $pdo->prepare("UPDATE books SET status = 'rented' WHERE book_id = ?");
        $stmt->execute([$rental['book_id']]);
        
        // STEP C: Send success notification to the renter
        $message = "Good news! Your rental request for '{$rental['title']}' has been accepted by the owner.";
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)");
        $stmt->execute([$rental['renter_id'], $message]);

        // ---------------------------------------------------------
        // CRITICAL LOGIC: AUTO-DECLINE CONFLICTS
        // ---------------------------------------------------------
        // Since the book is now rented, we must find all OTHER pending requests 
        // for this specific book and automatically decline them.
        $stmt = $pdo->prepare("SELECT rental_id, renter_id FROM rentals WHERE book_id = ? AND status = 'pending' AND rental_id != ?");
        $stmt->execute([$rental['book_id'], $rental_id]);
        $other_requests = $stmt->fetchAll();

        foreach ($other_requests as $other) {
            // Update status to 'declined'
            $upd = $pdo->prepare("UPDATE rentals SET status = 'declined' WHERE rental_id = ?");
            $upd->execute([$other['rental_id']]);

            // Notify the disappointed user
            $declined_msg = "Sorry, the book '{$rental['title']}' was just rented by someone else. Your request has been automatically declined.";
            $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)");
            $notif->execute([$other['renter_id'], $declined_msg]);
        }
        // ---------------------------------------------------------

        // Commit all changes
        $pdo->commit();
        header("Location: " . url('notifications.php?rental_accepted=1'));
        
    } else {
        // ACTION: DECLINE
        // Simple update: just mark as declined and notify user.
        $stmt = $pdo->prepare("UPDATE rentals SET status = 'declined' WHERE rental_id = ?");
        $stmt->execute([$rental_id]);
        
        // Send notification
        $message = "Your rental request for '{$rental['title']}' has been declined by the owner.";
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)");
        $stmt->execute([$rental['renter_id'], $message]);
        
        $pdo->commit();
        header("Location: " . url('notifications.php?rental_declined=1'));
    }
    exit();
} catch (PDOException $e) {
    // If anything goes wrong, undo all changes in this transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: " . url('notifications.php?error=action_failed'));
    exit();
}
?>
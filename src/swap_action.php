<?php
/**
 * SWAP ACTION PROCESSOR
 * Handles the logic for accepting or declining a permanent book trade.
 * * Purpose: Updates the swap status and, if accepted, marks BOTH involved books
 * as unavailable to prevent further transactions.
 */

require_once 'config.php';
require_once 'auth.php';
require_login(); // Security: Only logged-in users can perform actions

$swap_id = $_GET['swap_id'] ?? 0;
$action = $_GET['action'] ?? '';
$user_id = get_user_id();

// ---------------------------------------------------------
// 1. DATA RETRIEVAL & SECURITY CHECK
// ---------------------------------------------------------
// 
// We need to fetch details about:
// 1. The Swap Record (s)
// 2. The Requested Book (b1 - Owned by current user)
// 3. The Offered Book (b2 - Owned by the requester)
// 4. The Requester (u1)
// 5. The Owner/Current User (u2)
// This query validates that the current user is indeed the owner (s.owner_id = ?).
$stmt = $pdo->prepare("SELECT s.*, 
                       b1.title as requested_title, b1.book_id as requested_book_id,
                       b2.title as offered_title, b2.book_id as offered_book_id,
                       u1.first_name as requester_first, u1.last_name as requester_last,
                       u2.first_name as owner_first, u2.last_name as owner_last
                       FROM swaps s
                       JOIN books b1 ON s.requested_book_id = b1.book_id
                       JOIN books b2 ON s.offered_book_id = b2.book_id
                       JOIN users u1 ON s.requester_id = u1.user_id
                       JOIN users u2 ON s.owner_id = u2.user_id
                       WHERE s.swap_id = ? AND s.owner_id = ? AND s.status = 'pending'");
$stmt->execute([$swap_id, $user_id]);
$swap = $stmt->fetch();

// Security: If swap doesn't exist or user isn't the owner, redirect
if (!$swap) {
    header("Location: " . url('notifications.php'));
    exit();
}

// Validation: Ensure action is valid
if (!in_array($action, ['accept', 'decline'])) {
    header("Location: " . url('notifications.php'));
    exit();
}

try {
    if ($action === 'accept') {
        // ---------------------------------------------------------
        // ACTION: ACCEPT SWAP
        // ---------------------------------------------------------
        
        // 1. Mark the swap record as accepted
        $stmt = $pdo->prepare("UPDATE swaps SET status = 'accepted' WHERE swap_id = ?");
        $stmt->execute([$swap_id]);
        
        // 2. CRITICAL: Update status of BOTH books
        // Unlike rentals, a swap affects two inventory items. 
        // We mark both the 'Requested Book' and the 'Offered Book' as 'unavailable'.
        // 
        $stmt = $pdo->prepare("UPDATE books SET status = 'unavailable' WHERE book_id IN (?, ?)");
        $stmt->execute([$swap['requested_book_id'], $swap['offered_book_id']]);
        
        // 3. Notify the user who initiated the request
        $message = "Great news! Your swap request has been accepted. '{$swap['offered_title']}' will be swapped for '{$swap['requested_title']}'.";
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)");
        $stmt->execute([$swap['requester_id'], $message]);
        
        header("Location: " . url('notifications.php?swap_accepted=1'));
    } else {
        // ---------------------------------------------------------
        // ACTION: DECLINE SWAP
        // ---------------------------------------------------------
        
        // 1. Mark swap as declined (Books remain available)
        $stmt = $pdo->prepare("UPDATE swaps SET status = 'declined' WHERE swap_id = ?");
        $stmt->execute([$swap_id]);
        
        // 2. Notify the requester
        $message = "Your swap request for '{$swap['requested_title']}' has been declined by the owner.";
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)");
        $stmt->execute([$swap['requester_id'], $message]);
        
        header("Location: " . url('notifications.php?swap_declined=1'));
    }
    exit();
} catch (PDOException $e) {
    // Error handling
    header("Location: " . url('notifications.php?error=action_failed'));
    exit();
}
?>
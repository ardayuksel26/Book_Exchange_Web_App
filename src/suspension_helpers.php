<?php
/**
 * SUSPENSION SYSTEM - HELPER FUNCTIONS
 * Contains all functions related to user suspension management.
 * * Purpose: Centralizes the logic for disciplining users. 
 * Handles state changes (Active <-> Suspended), audit logging, and automated cleanup.
 */

// 

/**
 * Check if a user is currently suspended
 * Also auto-expires suspensions that have passed their end date.
 * * Logic: We perform a "Lazy Expiration" check here. Before telling the app 
 * if a user is suspended, we first check if their time is up.
 * * @param int $user_id
 * @return bool True if suspended, false otherwise
 */
function is_user_suspended($user_id) {
    global $pdo;
    
    try {
        // First, auto-expire any suspensions that have passed
        // This ensures the user isn't blocked for even 1 second longer than necessary
        auto_expire_suspensions();
        
        $stmt = $pdo->prepare("
            SELECT is_suspended, suspension_end_date 
            FROM users 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // Cast integer 0/1 to boolean for strict type checking in the app
        return (bool)$user['is_suspended'];
        
    } catch (PDOException $e) {
        error_log("Error checking suspension status: " . $e->getMessage());
        return false;
    }
}

/**
 * Get suspension details for a user
 * Used to display the "Why am I suspended?" screen to the user.
 * * @param int $user_id
 * @return array|null Array with suspension details or null if not suspended
 */
function get_suspension_details($user_id) {
    global $pdo;
    
    try {
        auto_expire_suspensions();
        
        $stmt = $pdo->prepare("
            SELECT is_suspended, suspension_end_date, suspension_reason 
            FROM users 
            WHERE user_id = ? AND is_suspended = 1
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        return $result ?: null;
        
    } catch (PDOException $e) {
        error_log("Error getting suspension details: " . $e->getMessage());
        return null;
    }
}

/**
 * Suspend a user for a specified duration
 * * Critical Security Function: Uses a Database Transaction.
 * We must ensure that the User Update, History Log, and Notification all happen together.
 * * @param int $user_id User to suspend
 * @param int $admin_id Admin performing the suspension
 * @param int $duration_days Number of days to suspend
 * @param string $reason Reason for suspension
 * @return bool Success status
 */
function suspend_user($user_id, $admin_id, $duration_days, $reason) {
    global $pdo;
    
    try {
        // Start Transaction: Either all steps succeed, or none do.
        $pdo->beginTransaction();
        
        // Calculate the exact timestamp when the ban lifts
        $suspension_end = date('Y-m-d H:i:s', strtotime("+$duration_days days"));
        
        // Step 1: Update the user's main record to lock them out
        $stmt = $pdo->prepare("
            UPDATE users 
            SET is_suspended = 1, 
                suspension_end_date = ?, 
                suspension_reason = ?
            WHERE user_id = ?
        ");
        $stmt->execute([$suspension_end, $reason, $user_id]);
        
        // Step 2: Create an audit trail in the history table
        // This allows admins to see past offenses.
        $stmt = $pdo->prepare("
            INSERT INTO suspension_history 
            (user_id, admin_id, suspension_start, suspension_end, reason) 
            VALUES (?, ?, NOW(), ?, ?)
        ");
        $stmt->execute([$user_id, $admin_id, $suspension_end, $reason]);
        
        // Step 3: Send a system notification to the user so they know what happened
        $notification_msg = "Your account has been suspended until " . date('F j, Y g:i A', strtotime($suspension_end)) . ". Reason: " . $reason;
        $stmt = $pdo->prepare("
            INSERT INTO notifications 
            (user_id, message, type, is_read, created_at) 
            VALUES (?, ?, 'suspension', 0, NOW())
        ");
        $stmt->execute([$user_id, $notification_msg]);
        
        // Commit changes if no errors occurred
        $pdo->commit();
        return true;
        
    } catch (PDOException $e) {
        // If anything failed, undo changes to prevent data corruption
        $pdo->rollBack();
        error_log("Error suspending user: " . $e->getMessage());
        return false;
    }
}

/**
 * Manually unsuspend a user (before their suspension end date)
 * Used by admins to show mercy or correct mistakes.
 * * @param int $user_id User to unsuspend
 * @return bool Success status
 */
function unsuspend_user($user_id) {
    global $pdo;
    
    try {
        // Clear the suspension flags and dates
        $stmt = $pdo->prepare("
            UPDATE users 
            SET is_suspended = 0, 
                suspension_end_date = NULL, 
                suspension_reason = NULL
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        
        // Notify the user they are back in
        $stmt = $pdo->prepare("
            INSERT INTO notifications 
            (user_id, message, type, is_read, created_at) 
            VALUES (?, 'Your account suspension has been lifted. You can now use the platform normally.', 'unsuspension', 0, NOW())
        ");
        $stmt->execute([$user_id]);
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Error unsuspending user: " . $e->getMessage());
        return false;
    }
}

/**
 * Automatically expire suspensions that have passed their end date.
 * * Automation: This runs silently in the background whenever suspension status is checked.
 * It finds users whose 'suspension_end_date' is in the past and reactivates them.
 */
function auto_expire_suspensions() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET is_suspended = 0, 
                suspension_end_date = NULL, 
                suspension_reason = NULL
            WHERE is_suspended = 1 
            AND suspension_end_date <= NOW()
        ");
        $stmt->execute();
        
    } catch (PDOException $e) {
        error_log("Error auto-expiring suspensions: " . $e->getMessage());
    }
}

/**
 * Get suspension history for a user
 * Joins with the users table to get the name of the Admin who issued the ban.
 * * @param int $user_id
 * @param int $limit Number of records to return
 * @return array Suspension history records
 */
function get_suspension_history($user_id, $limit = 10) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                sh.*,
                CONCAT(u.first_name, ' ', u.last_name) as admin_name
            FROM suspension_history sh
            JOIN users u ON sh.admin_id = u.user_id
            WHERE sh.user_id = ?
            ORDER BY sh.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Error getting suspension history: " . $e->getMessage());
        return [];
    }
}

/**
 * Get count of all currently suspended users
 * Used for the Admin Dashboard statistics.
 * * @return int Count of suspended users
 */
function get_suspended_users_count() {
    global $pdo;
    
    try {
        // Run cleanup first to ensure count is accurate
        auto_expire_suspensions();
        
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM users 
            WHERE is_suspended = 1
        ");
        return (int)$stmt->fetchColumn();
        
    } catch (PDOException $e) {
        error_log("Error counting suspended users: " . $e->getMessage());
        return 0;
    }
}

/**
 * Check if user's books should be restricted
 * * Business Rule: If a user is suspended, their books are hidden from the marketplace.
 * * @param int $user_id
 * @return bool True if books should be restricted
 */
function are_user_books_restricted($user_id) {
    return is_user_suspended($user_id);
}

/**
 * Format remaining suspension time in human-readable format
 * Converts timestamps into "X days" or "Y hours".
 * * @param string $suspension_end_date
 * @return string Formatted time remaining
 */
function format_suspension_time_remaining($suspension_end_date) {
    $now = new DateTime();
    $end = new DateTime($suspension_end_date);
    $interval = $now->diff($end);
    
    if ($interval->days > 0) {
        return $interval->days . " day" . ($interval->days > 1 ? "s" : "");
    } elseif ($interval->h > 0) {
        return $interval->h . " hour" . ($interval->h > 1 ? "s" : "");
    } else {
        return $interval->i . " minute" . ($interval->i > 1 ? "s" : "");
    }
}
?>
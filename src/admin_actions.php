<?php
/**
 * ADMIN ACTION HANDLER
 * This script processes administrative actions such as sending warnings to users 
 * or dismissing reports. It acts as a controller between the dashboard UI and the database.
 */

// Uncomment for local development only
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once 'config.php';
require_once 'auth.php';

/**
 * ACCESS CONTROL (Authorization)
 * Ensures that only users with 'admin' privileges can execute this script.
 * This prevents unauthorized users from manipulating system data.
 */
require_admin(); 

// Fetch action parameters from the URL (GET) and form data (POST)
$type = $_GET['type'] ?? '';      // e.g., 'user', 'report'
$action = $_GET['action'] ?? '';  // e.g., 'warn', 'ignore'
$id = (int)($_GET['id'] ?? 0);    // Sanitize ID by casting to integer
$warning_msg = $_POST['warning_message'] ?? '';

// VALIDATION: Basic check to ensure a valid database ID is provided
if ($id <= 0) {
    header("Location: admin_dashboard.php?error=InvalidID");
    exit();
}

try {
    /**
     * CASE 1: SENDING USER WARNINGS
     * Logic for notifying a user about a policy violation without banning them.
     */
    if ($type === 'user' && $action === 'warn' && !empty($warning_msg)) {
        
        // Prepare statement to insert a notification into the user's inbox
        // Setting 'admin_warning' as the type allows for special styling in the UI
        $stmt = $pdo->prepare("INSERT INTO `notifications` (`user_id`, `message`, `type`, `is_read`, `created_at`) 
                               VALUES (?, ?, 'admin_warning', 0, NOW())");
        
        $stmt->execute([$id, $warning_msg]);
        
        // Redirect back to dashboard with a success message (URL Encoded)
        header("Location: admin_dashboard.php?success=Warning+sent+successfully");
        exit();
    }

    /**
     * CASE 2: DISMISSING REPORTS
     * Logic for handling reports that the admin deems non-violating or resolved.
     */
    elseif ($type === 'report' && $action === 'ignore') {
        
        // Mark the report status as 'reviewed' to remove it from the active queue
        $stmt = $pdo->prepare("UPDATE reports SET status = 'reviewed' WHERE report_id = ?");
        $stmt->execute([$id]);
        
        header("Location: admin_dashboard.php?success=Report+ignored");
        exit();
    }

} catch (PDOException $e) {
    /**
     * ERROR HANDLING
     * If a database error occurs, redirect to dashboard with the error details.
     * urlencode() is used to ensure the error message is safe for the URL.
     */
    header("Location: admin_dashboard.php?error=DatabaseError&details=" . urlencode($e->getMessage()));
    exit();
}
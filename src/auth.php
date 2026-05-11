<?php
/**
 * AUTHENTICATION HELPER FUNCTIONS
 * Core security file that manages user sessions, access control, and account status.
 * * * Purpose: Centralizes all logic for logging in, logging out, checking permissions,
 * and enforcing account suspensions across the application.
 */

// Include suspension helpers to enable status checking during login/navigation
require_once __DIR__ . '/suspension_helpers.php';

/**
 * Check if user is logged in
 * Validates if a valid user_id exists in the active session.
 * @return bool
 */
function is_logged_in() {
    // Ensure session is started before accessing $_SESSION to prevent errors
    // Check if session has not been started to avoid errors
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is an admin
 * Implements Role-Based Access Control (RBAC)
 * @return bool
 */
function is_admin() {
    // Checks both authentication (logged in) and authorization (role is 'admin')
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Require login - Middleware for protected pages
 * Redirects unauthenticated users to login and suspended users to the notice page.
 * MODIFIED: Added strict suspension enforcement.
 */
function require_login() {
    // 1. Basic Authentication Check
    if (!is_logged_in()) {
        header("Location: " . url('login.php'));
        exit();
    }
    
    // 2. Suspension Check (Security Enforcer)
    // We must allow suspended users to access 'suspension_notice.php' (to see why)
    // and 'logout.php' (to exit), but block them from everything else.
    $current_page = basename($_SERVER['PHP_SELF']);
    
    if ($current_page !== 'suspension_notice.php' && $current_page !== 'logout.php') {
        // Query the database via helper to check current status
        if (is_user_suspended(get_user_id())) {
            // Force redirect if suspended
            header("Location: " . url('suspension_notice.php'));
            exit();
        }
    }
}

/**
 * Require admin - Middleware for admin-only pages
 * Redirects to index if the user is logged in but not an admin.
 */
function require_admin() {
    require_login(); // First, ensure they are authenticated (Önce giriş yapmış mı bak)
    
    // Then check specific authorization
    if (!is_admin()) {
        header("Location: " . url('index.php?error=unauthorized'));
        exit();
    }
}

/**
 * Get current user ID
 * Helper to retrieve ID safely from session
 * @return int|null
 */
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user info
 * Returns a standardized array of user data for display purposes
 * @return array|null
 */
function get_user_info() {
    if (!is_logged_in()) return null;
    
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'first_name' => $_SESSION['first_name'] ?? '',
        'last_name' => $_SESSION['last_name'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? 'student' // Default to student if role is missing (Varsayılan student)
    ];
}

/**
 * Validate university email
 * Enforces domain restrictions for registration
 * @param string $email
 * @return bool
 */
function is_valid_university_email($email) {
    // Strict check for specific university domain
    return str_ends_with(strtolower($email), '@univ.edu');
}

/**
 * Set user session data
 * Initializes the session upon successful login
 * @param array $user Data fetched from database
 */
function set_user_session($user) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // SECURITY: Regenerate session ID to prevent Session Fixation attacks
    // (Güvenlik için session_regenerate_id kullanılması önerilir)
    session_regenerate_id(true);
    
    // Map database fields to session variables for easy access
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role']; // Stores 'admin' or 'student' (Veritabanındaki 'admin' veya 'student' değerini kaydeder)
}

/**
 * Destroy user session (logout)
 * Completely clears session data and ID
 */
function destroy_user_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = array(); // Clear variables
    session_destroy();   // Kill the session file on server
}
?>
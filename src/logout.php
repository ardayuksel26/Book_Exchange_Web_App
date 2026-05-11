<?php
/**
 * LOGOUT SCRIPT
 * Handles the secure termination of the user session.
 * * Purpose: Clears all session data and redirects the user back to the login page.
 */

// Import necessary files to access session helper functions
require_once 'config.php';
require_once 'auth.php';

// 1. Terminate Session
// Call the helper function (defined in auth.php) to:
// - Unset all $_SESSION variables
// - Destroy the actual session file on the server
destroy_user_session();

// 2. Redirect User
// Send the user back to the login page after successful logout.
header("Location: " . url('login.php'));

// 3. Stop Execution
// Security Best Practice: Always call exit() after a header redirect 
// to ensure no further code is executed.
exit();
?>
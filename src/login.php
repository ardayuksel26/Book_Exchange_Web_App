<?php
/**
 * LOGIN PAGE
 * Handles user authentication and session initiation.
 * * Purpose: Validates user credentials against the database and 
 * redirects them to the appropriate dashboard based on their role.
 */

require_once 'config.php';
require_once 'auth.php';

// ---------------------------------------------------------
// 1. PRE-LOGIN CHECK
// ---------------------------------------------------------
// Session Check: If the user is already logged in, redirect them immediately 
// to their appropriate dashboard (Admin or Student) to prevent re-login.
if (is_logged_in()) {
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        header("Location: " . url('admin_dashboard.php'));
    } else {
        header("Location: " . url('index.php'));
    }
    exit();
}

$error = '';

// ---------------------------------------------------------
// 2. AUTHENTICATION LOGIC (POST Request)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Input Sanitation: Trim whitespace to prevent accidental errors
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation: Ensure fields are not empty
    if (empty($email) || empty($password)) {
        $error = "Email and password are required.";
    } else {
        // Security: Use Prepared Statements to prevent SQL Injection
        // Fetch the user record associated with the provided email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        // Password Security: Use password_verify() to compare the input password 
        // against the hashed password stored in the database.
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // Success: Initialize the user session via helper function in auth.php
            set_user_session($user);
            
            // Role-Based Redirect: Send Admins to the dashboard and Students to the homepage
            if ($user['role'] === 'admin') {
                header("Location: " . url('admin_dashboard.php'));
            } else {
                header("Location: " . url('index.php'));
            }
            exit();
        } else {
            // Failure: Generic error message for security (don't reveal if email exists)
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Book Exchange</title>
    <link rel="stylesheet" href="<?= url('style.css') ?>">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>📚 Login</h1>
            <p class="subtitle">Welcome back to Book Exchange</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">Registration successful! Please login.</div>
            <?php endif; ?>
            
            <form method="POST" action="<?= url('login.php') ?>">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
            <p class="auth-footer">
                Don't have an account? <a href="<?= url('register.php') ?>">Register here</a>
            </p>
        </div>
    </div>
</body>
</html>
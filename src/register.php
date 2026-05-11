<?php
/**
 * REGISTRATION PAGE
 * Handles the creation of new student accounts.
 * * Purpose: Collects user details, validates university credentials, 
 * and securely stores the new account in the database.
 */

require_once 'config.php';
require_once 'auth.php';

$error = '';
$success = '';

// ---------------------------------------------------------
// FORM SUBMISSION HANDLING (POST Request)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs to remove unnecessary whitespace
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // ---------------------------------------------------------
    // 1. INPUT VALIDATION
    // ---------------------------------------------------------
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } 
    // Domain Check: Ensure the email belongs to the university domain (defined in auth.php)
    elseif (!is_valid_university_email($email)) {
        $error = "Only @univ.edu email addresses are allowed.";
    } 
    // Password Confirmation Check
    elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } 
    // Security: Enforce minimum password length
    elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // ---------------------------------------------------------
        // 2. DUPLICATE CHECK
        // ---------------------------------------------------------
        // Check if email already exists in the database
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = "Email already registered.";
        } else {
            // ---------------------------------------------------------
            // 3. ACCOUNT CREATION
            // ---------------------------------------------------------
            // Security: Hash the password using Bcrypt (PASSWORD_DEFAULT) before storing.
            // Never store passwords in plain text!
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user with default role 'student'
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES (?, ?, ?, ?, 'student')");
            
            if ($stmt->execute([$first_name, $last_name, $email, $password_hash])) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Book Exchange</title>
    <link rel="stylesheet" href="<?= url('style.css') ?>">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>📚 Register</h1>
            <p class="subtitle">Join the Book Exchange Platform</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="<?= url('register.php') ?>">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>University Email</label>
                    <input type="email" name="email" required placeholder="yourname@univ.edu" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <small>Only @univ.edu emails are accepted</small>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required minlength="6">
                    <small>At least 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required minlength="6">
                </div>
                
                <button type="submit" class="btn btn-primary">Register</button>
            </form>
            
            <p class="auth-footer">
                Already have an account? <a href="<?= url('login.php') ?>">Login here</a>
            </p>
        </div>
    </div>
</body>
</html>
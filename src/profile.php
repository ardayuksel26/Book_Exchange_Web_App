<?php
/**
 * USER PROFILE & SETTINGS PAGE
 * Displays user account details, activity statistics, and security settings.
 * * Purpose: Allows users to view their engagement metrics and securely change their password.
 */

require_once 'config.php';
require_once 'auth.php';
require_login(); // Security: Ensure session is active

require_once 'header.php';

$user_id = get_user_id();

// ---------------------------------------------------------
// 1. DATA RETRIEVAL
// ---------------------------------------------------------
// Fetch current user details from the database
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$error = '';
$success = '';

// ---------------------------------------------------------
// 2. PASSWORD CHANGE LOGIC (POST Request)
// ---------------------------------------------------------
// Handles secure password updates.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation: Check for empty fields
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required.";
    } 
    // Security: Verify the OLD password matches the hash in the database
    elseif (!password_verify($current_password, $user['password_hash'])) {
        $error = "Current password is incorrect.";
    } 
    // Validation: Ensure new passwords match
    elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } 
    // Validation: Enforce minimum length
    elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        // Success: Hash the new password and update the database
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        
        if ($stmt->execute([$new_hash, $user_id])) {
            $success = "Password changed successfully!";
        } else {
            $error = "Failed to change password.";
        }
    }
}

// ---------------------------------------------------------
// 3. ACTIVITY STATISTICS
// ---------------------------------------------------------
// Aggregates data from multiple tables to show user engagement.
// 

// Count total books uploaded by user
$stmt = $pdo->prepare("SELECT COUNT(*) as total_books FROM books WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats['total_books'] = $stmt->fetch()['total_books'];

// Count currently available books
$stmt = $pdo->prepare("SELECT COUNT(*) as available_books FROM books WHERE user_id = ? AND status = 'available'");
$stmt->execute([$user_id]);
$stats['available_books'] = $stmt->fetch()['available_books'];

// Count books currently rented out
$stmt = $pdo->prepare("SELECT COUNT(*) as rented_books FROM books WHERE user_id = ? AND status = 'rented'");
$stmt->execute([$user_id]);
$stats['rented_books'] = $stmt->fetch()['rented_books'];

// Count rental requests made by this user
$stmt = $pdo->prepare("SELECT COUNT(*) as rental_requests FROM rentals WHERE renter_id = ?");
$stmt->execute([$user_id]);
$stats['rental_requests'] = $stmt->fetch()['rental_requests'];

// Count swap requests made by this user
$stmt = $pdo->prepare("SELECT COUNT(*) as swap_requests FROM swaps WHERE requester_id = ?");
$stmt->execute([$user_id]);
$stats['swap_requests'] = $stmt->fetch()['swap_requests'];
?>

<style>
    .profile-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    
    /* Header with gradient background */
    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 2rem;
    }
    
    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: white;
        color: #667eea;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: 800;
    }
    
    .profile-header-info h1 {
        margin: 0 0 0.5rem 0;
        font-size: 2rem;
    }
    
    .profile-header-info p {
        margin: 0;
        opacity: 0.9;
    }
    
    /* Responsive Grid Layout */
    .content-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    .card {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.07);
    }
    
    .card h2 {
        margin: 0 0 1.5rem 0;
        color: #2d3748;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .info-table {
        width: 100%;
    }
    
    .info-table tr {
        border-bottom: 1px solid #e2e8f0;
    }
    
    .info-table tr:last-child {
        border-bottom: none;
    }
    
    .info-table th {
        text-align: left;
        padding: 0.75rem 0;
        color: #718096;
        font-weight: 600;
        width: 40%;
    }
    
    .info-table td {
        padding: 0.75rem 0;
        color: #2d3748;
    }
    
    .role-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 1rem;
    }
    
    .stat-card {
        text-align: center;
        padding: 1.5rem 1rem;
        background: #f7fafc;
        border-radius: 8px;
        border: 2px solid #e2e8f0;
        transition: all 0.3s;
    }
    
    .stat-card:hover {
        border-color: #667eea;
        transform: translateY(-2px);
    }
    
    .stat-value {
        font-size: 2.5rem;
        font-weight: 800;
        color: #667eea;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        font-size: 0.85rem;
        color: #718096;
        font-weight: 600;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: #2d3748;
        font-weight: 600;
    }
    
    .form-group input {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }
    
    .form-group input:focus {
        outline: none;
        border-color: #667eea;
    }
    
    .form-group small {
        display: block;
        margin-top: 0.25rem;
        color: #718096;
        font-size: 0.85rem;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.75rem 2rem;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .alert {
        padding: 1rem 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        font-weight: 500;
    }
    
    .alert-error {
        background: #fed7d7;
        color: #c53030;
        border-left: 4px solid #e53e3e;
    }
    
    .alert-success {
        background: #c6f6d5;
        color: #2f855a;
        border-left: 4px solid #38a169;
    }
    
    .full-width-card {
        grid-column: 1 / -1;
    }
</style>

<div class="profile-container">
    <div class="profile-header">
        <div class="profile-avatar">
            <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
        </div>
        <div class="profile-header-info">
            <h1>👤 <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h1>
            <p>Member since <?= date('F Y', strtotime($user['created_at'])) ?></p>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <div class="content-grid">
        <div class="card">
            <h2>📋 Account Information</h2>
            <table class="info-table">
                <tr>
                    <th>Full Name:</th>
                    <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                </tr>
                <tr>
                    <th>Role:</th>
                    <td><span class="role-badge"><?= htmlspecialchars($user['role']) ?></span></td>
                </tr>
                <tr>
                    <th>Member Since:</th>
                    <td><?= date('F j, Y', strtotime($user['created_at'])) ?></td>
                </tr>
            </table>
        </div>
        
        <div class="card full-width-card">
            <h2>📊 Activity Statistics</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['total_books'] ?></div>
                    <div class="stat-label">Total Books Listed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['available_books'] ?></div>
                    <div class="stat-label">Available Books</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['rented_books'] ?></div>
                    <div class="stat-label">Currently Rented</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['rental_requests'] ?></div>
                    <div class="stat-label">Rental Requests</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['swap_requests'] ?></div>
                    <div class="stat-label">Swap Requests</div>
                </div>
            </div>
        </div>
        
        <div class="card full-width-card">
            <h2>🔒 Change Password</h2>
            <form method="POST" action="profile.php">
                <div class="content-grid" style="margin-bottom: 0;">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required minlength="6">
                        <small>At least 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required minlength="6">
                    </div>
                </div>
                
                <button type="submit" name="change_password" class="btn-primary">
                    Change Password
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
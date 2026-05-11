<?php
/**
 * SUSPENSION NOTICE PAGE
 * The "Penalty Box" for the application.
 * * Purpose: This is the ONLY page a suspended user can see. 
 * It informs them why they are suspended, how long until it expires, 
 * and what restrictions are currently applied to their account.
 */

require_once 'config.php';
require_once 'auth.php';

// 

// ---------------------------------------------------------
// 1. SECURITY & STATE CHECKS
// ---------------------------------------------------------

// First, ensure the visitor is actually logged in.
if (!is_logged_in()) {
    header("Location: " . url('login.php'));
    exit();
}

$user_id = get_user_id();

// Fetch fresh suspension details from the database.
// We do this instead of relying on the session to ensure we have 
// the absolute latest 'end_date' and 'reason'.
$suspension = get_suspension_details($user_id);

// SANITY CHECK: If the user is NOT actually suspended (perhaps the time just expired),
// immediately redirect them back to the dashboard/homepage.
if (!$suspension) {
    header("Location: " . url('index.php'));
    exit();
}

// ---------------------------------------------------------
// 2. DATA FORMATTING
// ---------------------------------------------------------
// Calculate the "Time Remaining" string (e.g., "2 days 4 hours")
$time_remaining = format_suspension_time_remaining($suspension['suspension_end_date']);
// Format the exact expiration date for display
$end_date_formatted = date('F j, Y \a\t g:i A', strtotime($suspension['suspension_end_date']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Suspended - Book Exchange</title>
    <link rel="stylesheet" href="<?= url('style.css') ?>">
    
    <style>
        body {
            /* Gradient background to differentiate from the normal app interface */
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }
        
        .suspension-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 90%;
            padding: 3rem;
            text-align: center;
        }
        
        .suspension-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
        }
        
        .suspension-title {
            font-size: 2rem;
            color: #e53e3e; /* Red for alert */
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .suspension-subtitle {
            font-size: 1.1rem;
            color: #4a5568;
            margin-bottom: 2rem;
        }
        
        .info-box {
            background: #fff5f5;
            border: 2px solid #fc8181;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #fed7d7;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #742a2a;
        }
        
        .info-value {
            color: #4a5568;
        }
        
        .reason-box {
            background: #f7fafc;
            border-left: 4px solid #e53e3e;
            padding: 1rem;
            margin: 1.5rem 0;
            text-align: left;
            border-radius: 4px;
        }
        
        .reason-label {
            font-weight: 700;
            color: #2d3748;
            font-size: 0.9rem;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        
        .reason-text {
            color: #4a5568;
            line-height: 1.6;
        }
        
        .countdown-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .countdown-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }
        
        .countdown-value {
            font-size: 2.5rem;
            font-weight: 800;
        }
        
        .logout-btn {
            background: #4a5568;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: #2d3748;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .help-text {
            margin-top: 2rem;
            color: #718096;
            font-size: 0.9rem;
        }
        
        .restrictions-list {
            background: #fffaf0;
            border: 2px solid #f6ad55;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            text-align: left;
        }
        
        .restrictions-title {
            font-weight: 700;
            color: #7c2d12;
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        
        .restrictions-list ul {
            margin: 0;
            padding-left: 1.5rem;
            color: #744210;
        }
        
        .restrictions-list li {
            margin: 0.5rem 0;
        }
    </style>
</head>
<body>
    <div class="suspension-container">
        <div class="suspension-icon">⛔</div>
        <h1 class="suspension-title">Account Suspended</h1>
        <p class="suspension-subtitle">Your account has been temporarily suspended by an administrator</p>
        
        <div class="countdown-box">
            <div class="countdown-label">TIME REMAINING</div>
            <div class="countdown-value"><?= htmlspecialchars($time_remaining) ?></div>
        </div>
        
        <div class="info-box">
            <div class="info-row">
                <span class="info-label">Suspension Ends:</span>
                <span class="info-value"><?= htmlspecialchars($end_date_formatted) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Account Status:</span>
                <span class="info-value" style="color: #e53e3e; font-weight: 600;">SUSPENDED</span>
            </div>
        </div>
        
        <div class="reason-box">
            <div class="reason-label">📋 Reason for Suspension</div>
            <div class="reason-text"><?= nl2br(htmlspecialchars($suspension['suspension_reason'])) ?></div>
        </div>
        
        <div class="restrictions-list">
            <div class="restrictions-title">⚠️ Current Restrictions</div>
            <ul>
                <li>You cannot add, edit, or delete books</li>
                <li>You cannot make rental or swap requests</li>
                <li>Your existing books are hidden from other users</li>
                <li>You cannot interact with the platform</li>
                <li>You can only view this suspension notice</li>
            </ul>
        </div>
        
        <a href="<?= url('logout.php') ?>" class="logout-btn">Logout</a>
        
        <p class="help-text">
            Your access will be automatically restored when the suspension period ends.<br>
            If you believe this suspension was made in error, please contact an administrator.
        </p>
    </div>
</body>
</html>
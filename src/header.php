<?php
/**
 * GLOBAL HEADER FILE
 * Included on every page to provide consistent navigation, 
 * user authentication checks, and the notification system.
 */

require_once 'config.php';
require_once 'auth.php';

// Security Check: Ensure the user is authenticated before showing the header/navigation.
// If not logged in, they are redirected to login.php via this function.
require_login();

$user_id = get_user_id();

/**
 * NOTIFICATION COUNTER LOGIC
 * Calculates the total number of "pending" actions requiring the user's attention.
 * It sums up:
 * 1. Pending Rental Requests (where user is the owner)
 * 2. Pending Swap Requests (where user is the owner)
 */
// SAYAÇ: Sadece onay bekleyen (pending) talepleri sayar.
$stmt = $pdo->prepare("
    SELECT (
        (SELECT COUNT(*) FROM rentals WHERE owner_id = ? AND status = 'pending') + 
        (SELECT COUNT(*) FROM swaps WHERE owner_id = ? AND status = 'pending')
    ) as pending_actions
");
$stmt->execute([$user_id, $user_id]);
$unread_count = $stmt->fetch()['pending_actions'];

// User Role & Info Retrieval
// Fetches the user's name for the profile menu and role to determine admin access.
$user_info_stmt = $pdo->prepare("SELECT first_name, role FROM users WHERE user_id = ?");
$user_info_stmt->execute([$user_id]);
$user_data = $user_info_stmt->fetch();

// Determine if the current user has administrative privileges
$is_admin = ($user_data['role'] === 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Exchange Platform</title>
    <link rel="stylesheet" href="<?= url('style.css') ?>">
    <style>
        /* Navigation Layout Configuration */
        .nav-container { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .nav-links { display: flex; align-items: center; gap: 20px; }
        
        /* Dropdown Menu CSS Logic */
        .dropdown { position: relative; display: inline-block; }
        
        .dropdown-content {
            display: none; /* Hidden by default */
            position: absolute;
            background-color: white;
            min-width: 160px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
            z-index: 1000;
            border-radius: 8px;
            top: 100%;
            right: 0;
            margin-top: -2px;
            overflow: hidden;
        }

        /* Invisible bridge to keep dropdown open when moving mouse from button to content */
        .dropdown::after {
            content: "";
            position: absolute;
            bottom: -15px;
            left: 0;
            width: 100%;
            height: 15px;
            background: transparent;
            z-index: 999;
        }

        /* Show content on hover */
        .dropdown:hover .dropdown-content { display: block; }

        /* NOTIFICATION BADGE STYLING */
        .badge {
            background-color: #e74c3c !important;
            color: white !important;
            font-size: 11px !important;
            font-weight: bold !important;
            width: 20px !important;
            height: 20px !important;
            border-radius: 50% !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex-shrink: 0 !important;
            box-sizing: border-box !important;
            line-height: 1 !important;
            padding: 0 !important;
        }
    </style>
</head>
<body>
    <nav class="navbar" style="background-color: #2c3e50; padding: 10px 0; color: white;">
        <div class="nav-container">
            <a href="<?= url('index.php') ?>" class="nav-brand" style="color: white; text-decoration: none; font-weight: bold; font-size: 1.5rem;">📚 Book Exchange</a>
            
            <form action="<?= url('index.php') ?>" method="GET" class="nav-search">
                <input type="text" name="search" placeholder="Title, author or category..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="padding: 8px 15px; border-radius: 20px; border: none; width: 250px;">
            </form>

            <div class="nav-links">
                <?php if ($is_admin): ?>
                    <a href="<?= url('admin_dashboard.php') ?>" style="color: #f6ad55; font-weight: bold; border: 1px solid #f6ad55; padding: 5px 12px; border-radius: 6px;">🛡️ Admin Panel</a>
                <?php endif; ?>

                <a href="<?= url('add_book.php') ?>" style="color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap;">➕ Add Book</a>
                
                <div class="dropdown">
                    <button class="dropbtn" style="background: none; border: none; color: white; cursor: pointer; font-family: inherit;">📖 My Books ▾</button>
                    <div class="dropdown-content">
                        <a href="<?= url('my_books.php') ?>" style="color: #2d3748; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #eee;">My Listings</a>
                        <a href="<?= url('my_books.php?tab=rented') ?>" style="color: #2d3748; padding: 12px 16px; text-decoration: none; display: block;">Rented by Me</a>
                    </div>
                </div>

                <a href="<?= url('notifications.php') ?>" style="color: white; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;">🔔 Notifications <?php if ($unread_count > 0): ?><span class="badge"><?= $unread_count ?></span><?php endif; ?></a>

                <div class="dropdown">
                    <button class="dropbtn" style="background: none; border: none; color: white; cursor: pointer; font-family: inherit;">👤 <?= htmlspecialchars($user_data['first_name']) ?> ▾</button>
                    <div class="dropdown-content">
                        <a href="<?= url('profile.php') ?>" style="color: #2d3748; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #eee;">Profile Settings</a>
                        <a href="<?= url('logout.php') ?>" style="color: #e74c3c; padding: 12px 16px; text-decoration: none; display: block; font-weight: bold;">🚪 Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container" style="margin-top: 20px;">
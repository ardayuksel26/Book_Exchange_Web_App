<?php
/**
 * NOTIFICATIONS & REQUESTS CENTER
 * Central hub where users view system messages and manage incoming trade/rental proposals.
 * * Purpose: Aggregates three distinct data streams:
 * 1. System Notifications (Admin warnings, general info)
 * 2. Incoming Rental Requests (requiring approval)
 * 3. Incoming Swap Requests (requiring approval)
 */

require_once 'config.php';
require_once 'auth.php';

// Security Check: Enforce authentication. Redirects unauthenticated users.
// Giriş kontrolü
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

require_once 'header.php';
$user_id = get_user_id();

// 

// ---------------------------------------------------------
// 1. NOTIFICATION LOGIC
// ---------------------------------------------------------
// Fetch notifications (Including Admin warnings and system messages)
// Ordered by newest first
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

// AUTO-READ LOGIC: 
// Automatically marks unread notifications as 'seen' (is_read = 1) 
// the moment the user visits this page.
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$user_id]);

// ---------------------------------------------------------
// 2. RENTAL REQUESTS LOGIC
// ---------------------------------------------------------
// Fetch pending rental requests where the current user is the OWNER of the book.
// Joins 'books' to get the title/price and 'users' to get the renter's name.
$stmt = $pdo->prepare("SELECT r.rental_id, r.book_id, r.start_date, r.end_date, 
                       b.title, b.price, u.first_name, u.last_name
                       FROM rentals r
                       JOIN books b ON r.book_id = b.book_id
                       JOIN users u ON r.renter_id = u.user_id
                       WHERE r.owner_id = ? AND r.status = 'pending'
                       ORDER BY r.created_at DESC");
$stmt->execute([$user_id]);
$pending_rentals = $stmt->fetchAll();

// ---------------------------------------------------------
// 3. SWAP REQUESTS LOGIC
// ---------------------------------------------------------
// Fetch pending swap requests where the current user is the OWNER.
// Complex Join: Requires joining the 'books' table TWICE:
// - b1: The book the requester wants (Requested Book)
// - b2: The book the requester is offering in return (Offered Book)
$stmt = $pdo->prepare("SELECT s.swap_id, s.requested_book_id, s.offered_book_id,
                       b1.title as requested_title, b2.title as offered_title,
                       u.first_name, u.last_name
                       FROM swaps s
                       JOIN books b1 ON s.requested_book_id = b1.book_id
                       JOIN books b2 ON s.offered_book_id = b2.book_id
                       JOIN users u ON s.requester_id = u.user_id
                       WHERE s.owner_id = ? AND s.status = 'pending'
                       ORDER BY s.created_at DESC");
$stmt->execute([$user_id]);
$pending_swaps = $stmt->fetchAll();
?>

<div class="container mt-4">
    <h1 style="margin-bottom: 30px; color: #2d3748;">🔔 Notifications</h1>

    <div class="notification-section" style="margin-bottom: 40px;">
        <h2 style="font-size: 1.25rem; color: #4a5568; margin-bottom: 15px;">📫 Messages from System</h2>
        
        <?php if (empty($notifications)): ?>
            <div style="padding: 20px; background: #f8fafc; border-radius: 8px; text-align: center; color: #a0aec0; border: 1px dashed #cbd5e0;">
                No messages found.
            </div>
        <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notif): 
                    // Visual Distinction Logic: 
                    // Checks if the notification type is a critical 'admin_warning' to apply red styling.
                    // KRİTİK AYRIM: Sadece admin_warning tipindekiler kırmızı olur
                    $is_admin = ($notif['type'] === 'admin_warning');
                ?>
                    <div class="msg-card" style="padding: 15px; background: white; border-radius: 10px; margin-bottom: 12px; border-left: 6px solid <?= $is_admin ? '#e53e3e' : '#3182ce' ?>; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                        <div style="display: flex; flex-direction: column;">
                            <div style="margin-bottom: 5px;">
                                <?php if($is_admin): ?>
                                    <span style="background: #fff5f5; color: #e53e3e; font-size: 0.65rem; font-weight: 800; padding: 3px 8px; border-radius: 4px; text-transform: uppercase;">⚠️ Admin Warning</span>
                                <?php else: ?>
                                    <span style="background: #ebf8ff; color: #3182ce; font-size: 0.65rem; font-weight: 800; padding: 3px 8px; border-radius: 4px; text-transform: uppercase;">ℹ️ Information</span>
                                <?php endif; ?>
                            </div>
                            
                            <p style="margin: 5px 0; color: #2d3748; font-size: 0.95rem; font-weight: <?= $is_admin ? '600' : '400' ?>;">
                                <?= htmlspecialchars($notif['message']) ?>
                            </p>
                            
                            <small style="color: #a0aec0; font-size: 0.8rem;"><?= date('d M, H:i', strtotime($notif['created_at'])) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="requests-section">
        <h2 style="font-size: 1.25rem; color: #4a5568; margin-bottom: 15px;">📥 Trade & Rental Requests</h2>
        
        <?php if (empty($pending_rentals) && empty($pending_swaps)): ?>
            <div style="padding: 20px; background: #f8fafc; border-radius: 8px; text-align: center; color: #a0aec0; border: 1px dashed #cbd5e0;">
                No pending requests.
            </div>
        <?php else: ?>
            <?php foreach ($pending_rentals as $rental): 
                // On-the-fly Calculation:
                // Computes the total days and total earnings based on daily price.
                $start = new DateTime($rental['start_date']);
                $end = new DateTime($rental['end_date']);
                $days = $start->diff($end)->days + 1; // +1 to include the start day
                $total_price = $days * $rental['price'];
            ?>
                <div class="request-card" style="border-left-color: #3182ce;">
                    <div class="request-info">
                        <span class="req-badge req-badge-rental">RENTAL</span>
                        <h4><?= htmlspecialchars($rental['first_name']) ?> wants to rent your book</h4>
                        <p><strong>Book:</strong> <?= htmlspecialchars($rental['title']) ?></p>
                        <div class="price-info">
                            💰 Earnings: $<?= number_format($total_price, 2) ?> (<?= $days ?> days)
                        </div>
                    </div>
                    <div class="request-actions">
                        <a href="<?= url('rental_action.php?rental_id=' . $rental['rental_id'] . '&action=accept') ?>" class="btn btn-success">Accept</a>
                        <a href="<?= url('rental_action.php?rental_id=' . $rental['rental_id'] . '&action=decline') ?>" class="btn btn-danger">Decline</a>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php foreach ($pending_swaps as $swap): ?>
                <div class="request-card" style="border-left-color: #805ad5;">
                    <div class="request-info">
                        <span class="req-badge req-badge-swap">SWAP</span>
                        <h4><?= htmlspecialchars($swap['first_name']) ?> wants a swap</h4>
                        <p><strong>Offer:</strong> <span style="color: #805ad5;"><?= htmlspecialchars($swap['offered_title']) ?></span></p>
                        <p><strong>For:</strong> <?= htmlspecialchars($swap['requested_title']) ?></p>
                    </div>
                    <div class="request-actions">
                        <a href="<?= url('swap_action.php?swap_id=' . $swap['swap_id'] . '&action=accept') ?>" class="btn btn-success">Accept</a>
                        <a href="<?= url('swap_action.php?swap_id=' . $swap['swap_id'] . '&action=decline') ?>" class="btn btn-danger">Decline</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
    .request-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: white;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 15px;
        border: 1px solid #e2e8f0;
        border-left-width: 6px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    .req-badge {
        font-size: 0.7rem;
        font-weight: 800;
        padding: 3px 8px;
        border-radius: 4px;
        margin-bottom: 8px;
        display: inline-block;
        width: auto;
        height: auto;
        background-color: transparent;
        line-height: normal;
    }
    .req-badge-rental { background: #ebf8ff !important; color: #3182ce !important; }
    .req-badge-swap { background: #faf5ff !important; color: #805ad5 !important; }
    
    .price-info {
        background: #f0fff4;
        color: #27ae60;
        padding: 8px 12px;
        border-radius: 6px;
        font-weight: 700;
        margin-top: 10px;
        display: inline-block;
    }

    .request-actions { display: flex; flex-direction: column; gap: 8px; }
    
    .btn {
        padding: 8px 20px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.85rem;
        text-align: center;
        min-width: 110px;
    }
    .btn-success { background: #38a169; color: white; }
    .btn-danger { background: #e53e3e; color: white; }
    
    .request-info h4 { margin: 0 0 5px 0; color: #2d3748; }
    .request-info p { margin: 3px 0; font-size: 0.9rem; color: #4a5568; }
</style>

<?php require_once 'footer.php'; ?>
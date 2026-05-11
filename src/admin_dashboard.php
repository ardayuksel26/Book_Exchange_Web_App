<?php
/**
 * ADMIN DASHBOARD
 * Central management hub for system administrators.
 * This page provides a high-level overview of system metrics
 * and quick access to moderation tools.
 */

// Uncomment for local development only
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once 'config.php';
require_once 'auth.php';

/**
 * SECURITY CHECK
 * Enforces the "Principle of Least Privilege" by ensuring only admins 
 * can view system-wide statistics.
 */
require_admin(); 

require_once 'header.php';

/**
 * DATA AGGREGATION
 * Using SQL COUNT functions to fetch vital statistics for the dashboard cards.
 */
// Total registered students
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
// Total inventory count
$total_books = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
// Count of active rental transactions
$active_rentals = $pdo->query("SELECT COUNT(*) FROM rentals WHERE status = 'accepted'")->fetchColumn();
// Count of unresolved policy violation reports
$pending_reports = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();

/**
 * FEATURE DETECTION & COMPATIBILITY
 * Dynamic check to see if the Suspension System is installed in the database.
 * This prevents the script from crashing if the 'is_suspended' column is missing.
 */
$suspension_stats_available = false;
try {
    $check_column = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_suspended'");
    if ($check_column->rowCount() > 0) {
        $suspension_stats_available = true;
        $suspended_users = $pdo->query("SELECT COUNT(*) FROM users WHERE is_suspended = 1")->fetchColumn();
    }
} catch (PDOException $e) {
    // Graceful failure: if column doesn't exist, we simply hide the suspension features
    $suspension_stats_available = false;
}

/**
 * MODERATION QUEUE
 * Fetching the most recent pending reports using a triple-table join logic.
 * We link Reports with Books (to get titles) and Users (to see who reported).
 */
$stmt = $pdo->query("
    SELECT r.*, b.title as book_title, u.first_name as reporter_name 
    FROM reports r 
    JOIN books b ON r.reported_book_id = b.book_id 
    JOIN users u ON r.reporter_id = u.user_id 
    WHERE r.status = 'pending' 
    ORDER BY r.created_at DESC 
    LIMIT 5
");
$reports = $stmt->fetchAll();
?>

<div class="container mt-4">
    <?php if(isset($_GET['success'])): ?>
        <div style="background: #c6f6d5; color: #2f855a; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
            ✅ <?= htmlspecialchars($_GET['success']) ?>
        </div>
    <?php endif; ?>

    <div style="margin-bottom: 2rem; border-bottom: 2px solid #edf2f7; padding-bottom: 1rem;">
        <h1 style="margin: 0; font-size: 2rem; color: #2d3748;">🛡️ Admin Dashboard</h1>
        <p style="margin: 5px 0 0 0; color: #718096;">Secure System Management & Control</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
        <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 5px solid #3182ce;">
            <span style="color: #718096; font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">Students</span>
            <div style="font-size: 2.2rem; font-weight: 800; color: #2d3748;"><?= $total_users ?></div>
        </div>
        
        <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 5px solid #e74c3c;">
            <span style="color: #718096; font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">Reports</span>
            <div style="font-size: 2.2rem; font-weight: 800; color: #e74c3c;"><?= $pending_reports ?></div>
        </div>
        
        <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 5px solid #38a169;">
            <span style="color: #718096; font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">Books</span>
            <div style="font-size: 2.2rem; font-weight: 800; color: #2d3748;"><?= $total_books ?></div>
        </div>
        
        <?php if($suspension_stats_available): ?>
        <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 5px solid #9333ea;">
            <span style="color: #718096; font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">Suspended</span>
            <div style="font-size: 2.2rem; font-weight: 800; color: #9333ea;"><?= $suspended_users ?></div>
            <a href="admin_suspend.php" style="display: inline-block; margin-top: 0.5rem; color: #9333ea; text-decoration: none; font-size: 0.8rem; font-weight: 600;">
                Manage →
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if($suspension_stats_available): ?>
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; color: white; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.2rem;">⛔ User Suspension Management</h3>
            <p style="margin: 0; opacity: 0.9; font-size: 0.9rem;">Suspend problematic users and manage account restrictions</p>
        </div>
        <a href="admin_suspend.php" style="background: white; color: #667eea; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 700; white-space: nowrap;">
            Open Suspension Panel
        </a>
    </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr; gap: 2rem; margin-bottom: 3rem;">
        <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 10px 15px rgba(0,0,0,0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; color: #2d3748; font-size: 1.2rem;">🚩 Reported Items Preview</h3>
                <a href="admin_reports.php" style="background: #3182ce; color: white; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600;">
                    View All Reports →
                </a>
            </div>
            <table style="width: 100%; border-collapse: collapse;">
                <tbody>
                    <?php if(empty($reports)): ?>
                        <tr><td style="padding: 20px; text-align: center; color: #a0aec0;">No pending reports at this time.</td></tr>
                    <?php endif; ?>
                    <?php foreach($reports as $report): ?>
                    <tr style="border-bottom: 1px solid #edf2f7;">
                        <td style="padding: 12px;">
                            <span style="font-weight: 600; color: #4a5568;"><?= htmlspecialchars($report['book_title']) ?></span>
                            <br><small style="color: #e74c3c;">Reason: <?= htmlspecialchars($report['reason']) ?></small>
                        </td>
                        <td style="padding: 12px; text-align: right;">
                            <a href="admin_actions.php?type=report&action=ignore&id=<?= $report['report_id'] ?>" 
                               style="background: #edf2f7; color: #4a5568; padding: 6px 10px; border-radius: 6px; text-decoration: none; font-size: 0.7rem; font-weight: 700;">IGNORE</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
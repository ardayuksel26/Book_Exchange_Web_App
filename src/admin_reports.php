<?php
/**
 * ADMIN REPORTS PAGE
 * Shows all reported items with filtering and management options
 * * Purpose: Allows administrators to view user-reported books, 
 * see the reason for the report, and take action (view or mark as reviewed).
 */

// Import necessary configuration and authentication systems
require_once 'config.php';
require_once 'auth.php';

// Security Check: Ensure the user is logged in and has 'admin' role
require_admin(); // Only admins can access

require_once 'header.php';

// ---------------------------------------------------------
// 1. ACTION HANDLING
// ---------------------------------------------------------
// Check if an administrative action is requested via GET parameters
if (isset($_GET['action']) && isset($_GET['report_id'])) {
    $report_id = (int)$_GET['report_id']; // Cast to int for security
    $action = $_GET['action'];
    
    // Logic to mark a report as 'reviewed' (effectively ignoring it/closing it)
    if ($action === 'ignore' && $report_id > 0) {
        // Update the database status using a prepared statement
        $pdo->prepare("UPDATE reports SET status = 'reviewed' WHERE report_id = ?")->execute([$report_id]);
        
        // Redirect back to the page to prevent form resubmission and show success message
        header("Location: admin_reports.php?success=Report+marked+as+reviewed");
        exit();
    }
}

// ---------------------------------------------------------
// 2. DATA RETRIEVAL & FILTERING
// ---------------------------------------------------------

// Determine current filter state (defaults to 'pending' if not set)
$filter = $_GET['filter'] ?? 'pending';

// Fetch reports based on the selected filter
// We join multiple tables (reports, books, users) to get all context in one query
if ($filter === 'all') {
    // Fetch ALL reports regardless of status
    $stmt = $pdo->query("
        SELECT r.*, b.title as book_title, u.first_name as reporter_name, u.last_name as reporter_lastname,
               owner.first_name as owner_name, owner.last_name as owner_lastname
        FROM reports r 
        JOIN books b ON r.reported_book_id = b.book_id 
        JOIN users u ON r.reporter_id = u.user_id
        JOIN users owner ON b.user_id = owner.user_id
        ORDER BY r.created_at DESC
    ");
} else {
    // Fetch reports specific to the status (pending or reviewed)
    $stmt = $pdo->prepare("
        SELECT r.*, b.title as book_title, u.first_name as reporter_name, u.last_name as reporter_lastname,
               owner.first_name as owner_name, owner.last_name as owner_lastname
        FROM reports r 
        JOIN books b ON r.reported_book_id = b.book_id 
        JOIN users u ON r.reporter_id = u.user_id
        JOIN users owner ON b.user_id = owner.user_id
        WHERE r.status = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$filter]);
}
$reports = $stmt->fetchAll();

// ---------------------------------------------------------
// 3. DASHBOARD STATISTICS
// ---------------------------------------------------------
// Calculate counts for the top statistic boxes
$total_reports = $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
$pending_count = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
$reviewed_count = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'reviewed'")->fetchColumn();
?>

<style>
    .filter-tabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 2rem;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .filter-tab {
        padding: 0.75rem 1.5rem;
        background: transparent;
        border: none;
        color: #718096;
        font-weight: 600;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
        text-decoration: none;
    }
    
    .filter-tab:hover {
        color: #3182ce;
    }
    
    .filter-tab.active {
        color: #3182ce;
        border-bottom-color: #3182ce;
    }
    
    .report-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border-left: 4px solid #e53e3e; /* Red border for emphasis */
    }
    
    .report-card.reviewed {
        border-left-color: #a0aec0; /* Grey border for resolved items */
        opacity: 0.7;
    }
    
    .report-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 1rem;
    }
    
    .report-info h3 {
        margin: 0 0 0.5rem 0;
        color: #2d3748;
        font-size: 1.1rem;
    }
    
    .report-meta {
        font-size: 0.85rem;
        color: #718096;
    }
    
    .report-reason {
        background: #fff5f5;
        padding: 1rem;
        border-radius: 8px;
        margin: 1rem 0;
        border-left: 3px solid #e53e3e;
    }
    
    .report-reason strong {
        color: #742a2a;
        display: block;
        margin-bottom: 0.5rem;
    }
    
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    
    .status-pending {
        background: #fed7d7;
        color: #c53030;
    }
    
    .status-reviewed {
        background: #e2e8f0;
        color: #4a5568;
    }
    
    .action-btn {
        padding: 0.5rem 1rem;
        border-radius: 6px;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-block;
        margin-right: 0.5rem;
    }
    
    .btn-view {
        background: #3182ce;
        color: white;
    }
    
    .btn-ignore {
        background: #718096;
        color: white;
    }
    
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .stat-box {
        background: white;
        padding: 1.5rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .stat-box h4 {
        margin: 0 0 0.5rem 0;
        color: #718096;
        font-size: 0.85rem;
        text-transform: uppercase;
        font-weight: 600;
    }
    
    .stat-box .value {
        font-size: 2rem;
        font-weight: 800;
        color: #2d3748;
    }
</style>

<div class="container mt-4">
    <?php if(isset($_GET['success'])): ?>
        <div style="background: #c6f6d5; color: #2f855a; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
            ✅ <?= htmlspecialchars($_GET['success']) ?>
        </div>
    <?php endif; ?>
    
    <div style="margin-bottom: 2rem;">
        <a href="admin_dashboard.php" style="text-decoration: none; color: #3182ce; font-weight: bold;">← Back to Dashboard</a>
        <h1 style="margin-top: 1rem; color: #2d3748;">🚩 Reported Items</h1>
        <p style="color: #718096;">Review and manage reported books</p>
    </div>
    
    <div class="stats-row">
        <div class="stat-box" style="border-left: 4px solid #3182ce;">
            <h4>Total Reports</h4>
            <div class="value"><?= $total_reports ?></div>
        </div>
        <div class="stat-box" style="border-left: 4px solid #e53e3e;">
            <h4>Pending</h4>
            <div class="value"><?= $pending_count ?></div>
        </div>
        <div class="stat-box" style="border-left: 4px solid #38a169;">
            <h4>Reviewed</h4>
            <div class="value"><?= $reviewed_count ?></div>
        </div>
    </div>
    
    <div class="filter-tabs">
        <a href="admin_reports.php?filter=pending" class="filter-tab <?= $filter === 'pending' ? 'active' : '' ?>">
            Pending (<?= $pending_count ?>)
        </a>
        <a href="admin_reports.php?filter=reviewed" class="filter-tab <?= $filter === 'reviewed' ? 'active' : '' ?>">
            Reviewed (<?= $reviewed_count ?>)
        </a>
        <a href="admin_reports.php?filter=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">
            All Reports (<?= $total_reports ?>)
        </a>
    </div>
    
    <?php if(empty($reports)): ?>
        <div style="text-align: center; padding: 3rem; background: white; border-radius: 12px;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📭</div>
            <h3 style="color: #4a5568;">No reports found</h3>
            <p style="color: #a0aec0;">There are no <?= $filter === 'all' ? '' : $filter ?> reports at the moment.</p>
        </div>
    <?php else: ?>
        <?php foreach($reports as $report): ?>
            <div class="report-card <?= $report['status'] === 'reviewed' ? 'reviewed' : '' ?>">
                <div class="report-header">
                    <div class="report-info">
                        <h3>📚 <?= htmlspecialchars($report['book_title']) ?></h3>
                        <div class="report-meta">
                            Reported by: <strong><?= htmlspecialchars($report['reporter_name'] . ' ' . $report['reporter_lastname']) ?></strong>
                            • Book Owner: <strong><?= htmlspecialchars($report['owner_name'] . ' ' . $report['owner_lastname']) ?></strong>
                            • <?= date('F j, Y \a\t g:i A', strtotime($report['created_at'])) ?>
                        </div>
                    </div>
                    <span class="status-badge status-<?= $report['status'] ?>">
                        <?= htmlspecialchars($report['status']) ?>
                    </span>
                </div>
                
                <div class="report-reason">
                    <strong>📋 Reason for Report:</strong>
                    <?= nl2br(htmlspecialchars($report['reason'])) ?>
                </div>
                
                <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                    <a href="book_detail.php?book_id=<?= $report['reported_book_id'] ?>" class="action-btn btn-view">
                        View Book
                    </a>
                    
                    <?php if($report['status'] === 'pending'): ?>
                        <a href="admin_reports.php?action=ignore&report_id=<?= $report['report_id'] ?>&filter=<?= $filter ?>" 
                           class="action-btn btn-ignore"
                           onclick="return confirm('Mark this report as reviewed?')">
                            Mark as Reviewed
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
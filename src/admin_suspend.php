<?php
/**
 * ADMIN SUSPENSION MANAGEMENT PAGE
 * Allows admins to suspend/unsuspend users and view suspension history
 * * Purpose: Central control panel for managing user discipline.
 * Handles the logic for processing suspension forms and listing user statuses.
 */

// Import configuration and authentication systems
require_once 'config.php';
require_once 'auth.php';

// Security Check: Strictly enforce Admin access
require_admin(); // Only admins can access

// Initialize variables for action handling
$action = $_GET['action'] ?? 'list';
$user_id = (int)($_GET['user_id'] ?? 0); // Cast to int for security (prevents SQL injection)

// ---------------------------------------------------------
// 1. POST REQUEST HANDLING (Form Submissions)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CASE A: Suspend a User
    if ($action === 'suspend' && $user_id > 0) {
        $duration = (int)$_POST['duration_days'];
        $reason = trim($_POST['reason']);
        
        // Validate inputs: Ensure duration is positive and reason is not empty
        if ($duration > 0 && !empty($reason)) {
            $admin_id = get_user_id(); // Get current admin's ID for audit logging
            
            // Call helper function to update database (sets is_suspended=1 and calculates end date)
            if (suspend_user($user_id, $admin_id, $duration, $reason)) {
                header("Location: " . url('admin_suspend.php?success=User+suspended+successfully'));
                exit();
            } else {
                $error_msg = "Failed to suspend user. Please try again.";
            }
        } else {
            $error_msg = "Please provide valid duration and reason.";
        }
    }
    
    // CASE B: Unsuspend (Reactivate) a User
    elseif ($action === 'unsuspend' && $user_id > 0) {
        // Call helper function to clear suspension flags
        if (unsuspend_user($user_id)) {
            header("Location: " . url('admin_suspend.php?success=User+unsuspended+successfully'));
            exit();
        } else {
            $error_msg = "Failed to unsuspend user.";
        }
    }
}

// ---------------------------------------------------------
// 2. DATA RETRIEVAL
// ---------------------------------------------------------

// Fetch all users to display in the management table
// We order by 'is_suspended' DESC so suspended users appear at the top of the list
$stmt = $pdo->query("
    SELECT user_id, first_name, last_name, email, role, 
           is_suspended, suspension_end_date, suspension_reason, created_at
    FROM users 
    ORDER BY is_suspended DESC, created_at DESC
");
$users = $stmt->fetchAll();

// Get statistics for the dashboard summary cards
$total_suspended = get_suspended_users_count();
$total_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();

require_once 'header.php';
?>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        color: #718096;
        font-size: 0.9rem;
        text-transform: uppercase;
        font-weight: 600;
    }
    
    .suspended-badge {
        background: #e53e3e;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
    }
    
    .active-badge {
        background: #38a169;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
    }
    
    .action-btn {
        padding: 6px 12px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 0.75rem;
        font-weight: 700;
        margin: 0 2px;
        display: inline-block;
    }
    
    .suspend-btn {
        background: #e53e3e;
        color: white;
    }
    
    .unsuspend-btn {
        background: #38a169;
        color: white;
    }
    
    .history-btn {
        background: #3182ce;
        color: white;
    }
    
    /* Modal Styling for the Pop-up Form */
    .modal {
        display: none; /* Hidden by default */
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    
    .modal.active {
        display: flex; /* Flex is used to center content */
    }
    
    .modal-content {
        background: white;
        padding: 2rem;
        border-radius: 12px;
        max-width: 500px;
        width: 90%;
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #2d3748;
    }
    
    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        font-size: 1rem;
    }
    
    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }
    
    .btn-primary {
        background: #3182ce;
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
    }
    
    .btn-secondary {
        background: #718096;
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        margin-left: 0.5rem;
    }
</style>

<div class="container mt-4">
    <?php if(isset($_GET['success'])): ?>
        <div style="background: #c6f6d5; color: #2f855a; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
            ✅ <?= htmlspecialchars($_GET['success']) ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($error_msg)): ?>
        <div style="background: #fed7d7; color: #c53030; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
            ❌ <?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>
    
    <div style="margin-bottom: 2rem;">
        <a href="<?= url('admin_dashboard.php') ?>" style="text-decoration: none; color: #3182ce; font-weight: bold;">← Back to Dashboard</a>
        <h1 style="margin-top: 1rem; color: #2d3748;">⛔ User Suspension Management</h1>
        <p style="color: #718096;">Manage user suspensions and view suspension history</p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card" style="border-left: 4px solid #3182ce;">
            <div class="stat-value" style="color: #3182ce;"><?= $total_students ?></div>
            <div class="stat-label">Total Students</div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #e53e3e;">
            <div class="stat-value" style="color: #e53e3e;"><?= $total_suspended ?></div>
            <div class="stat-label">Currently Suspended</div>
        </div>
    </div>
    
    <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 1.5rem;">All Users</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; border-bottom: 2px solid #edf2f7;">
                    <th style="padding: 12px; color: #718096;">NAME</th>
                    <th style="padding: 12px; color: #718096;">EMAIL</th>
                    <th style="padding: 12px; color: #718096;">STATUS</th>
                    <th style="padding: 12px; color: #718096;">SUSPENSION ENDS</th>
                    <th style="padding: 12px; color: #718096; text-align: right;">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $user): ?>
                    <?php if($user['role'] === 'student'): ?>
                    <tr style="border-bottom: 1px solid #edf2f7;">
                        <td style="padding: 12px; font-weight: 600;">
                            <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                        </td>
                        <td style="padding: 12px; color: #4a5568;">
                            <?= htmlspecialchars($user['email']) ?>
                        </td>
                        
                        <td style="padding: 12px;">
                            <?php if($user['is_suspended']): ?>
                                <span class="suspended-badge">SUSPENDED</span>
                            <?php else: ?>
                                <span class="active-badge">ACTIVE</span>
                            <?php endif; ?>
                        </td>
                        
                        <td style="padding: 12px; color: #718096; font-size: 0.85rem;">
                            <?php if($user['is_suspended']): ?>
                                <?= date('M j, Y g:i A', strtotime($user['suspension_end_date'])) ?>
                                <br><small style="color: #e53e3e;">(<?= format_suspension_time_remaining($user['suspension_end_date']) ?> remaining)</small>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        
                        <td style="padding: 12px; text-align: right;">
                            <?php if($user['is_suspended']): ?>
                                <form method="POST" action="<?= url('admin_suspend.php?action=unsuspend&user_id=' . $user['user_id']) ?>" style="display: inline;">
                                    <button type="submit" class="action-btn unsuspend-btn" onclick="return confirm('Are you sure you want to unsuspend this user?')">
                                        UNSUSPEND
                                    </button>
                                </form>
                            <?php else: ?>
                                <a href="#" class="action-btn suspend-btn" onclick="openSuspendModal(<?= $user['user_id'] ?>, '<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>')">
                                    SUSPEND
                                </a>
                            <?php endif; ?>
                            
                            <a href="<?= url('admin_suspend_history.php?user_id=' . $user['user_id']) ?>" class="action-btn history-btn">
                                HISTORY
                            </a>
                        </td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="suspendModal" class="modal">
    <div class="modal-content">
        <h2 style="margin-bottom: 1rem;">Suspend User</h2>
        <p id="suspendUserName" style="color: #718096; margin-bottom: 1.5rem;"></p>
        
        <form method="POST" id="suspendForm">
            <div class="form-group">
                <label>Suspension Duration</label>
                <select name="duration_days" required>
                    <option value="">Select duration...</option>
                    <option value="1">1 Day</option>
                    <option value="3">3 Days</option>
                    <option value="7">7 Days (1 Week)</option>
                    <option value="14">14 Days (2 Weeks)</option>
                    <option value="30">30 Days (1 Month)</option>
                    <option value="90">90 Days (3 Months)</option>
                    <option value="180">180 Days (6 Months)</option>
                    <option value="365">365 Days (1 Year)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Reason for Suspension *</label>
                <textarea name="reason" required placeholder="Explain why this user is being suspended..."></textarea>
            </div>
            
            <div style="text-align: right;">
                <button type="button" class="btn-secondary" onclick="closeSuspendModal()">Cancel</button>
                <button type="submit" class="btn-primary">Suspend User</button>
            </div>
        </form>
    </div>
</div>

<script>
function openSuspendModal(userId, userName) {
    // Update the modal text to show who we are suspending
    document.getElementById('suspendUserName').textContent = 'Suspending: ' + userName;
    
    // Dynamically set the form action URL to include the correct User ID
    document.getElementById('suspendForm').action = '<?= url('admin_suspend.php') ?>?action=suspend&user_id=' + userId;
    
    // Show the modal
    document.getElementById('suspendModal').classList.add('active');
}

function closeSuspendModal() {
    document.getElementById('suspendModal').classList.remove('active');
}

// Close modal when clicking outside the content area (on the background overlay)
document.getElementById('suspendModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSuspendModal();
    }
});
</script>

<?php require_once 'footer.php'; ?>
<?php
/**
 * SUSPENSION HISTORY PAGE
 * Shows suspension history for a specific user
 * * Purpose: Allows administrators to audit the discipline history of a specific user,
 * showing dates, reasons, and which admin issued the suspension.
 */

// Load database configuration and authentication logic
require_once 'config.php';
require_once 'auth.php';

// Security Check: Ensure the user is an administrator
require_admin(); // Only admins can view

// ---------------------------------------------------------
// 1. INPUT VALIDATION & USER RETRIEVAL
// ---------------------------------------------------------

// Retrieve the User ID from the URL parameter.
// Casting to (int) sanitizes the input to prevent SQL injection.
$user_id = (int)($_GET['user_id'] ?? 0);

// Validate that a valid ID was provided
if ($user_id <= 0) {
    header("Location: " . url('admin_suspend.php?error=Invalid+user'));
    exit();
}

// Prepare database query to get user details (Name, Email)
// We need this to confirm the user exists and to show their name on the page
$stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// If user does not exist in database, redirect with error
if (!$user) {
    header("Location: " . url('admin_suspend.php?error=User+not+found'));
    exit();
}

// ---------------------------------------------------------
// 2. FETCH HISTORY DATA
// ---------------------------------------------------------

// Call a helper function (likely defined in config.php or functions.php)
// to get the list of past suspensions. We limit it to the last 50 records.
$history = get_suspension_history($user_id, 50);

require_once 'header.php';
?>

<style>
    .history-item {
        background: white;
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border-left: 4px solid #e53e3e; /* Red border indicating disciplinary action */
    }
    
    .history-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #edf2f7;
    }
    
    .history-date {
        font-weight: 600;
        color: #2d3748;
    }
    
    .history-duration {
        color: #718096;
        font-size: 0.9rem;
    }
    
    .history-admin {
        background: #edf2f7;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.75rem;
        color: #4a5568;
    }
    
    .history-reason {
        color: #4a5568;
        line-height: 1.6;
        padding: 0.75rem;
        background: #f7fafc;
        border-radius: 6px;
    }
    
    .no-history {
        text-align: center;
        padding: 3rem;
        color: #a0aec0;
    }
</style>

<div class="container mt-4">
    <div style="margin-bottom: 2rem;">
        <a href="<?= url('admin_suspend.php') ?>" style="text-decoration: none; color: #3182ce; font-weight: bold;">← Back to Suspension Management</a>
        <h1 style="margin-top: 1rem; color: #2d3748;">📋 Suspension History</h1>
        <p style="color: #718096;">
            User: <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong>
            (<?= htmlspecialchars($user['email']) ?>)
        </p>
    </div>
    
    <?php if(empty($history)): ?>
        <div class="no-history">
            <div style="font-size: 3rem; margin-bottom: 1rem;">✅</div>
            <h3>No Suspension History</h3>
            <p>This user has never been suspended.</p>
        </div>
    <?php else: ?>
        <?php foreach($history as $record): ?>
            <div class="history-item">
                <div class="history-header">
                    <div>
                        <div class="history-date">
                            <?= date('F j, Y', strtotime($record['suspension_start'])) ?> 
                            → 
                            <?= date('F j, Y', strtotime($record['suspension_end'])) ?>
                        </div>
                        
                        <div class="history-duration">
                            Duration: 
                            <?php
                                // Use PHP DateTime class to calculate the exact difference in days
                                $start = new DateTime($record['suspension_start']);
                                $end = new DateTime($record['suspension_end']);
                                $diff = $start->diff($end);
                                echo $diff->days . ' day' . ($diff->days != 1 ? 's' : ''); // Handle pluralization
                            ?>
                        </div>
                    </div>
                    
                    <div class="history-admin">
                        By: <?= htmlspecialchars($record['admin_name']) ?>
                    </div>
                </div>
                
                <div class="history-reason">
                    <strong>Reason:</strong><br>
                    <?= nl2br(htmlspecialchars($record['reason'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
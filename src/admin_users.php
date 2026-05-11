<?php
/**
 * ADMIN USER MANAGEMENT PAGE
 * Displays a list of all registered users and allows for account management.
 * * Purpose: Allows the administrator to view and manage student accounts.
 */

// Load configuration and authentication systems
require_once 'config.php';
require_once 'auth.php';

// Security Check: Enforce Role-Based Access Control (RBAC)
require_admin(); // Sadece admin girebilir (Only admins can access)

require_once 'header.php';

// ---------------------------------------------------------
// DATA RETRIEVAL
// ---------------------------------------------------------
// Fetch all user records from the database
// Ordered by creation date (newest first) to see recent registrations easily
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$all_users = $stmt->fetchAll();
?>

<div class="container mt-4">
    <div style="margin-bottom: 2rem;">
        <a href="admin_dashboard.php" style="text-decoration: none; color: #3182ce; font-weight: bold;">← Back to Dashboard</a>
        <h1 style="margin-top: 1rem; color: #2d3748;">👥 User Management</h1>
        <p style="color: #718096;">View and manage all student accounts.</p>
    </div>

    <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; border-bottom: 2px solid #edf2f7;">
                    <th style="padding: 12px; color: #718096;">ID</th>
                    <th style="padding: 12px; color: #718096;">STUDENT NAME</th>
                    <th style="padding: 12px; color: #718096;">EMAIL</th>
                    <th style="padding: 12px; color: #718096;">ROLE</th>
                    <th style="padding: 12px; color: #718096;">JOIN DATE</th>
                    <th style="padding: 12px; color: #718096; text-align: right;">ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($all_users as $user): ?>
                <tr style="border-bottom: 1px solid #edf2f7;">
                    <td style="padding: 12px; color: #a0aec0;">#<?= $user['user_id'] ?></td>
                    <td style="padding: 12px; font-weight: 600; color: #4a5568;"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                    <td style="padding: 12px; color: #4a5568;"><?= htmlspecialchars($user['email']) ?></td>
                    
                    <td style="padding: 12px;">
                        <span style="background: #ebf8ff; color: #2b6cb0; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                            <?= $user['role'] ?>
                        </span>
                    </td>
                    
                    <td style="padding: 12px; color: #718096; font-size: 0.85rem;"><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                    
                    <td style="padding: 12px; text-align: right;">
                        <?php if($user['user_id'] != get_user_id()): ?>
                            <a href="admin_actions.php?type=user&action=delete&id=<?= $user['user_id'] ?>" 
                               style="color: #e53e3e; font-weight: 700; text-decoration: none; font-size: 0.75rem; background: #fff5f5; padding: 6px 12px; border-radius: 6px;"
                               onclick="return confirm('Are you sure you want to PERMANENTLY delete this student?')">DELETE</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
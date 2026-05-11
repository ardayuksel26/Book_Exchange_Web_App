<?php
/**
 * ADMIN BOOK MODERATION PAGE
 * This page allows administrators to view, edit, and delete any book listing
 * across the entire platform.
 */

require_once 'config.php';
require_once 'auth.php';

/**
 * ACCESS CONTROL
 * Strictly restricts access to administrators only. 
 * If a non-admin attempts to access, the require_admin() function handles the rejection.
 */
require_admin();

require_once 'header.php';

/**
 * DATA RETRIEVAL (Relational Query)
 * Fetches all books while performing a JOIN with the 'users' table.
 * This allows us to display the owner's name along with the book details.
 * Ordered by 'created_at' DESC to show the newest listings first.
 */
$stmt = $pdo->query("SELECT b.*, u.first_name, u.last_name 
                     FROM books b 
                     JOIN users u ON b.user_id = u.user_id 
                     ORDER BY b.created_at DESC");
$all_books = $stmt->fetchAll();
?>

<div class="container mt-4">
    <div style="margin-bottom: 2rem;">
        <a href="admin_dashboard.php" style="text-decoration: none; color: #3182ce; font-weight: bold;">← Back to Dashboard</a>
        <h1 style="margin-top: 1rem; color: #2d3748;">📚 Book Moderation</h1>
        <p style="color: #718096;">Global management of book listings.</p>
    </div>

    <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; border-bottom: 2px solid #edf2f7;">
                    <th style="padding: 12px; color: #718096;">BOOK TITLE</th>
                    <th style="padding: 12px; color: #718096;">OWNER</th>
                    <th style="padding: 12px; color: #718096;">CATEGORY</th>
                    <th style="padding: 12px; color: #718096;">STATUS</th>
                    <th style="padding: 12px; color: #718096; text-align: right;">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($all_books as $book): ?>
                <tr style="border-bottom: 1px solid #edf2f7;">
                    <td style="padding: 12px; font-weight: 600; color: #4a5568;">
                        <?= htmlspecialchars($book['title']) ?>
                        <br><small style="color: #a0aec0; font-weight: normal;">Author: <?= htmlspecialchars($book['author']) ?></small>
                    </td>
                    
                    <td style="padding: 12px; color: #4a5568;"><?= htmlspecialchars($book['first_name'] . ' ' . $book['last_name']) ?></td>
                    
                    <td style="padding: 12px; color: #718096; font-size: 0.85rem;"><?= htmlspecialchars($book['category']) ?></td>
                    
                    <td style="padding: 12px;">
                        <span class="status-badge status-<?= $book['status'] ?>">
                            <?= strtoupper($book['status']) ?>
                        </span>
                    </td>
                    
                    <td style="padding: 12px; text-align: right;">
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <a href="edit_book.php?book_id=<?= $book['book_id'] ?>&admin=true" 
                               style="background: #ebf4ff; color: #3182ce; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.75rem; font-weight: 700;">EDIT</a>
                            
                            <a href="admin_actions.php?type=book&action=delete&id=<?= $book['book_id'] ?>" 
                               style="background: #fff5f5; color: #e53e3e; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.75rem; font-weight: 700;"
                               onclick="return confirm('Are you sure you want to delete this book listing?')">DELETE</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
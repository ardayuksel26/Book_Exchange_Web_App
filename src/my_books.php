<?php
/**
 * MY BOOKS MANAGEMENT PAGE
 * * Purpose: Acts as a central hub for the user to manage their inventory.
 * It has two modes controlled by the 'tab' parameter:
 * 1. 'my' (Default): Shows books the user owns/uploaded.
 * 2. 'rented': Shows books the user is currently borrowing from others.
 */

require_once 'config.php';
require_once 'auth.php';
require_login(); // Security: Only logged-in users can access this page

require_once 'header.php';
$user_id = get_user_id();
$tab = $_GET['tab'] ?? 'my'; // Default to showing user's own listings

// ---------------------------------------------------------
// DATA RETRIEVAL LOGIC
// ---------------------------------------------------------

if ($tab === 'rented') {
    // MODE 1: Rented Books
    // We join 'rentals', 'books', and 'users' to show WHAT is rented, 
    // WHO owns it, and WHEN it must be returned.
    // 
    $stmt = $pdo->prepare("SELECT b.*, r.start_date, r.end_date, u.first_name, u.last_name, 
                          (SELECT image_path FROM book_images WHERE book_id = b.book_id LIMIT 1) as image_path 
                          FROM rentals r 
                          JOIN books b ON r.book_id = b.book_id 
                          JOIN users u ON b.user_id = u.user_id 
                          WHERE r.renter_id = ? AND r.status = 'accepted' 
                          ORDER BY r.start_date DESC");
    $stmt->execute([$user_id]);
    $books = $stmt->fetchAll();
    $page_title = "Books Rented by Me";
} else {
    // MODE 2: My Listings (Owned Books)
    // Here we use SUBQUERIES to count 'pending_rentals' and 'pending_swaps'.
    // This allows the user to see if a book has requests directly from the list view
    // without clicking into every single book.
    $stmt = $pdo->prepare("SELECT b.*, 
                          (SELECT image_path FROM book_images WHERE book_id = b.book_id LIMIT 1) as image_path, 
                          (SELECT COUNT(*) FROM rentals WHERE book_id = b.book_id AND status = 'pending') as pending_rentals, 
                          (SELECT COUNT(*) FROM swaps WHERE requested_book_id = b.book_id AND status = 'pending') as pending_swaps 
                          FROM books b 
                          WHERE b.user_id = ? 
                          ORDER BY b.created_at DESC");
    $stmt->execute([$user_id]);
    $books = $stmt->fetchAll();
    $page_title = "My Book Listings";
}
?>

<style>
    .data-table { 
        width: 100%; 
        border-collapse: collapse; 
        background: white; 
        border-radius: 12px; 
        overflow: hidden; 
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        margin-top: 20px;
    }
    .data-table th { 
        background: #2c3e50; 
        color: white; 
        padding: 15px; 
        font-size: 0.8rem; 
        text-transform: uppercase; 
        text-align: center;
    }
    .data-table td { 
        padding: 15px; 
        border-bottom: 1px solid #edf2f7; 
        text-align: center; 
        vertical-align: middle; 
    }

    .request-badge {
        background: #fdf2f2;
        color: #c53030;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 700;
        border: 1px solid #feb2b2;
        display: block;
        margin: 2px 0;
    }

    .btn-action {
        padding: 6px 12px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 0.75rem;
        font-weight: bold;
        display: inline-block;
    }
    .btn-view { background: #3182ce; color: white; }
    .btn-edit { background: #edf2f7; color: #4a5568; border: 1px solid #cbd5e0; }

    .book-thumb { width: 50px; height: 70px; object-fit: cover; border-radius: 4px; }
</style>

<div class="container" style="max-width: 1100px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #2d3748; margin-bottom: 25px;">📖 <?= $page_title ?></h1>
    
    <?php if (empty($books)): ?>
        <div style="text-align: center; padding: 50px; background: white; border-radius: 12px;">
            <p style="color: #718096;">No books found in this section.</p>
        </div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th style="text-align: left;">Title</th>
                    <?php if ($tab === 'rented'): ?>
                        <th>Owner</th>
                        <th>Period</th>
                    <?php else: ?>
                        <th>Pending</th>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $book): ?>
                    <tr>
                        <td>
                            <?php if (!empty($book['image_path']) && file_exists($book['image_path'])): ?>
                                <img src="<?= htmlspecialchars($book['image_path']) ?>" class="book-thumb">
                            <?php else: ?>
                                <div style="width:50px; height:70px; background:#eee; display:flex; align-items:center; justify-content:center; border-radius:4px;">📚</div>
                            <?php endif; ?>
                        </td>
                        
                        <td style="text-align: left;">
                            <strong><?= htmlspecialchars($book['title']) ?></strong><br>
                            <small style="color: #718096;"><?= htmlspecialchars($book['author']) ?></small>
                        </td>
                        
                        <?php if ($tab === 'rented'): ?>
                            <td><?= htmlspecialchars($book['first_name'] . ' ' . $book['last_name']) ?></td>
                            <td style="font-size: 0.75rem;">
                                <?= date('d M Y', strtotime($book['start_date'])) ?> <br>
                                <span style="color: #cbd5e0;">to</span> <br>
                                <?= date('d M Y', strtotime($book['end_date'])) ?>
                            </td>
                        <?php else: ?>
                            <td>
                                <span class="request-badge">📩 <?= (int)$book['pending_rentals'] ?> Rent</span>
                                <span class="request-badge">🔄 <?= (int)$book['pending_swaps'] ?> Swap</span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px; justify-content: center;">
                                    <a href="book_detail.php?book_id=<?= $book['book_id'] ?>" class="btn-action btn-view">View</a>
                                    <a href="edit_book.php?book_id=<?= $book['book_id'] ?>" class="btn-action btn-edit">Edit</a>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
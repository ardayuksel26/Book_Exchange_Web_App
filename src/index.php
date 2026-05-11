<?php
/**
 * MAIN DISCOVERY / HOME PAGE
 * Displays a searchable, filterable grid of all available books.
 * * Purpose: This is the main entry point where users browse books.
 * It includes logic to hide books from suspended users and prioritizes available items.
 */

require_once 'config.php';
require_once 'auth.php';
require_login(); // Ensure the user is logged in to view the marketplace

require_once 'header.php';
$current_user_id = get_user_id();

// ---------------------------------------------------------
// 1. FILTER INPUT HANDLING
// ---------------------------------------------------------
// Collect GET parameters for search and filtering
// Using null coalescing operator (??) to handle missing values gracefully
$search = trim($_GET['search'] ?? '');
$category = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? 'all';
$year_value = trim($_GET['year_val'] ?? '');
$year_operator = $_GET['year_op'] ?? 'after';

// ---------------------------------------------------------
// 2. QUERY CONSTRUCTION
// ---------------------------------------------------------

// MODIFIED: Filter out books from suspended users
// We perform a JOIN with the 'users' table to check the 'is_suspended' status.
// We also exclude the current user's own books from the discovery feed (user_id != ?).
$query = "SELECT b.*, u.first_name, u.last_name, u.is_suspended,
          (SELECT image_path FROM book_images WHERE book_id = b.book_id LIMIT 1) as image_path
          FROM books b
          JOIN users u ON b.user_id = u.user_id
          WHERE b.user_id != ? AND u.is_suspended = 0";

// Initialize parameters array with the current user ID
$params = [$current_user_id];

// Dynamic SQL construction based on active filters
if (!empty($search)) {
    // Search in both Title and Author fields
    $query .= " AND (b.title LIKE ? OR b.author LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}
if (!empty($category)) {
    $query .= " AND b.category = ?";
    $params[] = $category;
}
if ($status_filter !== 'all') {
    $query .= " AND b.status = ?";
    $params[] = $status_filter;
}
// Numeric validation for year filter to prevent SQL errors
if (!empty($year_value) && is_numeric($year_value)) {
    $query .= ($year_operator === 'before') ? " AND b.publication_year < ?" : " AND b.publication_year >= ?";
    $params[] = (int)$year_value;
}

// ---------------------------------------------------------
// 3. SORTING LOGIC
// ---------------------------------------------------------
// Custom sorting:
// 1. Available books appear first (Priority 1)
// 2. Rented books appear second (Priority 2)
// 3. Others appear last
// 4. Secondary sort by creation date (newest first)
$query .= " ORDER BY CASE WHEN b.status = 'available' THEN 1 WHEN b.status = 'rented' THEN 2 ELSE 3 END ASC, b.created_at DESC";

// Execute the prepared statement
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Fetch unique categories for the filter dropdown
$categories = $pdo->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>

<style>
    .alert {
        padding: 1rem 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        font-weight: 500;
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    .alert-success { background: #c6f6d5; color: #2f855a; border-left: 4px solid #38a169; }
    .alert-error { background: #fed7d7; color: #c53030; border-left: 4px solid #e53e3e; }
    
    .status-badge {
        position: absolute; top: 12px; right: 12px;
        height: 24px; padding: 0 10px; border-radius: 4px;
        color: white; font-size: 0.7rem; font-weight: bold; z-index: 5;
        display: inline-flex; align-items: center; justify-content: center;
        line-height: 1; text-transform: uppercase;
    }

    /* Button and Input Styling */
    .filter-btn {
        height: 40px;
        padding: 0 20px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        font-weight: 600;
        text-decoration: none;
        border: none;
        cursor: pointer;
        font-size: 0.9rem;
        box-sizing: border-box;
        transition: opacity 0.2s;
    }
    .filter-btn:hover { opacity: 0.9; color: white; }
</style>

<div class="container mt-4">
    <?php if(isset($_GET['report_success'])): ?>
        <div class="alert alert-success">
            ✅ Thank you! Your report has been submitted successfully. Our admin team will review it shortly.
        </div>
    <?php endif; ?>
    
    <?php if(isset($_GET['report_error'])): ?>
        <div class="alert alert-error">
            <?php if($_GET['report_error'] === 'already_reported'): ?>
                ❌ You have already reported this book.
            <?php elseif($_GET['report_error'] === 'no_reason'): ?>
                ❌ Please select at least one reason for reporting.
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <h1>📖 Discover Books</h1>
    
    <div class="filters" style="margin-bottom: 30px; background: #f8f9fa; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <form method="GET" action="index.php" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
            <div class="filter-group">
                <label style="display: block; font-weight: bold; margin-bottom: 5px; font-size: 0.9rem;">📁 Category:</label>
                <select name="category" style="height: 40px; padding: 0 10px; border-radius: 4px; border: 1px solid #ddd; min-width: 160px;">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label style="display: block; font-weight: bold; margin-bottom: 5px; font-size: 0.9rem;">📊 Status:</label>
                <select name="status" style="height: 40px; padding: 0 10px; border-radius: 4px; border: 1px solid #ddd; min-width: 120px;">
                    <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="available" <?= $status_filter === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="rented" <?= $status_filter === 'rented' ? 'selected' : '' ?>>Rented</option>
                </select>
            </div>
            
            <button type="submit" class="filter-btn" style="background-color: #007bff; color: white;">
                🔍 Apply Filters
            </button>
            <a href="index.php" class="filter-btn" style="background-color: #6c757d; color: white;">
                ↻ Clear
            </a>
        </form>
    </div>
    
    <div class="book-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 25px;">
        <?php if (empty($books)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 50px;">
                <p style="font-size: 1.2rem; color: #999;">No books found matching your criteria.</p>
            </div>
        <?php endif; ?>
        
        <?php foreach ($books as $book): ?>
        <div class="book-card" style="background: white; border-radius: 12px; padding: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); transition: transform 0.2s, box-shadow 0.2s; position: relative; display: flex; flex-direction: column;">
            <a href="book_detail.php?book_id=<?= $book['book_id'] ?>" style="text-decoration: none; color: inherit; flex: 1; display: flex; flex-direction: column;">
                
                <div style="position: relative; margin-bottom: 12px;">
                    <?php if (!empty($book['image_path']) && file_exists($book['image_path'])): ?>
                        <img src="<?= htmlspecialchars($book['image_path']) ?>" 
                             alt="Book Cover" 
                             style="width: 100%; height: 280px; object-fit: cover; border-radius: 8px;">
                    <?php else: ?>
                        <div style="width: 100%; height: 280px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                            📚
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    $status_bg = ($book['status'] === 'available') ? '#28a745' : (($book['status'] === 'rented') ? '#dc3545' : '#6c757d');
                    $status_text = ($book['status'] === 'available') ? '✓ Available' : ucfirst($book['status']);
                    ?>
                    <span class="status-badge" style="background-color: <?= $status_bg ?>;">
                        <?= $status_text ?>
                    </span>
                </div>
                
                <h3 style="font-size: 1.1rem; margin-bottom: 8px; color: #333; font-weight: 700; line-height: 1.3;">
                    <?= htmlspecialchars($book['title']) ?>
                </h3>
                
                <p style="color: #777; font-size: 0.9rem; margin-bottom: 10px;">
                    by <?= htmlspecialchars($book['author']) ?>
                </p>
                
                <div style="margin-top: auto;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid #eee;">
                        <span style="font-weight: 700; color: #28a745; font-size: 1.2rem;">
                            ₺<?= number_format($book['price'], 2) ?>
                        </span>
                        <span style="font-size: 0.75rem; color: #999;">
                            Owner: <?= htmlspecialchars($book['first_name']) ?>
                        </span>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    /* Hover effect for book cards */
    .book-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
</style>

<?php require_once 'footer.php'; ?>
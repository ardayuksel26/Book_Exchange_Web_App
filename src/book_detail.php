<?php
/**
 * BOOK DETAIL & REPORTING PAGE
 * Displays detailed information about a specific book.
 * * Purpose: Handles displaying book data, owner permissions, 
 * and processing user reports against inappropriate listings.
 */

require_once 'config.php';
require_once 'auth.php';
require_login(); // Ensure user is logged in

// Get book ID from URL, default to 0 to prevent errors
$book_id = $_GET['book_id'] ?? 0;
$user_id = get_user_id();

// ---------------------------------------------------------
// 1. REPORT SUBMISSION HANDLING (POST Request)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $report_reasons = $_POST['report_reasons'] ?? [];
    
    // Ensure at least one reason was selected
    if (!empty($report_reasons)) {
        // Convert array of reasons into a single string
        $reason = implode(", ", $report_reasons);
        
        // Security Check: Prevent duplicate reports
        // Check if this specific user has already reported this specific book
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE reported_book_id = ? AND reporter_id = ?");
        $check_stmt->execute([$book_id, $user_id]);
        $already_reported = $check_stmt->fetchColumn() > 0;
        
        if (!$already_reported) {
            // Insert new report record with 'pending' status
            $stmt = $pdo->prepare("INSERT INTO reports (reported_book_id, reporter_id, reason, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
            if ($stmt->execute([$book_id, $user_id, $reason])) {
                // Redirect with success flag
                header("Location: " . url('index.php?report_success=1'));
                exit();
            }
        } else {
            // Error: User already reported this
            header("Location: " . url('index.php?report_error=already_reported'));
            exit();
        }
    } else {
        // Error: No reason selected
        header("Location: " . url('index.php?report_error=no_reason'));
        exit();
    }
}

require_once 'header.php';

// ---------------------------------------------------------
// 2. DATA RETRIEVAL
// ---------------------------------------------------------
// Fetch book details + Owner Information + Book Image
// Using SQL JOIN to retrieve everything in a single query
$stmt = $pdo->prepare("SELECT b.*, u.first_name, u.last_name, u.email,
                       (SELECT image_path FROM book_images WHERE book_id = b.book_id LIMIT 1) as image_path
                       FROM books b
                       JOIN users u ON b.user_id = u.user_id
                       WHERE b.book_id = ?");
$stmt->execute([$book_id]);
$book = $stmt->fetch();

// If book doesn't exist, redirect to home
if (!$book) {
    header("Location: index.php");
    exit();
}

// ---------------------------------------------------------
// 3. PERMISSION LOGIC
// ---------------------------------------------------------
// Check if the current viewer is the owner of the book
$is_owner = ($book['user_id'] == $user_id);

// Check if the viewer can make a request (Must NOT be owner AND book must be available)
$can_request = !$is_owner && $book['status'] === 'available';

// Check if user has already reported this book (for UI button state)
$check_reported = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE reported_book_id = ? AND reporter_id = ?");
$check_reported->execute([$book_id, $user_id]);
$already_reported = $check_reported->fetchColumn() > 0;
?>

<style>
    body {
        background: #f7fafc;
    }
    
    /* Hide default navbar status elements to prevent clutter */
    .navbar .status-badge, 
    .navbar .available-status, 
    .navbar .status-available { 
        display: none !important; 
    }
    
    .book-detail-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 15px;
    }
    
    .book-detail-card {
        background: white;
        padding: 40px;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    }
    
    .info-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .info-table th { 
        width: 150px; 
        color: #718096; 
        padding: 12px 0; 
        text-align: left; 
        text-transform: uppercase; 
        font-size: 0.75rem;
        font-weight: 700;
    }
    
    .info-table td { 
        padding: 12px 0; 
        color: #2d3748; 
        font-weight: 500;
        font-size: 1rem;
    }
    
    .info-table tr {
        border-bottom: 1px solid #f7fafc;
    }
    
    .price-large { 
        color: #2f855a; 
        font-weight: 800; 
        font-size: 1.8rem; 
    }
    
    .back-link { 
        text-decoration: none; 
        color: #3182ce; 
        font-weight: bold; 
        display: inline-block; 
        margin-bottom: 15px;
        transition: color 0.3s;
    }
    
    .back-link:hover {
        color: #2c5282;
    }
    
    /* Report Modal Styles - Hidden by default */
    .report-modal { 
        display: none; 
        position: fixed; 
        top: 0; 
        left: 0; 
        width: 100%; 
        height: 100%; 
        background: rgba(0, 0, 0, 0.6); 
        z-index: 9999; 
        align-items: center; 
        justify-content: center; 
        backdrop-filter: blur(4px); 
    }
    
    .report-modal.active { 
        display: flex; 
    }
    
    .report-modal-content { 
        background: white; 
        padding: 2rem; 
        border-radius: 16px; 
        max-width: 500px; 
        width: 90%; 
        max-height: 80vh; 
        overflow-y: auto; 
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); 
        animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
        from {
            transform: translateY(-30px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .report-modal-header { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 1.5rem; 
        padding-bottom: 1rem; 
        border-bottom: 2px solid #e2e8f0; 
    }
    
    .report-modal-header h2 {
        margin: 0;
        color: #2d3748;
        font-size: 1.4rem;
    }
    
    .close-modal { 
        background: transparent; 
        border: none; 
        font-size: 2rem; 
        color: #a0aec0; 
        cursor: pointer; 
        border-radius: 50%; 
        width: 32px; 
        height: 32px; 
        display: flex; 
        align-items: center; 
        justify-content: center;
        transition: all 0.3s;
    }
    
    .close-modal:hover {
        background: #fed7d7;
        color: #e53e3e;
    }
    
    .reason-option { 
        display: block; 
        padding: 1rem; 
        margin-bottom: 0.75rem; 
        border: 2px solid #e2e8f0; 
        border-radius: 8px; 
        cursor: pointer; 
        position: relative;
        transition: all 0.3s;
    }
    
    .reason-option:hover {
        border-color: #e53e3e;
        background: #fff5f5;
    }
    
    .reason-option input[type="checkbox"] { 
        position: absolute; 
        opacity: 0; 
    }
    
    /* Highlight selected reason */
    .reason-option input[type="checkbox"]:checked + .reason-content {
        color: #e53e3e;
        font-weight: 600;
    }
    
    .reason-option input[type="checkbox"]:checked ~ .check-icon { 
        display: block; 
    }
    
    .reason-content {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #4a5568;
    }
    
    .check-icon { 
        display: none; 
        position: absolute; 
        right: 1rem; 
        top: 50%; 
        transform: translateY(-50%); 
        color: #38a169; 
        font-size: 1.2rem; 
    }
    
    .submit-report-btn { 
        width: 100%; 
        padding: 1rem; 
        background: #e53e3e; 
        color: white; 
        border: none; 
        border-radius: 8px; 
        font-weight: 700; 
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .submit-report-btn:hover:not(:disabled) {
        background: #c53030;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(229, 62, 62, 0.4);
    }
    
    .submit-report-btn:disabled { 
        background: #cbd5e0; 
        cursor: not-allowed;
        transform: none;
    }
    
    .cancel-btn { 
        width: 100%; 
        padding: 0.75rem; 
        background: transparent; 
        color: #718096; 
        border: 2px solid #e2e8f0; 
        border-radius: 8px; 
        margin-top: 0.5rem; 
        cursor: pointer;
        transition: all 0.3s;
        font-weight: 600;
    }
    
    .cancel-btn:hover {
        border-color: #cbd5e0;
        background: #f7fafc;
    }
    
    .report-btn { 
        background: transparent; 
        color: #e53e3e; 
        border: 2px solid #e53e3e; 
        padding: 12px 24px; 
        border-radius: 8px; 
        font-weight: 600; 
        cursor: pointer; 
        transition: 0.3s;
        font-size: 0.95rem;
    }
    
    .report-btn:hover:not(:disabled) { 
        background: #fff5f5; 
    }
    
    .report-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .alert { 
        padding: 1rem 1.5rem; 
        border-radius: 8px; 
        margin-bottom: 1.5rem;
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from {
            transform: translateY(-10px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .alert-success { 
        background: #c6f6d5; 
        color: #2f855a; 
        border-left: 4px solid #38a169; 
    }
    
    .alert-error { 
        background: #fed7d7; 
        color: #c53030; 
        border-left: 4px solid #e53e3e; 
    }
    
    .book-image-container {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .book-image {
        width: 100%;
        max-width: 320px;
        border-radius: 12px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.12);
        border: 1px solid #e2e8f0;
    }
    
    .book-image-placeholder {
        width: 100%;
        max-width: 320px;
        height: 420px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 5rem;
        margin: 0 auto;
        box-shadow: 0 15px 35px rgba(0,0,0,0.12);
    }
    
    .action-buttons {
        margin-top: 35px;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    .btn {
        padding: 14px 28px;
        border-radius: 8px;
        font-weight: 700;
        text-decoration: none;
        font-size: 1rem;
        display: inline-block;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
    }
    
    .btn-success {
        background: #48bb78;
        color: white;
    }
    
    .btn-success:hover {
        background: #38a169;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(72, 187, 120, 0.4);
    }
    
    .btn-info {
        background: #4299e1;
        color: white;
    }
    
    .btn-info:hover {
        background: #3182ce;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(66, 153, 225, 0.4);
    }
    
    .btn-warning {
        background: #ecc94b;
        color: #744210;
    }
    
    .btn-warning:hover {
        background: #d69e2e;
        transform: translateY(-2px);
    }
    
    .btn-danger {
        background: #f56565;
        color: white;
    }
    
    .btn-danger:hover {
        background: #e53e3e;
        transform: translateY(-2px);
    }
    
    @media (max-width: 768px) {
        .book-detail-card {
            padding: 25px;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .btn, .report-btn {
            width: 100%;
            text-align: center;
        }
    }
</style>

<script>
    function openReportModal() { 
        document.getElementById('reportModal').classList.add('active'); 
    }
    
    function closeReportModal() { 
        document.getElementById('reportModal').classList.remove('active'); 
    }
    
    // Enables the submit button only if at least one checkbox is checked
    function updateSubmitButton() {
        const checkboxes = document.querySelectorAll('.reason-checkbox');
        const submitBtn = document.getElementById('submitReportBtn');
        submitBtn.disabled = !Array.from(checkboxes).some(cb => cb.checked);
    }
    
    // Close modal when clicking on the dark background
    window.onclick = function(event) {
        if (event.target == document.getElementById('reportModal')) {
            closeReportModal();
        }
    }
</script>

<div class="book-detail-container">
    <div class="book-detail-card">
        
        <?php if(isset($_GET['report_success'])): ?>
            <div class="alert alert-success">
                ✅ Report submitted successfully! Our admins will review it shortly.
            </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['report_error'])): ?>
            <div class="alert alert-error">
                <?php if($_GET['report_error'] === 'already_reported'): ?>
                    ❌ You have already reported this book.
                <?php else: ?>
                    ❌ Please select at least one reason.
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="book-detail-header" style="border-bottom: 2px solid #edf2f7; margin-bottom: 30px; padding-bottom: 20px;">
            <a href="index.php" class="back-link">← Back to Books</a>
            <h1 style="font-size: 2.2rem; color: #1a202c; margin-top: 10px; margin-bottom: 0;">
                <?= htmlspecialchars($book['title']) ?>
            </h1>
        </div>
        
        <div class="row">
            <div class="col-md-5">
                <div class="book-image-container">
                    <?php if (!empty($book['image_path']) && file_exists($book['image_path'])): ?>
                        <img src="<?= htmlspecialchars($book['image_path']) ?>" 
                             alt="<?= htmlspecialchars($book['title']) ?>"
                             class="book-image">
                    <?php else: ?>
                        <div class="book-image-placeholder">📚</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-7">
                <table class="info-table">
                    <tr>
                        <th>Author</th>
                        <td><?= htmlspecialchars($book['author']) ?></td>
                    </tr>
                    <tr>
                        <th>Year</th>
                        <td><?= htmlspecialchars($book['publication_year']) ?></td>
                    </tr>
                    <tr>
                        <th>Category</th>
                        <td><?= htmlspecialchars($book['category']) ?></td>
                    </tr>
                    <tr>
                        <th>Condition</th>
                        <td>
                            <span style="background: #ebf8ff; color: #2b6cb0; padding: 6px 14px; border-radius: 6px; font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">
                                <?= htmlspecialchars($book['condition']) ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Price</th>
                        <td class="price-large">₺<?= number_format($book['price'], 2) ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <?php if($book['status'] === 'available'): ?>
                                <span style="color: #38a169; font-weight: 700; font-size: 1.05rem;">● AVAILABLE</span>
                            <?php else: ?>
                                <span style="color: #e53e3e; font-weight: 700; font-size: 1.05rem;">● NOT AVAILABLE</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Owner</th>
                        <td><?= htmlspecialchars($book['first_name'] . ' ' . $book['last_name']) ?></td>
                    </tr>
                </table>
                
                <div class="action-buttons">
                    <?php if ($is_owner): ?>
                        <a href="edit_book.php?book_id=<?= $book['book_id'] ?>" class="btn btn-warning">
                            ✏️ Edit Book
                        </a>
                        <a href="delete_book.php?book_id=<?= $book['book_id'] ?>" 
                           onclick="return confirm('Are you sure you want to delete this book?')" 
                           class="btn btn-danger">
                            🗑️ Delete
                        </a>
                    <?php elseif ($can_request): ?>
                        <a href="rent_confirm.php?book_id=<?= $book['book_id'] ?>" class="btn btn-success">
                            📖 Request to Rent
                        </a>
                        <a href="swap_request.php?book_id=<?= $book['book_id'] ?>" class="btn btn-info">
                            🔄 Request to Swap
                        </a>
                    <?php else: ?>
                        <button class="btn" style="background: #cbd5e0; color: #4a5568; cursor: not-allowed;" disabled>
                            Not Available
                        </button>
                    <?php endif; ?>
                    
                    <?php if (!$is_owner): ?>
                        <button onclick="openReportModal()" class="report-btn" <?= $already_reported ? 'disabled' : '' ?>>
                            🚩 <?= $already_reported ? 'Already Reported' : 'Report Book' ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="reportModal" class="report-modal">
    <div class="report-modal-content">
        <div class="report-modal-header">
            <h2>🚩 Report This Book</h2>
            <button class="close-modal" onclick="closeReportModal()">&times;</button>
        </div>
        
        <p style="color: #718096; margin-bottom: 1.5rem; font-size: 0.95rem;">
            Please select the reason(s) why you're reporting this book:
        </p>
        
        <form method="POST">
            <div class="report-reasons">
                <label class="reason-option">
                    <input type="checkbox" name="report_reasons[]" value="Inappropriate content" class="reason-checkbox" onchange="updateSubmitButton()">
                    <div class="reason-content"><span>⚠️ Inappropriate content</span></div>
                    <span class="check-icon">✓</span>
                </label>
                
                <label class="reason-option">
                    <input type="checkbox" name="report_reasons[]" value="Wrong category" class="reason-checkbox" onchange="updateSubmitButton()">
                    <div class="reason-content"><span>📂 Wrong category listed</span></div>
                    <span class="check-icon">✓</span>
                </label>
                
                <label class="reason-option">
                    <input type="checkbox" name="report_reasons[]" value="Fake listing" class="reason-checkbox" onchange="updateSubmitButton()">
                    <div class="reason-content"><span>🚫 Fake or misleading listing</span></div>
                    <span class="check-icon">✓</span>
                </label>
                
                <label class="reason-option">
                    <input type="checkbox" name="report_reasons[]" value="Spam" class="reason-checkbox" onchange="updateSubmitButton()">
                    <div class="reason-content"><span>📧 Spam or duplicate listing</span></div>
                    <span class="check-icon">✓</span>
                </label>
                
                <label class="reason-option">
                    <input type="checkbox" name="report_reasons[]" value="Price too high" class="reason-checkbox" onchange="updateSubmitButton()">
                    <div class="reason-content"><span>💰 Price is too high/unfair</span></div>
                    <span class="check-icon">✓</span>
                </label>
                
                <label class="reason-option">
                    <input type="checkbox" name="report_reasons[]" value="Other" class="reason-checkbox" onchange="updateSubmitButton()">
                    <div class="reason-content"><span>❓ Other issues</span></div>
                    <span class="check-icon">✓</span>
                </label>
            </div>
            
            <button type="submit" name="submit_report" id="submitReportBtn" class="submit-report-btn" disabled>
                Submit Report
            </button>
            <button type="button" class="cancel-btn" onclick="closeReportModal()">
                Cancel
            </button>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>
<?php
/**
 * SWAP REQUEST PAGE
 * Handles the logic for initiating a book exchange.
 * * Purpose: Allows a user to select one of their own books to offer 
 * in exchange for a target book. Includes validation and notifications.
 */

require_once 'header.php';

// Retrieve Book ID to be requested
$book_id = $_GET['book_id'] ?? 0;
$user_id = get_user_id();

// ---------------------------------------------------------
// 1. VALIDATE TARGET BOOK (The book the user wants)
// ---------------------------------------------------------
// Fetch details of the book being requested.
// Validation: Book must be 'available' AND not owned by the current user.
$stmt = $pdo->prepare("SELECT b.*, u.first_name, u.last_name
                       FROM books b
                       JOIN users u ON b.user_id = u.user_id
                       WHERE b.book_id = ? AND b.status = 'available' AND b.user_id != ?");
$stmt->execute([$book_id, $user_id]);
$requested_book = $stmt->fetch();

// If target book is invalid/unavailable, redirect home
if (!$requested_book) {
    header("Location: index.php");
    exit();
}

// ---------------------------------------------------------
// 2. FETCH USER'S INVENTORY (The books the user can offer)
// ---------------------------------------------------------
// Get the current user's list of 'available' books to populate the selection form.
$stmt = $pdo->prepare("SELECT book_id, title, author, category, price,
                       (SELECT image_path FROM book_images WHERE book_id = books.book_id LIMIT 1) as image_path
                       FROM books 
                       WHERE user_id = ? AND status = 'available'
                       ORDER BY title");
$stmt->execute([$user_id]);
$my_books = $stmt->fetchAll();

// ---------------------------------------------------------
// 3. DUPLICATE CHECK
// ---------------------------------------------------------
// Ensure the user hasn't already requested this specific swap.
$stmt = $pdo->prepare("SELECT swap_id FROM swaps 
                       WHERE requested_book_id = ? AND requester_id = ? AND status = 'pending'");
$stmt->execute([$book_id, $user_id]);
$existing_swap = $stmt->fetch();

$error = '';
$success = '';

// 

// ---------------------------------------------------------
// 4. FORM SUBMISSION HANDLING
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existing_swap) {
    $offered_book_id = $_POST['offered_book_id'] ?? 0;
    
    // Validate selection
    if (empty($offered_book_id)) {
        $error = "Please select a book to offer for swap.";
    } else {
        // Double-check: Does the user actually own the offered book and is it available?
        $stmt = $pdo->prepare("SELECT book_id, title FROM books WHERE book_id = ? AND user_id = ? AND status = 'available'");
        $stmt->execute([$offered_book_id, $user_id]);
        $offered_book = $stmt->fetch();
        
        if (!$offered_book) {
            $error = "Invalid book selection.";
        } else {
            try {
                // A. Insert Swap Request
                $stmt = $pdo->prepare("INSERT INTO swaps (requested_book_id, offered_book_id, requester_id, owner_id, status)
                                       VALUES (?, ?, ?, ?, 'pending')");
                $stmt->execute([$book_id, $offered_book_id, $user_id, $requested_book['user_id']]);
                $swap_id = $pdo->lastInsertId();
                
                // B. Notify the Owner of the target book
                $requester_name = get_user_info()['first_name'] . ' ' . get_user_info()['last_name'];
                $message = "User '$requester_name' offered to swap their book '{$offered_book['title']}' for your book '{$requested_book['title']}'. Swap ID: $swap_id";
                
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)");
                $stmt->execute([$requested_book['user_id'], $message]);
                
                // C. Notify the Requester (Confirmation)
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)");
                $stmt->execute([$user_id, "Your swap request for Book #{$book_id} has been sent."]);
                
                $success = "Swap request sent successfully! The owner will be notified.";
            } catch (PDOException $e) {
                $error = "Failed to send swap request: " . $e->getMessage();
            }
        }
    }
}
?>

<style>
    .swap-container { max-width: 800px; margin: 40px auto; padding: 20px; }
    
    /* Header Info Box: Shows details of the book being requested */
    .swap-header-box {
        background: white; padding: 25px; border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-left: 5px solid #3498db;
        margin-bottom: 30px;
    }
    .swap-header-box h3 { margin: 0 0 10px 0; color: #7f8c8d; font-size: 0.9rem; text-transform: uppercase; }
    .swap-header-box h2 { margin: 0; color: #2c3e50; }

    /* Selection Grid Container */
    .selection-card {
        background: white; padding: 30px; border-radius: 15px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    }
    
    /* Custom Radio Button Styling */
    /* Hide default radio circle */
    .book-option input[type="radio"] { display: none; }

    .book-option { display: block; cursor: pointer; margin-bottom: 15px; }

    .book-option-content {
        display: flex; align-items: center; padding: 15px;
        border: 2px solid #edf2f7; border-radius: 12px;
        transition: all 0.3s ease; position: relative;
    }

    /* Active State: Highlight selected book */
    .book-option input[type="radio"]:checked + .book-option-content {
        border-color: #3498db; background-color: #f0f7ff; transform: scale(1.02);
    }

    /* Checkmark Icon: Only visible when selected */
    .check-icon {
        position: absolute; right: 20px; background: #3498db; color: white;
        width: 28px; height: 28px; border-radius: 50%;
        display: none; align-items: center; justify-content: center; font-size: 14px;
    }
    .book-option input[type="radio"]:checked + .book-option-content .check-icon { display: flex; }

    /* Book Info & Thumbnail */
    .book-option-img { width: 60px; height: 85px; border-radius: 6px; object-fit: cover; margin-right: 20px; }
    .book-option-placeholder { 
        width: 60px; height: 85px; border-radius: 6px; background: #f8f9fa;
        display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-right: 20px;
    }
    .book-option-info h4 { margin: 0; color: #2c3e50; font-size: 1.1rem; }
    .book-option-info p { margin: 3px 0; color: #7f8c8d; font-size: 0.9rem; }
    .book-price-tag { color: #27ae60; font-weight: bold; margin-top: 5px; }

    .btn-submit {
        width: 100%; padding: 15px; border-radius: 10px; border: none;
        background: #27ae60; color: white; font-weight: bold; font-size: 1rem;
        cursor: pointer; transition: background 0.3s; margin-top: 20px;
    }
    .btn-submit:hover { background: #219150; }
</style>

<div class="swap-container">
    <div class="swap-header-box">
        <h3>You Are Requesting:</h3>
        <h2>🔄 <?= htmlspecialchars($requested_book['title']) ?></h2>
        <p style="margin: 5px 0 0 0; color: #7f8c8d;">Owner: <?= htmlspecialchars($requested_book['first_name'] . ' ' . $requested_book['last_name']) ?></p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error" style="margin-bottom: 20px;">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success" style="text-align: center; padding: 30px;">
            <div style="font-size: 3rem; margin-bottom: 15px;">✅</div>
            <h2 style="margin-bottom: 10px;"><?= htmlspecialchars($success) ?></h2>
            <div style="margin-top: 20px;">
                <a href="book_detail.php?book_id=<?= $book_id ?>" class="btn btn-secondary">Back to Book</a>
                <a href="notifications.php" class="btn btn-primary">Check Notifications</a>
            </div>
        </div>
    
    <?php elseif ($existing_swap): ?>
        <div class="alert alert-warning" style="text-align: center; padding: 30px;">
            <div style="font-size: 3rem; margin-bottom: 15px;">⏳</div>
            <p>You already have a pending swap request for this book.</p>
            <div style="margin-top: 20px;">
                <a href="book_detail.php?book_id=<?= $book_id ?>" class="btn btn-secondary">Back to Book</a>
            </div>
        </div>
    
    <?php elseif (empty($my_books)): ?>
        <div class="alert alert-warning" style="text-align: center; padding: 30px;">
            <p>You don't have any available books to offer for swap.</p>
            <a href="add_book.php" class="btn btn-primary" style="margin-top: 15px;">Add a Book Now</a>
        </div>
    
    <?php else: ?>
        <div class="selection-card">
            <h2 style="margin-bottom: 10px;">Select Your Book to Offer</h2>
            <p style="color: #7f8c8d; margin-bottom: 25px;">Choose one of your books to propose for this swap.</p>

            <form method="POST" action="swap_request.php?book_id=<?= $book_id ?>">
                <div class="book-selection-list">
                    <?php foreach ($my_books as $book): ?>
                        <label class="book-option">
                            <input type="radio" name="offered_book_id" value="<?= $book['book_id'] ?>" required>
                            <div class="book-option-content">
                                <?php if ($book['image_path']): ?>
                                    <img src="<?= htmlspecialchars($book['image_path']) ?>" class="book-option-img">
                                <?php else: ?>
                                    <div class="book-option-placeholder">📚</div>
                                <?php endif; ?>
                                
                                <div class="book-option-info">
                                    <h4><?= htmlspecialchars($book['title']) ?></h4>
                                    <p><?= htmlspecialchars($book['author']) ?></p>
                                    <div class="book-price-tag"><?= number_format($book['price'], 2) ?> TL</div>
                                </div>

                                <div class="check-icon">✓</div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 20px;">
                    <button type="submit" class="btn-submit">Send Swap Request</button>
                    <a href="book_detail.php?book_id=<?= $book_id ?>" style="flex: 0.4; text-align: center; padding: 15px; background: #95a5a6; color: white; text-decoration: none; border-radius: 10px; font-weight: bold;">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
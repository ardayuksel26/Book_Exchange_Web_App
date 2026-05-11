<?php
/**
 * RENTAL REQUEST PAGE
 * Handles the creation of a new rental transaction.
 * * Purpose: Validates rental dates, prevents duplicate requests, 
 * creates the rental record, and notifies the book owner.
 */

require_once 'header.php';

// 

$book_id = $_GET['book_id'] ?? 0;
$user_id = get_user_id();

// ---------------------------------------------------------
// 1. DATA VALIDATION & RETRIEVAL
// ---------------------------------------------------------
// Fetch book details, but ONLY if:
// 1. The book exists AND is 'available'
// 2. The current user is NOT the owner (Business Rule: Cannot rent your own book)
$stmt = $pdo->prepare("SELECT b.*, u.first_name, u.last_name
                       FROM books b
                       JOIN users u ON b.user_id = u.user_id
                       WHERE b.book_id = ? AND b.status = 'available' AND b.user_id != ?");
$stmt->execute([$book_id, $user_id]);
$book = $stmt->fetch();

// If validation fails (book doesn't exist or user owns it), redirect to home
if (!$book) {
    header("Location: index.php");
    exit();
}

// ---------------------------------------------------------
// 2. DUPLICATE REQUEST CHECK
// ---------------------------------------------------------
// Check if this user has already requested this specific book 
// and the request is still 'pending'. Prevents spamming the owner.
$stmt = $pdo->prepare("SELECT rental_id FROM rentals 
                       WHERE book_id = ? AND renter_id = ? AND status = 'pending'");
$stmt->execute([$book_id, $user_id]);
$existing_rental = $stmt->fetch();

$error = '';
$success = '';

// ---------------------------------------------------------
// 3. FORM SUBMISSION HANDLING
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existing_rental) {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    
    // Date Validation Logic
    if (empty($start_date) || empty($end_date)) {
        $error = "Please select both start and end dates.";
    } elseif ($start_date >= $end_date) {
        $error = "End date must be after start date.";
    } elseif ($start_date < date('Y-m-d')) {
        $error = "Start date cannot be in the past.";
    } else {
        try {
            // A. Create the Rental Record
            // Status is set to 'pending' waiting for owner approval
            $stmt = $pdo->prepare("INSERT INTO rentals (book_id, renter_id, owner_id, start_date, end_date, status)
                                   VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$book_id, $user_id, $book['user_id'], $start_date, $end_date]);
            $rental_id = $pdo->lastInsertId();
            
            // B. Send Notification to Owner
            // Inform the owner that action is required
            $renter_name = get_user_info()['first_name'] . ' ' . get_user_info()['last_name'];
            $message = "User '$renter_name' requested to rent your book '{$book['title']}' from $start_date to $end_date. Rental ID: $rental_id";
            
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)");
            $stmt->execute([$book['user_id'], $message]);
            
            // C. Send Confirmation to Renter (System Feedback)
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)");
            $stmt->execute([$user_id, "Your rental request for Book #{$book_id} has been successfully sent."]);
            
            $success = "Rental request sent successfully! The owner will be notified.";
        } catch (PDOException $e) {
            $error = "Failed to send rental request: " . $e->getMessage();
        }
    }
}
?>

<div class="form-page">
    <h1>📅 Request to Rent Book</h1>
    
    <div class="book-summary">
        <h3><?= htmlspecialchars($book['title']) ?></h3>
        <p>by <?= htmlspecialchars($book['author']) ?></p>
        <p>Owner: <?= htmlspecialchars($book['first_name'] . ' ' . $book['last_name']) ?></p>
        <p class="price">Rental Price: $<?= number_format($book['price'], 2) ?></p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
            <br><br>
            <a href="book_detail.php?book_id=<?= $book_id ?>">Back to Book</a> | 
            <a href="notifications.php">View Notifications</a>
        </div>
    <?php elseif ($existing_rental): ?>
        <div class="alert alert-warning">
            You already have a pending rental request for this book. Please wait for the owner's response.
            <br><br>
            <a href="book_detail.php?book_id=<?= $book_id ?>">Back to Book</a> | 
            <a href="notifications.php">View Notifications</a>
        </div>
    <?php else: ?>
        <form method="POST" action="rent_confirm.php?book_id=<?= $book_id ?>" class="form-container">
            <div class="form-group">
                <label>Start Date *</label>
                <input type="date" name="start_date" required min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['start_date'] ?? date('Y-m-d')) ?>">
            </div>
            
            <div class="form-group">
                <label>End Date *</label>
                <input type="date" name="end_date" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= htmlspecialchars($_POST['end_date'] ?? date('Y-m-d', strtotime('+7 days'))) ?>">
                <small>Suggested rental period: 1-2 weeks</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Confirm Rental Request</button>
                <a href="book_detail.php?book_id=<?= $book_id ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
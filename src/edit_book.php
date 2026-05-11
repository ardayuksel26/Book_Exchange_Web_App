<?php
/**
 * EDIT BOOK PAGE
 * Allows users to update details of an existing book listing.
 * * Purpose: Provides a form to modify book metadata and cover images, 
 * enforcing strict ownership permissions.
 */

require_once 'header.php';

// Retrieve Book ID from URL
$book_id = $_GET['book_id'] ?? 0;
// Get the currently logged-in user's ID (from session)
$user_id = get_user_id();

// ---------------------------------------------------------
// 1. SECURITY & AUTHORIZATION CHECK
// ---------------------------------------------------------
// Fetch book details ONLY if the book belongs to the current user.
// The SQL clause `AND user_id = ?` prevents users from editing books they don't own 
// by simply changing the ID in the URL.
$stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = ? AND user_id = ?");
$stmt->execute([$book_id, $user_id]);
$book = $stmt->fetch();

// If no book is found (or user is not the owner), redirect to the list page
if (!$book) {
    header("Location: my_books.php");
    exit();
}

$error = '';
$success = '';

// ---------------------------------------------------------
// 2. FORM SUBMISSION HANDLING (POST Request)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $publication_year = $_POST['publication_year'] ?? '';
    $condition = $_POST['condition'] ?? '';
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? '';
    
    // Validate required fields
    if (empty($title) || empty($author) || empty($publication_year) || empty($condition) || empty($price)) {
        $error = "Lütfen zorunlu alanları doldurun."; // "Please fill in required fields"
    } elseif (!is_numeric($price) || $price < 0) {
        $error = "Fiyat geçerli bir sayı olmalıdır."; // "Price must be a valid number"
    } else {
        try {
            // Update the main book details in the database
            $stmt = $pdo->prepare("UPDATE books 
                                   SET title = ?, author = ?, publication_year = ?, `condition` = ?, 
                                       category = ?, description = ?, price = ?
                                   WHERE book_id = ? AND user_id = ?");
            $stmt->execute([$title, $author, $publication_year, $condition, $category, $description, $price, $book_id, $user_id]);
            
            // ---------------------------------------------------------
            // 3. IMAGE UPLOAD HANDLING
            // ---------------------------------------------------------
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/';
                // Ensure upload directory exists
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0755, true);
                
                // Generate a unique filename to prevent overwriting other users' files
                // Format: book_{ID}_{TIMESTAMP}.{EXTENSION}
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = 'book_' . $book_id . '_' . time() . '.' . $extension;
                $filepath = $upload_dir . $filename;
                
                // Move uploaded file to server storage
                if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                    // Check if there is an existing image for this book
                    $stmt = $pdo->prepare("SELECT image_path FROM book_images WHERE book_id = ? LIMIT 1");
                    $stmt->execute([$book_id]);
                    $existing = $stmt->fetch();
                    
                    if ($existing) {
                        // CLEANUP: Delete the old image file from the server to save space
                        if (file_exists($existing['image_path'])) unlink($existing['image_path']);
                        
                        // Update the database record with the new path
                        $stmt = $pdo->prepare("UPDATE book_images SET image_path = ? WHERE book_id = ?");
                        $stmt->execute([$filepath, $book_id]);
                    } else {
                        // If no previous image, insert a new record
                        $stmt = $pdo->prepare("INSERT INTO book_images (book_id, image_path) VALUES (?, ?)");
                        $stmt->execute([$book_id, $filepath]);
                    }
                }
            }
            $success = "Kitap başarıyla güncellendi!"; // "Book updated successfully!"
            
            // Re-fetch book data to display the updated values in the form
            $stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = ?");
            $stmt->execute([$book_id]);
            $book = $stmt->fetch();
        } catch (PDOException $e) {
            $error = "Hata: " . $e->getMessage();
        }
    }
}

// Fetch current image for display in the form
$stmt = $pdo->prepare("SELECT image_path FROM book_images WHERE book_id = ? LIMIT 1");
$stmt->execute([$book_id]);
$image = $stmt->fetch();
?>

<style>
    .edit-page-container {
        max-width: 900px;
        margin: 40px auto;
        padding: 0 20px;
    }
    .edit-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .edit-header {
        background: #2c3e50;
        color: white;
        padding: 30px;
        text-align: center;
    }
    .edit-header h1 { margin: 0; font-size: 24px; }
    .edit-body { padding: 40px; }
    
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
        margin-bottom: 25px;
    }
    
    .custom-input-group { margin-bottom: 20px; }
    .custom-input-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #34495e;
    }
    .custom-input-group input, 
    .custom-input-group select, 
    .custom-input-group textarea {
        width: 100%;
        padding: 12px;
        border: 2px solid #ecf0f1;
        border-radius: 8px;
        transition: all 0.3s;
        font-size: 15px;
    }
    .custom-input-group input:focus { border-color: #3498db; outline: none; }
    
    .image-upload-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 12px;
        border: 2px dashed #dee2e6;
        text-align: center;
        margin-bottom: 30px;
    }
    .current-img-preview {
        width: 120px;
        height: 170px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 15px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .btn-update {
        background: #27ae60;
        color: white;
        padding: 15px 30px;
        border: none;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        width: 100%;
        font-size: 16px;
        transition: background 0.3s;
    }
    .btn-update:hover { background: #219150; }
    
    .alert { padding: 15px; border-radius: 8px; margin-bottom: 25px; }
    .alert-error { background: #ffeded; color: #c0392b; border: 1px solid #ffcccc; }
    .alert-success { background: #edffef; color: #27ae60; border: 1px solid #ccffcf; }

    @media (max-width: 600px) {
        .form-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="edit-page-container">
    <div class="edit-card">
        <div class="edit-header">
            <h1>✏️ Edit Book Listing</h1>
            <p style="opacity: 0.8; margin-top: 5px;">Update your book information below</p>
        </div>

        <div class="edit-body">
            <?php if ($error): ?>
                <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?> 
                    <a href="my_books.php" style="margin-left:10px; font-weight:bold; color:inherit;">Go Back</a>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="custom-input-group">
                        <label>Book Title *</label>
                        <input type="text" name="title" required value="<?= htmlspecialchars($book['title']) ?>" placeholder="e.g. The Great Gatsby">
                    </div>
                    <div class="custom-input-group">
                        <label>Author *</label>
                        <input type="text" name="author" required value="<?= htmlspecialchars($book['author']) ?>" placeholder="e.g. F. Scott Fitzgerald">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="custom-input-group">
                        <label>Publication Year *</label>
                        <input type="number" name="publication_year" min="1800" max="<?= date('Y') ?>" required value="<?= htmlspecialchars($book['publication_year']) ?>">
                    </div>
                    <div class="custom-input-group">
                        <label>Condition *</label>
                        <select name="condition" required>
                            <option value="new" <?= $book['condition'] === 'new' ? 'selected' : '' ?>>✨ New (Mint)</option>
                            <option value="good" <?= $book['condition'] === 'good' ? 'selected' : '' ?>>👍 Good (Used)</option>
                            <option value="fair" <?= $book['condition'] === 'fair' ? 'selected' : '' ?>>📖 Fair (Noticeable Wear)</option>
                            <option value="worn" <?= $book['condition'] === 'worn' ? 'selected' : '' ?>>🏚️ Worn (Damaged)</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="custom-input-group">
                        <label>Category</label>
                        <input type="text" name="category" value="<?= htmlspecialchars($book['category'] ?? '') ?>" placeholder="e.g. Classic Literature">
                    </div>
                    <div class="custom-input-group">
                        <label>Price (USD) *</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 12px; top: 12px; color: #7f8c8d;">$</span>
                            <input type="number" name="price" step="0.01" min="0" required value="<?= htmlspecialchars($book['price']) ?>" style="padding-left: 25px;">
                        </div>
                    </div>
                </div>

                <div class="custom-input-group">
                    <label>Description</label>
                    <textarea name="description" rows="4" placeholder="Briefly describe the book..."><?= htmlspecialchars($book['description'] ?? '') ?></textarea>
                </div>

                <div class="image-upload-section">
                    <label style="display: block; font-weight: bold; margin-bottom: 15px;">Book Cover Image</label>
                    <?php if ($image): ?>
                        <img src="<?= htmlspecialchars($image['image_path']) ?>" class="current-img-preview" id="preview">
                    <?php else: ?>
                        <div style="font-size: 50px; margin-bottom: 10px;">📚</div>
                    <?php endif; ?>
                    
                    <input type="file" name="image" id="imgInput" accept="image/*" style="max-width: 250px; margin: 0 auto; display: block;">
                    <p style="font-size: 12px; color: #7f8c8d; margin-top: 10px;">Click to change current image</p>
                </div>

                <div style="display: flex; gap: 15px;">
                    <button type="submit" class="btn-update">Save Changes</button>
                    <a href="my_books.php" style="flex: 1; text-align: center; padding: 15px; background: #95a5a6; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // JS Logic: Update the image preview immediately when a new file is selected
    document.getElementById('imgInput').onchange = evt => {
        const [file] = document.getElementById('imgInput').files;
        if (file) {
            document.getElementById('preview').src = URL.createObjectURL(file);
        }
    }
</script>

<?php require_once 'footer.php'; ?>
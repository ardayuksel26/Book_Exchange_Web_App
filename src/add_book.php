<?php
/**
 * BOOK ADDITION PAGE
 * This script handles the logic for adding a new book listing to the database,
 * including data validation and image file upload management.
 */

require_once 'header.php';

// Initialize feedback variables
$error = '';
$success = '';

// Check if the form has been submitted via POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize basic text inputs to prevent leading/trailing whitespace issues
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $publication_year = $_POST['publication_year'] ?? '';
    $condition = $_POST['condition'] ?? '';
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? '';
    
    // SERVER-SIDE VALIDATION
    // 1. Check for required fields
    if (empty($title) || empty($author) || empty($publication_year) || empty($condition) || empty($price)) {
        $error = "Title, author, publication year, condition, and price are required.";
    } 
    // 2. Validate condition against allowed predefined values (Whitelisting)
    elseif (!in_array($condition, ['new', 'good', 'fair', 'worn'])) {
        $error = "Invalid condition selected.";
    } 
    // 3. Ensure price is a positive numeric value
    elseif (!is_numeric($price) || $price < 0) {
        $error = "Price must be a valid positive number.";
    } 
    else {
        try {
            // DATABASE OPERATION: INSERT BOOK DATA
            // Using Prepared Statements to prevent SQL Injection attacks
            $stmt = $pdo->prepare("INSERT INTO books (user_id, title, author, publication_year, `condition`, category, description, price, status) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'available')");
            $stmt->execute([
                get_user_id(), // Custom function assumed to return current logged-in user's ID
                $title,
                $author,
                $publication_year,
                $condition,
                $category,
                $description,
                $price
            ]);
            
            // Get the ID of the newly created book record for image linking
            $book_id = $pdo->lastInsertId();
            
            // IMAGE UPLOAD HANDLING
            // Check if a file was uploaded without errors
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                
                // Verify if the uploaded file type is within our allowed list
                if (in_array($_FILES['image']['type'], $allowed_types)) {
                    $upload_dir = 'uploads/';
                    
                    // Create directory if it doesn't exist with appropriate permissions
                    if (!file_exists($upload_dir)) mkdir($upload_dir, 0755, true);
                    
                    // Generate a unique filename to prevent overwriting existing files
                    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filename = 'book_' . $book_id . '_' . time() . '.' . $extension;
                    $filepath = $upload_dir . $filename;
                    
                    // Move the file from temporary system storage to the target directory
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                        // Store the image path in the database linked to the book_id
                        $stmt = $pdo->prepare("INSERT INTO book_images (book_id, image_path) VALUES (?, ?)");
                        $stmt->execute([$book_id, $filepath]);
                    }
                }
            }
            
            $success = "Book added successfully!";
            $_POST = []; // Clear form data after successful submission
            
        } catch (PDOException $e) {
            // Log the error and show a user-friendly message
            $error = "Failed to add book: " . $e->getMessage();
        }
    }
}
?>

<style>
    /* MAIN WRAPPER: Centers the form and ensures consistent layout */
    .add-page-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 40px 20px;
        min-height: calc(100vh - 200px); /* Adjusting for Header/Footer height */
    }

    /* CARD COMPONENT: Modern container for the form */
    .add-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 800px;
        overflow: hidden;
    }

    .add-header {
        background: #2c3e50;
        color: white;
        padding: 30px;
        text-align: center;
    }

    .add-header h1 { margin: 0; font-size: 24px; color: white; }

    .add-body { padding: 40px; }
    
    /* FORM LAYOUT: Two-column grid for better space utilization */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .custom-group { margin-bottom: 15px; }
    .custom-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #34495e;
    }

    .custom-group input, 
    .custom-group select, 
    .custom-group textarea {
        width: 100%;
        padding: 12px;
        border: 2px solid #ecf0f1;
        border-radius: 8px;
        font-size: 15px;
        transition: border-color 0.3s;
    }

    /* Focus state for better UX accessibility */
    .custom-group input:focus { border-color: #3498db; outline: none; }

    /* IMAGE UPLOAD ZONE: Styled to look like a drag-and-drop area */
    .image-zone {
        background: #f8f9fa;
        padding: 20px;
        border: 2px dashed #dee2e6;
        border-radius: 10px;
        text-align: center;
        cursor: pointer;
    }

    .btn-submit {
        background: #3498db;
        color: white;
        padding: 15px;
        border: none;
        border-radius: 8px;
        font-weight: bold;
        width: 100%;
        cursor: pointer;
        font-size: 16px;
        transition: background 0.2s;
    }

    .btn-submit:hover { background: #2980b9; }

    /* RESPONSIVENESS: Stack columns on smaller screens (Mobile) */
    @media (max-width: 600px) {
        .form-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="add-page-wrapper">
    <div class="add-card">
        <div class="add-header">
            <h1>➕ Add New Book Listing</h1>
        </div>

        <div class="add-body">
            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom: 20px;">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-bottom: 20px;">
                    ✅ <?= htmlspecialchars($success) ?>
                    <div style="margin-top: 10px;">
                        <a href="my_books.php" style="font-weight: bold; color: inherit;">My Books</a> | 
                        <a href="index.php" style="font-weight: bold; color: inherit;">Browse Gallery</a>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="add_book.php" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="custom-group">
                        <label>Title *</label>
                        <input type="text" name="title" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" placeholder="Enter book title">
                    </div>
                    <div class="custom-group">
                        <label>Author *</label>
                        <input type="text" name="author" required value="<?= htmlspecialchars($_POST['author'] ?? '') ?>" placeholder="Enter author name">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="custom-group">
                        <label>Publication Year *</label>
                        <input type="number" name="publication_year" min="1800" max="<?= date('Y') ?>" required value="<?= htmlspecialchars($_POST['publication_year'] ?? '') ?>">
                    </div>
                    <div class="custom-group">
                        <label>Condition *</label>
                        <select name="condition" required>
                            <option value="">Select Condition</option>
                            <option value="new" <?= ($_POST['condition'] ?? '') === 'new' ? 'selected' : '' ?>>✨ New</option>
                            <option value="good" <?= ($_POST['condition'] ?? '') === 'good' ? 'selected' : '' ?>>👍 Good</option>
                            <option value="fair" <?= ($_POST['condition'] ?? '') === 'fair' ? 'selected' : '' ?>>📖 Fair</option>
                            <option value="worn" <?= ($_POST['condition'] ?? '') === 'worn' ? 'selected' : '' ?>>🏚️ Worn</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="custom-group">
                        <label>Category</label>
                        <input type="text" name="category" value="<?= htmlspecialchars($_POST['category'] ?? '') ?>" placeholder="e.g. Science, Fiction">
                    </div>
                    <div class="custom-group">
                        <label>Price (USD) *</label>
                        <input type="number" name="price" step="0.01" min="0" required value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
                    </div>
                </div>

                <div class="custom-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Additional details..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="custom-group">
                    <label>Book Cover Image</label>
                    <div class="image-zone" onclick="document.getElementById('imgInput').click();">
                        <p id="file-name">Click to upload image (JPG, PNG, WEBP)</p>
                        <input type="file" name="image" id="imgInput" accept="image/*" style="display: none;" onchange="document.getElementById('file-name').innerText = this.files[0].name">
                    </div>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 20px;">
                    <button type="submit" class="btn-submit">Add Book</button>
                    <a href="index.php" style="flex: 1; text-align: center; padding: 15px; background: #95a5a6; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
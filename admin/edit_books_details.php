<?php
// Include database connection file
include('../includes/db.php');
include('../includes/dashboard_header.php');

// Check if book ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to the book listing page if no ID is provided
    header("Location: all_books.php");
    exit;
}

$book_id = $_GET['id'];

// Fetch the book details
$edit_query = "SELECT * FROM books WHERE id = ?";
$edit_stmt = $conn->prepare($edit_query);
$edit_stmt->bind_param("i", $book_id);
$edit_stmt->execute();
$edit_result = $edit_stmt->get_result();

if ($edit_result->num_rows == 0) {
    // Book not found, redirect to books listing
    header("Location: all_books.php");
    exit;
}

$edit_book = $edit_result->fetch_assoc();

// Check if update form is submitted
if (isset($_POST['update_book'])) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $genre = $_POST['genre'];
    $serialno = $_POST['serialno'];
    $pages = empty($_POST['pages']) ? NULL : $_POST['pages'];
    $availability = $_POST['availability'];
    $publication_details = $_POST['publication_details'];
    $ISBN = $_POST['ISBN'];
    $edition = $_POST['edition'];

    // Initialize update query - removed image from the update
    $update_query = "UPDATE books SET 
                    title = ?, 
                    author = ?, 
                    genre = ?,
                    serialno = ?,
                    pages = ?,
                    availability = ?,
                    publication_details = ?,
                    ISBN = ?,
                    edition = ?
                    WHERE id = ?";

    // Parameters array - removed image parameter
    $params = array($title, $author, $genre, $serialno, $pages, $availability, $publication_details, $ISBN, $edition, $book_id);
    $types = "ssssissssi";

    // Prepare and execute the update statement
    $stmt = $conn->prepare($update_query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $success_message = "Book details updated successfully!";

            // Refresh the book data after update
            $edit_stmt->execute();
            $edit_result = $edit_stmt->get_result();
            $edit_book = $edit_result->fetch_assoc();
        } else {
            $error_message = "Error updating book details: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Failed to prepare statement: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Book - Employee Dashboard</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Color Palette */
        :root {
            --main-green: #2e6a33;
            --dark-green: #1d4521;
            --light-green: #c9dccb;
            --accent-green: #4a9950;
            --ultra-light-green: #eef5ef;
            --text-dark: #263028;
            --text-medium: #45634a;
            --text-light: #6b8f70;
            --white: #ffffff;
            --error-red: #d64541;
            --success-green: #2ecc71;
            --warning-yellow: #f39c12;

            /* Updated color mappings */
            --primary-color: var(--main-green);
            --primary-hover: var(--dark-green);
            --secondary-color: var(--text-medium);
            --secondary-hover: var(--text-dark);
            --success-color: var(--success-green);
            --danger-color: var(--error-red);
            --warning-color: var(--warning-yellow);
            --info-color: var(--accent-green);
            --light-color: var(--ultra-light-green);
            --dark-color: var(--text-dark);
            --text-color: var(--text-dark);
            --text-muted: var(--text-light);
            --border-color: var(--light-green);
            --bg-color: var(--ultra-light-green);
            --card-bg: var(--white);
            --shadow: 0 4px 6px rgba(46, 106, 51, 0.08), 0 1px 3px rgba(46, 106, 51, 0.12);
            --border-radius: 12px;
            --transition: all 0.3s ease;
            --font-family: 'Poppins', 'Segoe UI', sans-serif;
        }

        /* Global Styles */
        body {
            font-family: var(--font-family);
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        /* Layout */
        .content-wrapper {
            margin-top: 60px;
            transition: var(--transition);
        }

        .dashboard-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Section Header */
        .section-header {
            margin-bottom: 2.5rem;
            position: relative;
            text-align: center;
        }

        .section-header h2 {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
        }

        .section-header p {
            color: var(--text-muted);
            font-size: 1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .section-divider {
            height: 4px;
            width: 80px;
            background: linear-gradient(90deg, var(--main-green), var(--accent-green));
            margin: 1rem auto 1.5rem;
            border-radius: 3px;
        }

        /* Book Info Layout */
        .book-edit-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2.5rem;
            margin-bottom: 2rem;
        }

        /* Book Cover Sidebar */
        .book-cover-sidebar {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 2rem;
            text-align: center;
            box-shadow: var(--shadow);
            height: fit-content;
            border: 1px solid var(--border-color);
        }

        .book-cover-title {
            font-size: 1.1rem;
            color: var(--text-medium);
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .book-cover-image {
            max-width: 100%;
            height: auto;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 12px rgba(46, 106, 51, 0.15);
            margin-bottom: 1rem;
        }

        .no-image-container {
            background: linear-gradient(135deg, var(--ultra-light-green), var(--light-green));
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 300px;
            border-radius: var(--border-radius);
            border: 2px dashed var(--accent-green);
            margin-bottom: 1rem;
        }

        .no-image-icon {
            color: var(--accent-green);
            font-size: 3.5rem;
            margin-bottom: 1rem;
        }

        .no-image-text {
            color: var(--text-medium);
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Book Details Form */
        .edit-form-container {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 2.5rem;
            border: 1px solid var(--border-color);
        }

        .edit-form-title {
            color: var(--text-dark);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--light-green);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .edit-form-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: linear-gradient(135deg, var(--main-green), var(--accent-green));
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            color: var(--text-medium);
            margin-bottom: 0.6rem;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 0.9rem 1.2rem;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            transition: var(--transition);
            background-color: var(--white);
            color: var(--text-dark);
        }

        .form-control:focus {
            border-color: var(--main-green);
            box-shadow: 0 0 0 0.25rem rgba(46, 106, 51, 0.15);
            outline: none;
            background-color: var(--ultra-light-green);
        }

        .form-control:hover {
            border-color: var(--accent-green);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1rem;
        }

        /* Button Styles */
        .btn-container {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--light-green);
        }

        .btn {
            padding: 0.9rem 2rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--main-green), var(--accent-green));
            color: white;
            box-shadow: 0 4px 12px rgba(46, 106, 51, 0.25);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--dark-green), var(--main-green));
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(46, 106, 51, 0.35);
        }

        .btn-secondary {
            background-color: var(--text-medium);
            color: white;
            box-shadow: 0 4px 12px rgba(69, 99, 74, 0.25);
        }

        .btn-secondary:hover {
            background-color: var(--text-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(69, 99, 74, 0.35);
        }

        /* Notification Styles */
        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            min-width: 320px;
            max-width: 420px;
        }

        .notification {
            border-radius: var(--border-radius);
            padding: 1rem 1.25rem;
            margin-bottom: 10px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            animation: slideInRight 0.3s ease;
            border-left: 4px solid;
        }

        .notification.success {
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.1), rgba(46, 204, 113, 0.05));
            color: var(--text-dark);
            border-left-color: var(--success-color);
        }

        .notification.error {
            background: linear-gradient(135deg, rgba(214, 69, 65, 0.1), rgba(214, 69, 65, 0.05));
            color: var(--text-dark);
            border-left-color: var(--danger-color);
        }

        .notification-icon {
            font-size: 1.25rem;
            margin-right: 12px;
            color: var(--main-green);
        }

        .notification.error .notification-icon {
            color: var(--danger-color);
        }

        .notification.success .notification-icon {
            color: var(--success-color);
        }

        .notification-message {
            flex: 1;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .notification-close {
            cursor: pointer;
            background: none;
            border: none;
            color: var(--text-medium);
            font-size: 1.1rem;
            opacity: 0.7;
            transition: opacity 0.2s ease;
            padding: 2px;
        }

        .notification-close:hover {
            opacity: 1;
            color: var(--text-dark);
        }

        /* Back Button Styles */
        .back-link {
            display: inline-flex;
            align-items: center;
            color: var(--main-green);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            transition: var(--transition);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            background-color: var(--ultra-light-green);
            border: 1px solid var(--light-green);
        }

        .back-link i {
            margin-right: 0.5rem;
        }

        .back-link:hover {
            color: var(--white);
            background: linear-gradient(135deg, var(--main-green), var(--accent-green));
            transform: translateX(-3px);
            box-shadow: 0 4px 12px rgba(46, 106, 51, 0.25);
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(38, 48, 40, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1100;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
            backdrop-filter: blur(4px);
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-container {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 500px;
            box-shadow: 0 8px 32px rgba(46, 106, 51, 0.2);
            transform: translateY(-20px);
            transition: transform 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .modal-overlay.active .modal-container {
            transform: translateY(0);
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem 2rem;
            border-bottom: 2px solid var(--light-green);
            background: linear-gradient(135deg, var(--ultra-light-green), var(--light-green));
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            color: var(--text-medium);
            transition: color 0.2s ease;
            padding: 4px;
        }

        .modal-close:hover {
            color: var(--danger-color);
        }

        .modal-body {
            padding: 2rem;
            font-size: 1rem;
            color: var(--text-dark);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding: 1.5rem 2rem;
            border-top: 1px solid var(--border-color);
            background-color: var(--ultra-light-green);
        }

        .success-modal .modal-header {
            background: linear-gradient(135deg, var(--success-color), var(--accent-green));
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .success-modal .modal-title {
            color: white;
        }

        .success-modal .modal-close {
            color: rgba(255, 255, 255, 0.8);
        }

        .success-modal .modal-close:hover {
            color: white;
        }

        .success-icon {
            font-size: 4rem;
            color: var(--success-color);
            margin-bottom: 1.5rem;
            display: block;
            text-align: center;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .book-edit-layout {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .book-cover-sidebar {
                max-width: 280px;
                margin: 0 auto;
            }
        }

        @media (max-width: 768px) {
            .section-header h2 {
                font-size: 1.5rem;
            }

            .dashboard-container {
                padding: 1.5rem 1rem;
            }

            .edit-form-container {
                padding: 2rem;
            }

            .btn-container {
                flex-direction: column;
                gap: 0.75rem;
            }

            .btn {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .section-header h2 {
                font-size: 1.25rem;
            }

            .edit-form-container {
                padding: 1.5rem;
            }
        }

        /* Animations */
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Enhanced Focus States */
        .form-control:focus,
        .btn:focus {
            outline: 2px solid var(--accent-green);
            outline-offset: 2px;
        }

        /* Loading States */
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Select Dropdown Styling */
        select.form-control {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b8f70' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        select.form-control:focus {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%232e6a33' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        }
    </style>
</head>

<body>
    <div class="content-wrapper" id="contentWrapper">
        <div class="dashboard-container">
            <!-- Notification Messages -->
            <?php if (isset($success_message) || isset($error_message)): ?>
                <div class="notification-container">
                    <?php if (isset($success_message)): ?>
                        <div class="notification success">
                            <i class="fas fa-check-circle notification-icon"></i>
                            <div class="notification-message"><?php echo $success_message; ?></div>
                            <button class="notification-close">&times;</button>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($error_message)): ?>
                        <div class="notification error">
                            <i class="fas fa-exclamation-circle notification-icon"></i>
                            <div class="notification-message"><?php echo $error_message; ?></div>
                            <button class="notification-close">&times;</button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <section class="section-header">
                <h2>Edit Book Details</h2>
                <div class="section-divider"></div>
                <p>Update the book information in the library database</p>
            </section>

            <!-- Back Button -->
            <a href="all_books.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Books
            </a>

            <!-- Two-column layout for book cover and form -->
            <div class="book-edit-layout">
                <!-- Book Cover Sidebar -->
                <div class="book-cover-sidebar">
                    <h4 class="book-cover-title">Book Cover</h4>
                    <?php if (!empty($edit_book['image'])): ?>
                        <img class="book-cover-image" src="data:image/jpeg;base64,<?php echo base64_encode($edit_book['image']); ?>" alt="Book Cover">
                    <?php else: ?>
                        <div class="no-image-container">
                            <i class="fas fa-book no-image-icon"></i>
                            <p class="no-image-text">No cover image available</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Edit Form -->
                <div class="edit-form-container">
                    <h3 class="edit-form-title">Edit Book Details</h3>
                    <form method="POST" action="" id="updateBookForm">
                        <input type="hidden" name="book_id" value="<?php echo $edit_book['id']; ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="title">Book Title</label>
                                <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($edit_book['title']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="author">Author</label>
                                <input type="text" id="author" name="author" class="form-control" value="<?php echo htmlspecialchars($edit_book['author']); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="genre">Genre</label>
                                <input type="text" id="genre" name="genre" class="form-control" value="<?php echo htmlspecialchars($edit_book['genre']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="serialno">Serial Number</label>
                                <input type="text" id="serialno" name="serialno" class="form-control" value="<?php echo htmlspecialchars($edit_book['serialno']); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="pages">Pages</label>
                                <input type="number" id="pages" name="pages" class="form-control" value="<?php echo $edit_book['pages']; ?>">
                            </div>

                            <div class="form-group">
                                <label for="availability">Availability</label>
                                <select id="availability" name="availability" class="form-control">
                                    <option value="Available" <?php echo ($edit_book['availability'] == 'Available') ? 'selected' : ''; ?>>Available</option>
                                    <option value="Borrowed" <?php echo ($edit_book['availability'] == 'Not Available') ? 'selected' : ''; ?>>Not Available</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="publication_details">Publication Details</label>
                                <input type="text" id="publication_details" name="publication_details" class="form-control" value="<?php echo htmlspecialchars($edit_book['publication_details']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="ISBN">ISBN</label>
                                <input type="text" id="ISBN" name="ISBN" class="form-control" value="<?php echo htmlspecialchars($edit_book['ISBN']); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="edition">Edition</label>
                                <input type="text" id="edition" name="edition" class="form-control" value="<?php echo htmlspecialchars($edit_book['edition']); ?>">
                            </div>
                        </div>

                        <div class="btn-container">
                            <a href="all_books.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="button" id="confirmUpdateBtn" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Book
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal-overlay" id="confirmationModal">
        <div class="modal-container">
            <div class="modal-header">
                <h4 class="modal-title">Confirm Update</h4>
                <button class="modal-close" id="closeConfirmModal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to update this book record?</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelUpdate">No, Cancel</button>
                <button class="btn btn-primary" id="confirmUpdate">Yes, Update</button>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal-overlay success-modal" id="successModal">
        <div class="modal-container">
            <div class="modal-header">
                <h4 class="modal-title">Success</h4>
                <button class="modal-close" id="closeSuccessModal">&times;</button>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-check-circle success-icon"></i>
                <p>Book details have been updated successfully!</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="okButton">OK</button>
            </div>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

    <script>
        // Secondary navbar toggle (if you need it)
        if (document.getElementById('navbarToggle')) {
            document.getElementById('navbarToggle').addEventListener('click', function() {
                this.classList.toggle('active');
                document.getElementById('secondaryNavbar').classList.toggle('active');
                document.getElementById('contentWrapper').classList.toggle('navbar-active');
            });
        }

        // Auto-dismiss notifications after 5 seconds
        setTimeout(function() {
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(notification => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    notification.remove();
                }, 500);
            });
        }, 5000);

        // Close notification on click
        document.querySelectorAll('.notification-close').forEach(button => {
            button.addEventListener('click', function() {
                const notification = this.closest('.notification');
                notification.style.opacity = '0';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            });
        });

        // Show confirmation modal
        document.getElementById('confirmUpdateBtn').addEventListener('click', function() {
            document.getElementById('confirmationModal').classList.add('active');
        });

        // Close confirmation modal
        document.getElementById('closeConfirmModal').addEventListener('click', function() {
            document.getElementById('confirmationModal').classList.remove('active');
        });

        document.getElementById('cancelUpdate').addEventListener('click', function() {
            document.getElementById('confirmationModal').classList.remove('active');
        });

        // Confirm update and submit form
        document.getElementById('confirmUpdate').addEventListener('click', function() {
            document.getElementById('confirmationModal').classList.remove('active');

            // Add hidden input to indicate form submission
            let updateInput = document.createElement('input');
            updateInput.type = 'hidden';
            updateInput.name = 'update_book';
            updateInput.value = '1';
            document.getElementById('updateBookForm').appendChild(updateInput);

            // Submit the form
            document.getElementById('updateBookForm').submit();
        });

        // Show success modal if update was successful
        <?php if (isset($success_message)): ?>
            window.onload = function() {
                document.getElementById('successModal').classList.add('active');
            };
        <?php endif; ?>

        // Close success modal
        document.getElementById('closeSuccessModal').addEventListener('click', function() {
            document.getElementById('successModal').classList.remove('active');
        });

        document.getElementById('okButton').addEventListener('click', function() {
            document.getElementById('successModal').classList.remove('active');
        });
    </script>
</body>

</html>
<?php
ob_start();
session_start();
include('../includes/db.php');
include('../includes/dashboard_header.php');

$uploadsDir = '../uploads/';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

// Handle individual book upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['individual_upload'])) {
    $title = htmlspecialchars(trim($_POST['title']));
    $author = htmlspecialchars(trim($_POST['author']));
    $genre = htmlspecialchars(trim($_POST['genre']));
    $serialno = htmlspecialchars(trim($_POST['serialno']));
    $pages = !empty($_POST['pages']) ? (int) $_POST['pages'] : null;
    $publication_details = isset($_POST['publication_details']) ? htmlspecialchars(trim($_POST['publication_details'])) : null;
    $ISBN = isset($_POST['ISBN']) ? htmlspecialchars(trim($_POST['ISBN'])) : null;
    $edition = isset($_POST['edition']) ? htmlspecialchars(trim($_POST['edition'])) : null;
    $quantity = !empty($_POST['quantity']) ? (int) $_POST['quantity'] : 1; // New quantity field
    $imageData = null;
    $imageSizeKiB = null;

    // Validate quantity
    if ($quantity < 1) {
        $_SESSION['error_message'] = "Error: Quantity must be at least 1.";
        header("Location: individual_upload.php");
        exit;
    }

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $fileType = $finfo->file($_FILES['image']['tmp_name']);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($fileType, $allowedTypes)) {
            $_SESSION['error_message'] = "Error: Only JPG, PNG, GIF, and WebP images are allowed.";
            header("Location: individual_upload.php");
            exit;
        }

        $fileSizeBytes = $_FILES['image']['size'];
        $imageSizeKiB = round($fileSizeBytes / 1024, 1);
        $maxSizeKiB = 5 * 1024;

        if ($imageSizeKiB > $maxSizeKiB) {
            $_SESSION['error_message'] = "Error: Image size exceeds 5MB.";
            header("Location: individual_upload.php");
            exit;
        }

        $timestamp = uniqid();
        $originalFilename = pathinfo($_FILES['image']['name'], PATHINFO_FILENAME);
        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $newFilename = $timestamp . '_' . $originalFilename . '.' . $extension;
        $uploadPath = $uploadsDir . $newFilename;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
            $_SESSION['error_message'] = "Error uploading file.";
            header("Location: individual_upload.php");
            exit;
        }

        $imageData = file_get_contents($uploadPath);
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert book into database
        $query = "INSERT INTO books (title, author, genre, serialno, pages, publication_details, ISBN, edition, image, image_size_kib) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt->bind_param("ssssissssd", $title, $author, $genre, $serialno, $pages, $publication_details, $ISBN, $edition, $imageData, $imageSizeKiB);

        if (!$stmt->execute()) {
            throw new Exception("Error inserting book: " . $stmt->error);
        }

        $bookId = $stmt->insert_id;
        $stmt->close();

        // Insert into inventory table
        $inventoryQuery = "INSERT INTO inventory (book_id, quantity) VALUES (?, ?)";
        $inventoryStmt = $conn->prepare($inventoryQuery);

        if (!$inventoryStmt) {
            throw new Exception("Database error for inventory: " . $conn->error);
        }

        $inventoryStmt->bind_param("ii", $bookId, $quantity);

        if (!$inventoryStmt->execute()) {
            throw new Exception("Error inserting inventory: " . $inventoryStmt->error);
        }

        $inventoryStmt->close();

        // Commit transaction
        $conn->commit();

        $_SESSION['success_message'] = "Book and inventory added successfully! Quantity: $quantity";
        header("Location: books_information.php?book_id=$bookId&title=" . urlencode($title) . "&author=" . urlencode($author));
        exit;

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: individual_upload.php");
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Individual Book</title>
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
            background-color: #f5f8f5;
            color: var(--text-dark);
            padding-top: 245px;
            /* Space for fixed navbar */
        }

        /* Main content wrapper */
        .content-wrapper {
            padding: 20px;
            transition: padding-top 0.3s ease;
            margin: 20px auto;
        }

        .content-wrapper.navbar-active {
            padding-top: 70px;
            /* Increased padding when navbar is active */
        }

        /* Form Container Styles */
        .form-container {
            max-width: 800px;
            margin: 30px auto;
            background: linear-gradient(to right bottom, var(--white), var(--ultra-light-green));
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 15px 30px rgba(38, 48, 40, 0.08);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(201, 220, 203, 0.7);
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
        }

        .form-container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(to right, var(--main-green), var(--accent-green));
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 30px;
            color: var(--text-dark);
            font-size: 28px;
            position: relative;
            padding-bottom: 12px;
        }

        .form-container h2::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background-color: var(--main-green);
            border-radius: 2px;
        }

        /* Step indicator */
        .steps-indicator {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 40px;
            position: relative;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
            width: 200px;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--light-green);
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            color: var(--text-medium);
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .step-active .step-circle {
            background-color: var(--main-green);
            color: var(--white);
            box-shadow: 0 4px 8px rgba(46, 106, 51, 0.2);
            transform: scale(1.1);
        }

        .step-completed .step-circle {
            background-color: var(--success-green);
            color: var(--white);
        }

        .step-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-medium);
            text-align: center;
            transition: all 0.3s ease;
        }

        .step-active .step-title {
            color: var(--main-green);
            font-weight: 700;
        }

        .step-completed .step-title {
            color: var(--success-green);
        }

        /* Step connector line */
        .steps-connector {
            position: absolute;
            height: 3px;
            background-color: var(--light-green);
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 200px;
            z-index: 0;
        }

        /* Form Layout */
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 22px;
        }

        .form-group {
            margin-bottom: 22px;
            position: relative;
            flex: 1;
        }

        .form-container label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            color: var(--text-medium);
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-container input[type="text"],
        .form-container input[type="number"] {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--light-green);
            border-radius: 8px;
            background-color: var(--ultra-light-green);
            transition: all 0.3s ease;
            font-size: 16px;
            color: var(--text-dark);
            box-shadow: inset 0 1px 3px rgba(29, 69, 33, 0.05);
        }

        .form-container input[type="file"] {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            background-color: var(--ultra-light-green);
            border: 2px dashed var(--light-green);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-container input[type="text"]:focus,
        .form-container input[type="number"]:focus {
            border-color: var(--accent-green);
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 153, 80, 0.2);
            background-color: var(--white);
        }

        .form-container input[type="file"]:hover {
            border-color: var(--accent-green);
        }

        .button-wrapper {
            margin-top: 30px;
            text-align: center;
        }

        .form-container button {
            background: linear-gradient(to right, var(--main-green), var(--accent-green));
            color: var(--white);
            padding: 14px 28px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(46, 106, 51, 0.3);
            width: auto;
            min-width: 200px;
        }

        .form-container button:hover {
            background: linear-gradient(to right, var(--dark-green), var(--main-green));
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(46, 106, 51, 0.4);
        }

        .form-container button:active {
            transform: translateY(1px);
        }

        /* Message styles */
        .success-message,
        .error-message {
            text-align: center;
            margin: 15px 0;
            padding: 12px;
            border-radius: 8px;
            animation: fadeIn 0.5s ease-out;
        }

        .success-message {
            color: var(--success-green);
            background-color: rgba(46, 204, 113, 0.1);
            border-left: 4px solid var(--success-green);
        }

        .error-message {
            color: var(--error-red);
            background-color: rgba(214, 69, 65, 0.1);
            border-left: 4px solid var(--error-red);
        }

        .required-field::after {
            content: "*";
            color: var(--error-red);
            margin-left: 4px;
        }

        /* Animation for messages */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* File upload preview */
        .file-preview {
            display: none;
            text-align: center;
            margin-top: 15px;
        }

        .file-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .form-container {
                padding: 25px;
                max-width: 95%;
                margin: 20px auto;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .steps-indicator {
                flex-direction: column;
                align-items: center;
                margin-bottom: 30px;
            }

            .steps-connector {
                width: 3px;
                height: 40px;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
            }

            .step {
                margin-bottom: 50px;
                width: 100%;
            }

            .navbar-links {
                flex-wrap: wrap;
                padding: 10px;
                justify-content: space-around;
            }

            .secondary-navbar.active {
                height: auto;
                max-height: 120px;
                overflow-y: auto;
            }

            .navbar-links a {
                font-size: 13px;
                padding: 6px 10px;
            }

            .content-wrapper.navbar-active {
                padding-top: 130px;
            }
        }
    </style>
</head>

<body>
    <!-- Main Content -->
    <div class="content-wrapper" id="contentWrapper">
        <div class="form-container">
            <h2><i class="fas fa-book-open"></i> Add Individual Book</h2>

            <!-- Step Indicator -->
            <div class="steps-indicator">
                <div class="steps-connector"></div>
                <div class="step step-active">
                    <div class="step-circle">1</div>
                    <div class="step-title">Book Details</div>
                </div>
                <div class="step">
                    <div class="step-circle">2</div>
                    <div class="step-title">Location Details</div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <p class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_SESSION['success_message']; ?>
                </p>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <p class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $_SESSION['error_message']; ?>
                </p>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Information Box -->
            <div class="info-box">
                <h4><i class="fas fa-info-circle"></i> Book Information Guidelines</h4>
                <ul>
                    <li><strong>Required fields:</strong> Title, Author, Genre, Serial Number, and Quantity must be filled</li>
                    <li><strong>Image upload:</strong> Supports JPG, PNG, GIF, and WebP formats (max 5MB)</li>
                    <li><strong>Serial Number:</strong> Must be unique for each book</li>
                    <li><strong>Quantity:</strong> Specify available copies </li>
                    <li><strong>Publication Details:</strong> Include publisher name, year, and location if available</li>
                    <li><strong>ISBN:</strong> Include both 10-digit and 13-digit ISBN if available</li>
                </ul>
            </div>

            <!-- Add Book Form -->
            <form method="POST" enctype="multipart/form-data" id="addBookForm">
                <input type="hidden" name="individual_upload" value="1">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required-field">Title</label>
                        <input type="text" name="title" required placeholder="Enter book title">
                    </div>
                    <div class="form-group">
                        <label class="required-field">Author</label>
                        <input type="text" name="author" required placeholder="Enter author name">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required-field">Genre</label>
                        <input type="text" name="genre" required placeholder="Enter book genre">
                    </div>
                    <div class="form-group">
                        <label class="required-field">Serial Number</label>
                        <input type="text" name="serialno" required placeholder="Enter unique serial number">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Number of Pages</label>
                        <input type="number" name="pages" min="1" placeholder="Enter number of pages">
                    </div>
                    <div class="form-group">
                        <label class="required-field">Available Quantity</label>
                        <input type="number" name="quantity" min="1" value="1" required placeholder="Enter available copies">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Edition</label>
                        <input type="text" name="edition" placeholder="e.g., 1st Edition, Revised Edition">
                    </div>
                    <div class="form-group">
                        <label>Publication Details</label>
                        <input type="text" name="publication_details" placeholder="Publisher, Year, Location">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>ISBN</label>
                        <input type="text" name="ISBN" placeholder="Enter ISBN number">
                    </div>
                    <div class="form-group">
                        <!-- Empty space for layout -->
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Upload Book Cover Image</label>
                    <input type="file" name="image" id="imageUpload" accept="image/*">
                    <div class="file-preview" id="imagePreview"></div>
                </div>
                
                <div class="button-wrapper">
                    <button type="submit">
                        <i class="fas fa-save"></i> Add Book
                    </button>
                    <button type="button" onclick="window.location.href='add_books.php'">
                        <i class="fas fa-arrow-left"></i> Back to Options
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

    <script>

        // Image preview functionality
        document.getElementById('imageUpload').addEventListener('change', function (event) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';

            if (this.files && this.files[0]) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.maxWidth = '200px';
                    img.style.maxHeight = '200px';
                    img.style.borderRadius = '8px';
                    img.style.border = '2px solid #e0e0e0';
                    img.style.objectFit = 'cover';
                    preview.appendChild(img);
                    preview.style.display = 'block';
                };

                reader.readAsDataURL(this.files[0]);
            } else {
                preview.style.display = 'none';
            }
        });

        // Toast notification function
        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toastContainer');

            const toast = document.createElement('div');
            toast.className = 'toast';

            let borderColor = '#28a745';
            if (type === 'error') borderColor = '#dc3545';
            if (type === 'warning') borderColor = '#ffc107';
            
            toast.style.borderLeftColor = borderColor;

            toast.innerHTML = `
                <div class="toast-header">
                    <span class="toast-title">${type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Warning'}</span>
                    <button class="toast-close">&times;</button>
                </div>
                <div class="toast-body">${message}</div>
            `;

            toastContainer.appendChild(toast);

            // Trigger animation
            toast.offsetHeight;
            toast.classList.add('show');

            // Close button functionality
            toast.querySelector('.toast-close').addEventListener('click', function () {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            });

            // Auto-close after 5 seconds
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                }
            }, 5000);
        }

        // Show toast messages based on PHP session messages
        <?php if (isset($_SESSION['success_message'])): ?>
            showToast('<?php echo addslashes($_SESSION['success_message']); ?>', 'success');
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            showToast('<?php echo addslashes($_SESSION['error_message']); ?>', 'error');
        <?php endif; ?>

        // Form validation
        document.getElementById('addBookForm').addEventListener('submit', function (event) {
            const title = this.querySelector('input[name="title"]').value.trim();
            const author = this.querySelector('input[name="author"]').value.trim();
            const genre = this.querySelector('input[name="genre"]').value.trim();
            const serialno = this.querySelector('input[name="serialno"]').value.trim();
            const quantity = parseInt(this.querySelector('input[name="quantity"]').value) || 0;

            if (!title || !author || !genre || !serialno) {
                event.preventDefault();
                showToast('Please fill in all required fields.', 'error');
                return false;
            }

            if (quantity < 1) {
                event.preventDefault();
                showToast('Quantity must be at least 1.', 'error');
                return false;
            }

            // Additional validation for image file
            const imageFile = this.querySelector('input[name="image"]').files[0];
            if (imageFile) {
                const maxSize = 5 * 1024 * 1024; // 5MB in bytes
                if (imageFile.size > maxSize) {
                    event.preventDefault();
                    showToast('Image size must be less than 5MB.', 'error');
                    return false;
                }

                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(imageFile.type)) {
                    event.preventDefault();
                    showToast('Only JPG, PNG, GIF, and WebP images are allowed.', 'error');
                    return false;
                }
            }

            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding Book...';
            submitButton.disabled = true;

            // Re-enable button after a delay (in case form submission fails)
            setTimeout(() => {
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            }, 5000);
        });

        // Real-time validation feedback
        const requiredFields = document.querySelectorAll('input[required]');
        requiredFields.forEach(field => {
            field.addEventListener('blur', function() {
                if (this.value.trim() === '' || (this.type === 'number' && parseInt(this.value) < 1)) {
                    this.style.borderColor = '#dc3545';
                    this.style.backgroundColor = '#fff5f5';
                } else {
                    this.style.borderColor = '#28a745';
                    this.style.backgroundColor = '#f8fff8';
                }
            });

            field.addEventListener('input', function() {
                if (this.value.trim() !== '' && (this.type !== 'number' || parseInt(this.value) >= 1)) {
                    this.style.borderColor = '#e0e0e0';
                    this.style.backgroundColor = '#f9f9f9';
                }
            });
        });

        // Auto-generate serial number functionality
        function generateSerialNumber() {
            const timestamp = Date.now();
            const randomNum = Math.floor(Math.random() * 1000);
            return `BK${timestamp}${randomNum}`;
        }

        // Add event listener for generate serial number button
        document.getElementById('generateSerialBtn').addEventListener('click', function() {
            const serialInput = document.querySelector('input[name="serialno"]');
            const generatedSerial = generateSerialNumber();
            serialInput.value = generatedSerial;
            
            // Show feedback
            this.innerHTML = '<i class="fas fa-check"></i>';
            this.style.backgroundColor = '#28a745';
            
            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-random"></i>';
                this.style.backgroundColor = '';
            }, 1000);
        });

        // ISBN validation
        function validateISBN(isbn) {
            // Remove hyphens and spaces
            isbn = isbn.replace(/[-\s]/g, '');
            
            // Check if it's 10 or 13 digits
            if (isbn.length === 10) {
                // ISBN-10 validation
                let sum = 0;
                for (let i = 0; i < 9; i++) {
                    if (!/\d/.test(isbn[i])) return false;
                    sum += parseInt(isbn[i]) * (10 - i);
                }
                const checkDigit = isbn[9].toUpperCase();
                const calculatedCheck = (11 - (sum % 11)) % 11;
                return checkDigit === (calculatedCheck === 10 ? 'X' : calculatedCheck.toString());
            } else if (isbn.length === 13) {
                // ISBN-13 validation
                if (!/^\d{13}$/.test(isbn)) return false;
                let sum = 0;
                for (let i = 0; i < 12; i++) {
                    sum += parseInt(isbn[i]) * (i % 2 === 0 ? 1 : 3);
                }
                const checkDigit = parseInt(isbn[12]);
                const calculatedCheck = (10 - (sum % 10)) % 10;
                return checkDigit === calculatedCheck;
            }
            return false;
        }

        // Add ISBN validation
        const isbnInput = document.querySelector('input[name="ISBN"]');
        if (isbnInput) {
            isbnInput.addEventListener('blur', function() {
                const isbn = this.value.trim();
                if (isbn && !validateISBN(isbn)) {
                    this.style.borderColor = '#ffc107';
                    this.style.backgroundColor = '#fff9c4';
                    showToast('ISBN format may be invalid. Please verify.', 'warning');
                } else if (isbn) {
                    this.style.borderColor = '#28a745';
                    this.style.backgroundColor = '#f8fff8';
                }
            });
        }

        // Quantity validation
        const quantityInput = document.querySelector('input[name="quantity"]');
        if (quantityInput) {
            quantityInput.addEventListener('input', function() {
                const value = parseInt(this.value) || 0;
                if (value < 1) {
                    this.style.borderColor = '#dc3545';
                    this.style.backgroundColor = '#fff5f5';
                } else {
                    this.style.borderColor = '#28a745';
                    this.style.backgroundColor = '#f8fff8';
                }
            });

            // Prevent negative values
            quantityInput.addEventListener('keydown', function(e) {
                if (e.key === '-' || e.key === '+' || e.key === 'e' || e.key === 'E') {
                    e.preventDefault();
                }
            });
        }

        // Prevent form submission on Enter key in text inputs (except textarea)
        document.querySelectorAll('input[type="text"], input[type="number"]').forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    // Focus next input
                    const inputs = Array.from(document.querySelectorAll('input, textarea, select'));
                    const currentIndex = inputs.indexOf(this);
                    if (currentIndex < inputs.length - 1) {
                        inputs[currentIndex + 1].focus();
                    }
                }
            });
        });

        // Auto-format ISBN input
        if (isbnInput) {
            isbnInput.addEventListener('input', function() {
                let value = this.value.replace(/[^\dX]/gi, '');
                if (value.length === 10) {
                    // Format ISBN-10: XXX-X-XX-XXXXXX-X
                    value = value.replace(/(\d{3})(\d{1})(\d{2})(\d{5})(\d{1})/, '$1-$2-$3-$4-$5');
                } else if (value.length === 13) {
                    // Format ISBN-13: XXX-X-XX-XXXXXX-X
                    value = value.replace(/(\d{3})(\d{1})(\d{2})(\d{5})(\d{1})/, '$1-$2-$3-$4-$5');
                }
                this.value = value;
            });
        }

        // Clear form function
        function clearForm() {
            if (confirm('Are you sure you want to clear all fields?')) {
                document.getElementById('addBookForm').reset();
                document.getElementById('imagePreview').style.display = 'none';
                document.getElementById('imagePreview').innerHTML = '';
                
                // Reset field styles
                document.querySelectorAll('input').forEach(input => {
                    input.style.borderColor = '#e0e0e0';
                    input.style.backgroundColor = '#f9f9f9';
                });

                // Reset quantity to default value
                document.querySelector('input[name="quantity"]').value = '1';
            }
        }

    </script>

</body>
</html>
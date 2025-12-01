<?php
ob_start();
session_start();
include('../includes/db.php');
include('../includes/dashboard_header.php');

$uploadsDir = '../uploads/';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars(trim($_POST['title']));
    $author = htmlspecialchars(trim($_POST['author']));
    $genre = htmlspecialchars(trim($_POST['genre']));
    $serialno = htmlspecialchars(trim($_POST['serialno']));
    $pages = !empty($_POST['pages']) ? (int) $_POST['pages'] : null;
    $publication_details = isset($_POST['publication_details']) ? htmlspecialchars(trim($_POST['publication_details'])) : null;
    $ISBN = isset($_POST['ISBN']) ? htmlspecialchars(trim($_POST['ISBN'])) : null;
    $edition = isset($_POST['edition']) ? htmlspecialchars(trim($_POST['edition'])) : null;
    $imageData = null;
    $imageSizeKiB = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $fileType = $finfo->file($_FILES['image']['tmp_name']);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($fileType, $allowedTypes)) {
            $_SESSION['error_message'] = "Error: Only JPG, PNG, GIF, and WebP images are allowed.";
            header("Location: add_books.php");
            exit;
        }

        $fileSizeBytes = $_FILES['image']['size'];
        $imageSizeKiB = round($fileSizeBytes / 1024, 1);
        $maxSizeKiB = 5 * 1024;

        if ($imageSizeKiB > $maxSizeKiB) {
            $_SESSION['error_message'] = "Error: Image size exceeds 5MB.";
            header("Location: add_books.php");
            exit;
        }

        $timestamp = uniqid();
        $originalFilename = pathinfo($_FILES['image']['name'], PATHINFO_FILENAME);
        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $newFilename = $timestamp . '_' . $originalFilename . '.' . $extension;
        $uploadPath = $uploadsDir . $newFilename;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
            $_SESSION['error_message'] = "Error uploading file.";
            header("Location: add_books.php");
            exit;
        }

        $imageData = file_get_contents($uploadPath);
    }

    $query = "INSERT INTO books (title, author, genre, serialno, pages, publication_details, ISBN, edition, image, image_size_kib) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        $_SESSION['error_message'] = "Database error: " . $conn->error;
        header("Location: add_books.php");
        exit;
    }

    $stmt->bind_param("ssssissssd", $title, $author, $genre, $serialno, $pages, $publication_details, $ISBN, $edition, $imageData, $imageSizeKiB);

    if ($stmt->execute()) {
        $bookId = $stmt->insert_id;
        $stmt->close();
        $_SESSION['success_message'] = "Book added successfully!";
        header("Location: books_information.php?book_id=$bookId&title=" . urlencode($title) . "&author=" . urlencode($author));
        exit;
    } else {
        $_SESSION['error_message'] = "Error: " . $stmt->error;
        $stmt->close();
        header("Location: add_books.php");
        exit;
    }
}

// Reservation count logic
$new_reservations_query = "SELECT COUNT(*) as count FROM reservations WHERE status = 'reserved' AND is_viewed = 0";
$new_reservations_result = $conn->query($new_reservations_query);
$new_reservations_count = ($new_reservations_result && $row = $new_reservations_result->fetch_assoc()) ? $row['count'] : 0;
?>


<!-- HTML STARTS BELOW -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Book</title>
    <link rel="stylesheet" href="employee.css">
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

        /* Fixed Top Navbar */
        .navbar-toggle-container {
            position: fixed;
            top: 255;
            left: 0;
            right: 0;
            z-index: 1000;
            background-color: var(--accent-green);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 50px;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
        }

        .toggle-btn {
            background-color: var(--main-green);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .toggle-btn:hover {
            background-color: var(--dark-green);
            transform: scale(1.05);
        }

        .toggle-btn i {
            transition: transform 0.3s ease;
        }

        .toggle-btn.active i {
            transform: rotate(180deg);
        }

        .dashboard-title {
            font-family: 'Georgia', serif;
            font-size: 20px;
            color: white;
            margin: 0 15px;
            font-weight: 600;
        }

        /* Notification icon styles */
        .navbar-actions {
            display: flex;
            align-items: center;
        }

        .notification-icon {
            position: relative;
            color: white;
            font-size: 18px;
            margin-left: 15px;
            text-decoration: none;
        }

        .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--error-red);
            color: white;
            font-size: 11px;
            font-weight: bold;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 2px solid var(--accent-green);
        }

        /* Secondary Navbar */
        .secondary-navbar {
            position: fixed;
            top: 285px;
            left: 0;
            right: 0;
            background-color: var(--dark-green);
            height: 0;
            overflow: hidden;
            transition: height 0.3s ease;
            z-index: 999;
            font-family: 'Times New Roman', Times, serif;
        }

        .secondary-navbar.active {
            height: 60px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .navbar-links {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            gap: 20px;
            padding: 0 20px;
        }

        .navbar-links a {
            color: white;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 4px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }

        .navbar-links a i {
            margin-right: 6px;
        }

        .navbar-links a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Adjust content wrapper to account for fixed navbar */
        .content-wrapper {
            padding: 20px;
            margin-top: 70px;
            /* Default margin to account for navbar */
            transition: margin-top 0.3s ease;
        }

        .content-wrapper.navbar-active {
            margin-top: 120px;
            /* Extra margin when secondary navbar is active */
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
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
                margin-top: 170px;
            }
        }

        /* Add Book Form Styles - REDESIGNED */
        .form-container {
            max-width: 700px;
            margin: 50px auto;
            background: linear-gradient(to right bottom, var(--white), var(--ultra-light-green));
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(38, 48, 40, 0.1);
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(201, 220, 203, 0.7);
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

        .form-container p {
            text-align: center;
            margin-top: 15px;
            padding: 10px;
            border-radius: 8px;
        }

        .success-message {
            color: var(--success-green);
            background-color: rgba(46, 204, 113, 0.1);
            border-left: 4px solid var(--success-green);
            padding: 12px;
        }

        .error-message {
            color: var(--error-red);
            background-color: rgba(214, 69, 65, 0.1);
            border-left: 4px solid var(--error-red);
            padding: 12px;
        }

        .required-field::after {
            content: "*";
            color: var(--error-red);
            margin-left: 4px;
        }

        .notification-badge {
            background-color: var(--error-red);
            color: var(--white);
            font-size: 12px;
            border-radius: 50%;
            padding: 2px 6px;
            margin-left: 5px;
        }

        /* Step indicator */
        .steps-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
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
        }

        .step-active .step-circle {
            background-color: var(--main-green);
            color: var(--white);
            box-shadow: 0 4px 8px rgba(46, 106, 51, 0.2);
        }

        .step-completed .step-circle {
            background-color: var(--success-green);
            color: var(--white);
        }

        .step-title {
            font-size: 15px;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 4px;
            transition: all 0.2s ease;
            position: relative;
        }

        .step-active .step-title {
            color: var(--main-green);
        }

        .step-completed .step-title {
            color: var(--success-green);
        }

        .step-connector {
            width: 100px;
            height: 3px;
            background-color: var(--light-green);
            position: absolute;
            top: 20px;
            left: 100%;
            z-index: 0;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .form-container {
                padding: 25px;
                max-width: 90%;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .steps-indicator {
                flex-direction: column;
                align-items: center;
                gap: 20px;
            }

            .step-connector {
                width: 3px;
                height: 30px;
                left: 50%;
                top: 100%;
            }
        }
    </style>

</head>

<body>

    <!-- Navigation Bar Container -->
    <div class="navbar-toggle-container"
        style="display: flex; justify-content: space-between; align-items: center; padding: 10px;">

        <!-- Toggle Button (Left) -->
        <button class="toggle-btn" id="navbarToggle" title="Toggle Menu">
            <i class="fas fa-chevron-down"></i>
        </button>

        <!-- Notification Icon (Right) -->
        <a href="notification.php" class="notification-icon" title="Notifications" style="position: relative;">
            <i class="fas fa-bell"></i>
            <?php if ($new_reservations_count > 0): ?>
                <span class="badge"><?php echo $new_reservations_count; ?></span>
            <?php endif; ?>
        </a>

    </div>

     <!-- Secondary Navigation Bar (Initially Hidden) -->
    <div class="secondary-navbar" id="secondaryNavbar">
        <div class="navbar-links">
            <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
            <a href="add_employee_data.php"><i class="fas fa-user-plus"></i> Add Detials</a>
            <a href="add_books.php"><i class="fas fa-book"></i> Add Books</a>
            <a href="books.php"><i class="fas fa-book"></i> Books</a>
            <a href="track_books.php"><i class="fas fa-search"></i> Track Books</a>
            <a href="reserved_details.php">
                <i class="fas fa-users"></i> Reserved
                <?php if ($new_reservations_count > 0): ?>
                    <span class="notification-badge"><?php echo $new_reservations_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="confirmed_reservations.php"><i class="fas fa-check-circle"></i> Confirmed</a>
            <a href="payment_details.php"><i class="fas fa-money-check-alt"></i> Payment</a>
            <a href="report.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="../login.php"><i class="fas fa-sign-out-alt"></i> LogOut</a>
        </div>
    </div>
    <div class="content-wrapper" id="contentWrapper">
        <div class="form-container">
            <h2>Add a New Book</h2>

            <div class="steps-indicator">
                <div class="step step-active">
                    <div class="step-circle">1</div>
                    <div class="step-title">Book Details</div>
                    <div class="step-connector"></div>
                </div>
                <div class="step">
                    <div class="step-circle">2</div>
                    <div class="step-title">Location Details</div>
                </div>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <p class="success-message"><?php echo $_SESSION['success_message'];
                unset($_SESSION['success_message']); ?>
                </p>
            <?php elseif (isset($_SESSION['error_message'])): ?>
                <p class="error-message"><?php echo $_SESSION['error_message'];
                unset($_SESSION['error_message']); ?></p>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label class="required-field">Title</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="form-group">
                        <label class="required-field">Author</label>
                        <input type="text" name="author" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="required-field">Genre</label>
                        <input type="text" name="genre" required>
                    </div>
                    <div class="form-group">
                        <label class="required-field">Serial Number</label>
                        <input type="text" name="serialno" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Pages</label>
                        <input type="number" name="pages">
                    </div>
                    <div class="form-group">
                        <label>Publication Details</label>
                        <input type="text" name="publication_details">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>ISBN</label>
                        <input type="text" name="ISBN">
                    </div>
                    <div class="form-group">
                        <label>Edition</label>
                        <input type="text" name="edition">
                    </div>
                </div>
                <div class="form-group">
                    <label>Upload Book Image</label>
                    <input type="file" name="image" accept="image/*">
                </div>
                <div class="button-wrapper">
                    <button type="submit">Add Book</button>
                </div>
            </form>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

    <script>
        document.getElementById('navbarToggle').addEventListener('click', function () {
            this.classList.toggle('active');
            const secondaryNavbar = document.getElementById('secondaryNavbar');
            secondaryNavbar.classList.toggle('active');
            const contentWrapper = document.getElementById('contentWrapper');
            contentWrapper.classList.toggle('navbar-active');
        });
    </script>

</body>

</html>
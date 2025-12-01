<?php
// Include database connection file
include('../includes/db.php');

// Check if book_id is provided
if (!isset($_GET['book_id'])) {
    // Redirect to add_books.php if book_id is missing
    header("Location: add_books.php");
    exit;
}

// Get book ID from URL parameter
$book_id = $_GET['book_id'];

// Try to get title and author from URL parameters
$title = isset($_GET['title']) ? urldecode($_GET['title']) : '';
$author = isset($_GET['author']) ? urldecode($_GET['author']) : '';

// If title or author is empty, try to fetch from database
if (empty($title) || empty($author)) {
    $book_query = "SELECT title, author FROM books WHERE id = ?";
    $book_stmt = $conn->prepare($book_query);
    $book_stmt->bind_param("i", $book_id);
    $book_stmt->execute();
    $book_result = $book_stmt->get_result();

    if ($book_result && $book_row = $book_result->fetch_assoc()) {
        $title = $book_row['title'];
        $author = $book_row['author'];
    } else {
        // If book not found, redirect to add_books.php
        header("Location: add_books.php");
        exit;
    }

    $book_stmt->close();
}

// Initialize variables for messages and redirect flag
$success_message = '';
$error_message = '';
$should_redirect = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get book information details
    $section = $_POST['section'];
    $call_no = $_POST['call_no'];
    $rack_no = $_POST['rack_no'];

    try {
        // Insert into the books_information table
        $info_query = "INSERT INTO books_information (book_id, section, call_no, rack_no) VALUES (?, ?, ?, ?)";
        $info_stmt = $conn->prepare($info_query);
        $info_stmt->bind_param("isss", $book_id, $section, $call_no, $rack_no);
        $info_stmt->execute();
        $info_stmt->close();

        // Set success message and redirect flag
        $success_message = "Book information added successfully!";
        $should_redirect = true;
    } catch (Exception $e) {
        // Something went wrong
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get count of new reservations
$new_reservations_query = "SELECT COUNT(*) as count FROM reservations WHERE status = 'reserved' AND is_viewed = 0";
$new_reservations_result = $conn->query($new_reservations_query);
$new_reservations_count = 0;
if ($new_reservations_result && $row = $new_reservations_result->fetch_assoc()) {
    $new_reservations_count = $row['count'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Location Details</title>
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f8f5;
            color: var(--text-dark);
            padding-top: 245px;
            /* Space for fixed navbar */
        }

        /* Fixed Top Navbar */
        .navbar-toggle-container {
            position: fixed;
            top: 245;
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
            background: linear-gradient(to right bottom, #ffffff, #f8f9fa);
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(230, 230, 230, 0.7);
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
        .form-container select {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #d9e2ec;
            border-radius: 8px;
            background-color: #f8fafc;
            transition: all 0.3s ease;
            font-size: 16px;
            color: #333;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .form-container input[disabled] {
            background-color: #e9ecef;
            cursor: not-allowed;
        }

        .form-container select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2345634a' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 40px;
        }

        .form-container input[type="text"]:focus,
        .form-container select:focus {
            border-color: var(--main-green);
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 106, 51, 0.2);
            background-color: #fff;
        }

        .button-wrapper {
            margin-top: 30px;
            text-align: center;
        }

        .form-container button {
            background: linear-gradient(to right, var(--main-green), var(--accent-green));
            color: white;
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
            background-color: #e8f5e9;
            border-left: 4px solid var(--success-green);
            padding: 12px;
        }

        .error-message {
            color: var(--error-red);
            background-color: #ffebee;
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
            color: white;
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
            background-color: #e0e0e0;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            color: #757575;
            margin-bottom: 10px;
        }

        .step-active .step-circle {
            background-color: var(--main-green);
            color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .step-completed .step-circle {
            background-color: var(--success-green);
            color: white;
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
            background-color: #e0e0e0;
            position: absolute;
            top: 20px;
            left: 100%;
            z-index: 0;
        }

        .step-completed .step-connector {
            background-color: var(--success-green);
        }

        /* Book info summary */
        .book-summary {
            background-color: var(--ultra-light-green);
            border-left: 4px solid var(--main-green);
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 4px;
        }

        .book-summary p {
            margin: 5px 0;
            color: var(--text-dark);
        }

        .book-summary strong {
            color: var(--dark-green);
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
    <?php if ($should_redirect): ?>
    <script>
        setTimeout(function() {
            window.location.href = 'add_books.php';
        }, 2000);
    </script>
    <?php endif; ?>
</head>

<body>
   <?php include('../includes/dashboard_header.php');?>
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

    <!-- Content Wrapper -->
    <div class="content-wrapper" id="contentWrapper">
        <div class="form-container">
            <h2>Book Location Details</h2>

            <!-- Step Indicator -->
            <div class="steps-indicator">
                <div class="step step-completed">
                    <div class="step-circle">1</div>
                    <div class="step-title">Book Details</div>
                    <div class="step-connector"></div>
                </div>
                <div class="step step-active">
                    <div class="step-circle">2</div>
                    <div class="step-title">Location Details</div>
                </div>
            </div>

            <!-- Book Summary -->
            <div class="book-summary">
                <p><strong>Book ID:</strong> <?php echo htmlspecialchars($book_id); ?></p>
                <p><strong>Title:</strong> <?php echo htmlspecialchars($title); ?></p>
                <p><strong>Author:</strong> <?php echo htmlspecialchars($author); ?></p>
            </div>

            <?php if (!empty($success_message)): ?>
                <p class="success-message"><?php echo $success_message; ?></p>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <p class="error-message"><?php echo $error_message; ?></p>
            <?php endif; ?>

            <form method="post" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="id" class="required-field">Book ID</label>
                        <input type="text" id="book_id" name="book_id" value="<?php echo htmlspecialchars($book_id); ?>"
                            disabled>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="section" class="required-field">Library Section</label>
                        <select id="section" name="section" required>
                            <option value="">Select Section</option>
                            <option value="General">General</option>
                            <option value="History & Geography">History & Geography</option>
                            <option value="IT section">IT section</option>
                            <option value="Literature">Literature</option>
                            <option value="Children">Children</option>
                            <option value="Novel">Novel</option>
                            <option value="Dzongkha Section">Dzongkha Section</option>
                            <option value="Poetry Section">Poetry</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="call_no">Call Number</label>
                        <input type="text" id="call_no" name="call_no" placeholder="e.g. 1-99">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="rack_no" class="required-field">Rack/Shelf Number</label>
                        <input type="text" id="rack_no" name="rack_no" placeholder="e.g. R-1" required>
                    </div>
                </div>

                <div class="button-wrapper">
                    <button type="submit">Save Location Details</button>
                </div>
            </form>
        </div>
    </div>
    <?php include('../includes/footer.php'); ?>
    <script>
        // Navbar toggle functionality
        document.getElementById('navbarToggle').addEventListener('click', function() {
            // Toggle the active class on the button and change icon
            this.classList.toggle('active');

            // Toggle the secondary navbar
            const secondaryNavbar = document.getElementById('secondaryNavbar');
            secondaryNavbar.classList.toggle('active');

            // Adjust content wrapper spacing
            const contentWrapper = document.getElementById('contentWrapper');
            contentWrapper.classList.toggle('navbar-active');
        });
    </script>
</body>

</html>
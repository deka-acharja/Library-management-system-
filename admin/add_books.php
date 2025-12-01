<?php
ob_start();
session_start();
include('../includes/db.php');
include('../includes/dashboard_header.php');

// Get count of new reservations
$new_reservations_query = "SELECT COUNT(*) as count FROM reservations WHERE status IN ('reserved', 'returned', 'cancelled', 'due paid') AND is_viewed = 0";
$new_reservations_result = $conn->query($new_reservations_query);
$new_reservations_count = 0;
if ($new_reservations_result && $row = $new_reservations_result->fetch_assoc()) {
    $new_reservations_count = $row['count'];
}

// Count unread employee registrations
$new_employees_query = "SELECT COUNT(*) as count FROM users WHERE role = 'employee' AND is_viewed = 0";
$new_employees_result = $conn->query($new_employees_query);
$new_employees_count = 0;
if ($new_employees_result && $row = $new_employees_result->fetch_assoc()) {
    $new_employees_count = $row['count'];
}

// Add both counts together for total notifications
$total_notifications_count = $new_reservations_count + $new_employees_count;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Books - Choose Method</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --main-green: #2e6a33;
            /* Main green color */
            --dark-green: #1d4521;
            /* Darker shade for hover/focus */
            --light-green: #c9dccb;
            /* Light green for backgrounds */
            --accent-green: #4a9950;
            /* Accent green for highlights */
            --ultra-light-green: #eef5ef;
            /* Ultra light green for subtle backgrounds */
            --text-dark: #263028;
            /* Dark text color */
            --text-medium: #45634a;
            /* Medium text color */
            --text-light: #6b8f70;
            /* Light text color */
            --white: #ffffff;
            /* White */
            --error-red: #d64541;
            /* Error red */
            --success-green: #2ecc71;
            /* Success green */
            --warning-yellow: #f39c12;
            /* Warning yellow */
            --font-main: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans';
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            --navbar-height: 50px;
            --secondary-navbar-height: 60px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-main);
            background: linear-gradient(135deg, var(--ultra-light-green) 0%, var(--light-green) 100%);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Fixed Top Navbar */
        .navbar-toggle-container {
            position: fixed;
            top: 240px;
            left: 0;
            right: 0;
            z-index: 1000;
            background: linear-gradient(135deg, var(--main-green) 0%, var(--accent-green) 100%);
            box-shadow: var(--box-shadow);
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: var(--navbar-height);
            backdrop-filter: blur(10px);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
        }

        .toggle-btn {
            background: rgba(255, 255, 255, 0.2);
            color: var(--white);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: var(--transition);
            backdrop-filter: blur(5px);
        }

        .toggle-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: scale(1.05);
        }

        .toggle-btn i {
            transition: transform 0.3s ease;
        }

        .toggle-btn.active i {
            transform: rotate(180deg);
        }

        .dashboard-title {
            font-family: var(--font-main);
            font-size: 20px;
            color: var(--white);
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
            color: var(--white);
            font-size: 18px;
            margin-left: 15px;
            text-decoration: none;
            padding: 8px;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .notification-icon:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: linear-gradient(135deg, var(--error-red) 0%, #c0392b 100%);
            color: var(--white);
            font-size: 11px;
            font-weight: bold;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 2px solid var(--white);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(214, 69, 65, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(214, 69, 65, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(214, 69, 65, 0);
            }
        }

        /* Secondary Navbar */
        .secondary-navbar {
            position: fixed;
            top: 290px;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, var(--dark-green) 0%, var(--main-green) 100%);
            height: 0;
            overflow: hidden;
            transition: height 0.3s ease;
            z-index: 999;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
        }

        .secondary-navbar.active {
            height: var(--secondary-navbar-height);
        }

        .navbar-links {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            gap: 5px;
            padding: 0 20px;
            flex-wrap: wrap;
        }

        .navbar-links a {
            color: var(--white);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 8px 12px;
            border-radius: var(--border-radius);
            transition: var(--transition);
            display: flex;
            align-items: center;
            white-space: nowrap;
        }

        .navbar-links a i {
            margin-right: 6px;
            font-size: 12px;
        }

        .navbar-links a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Toast notifications */
        .toast-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1100;
        }

        .toast {
            background: var(--white);
            border-left: 4px solid var(--main-green);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 15px;
            opacity: 0;
            padding: 15px 20px;
            transform: translateX(100%);
            transition: var(--transition);
            width: 300px;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .toast-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .toast-title {
            font-weight: 600;
            font-size: 16px;
            color: var(--text-dark);
        }

        .toast-close {
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 16px;
        }

        .toast-body {
            color: var(--text-medium);
            font-size: 14px;
        }

        /* Main content wrapper */
        .content-wrapper {
            padding: 80px 20px 40px;
            transition: padding-top 0.3s ease;
            min-height: 100vh;
        }

        .content-wrapper.navbar-active {
            padding-top: 140px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            color: var(--text-dark);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            position: relative;
        }

        .page-header h1::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(135deg, var(--main-green) 0%, var(--accent-green) 100%);
            border-radius: 2px;
        }

        .page-header p {
            color: var(--text-medium);
            font-size: 1.1rem;
            font-weight: 400;
        }

        /* Upload Type Selector */
        .upload-type-selector {
            max-width: 1000px;
            margin: 0 auto;
        }

        .upload-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            justify-items: center;
        }

        .upload-option {
            background: var(--white);
            border: 2px solid var(--light-green);
            border-radius: 16px;
            padding: 40px 30px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }

        .upload-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(46, 106, 51, 0.1), transparent);
            transition: left 0.6s ease;
        }

        .upload-option:hover::before {
            left: 100%;
        }

        .upload-option:hover {
            border-color: var(--accent-green);
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(46, 106, 51, 0.2);
        }

        .upload-option i {
            font-size: 4rem;
            color: var(--main-green);
            margin-bottom: 25px;
            display: block;
            transition: var(--transition);
        }

        .upload-option:hover i {
            transform: scale(1.1);
            color: var(--accent-green);
        }

        .upload-option h3 {
            margin: 0 0 15px 0;
            color: var(--text-dark);
            font-size: 1.6rem;
            font-weight: 600;
        }

        .upload-option p {
            margin: 0 0 25px 0;
            color: var(--text-medium);
            font-size: 1rem;
            line-height: 1.6;
        }

        .features {
            list-style: none;
            padding: 0;
            margin: 25px 0;
            text-align: left;
        }

        .features li {
            color: var(--text-medium);
            font-size: 0.95rem;
            margin: 12px 0;
            position: relative;
            padding-left: 25px;
            display: flex;
            align-items: center;
        }

        .features li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--success-green);
            font-weight: bold;
            font-size: 1.1rem;
        }

        .upload-btn {
            background: linear-gradient(135deg, var(--main-green) 0%, var(--accent-green) 100%);
            color: var(--white);
            padding: 15px 30px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
            box-shadow: 0 4px 15px rgba(46, 106, 51, 0.3);
        }

        .upload-btn:hover {
            background: linear-gradient(135deg, var(--dark-green) 0%, var(--main-green) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 106, 51, 0.4);
        }

        .upload-btn i {
            font-size: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .upload-options {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .upload-option {
                min-width: unset;
                max-width: unset;
                padding: 30px 20px;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .navbar-links {
                gap: 2px;
            }

            .navbar-links a {
                font-size: 12px;
                padding: 6px 8px;
            }

            .content-wrapper {
                padding: 70px 15px 30px;
            }

            .content-wrapper.navbar-active {
                padding-top: 130px;
            }
        }

        @media (max-width: 480px) {
            .upload-option {
                padding: 25px 15px;
            }

            .upload-option i {
                font-size: 3rem;
            }

            .upload-option h3 {
                font-size: 1.4rem;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>

<body>
    <!-- Toast Container for Notifications -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Fixed Top Navigation Bar -->
    <div class="navbar-toggle-container">
        <div class="navbar-brand">
           <button class="toggle-btn" id="navbarToggle" title="Toggle Menu">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>

        <div class="navbar-actions">
            <a href="notifiactions.php" class="notification-icon" title="Notifications">
                <i class="fas fa-bell"></i>
                <?php if ($total_notifications_count > 0): ?>
                    <span class="badge"><?php echo $total_notifications_count; ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>
    <!-- Secondary Navigation Bar (Initially Hidden) -->
    <div class="secondary-navbar" id="secondaryNavbar">
        <div class="navbar-links">
            <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
            <a href="add_books.php"><i class="fas fa-book"></i> Add Books</a>
            <a href="all_books.php"><i class="fas fa-book"></i> Books</a>
            <a href="confirmed_reservation.php"><i class="fas fa-check-circle"></i> Track Reservation</a>
            <a href="reservations_list.php"><i class="fas fa-chart-bar"></i> Reservation</a>
            <a href="view_borrow_details.php"><i class="fas fa-chart-bar"></i> Borrowed Book</a>
            <a href="payment_details.php"><i class="fas fa-money-check-alt"></i> Payment</a>
            <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="../login.php"><i class="fas fa-sign-out-alt"></i> LogOut</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content-wrapper" id="contentWrapper">
        <div class="page-header">
            <h1>Add Books to Library</h1>
            <p>Choose your preferred method to add books to the library system</p>
        </div>

        <!-- Upload Type Selector -->
        <div class="upload-type-selector">
            <div class="upload-options">
                <div class="upload-option">
                    <i class="fas fa-file-excel"></i>
                    <h3>Bulk Upload</h3>
                    <p>Upload multiple books at once using an Excel file with location details</p>
                    <ul class="features">
                        <li>Upload hundreds of books at once</li>
                        <li>Include location information</li>
                        <li>Automatic data validation</li>
                        <li>Excel template provided</li>
                    </ul>
                    <a href="bulk_upload_book.php" class="upload-btn">
                        <i class="fas fa-upload"></i> Start Bulk Upload
                    </a>
                </div>

                <div class="upload-option">
                    <i class="fas fa-book-open"></i>
                    <h3>Individual Book</h3>
                    <p>Add one book at a time with detailed information and cover image</p>
                    <ul class="features">
                        <li>Detailed book information</li>
                        <li>Upload book cover image</li>
                        <li>Complete control over data</li>
                        <li>Add location details later</li>
                    </ul>
                    <a href="individual_book_upload.php" class="upload-btn">
                        <i class="fas fa-plus"></i> Add Single Book
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

    <script>
        // Navbar toggle functionality
        document.getElementById('navbarToggle').addEventListener('click', function() {
            this.classList.toggle('active');
            const icon = this.querySelector('i');
            if (icon.classList.contains('fa-bars')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }

            const secondaryNavbar = document.getElementById('secondaryNavbar');
            secondaryNavbar.classList.toggle('active');

            const contentWrapper = document.getElementById('contentWrapper');
            contentWrapper.classList.toggle('navbar-active');
        });

        // Enhanced hover effects for upload options
        document.querySelectorAll('.upload-option').forEach(option => {
            option.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            });

            option.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Add smooth scrolling for better UX
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>

</html>
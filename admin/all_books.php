<?php
include('../includes/db.php');
include('../includes/dashboard_header.php');

// Define the number of records per page
$records_per_page = 10;

// Get the current page number from the URL, default is page 1
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

// Calculate the starting record for the SQL query
$start_from = ($page - 1) * $records_per_page;

// Check if filter is applied
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where_clause = "";

if ($filter == 'available') {
    $where_clause = "WHERE availability = 'Available'";
} else if ($filter == 'not_available') {
    $where_clause = "WHERE availability != 'Available'";
}

// Fetch books with pagination and filter
$query = "SELECT * FROM books $where_clause ORDER BY created_at DESC LIMIT $start_from, $records_per_page";
$result = $conn->query($query);

// Fetch total number of records to calculate the total number of pages
$total_query = "SELECT COUNT(*) FROM books $where_clause";
$total_result = $conn->query($total_query);
$total_records = $total_result->fetch_row()[0];
$total_pages = ceil($total_records / $records_per_page);

// Get count of new reservations (UPDATED to include all statuses)
$new_reservations_query = "SELECT COUNT(*) as count FROM reservations WHERE status IN ('reserved', 'returned', 'cancelled', 'due paid') AND is_viewed = 0";
$new_reservations_result = $conn->query($new_reservations_query);
$new_reservations_count = 0;
if ($new_reservations_result && $row = $new_reservations_result->fetch_assoc()) {
    $new_reservations_count = $row['count'];
}
// New code to count unread employee registrations
$new_employees_query = "SELECT COUNT(*) as count FROM users WHERE role = 'employee' AND is_viewed = 0";
$new_employees_result = $conn->query($new_employees_query);
$new_employees_count = 0;
if ($new_employees_result && $row = $new_employees_result->fetch_assoc()) {
    $new_employees_count = $row['count'];
}

// Calculate total notifications - THIS IS THE FIX
$total_notifications_count = $new_reservations_count + $new_employees_count;

// Handle delete operation
if (isset($_GET['delete_id'])) {
    $delete_id = (int) $_GET['delete_id'];
    $delete_query = "DELETE FROM books WHERE id = $delete_id";

    if ($conn->query($delete_query)) {
        // Redirect to avoid resubmission
        header("Location: all_books.php?status=deleted");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Books | Library Management System</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
            font-family: var(--font-main);
            background-color: var(--ultra-light-green);
            color: var(--text-dark);
            line-height: 1.6;
            padding-top: 240px;
            /* Only account for main navbar */
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }

        /* Fixed Top Navbar - Keep this fixed and stable */
        .navbar-toggle-container {
            position: fixed;
            top: 10;
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

        /* Fixed Secondary Navbar - Position it absolutely to not affect layout */
        .secondary-navbar {
            position: fixed;
            top: 285px;
            /* Position exactly below the main navbar */
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

        /* Toast notifications */
        .toast-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1100;
        }

        .toast {
            background-color: var(--white);
            border-left: 4px solid var(--main-green);
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            margin-bottom: 15px;
            opacity: 0;
            padding: 15px 20px;
            transform: translateX(100%);
            transition: all 0.3s ease;
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

        /* Main content wrapper - This will be pushed down when secondary navbar is active */
        .content-wrapper {
            padding: 20px;
            margin: 20px auto;
            transition: margin-top 0.3s ease;
        }

        /* When secondary navbar is active, push content down */
        .content-wrapper.secondary-active {
            margin-top: 100px;
            /* 60px for secondary navbar + 20px margin */
        }

        /* Books Container */
        .books-container {
            max-width: 100%;
            margin: 25px auto;
            padding: 25px;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-green);
        }

        .header-section h2 {
            font-size: 22px;
            color: var(--main-green);
            margin: 0;
        }

        .search-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 10px;
        }

        .search-box {
            position: relative;
            flex-grow: 1;
        }

        .search-box input {
            width: 100%;
            padding: 10px 40px 10px 15px;
            border: 1px solid var(--light-green);
            border-radius: var(--border-radius);
            font-size: 14px;
            transition: var(--transition);
        }

        .search-box input:focus {
            border-color: var(--main-green);
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 106, 51, 0.1);
        }

        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        /* Filter Dropdown Styling */
        .filter-dropdown {
            position: relative;
            display: inline-block;
        }

        .filter-btn {
            background-color: var(--light-green);
            border: none;
            border-radius: var(--border-radius);
            padding: 10px 15px;
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            transition: var(--transition);
            color: var(--text-dark);
        }

        .filter-btn:hover {
            background-color: var(--accent-green);
            color: var(--white);
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: var(--white);
            min-width: 180px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            z-index: 1;
            border-radius: var(--border-radius);
            margin-top: 5px;
        }

        .dropdown-content a {
            color: var(--text-dark);
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 14px;
            transition: var(--transition);
        }

        .dropdown-content a:hover {
            background-color: var(--ultra-light-green);
        }

        .dropdown-content a.active {
            background-color: var(--light-green);
            font-weight: 500;
            color: var(--text-dark);
        }

        .filter-dropdown:hover .dropdown-content {
            display: block;
        }

        .print-btn {
            background-color: var(--accent-green);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            padding: 10px 15px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .print-btn:hover {
            background-color: var(--dark-green);
            transform: translateY(-2px);
        }

        /* Table Styling */
        .table-responsive {
            overflow-x: auto;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--white);
            font-size: 14px;
        }

        th,
        td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--light-green);
        }

        th {
            background-color: var(--ultra-light-green);
            color: var(--text-medium);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background-color: var(--ultra-light-green);
        }

        img.book-cover {
            height: 70px;
            width: 50px;
            object-fit: cover;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .availability {
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }

        .available {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-green);
        }

        .not-available {
            background-color: rgba(214, 69, 65, 0.1);
            color: var(--error-red);
        }

        .book-title {
            font-weight: 600;
            color: var(--text-dark);
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden;
            max-width: 180px;
        }

        .book-author {
            color: var(--text-medium);
            font-size: 13px;
        }

        /* Delete confirmation modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: var(--white);
            margin: 15% auto;
            padding: 20px;
            border: 1px solid var(--light-green);
            border-radius: var(--border-radius);
            width: 400px;
            max-width: 90%;
            position: relative;
        }

        .modal-header {
            padding-bottom: 15px;
            margin-bottom: 15px;
            border-bottom: 1px solid var(--light-green);
        }

        .modal-title {
            margin: 0;
            font-size: 18px;
            color: var(--text-dark);
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: var(--border-radius);
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }

        .btn-secondary {
            background-color: var(--light-green);
            color: var(--text-dark);
        }

        .btn-danger {
            background-color: var(--error-red);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Status message */
        .status-message {
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: var(--border-radius);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-green);
            border-left: 4px solid var(--success-green);
        }

        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid var(--light-green);
        }

        .pagination-info {
            font-size: 14px;
            color: var(--text-medium);
        }

        .pagination {
            display: flex;
            gap: 5px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-size: 14px;
            transition: var(--transition);
        }

        .pagination a {
            background-color: var(--light-green);
            color: var(--text-dark);
        }

        .pagination a:hover {
            background-color: var(--accent-green);
            color: var(--white);
        }

        .pagination .active {
            background-color: var(--main-green);
            color: white;
        }

        .pagination .disabled {
            background-color: var(--ultra-light-green);
            color: var(--text-light);
            cursor: not-allowed;
        }

        /* Print Styles - Modified to print only the table */
        @media print {
            body * {
                visibility: hidden;
            }

            .table-responsive,
            .table-responsive * {
                visibility: visible;
            }

            .table-responsive {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                box-shadow: none;
            }

            table {
                width: 100%;
                border: 1px solid var(--light-green);
            }

            th {
                background-color: #f1f1f1 !important;
                color: black !important;
            }

            img.book-cover {
                max-height: 60px;
            }

            .availability {
                border: 1px solid #ddd;
            }

            .available {
                background-color: #f1f1f1 !important;
                color: black !important;
            }

            .not-available {
                background-color: #f1f1f1 !important;
                color: black !important;
            }

            td:last-child,
            /* Hide action column */
            th:last-child {
                display: none !important;
            }
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .books-container {
                padding: 15px;
            }

            .search-section {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-dropdown,
            .print-btn {
                width: 100%;
                margin-top: 10px;
            }

            .filter-btn {
                width: 100%;
                justify-content: center;
            }

            .dropdown-content {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .navbar-links {
                justify-content: flex-start;
                padding: 0 10px;
                overflow-x: auto;
            }

            .navbar-links a {
                padding: 8px 10px;
                font-size: 13px;
                white-space: nowrap;
            }

            .header-section {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .table-responsive {
                margin: 0 -15px;
                width: calc(100% + 30px);
                border-radius: 0;
            }

            th,
            td {
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    <!-- Toast Container for Notifications -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Navbar Toggle Button and Notification Icon - This stays fixed -->
    <div class="navbar-toggle-container">
        <!-- Left side with brand and toggle -->
        <div class="navbar-brand">
            <button class="toggle-btn" id="navbarToggle" title="Toggle Menu">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>

        <!-- Right side with notification -->
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

    <div id="contentWrapper" class="content-wrapper">
        <div class="container">
            <div class="books-container">
                <?php if (isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
                    <div class="status-message status-success">
                        <i class="fas fa-check-circle"></i>
                        Book has been successfully deleted.
                    </div>
                <?php endif; ?>

                <div class="header-section">
                    <h2>Library Collection</h2>
                </div>

                <div class="search-section">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search books by title, author, or ISBN...">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="filter-dropdown">
                        <button class="filter-btn">
                            <i class="fas fa-filter"></i>
                            Filter: <?php echo ucfirst(str_replace('_', ' ', $filter)); ?>
                        </button>
                        <div class="dropdown-content">
                            <a href="?filter=all" class="<?php echo $filter == 'all' ? 'active' : ''; ?>">All Status</a>
                            <a href="?filter=available"
                                class="<?php echo $filter == 'available' ? 'active' : ''; ?>">Available</a>
                            <a href="?filter=not_available"
                                class="<?php echo $filter == 'not_available' ? 'active' : ''; ?>">Not Available</a>
                        </div>
                    </div>
                    <button class="print-btn" id="printButton">
                        <i class="fas fa-print"></i>
                        Print
                    </button>
                </div>

                <div class="table-responsive">
                    <table id="booksTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Cover</th>
                                <th>Book Details</th>
                                <th>Genre</th>
                                <th>Serial No</th>
                                <th>ISBN</th>
                                <th>Edition</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0):
                                $i = $start_from + 1;
                                while ($book = $result->fetch_assoc()):
                                    // Determine the image source based on the database structure
                                    $image_src = '';
                                    if (isset($book['image'])) {
                                        // Check if the image is stored as BLOB or file path
                                        if (is_resource($book['image']) || (is_string($book['image']) && strlen($book['image']) > 255)) {
                                            // Handle BLOB data
                                            $image_data = base64_encode($book['image']);
                                            $image_src = 'data:image/jpeg;base64,' . $image_data;
                                        } else if (!empty($book['image'])) {
                                            // Handle file path
                                            $image_src = '../uploads/' . htmlspecialchars($book['image']);
                                        }
                                    }

                                    // Set default image if no image is available
                                    if (empty($image_src)) {
                                        $image_src = '../uploads/default.jpg';
                                    }
                            ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td>
                                            <?php if (!empty($image_src)): ?>
                                                <img class="book-cover" src="<?php echo $image_src; ?>" alt="Book Cover">
                                            <?php else: ?>
                                                <div class="book-cover"
                                                    style="background-color:var(--ultra-light-green);display:flex;justify-content:center;align-items:center;">
                                                    <i class="fas fa-book" style="color:var(--text-light);"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="book-title">
                                                <?php echo !empty($book['title']) ? htmlspecialchars($book['title']) : 'Untitled'; ?>
                                            </div>
                                            <div class="book-author">
                                                <?php echo !empty($book['author']) ? htmlspecialchars($book['author']) : 'Unknown'; ?>
                                            </div>
                                            <div class="book-details">
                                                <?php echo !empty($book['pages']) ? htmlspecialchars($book['pages']) . ' pages' : '--'; ?>
                                            </div>
                                        </td>
                                        <td><?php echo !empty($book['genre']) ? htmlspecialchars($book['genre']) : '--'; ?></td>
                                        <td><?php echo !empty($book['serialno']) ? htmlspecialchars($book['serialno']) : '--'; ?>
                                        </td>
                                        <td><?php echo !empty($book['ISBN']) ? htmlspecialchars($book['ISBN']) : '--'; ?></td>
                                        <td><?php echo !empty($book['edition']) ? htmlspecialchars($book['edition']) : '--'; ?>
                                        </td>
                                        <td>
                                            <span
                                                class="availability <?php echo $book['availability'] === 'Available' ? 'available' : 'not-available'; ?>">
                                                <?php echo !empty($book['availability']) ? $book['availability'] : '--'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <a href="edit_books_details.php?id=<?php echo $book['id']; ?>"
                                                    style="background-color: var(--accent-green); color: white; border: none; border-radius: 4px; padding: 5px; cursor: pointer; text-decoration: none;"
                                                    title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button
                                                    onclick="confirmDelete(<?php echo $book['id']; ?>, '<?php echo addslashes($book['title']); ?>')"
                                                    style="background-color: var(--error-red); color: white; border: none; border-radius: 4px; padding: 5px; cursor: pointer;"
                                                    title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 30px;">
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 10px;">
                                            <i class="fas fa-book-open"
                                                style="font-size: 3rem; color: var(--light-green);"></i>
                                            <p>No books found in the library collection.</p>
                                            <a href="add_books.php"
                                                style="text-decoration: none; color: var(--main-green);">Add
                                                your first book</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>


                <div class="pagination-container">
                    <div class="pagination-info">
                        Showing <?php echo $start_from + 1; ?> to
                        <?php echo min($start_from + $records_per_page, $total_records); ?> of
                        <?php echo $total_records; ?>
                        entries
                    </div>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?filter=<?php echo $filter; ?>&page=1" title="First Page"><i
                                    class="fas fa-angle-double-left"></i></a>
                            <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>"><i
                                    class="fas fa-angle-left"></i> Prev</a>
                        <?php else: ?>
                            <span class="disabled"><i class="fas fa-angle-double-left"></i></span>
                            <span class="disabled"><i class="fas fa-angle-left"></i> Prev</span>
                        <?php endif; ?>

                        <?php
                        // Calculate the range of page numbers to display
                        $range = 3;
                        $start_range = max(1, $page - $range);
                        $end_range = min($total_pages, $page + $range);

                        if ($start_range > 1) {
                            echo '<span class="disabled">...</span>';
                        }

                        for ($i = $start_range; $i <= $end_range; $i++): ?>
                            <a href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>"
                                class="<?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor;

                        if ($end_range < $total_pages) {
                            echo '<span class="disabled">...</span>';
                        }
                        ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>">Next <i
                                    class="fas fa-angle-right"></i></a>
                            <a href="?filter=<?php echo $filter; ?>&page=<?php echo $total_pages; ?>" title="Last Page"><i
                                    class="fas fa-angle-double-right"></i></a>
                        <?php else: ?>
                            <span class="disabled">Next <i class="fas fa-angle-right"></i></span>
                            <span class="disabled"><i class="fas fa-angle-double-right"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Confirm Delete</h3>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete "<span id="bookTitle"></span>"?</p>
                    <p>This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <a id="confirmDeleteBtn" href="#" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fixed navbar toggle JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Get references to key elements
            const navbarToggle = document.getElementById('navbarToggle');
            const secondaryNavbar = document.getElementById('secondaryNavbar');
            const contentWrapper = document.getElementById('contentWrapper');

            // Initialize the state
            let isNavbarOpen = false;

            // Toggle function
            navbarToggle.addEventListener('click', function() {
                // Toggle the state
                isNavbarOpen = !isNavbarOpen;

                // Toggle classes
                this.classList.toggle('active');
                secondaryNavbar.classList.toggle('active');
                contentWrapper.classList.toggle('secondary-active');
            });

            // Print button
            document.getElementById('printButton').addEventListener('click', function() {
                window.print();
            });

            // Live search functionality
            document.getElementById('searchInput').addEventListener('keyup', function() {
                const searchValue = this.value.toLowerCase();
                const tableRows = document.querySelectorAll('#booksTable tbody tr');

                tableRows.forEach(row => {
                    let found = false;
                    const cells = row.querySelectorAll('td:not(:first-child)');

                    cells.forEach(cell => {
                        if (cell.textContent.toLowerCase().includes(searchValue)) {
                            found = true;
                        }
                    });

                    if (found) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });

            // Delete confirmation modal
            window.confirmDelete = function(id, title) {
                document.getElementById('bookTitle').textContent = title;
                document.getElementById('confirmDeleteBtn').href = 'all_books.php?delete_id=' + id;
                document.getElementById('deleteModal').style.display = 'block';
            }

            window.closeModal = function() {
                document.getElementById('deleteModal').style.display = 'none';
            }

            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                const modal = document.getElementById('deleteModal');
                if (event.target == modal) {
                    closeModal();
                }
            });
        });
    </script>

    <?php include('../includes/footer.php'); ?>
</body>

</html>
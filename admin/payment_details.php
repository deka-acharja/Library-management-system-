<?php
// includes and DB connection
include('../includes/db.php');
include('../includes/dashboard_header.php');

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

// Add both counts together for total notifications
$total_notifications_count = $new_reservations_count + $new_employees_count;

// Pagination setup
$records_per_page = 50;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Count total records
$count_sql = "SELECT COUNT(*) as total FROM payments";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch payments with pagination
$sql = "SELECT * FROM payments ORDER BY payment_date DESC LIMIT $offset, $records_per_page";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html>

<head>
    <title>Payment Details</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
        }

        body {
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
            background-color: var(--ultra-light-green);
            padding: 0;
            padding-top: 315px; /* Increased to account for both navbars when expanded */
            color: var(--text-dark);
            margin: 0;
            transition: padding-top 0.3s ease; /* Smooth transition for body padding */
        }

        /* Main Content Container - Now with transition */
        .main-content {
            transition: margin-top 0.3s ease; /* Smooth transition */
            margin-top: 0;
        }

        /* When navbar is collapsed, reduce the top margin */
        .main-content.navbar-collapsed {
            margin-top: -60px; /* Pull content up when navbar is hidden */
        }

        /* Toast Container - Now positioned fixed and with z-index */
        .toast-container {
            position: fixed;
            top: 60px; /* Start position */
            right: 20px;
            z-index: 1005; /* Higher than navbar but lower than modal */
            max-width: 320px;
            transition: top 0.3s ease; /* Add transition for smooth movement */
        }

        /* Fixed Top Navbar - Always stays at the top */
        .navbar-toggle-container {
            position: fixed;
            top: 240px;
            left: 0;
            right: 0;
            background-color: var(--accent-green);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 50px;
            z-index: 1001;
        }

        .toggle-btn {
            background-color: var(--main-green);
            color: var(--white);
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
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

        .notification-icon {
            color: var(--white);
            font-size: 1.2rem;
            position: relative;
        }

        .notification-icon .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--error-red);
            color: var(--white);
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Fixed Secondary Navbar - Positioned below the toggle container */
        .secondary-navbar {
            position: fixed;
            top: 290px; /* Positioned right below the toggle container */
            left: 0;
            right: 0;
            background-color: var(--dark-green);
            height: 0;
            overflow: hidden;
            transition: height 0.3s ease;
            z-index: 1000;
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
            color: var(--white);
            text-decoration: none;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 15px;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .navbar-links a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .navbar-links a .notification-badge {
            background-color: var(--white);
            color: var(--main-green);
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 10px;
            position: relative;
            top: -8px;
            margin-left: 5px;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            background-color: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            padding: 25px;
            font-family:  Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--light-green);
            margin-bottom: 20px;
        }

        h2 {
            color: var(--text-dark);
            font-size: 28px;
            margin: 0;
            display: flex;
            align-items: center;
        }

        h2 i {
            color: var(--main-green);
            margin-right: 12px;
        }

        .search-box {
            position: relative;
            width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid var(--light-green);
            border-radius: 50px;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            border-color: var(--accent-green);
            box-shadow: 0 0 0 3px rgba(46, 106, 51, 0.2);
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 12px;
            color: var(--text-light);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 20px 0;
        }

        th,
        td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid var(--light-green);
        }

        th {
            background-color: var(--ultra-light-green);
            color: var(--text-medium);
            font-weight: 600;
            position: sticky;
            top: 0;
        }

        tr:hover {
            background-color: var(--ultra-light-green);
        }

        .amount {
            font-weight: 600;
            color: var(--success-green);
        }

        .overdue {
            font-weight: 600;
            color: var(--error-red);
        }

        .no-data {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 0;
            color: var(--text-light);
        }

        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            color: var(--light-green);
        }

        img.screenshot {
            width: 50px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid var(--light-green);
            transition: transform 0.2s ease;
            cursor: pointer;
        }

        img.screenshot:hover {
            transform: scale(1.15);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .badge-primary {
            background-color: var(--light-green);
            color: var(--main-green);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination a {
            padding: 8px 14px;
            margin: 0 5px;
            border-radius: 4px;
            border: 1px solid var(--light-green);
            color: var(--main-green);
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .pagination a:hover,
        .pagination a.active {
            background-color: var(--main-green);
            color: var(--white);
            border-color: var(--main-green);
        }

        .pagination a.disabled {
            color: var(--text-light);
            pointer-events: none;
            border-color: var(--light-green);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1010; /* Above toast */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: var(--white);
            padding: 20px;
            border-radius: 10px;
            max-width: 18%;
            max-height: 92%;
            overflow: auto;
            position: relative;
            margin-top: 240px;
        }

        .close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            color: var(--text-dark);
            cursor: pointer;
        }

        .modal-img {
            max-width: 100%;
            height: auto;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content.navbar-collapsed {
                margin-top: -50px; /* Smaller adjustment for mobile */
            }
            
            .container {
                margin: 20px;
                padding: 15px;
            }
            
            .navbar-links {
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .search-box {
                width: 100%;
                max-width: 250px;
            }
        }
          /* Filter Dropdown Styles */
        .filter-dropdown-container {
            position: relative;
            display: inline-block;
        }

        .filter-dropdown-btn {
            background: var(--primary-color,rgb(9, 94, 30));
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .filter-dropdown-btn:hover {
            background: var(--primary-dark,rgb(4, 85, 4));
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .filter-dropdown-btn i.dropdown-arrow {
            transition: transform 0.3s ease;
        }

        .filter-dropdown-btn.active i.dropdown-arrow {
            transform: rotate(180deg);
        }

        .filter-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--card-background, #fff);
            border: 1px solid var(--border-color, #e0e0e0);
            border-radius: 8px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            min-width: 250px;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            margin-top: 5px;
        }

        .filter-dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .filter-dropdown-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color, #e0e0e0);
            background: var(--primary-light,rgb(74, 74, 75));
            border-radius: 8px 8px 0 0;
        }

        .filter-dropdown-header h4 {
            margin: 0;
            font-size: 16px;
            color: var(--primary-color,rgb(0, 255, 55));
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-dropdown-options {
            padding: 10px 0;
        }

        .filter-dropdown-option {
            display: block;
            padding: 12px 20px;
            text-decoration: none;
            color: var(--text-color, #333);
            transition: all 0.3s ease;
            position: relative;
        }

        .filter-dropdown-option:hover {
            background: var(--hover-bg, #f8f9fa);
            color: var(--primary-color,rgb(0, 255, 98));
            padding-left: 25px;
        }

        .filter-dropdown-option.active {
            background: var(--primary-light, #e3f2fd);
            color: var(--primary-color,rgb(0, 255, 85));
            font-weight: 600;
            border-left: 4px solid var(--primary-color,rgb(0, 255, 85));
        }

        .filter-dropdown-option i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Header section styling */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Search box styling */
        .search-box {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-box i {
            position: absolute;
            left: 12px;
            color: var(--text-light, #666);
        }

        .search-box input {
            padding: 10px 10px 10px 40px;
            border: 1px solid var(--border-color, #ddd);
            border-radius: 6px;
            width: 250px;
            font-size: 14px;
        }

        /* Main content adjustments */
        .main-content {
            margin-left: 0;
            transition: margin-left 0.3s ease;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .header-left,
            .header-right {
                justify-content: space-between;
            }
            
            .filter-dropdown-menu {
                right: 0;
                left: auto;
                min-width: 200px;
            }
            
            .search-box input {
                width: 200px;
            }
        }

        @media (max-width: 480px) {
            .header-left,
            .header-right {
                flex-direction: column;
                gap: 10px;
            }
            
            .search-box input {
                width: 100%;
            }
            
            .filter-dropdown-menu {
                min-width: 180px;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .filter-dropdown-menu {
                background: var(--card-background-dark, #2d3748);
                border-color: var(--border-color-dark, #4a5568);
            }
            
            .filter-dropdown-header {
                background: var(--primary-dark, #1a365d);
            }
            
            .filter-dropdown-option {
                color: var(--text-color-dark, #e2e8f0);
            }
            
            .filter-dropdown-option:hover {
                background: var(--hover-bg-dark, #4a5568);
            }
        }
        /* Close dropdown when clicking outside */
        .dropdown-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 999;
            display: none;
        }

        .dropdown-overlay.show {
            display: block;
        }
    </style>
</head>

<body>
    <!-- Toast Container for Notifications -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Fixed Navbar Toggle Button and Notification Icon -->
    <div class="navbar-toggle-container">
        <!-- Toggle Button (Left) -->
        <button class="toggle-btn" id="navbarToggle" title="Toggle Menu">
            <i class="fas fa-chevron-down"></i>
        </button>

        <!-- Notification Icon (Right) -->
        <a href="notifiactions.php" class="notification-icon" title="Notifications">
            <i class="fas fa-bell"></i>
            <?php if ($total_notifications_count > 0): ?>
                <span class="badge"><?php echo $total_notifications_count; ?></span>
            <?php endif; ?>
        </a>
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
    <!-- Dropdown Overlay -->
    <div class="dropdown-overlay" id="dropdownOverlay"></div>

    <!-- Main Content -->
    <div class="main-content navbar-collapsed" id="mainContent">
        <div class="container">
            <div class="header">
                <div class="header-left">
                    <h2><i class="fas fa-money-check-alt"></i> Reservation Payment Details</h2>
                </div>
                <div class="header-right">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search payments...">
                    </div>
                    
                    <!-- Filter Dropdown -->
                    <div class="filter-dropdown-container">
                        <button class="filter-dropdown-btn" id="filterDropdownBtn">
                            <i class="fas fa-filter"></i>
                            Payment Filters
                            <i class="fas fa-chevron-down dropdown-arrow"></i>
                        </button>
                        <div class="filter-dropdown-menu" id="filterDropdownMenu">
                            <div class="filter-dropdown-header">
                                <h4>
                                    <i class="fas fa-filter"></i>
                                    Filter Options
                                </h4>
                            </div>
                            <div class="filter-dropdown-options">
                                <a href="payment_details.php" class="filter-dropdown-option active">
                                    <i class="fas fa-receipt"></i>
                                    Reservation Payments
                                </a>
                                <a href="borrower_payment.php" class="filter-dropdown-option">
                                    <i class="fas fa-user-check"></i>
                                    Borrower Payments
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($result && $result->num_rows > 0): ?>
                <div style="overflow-x: auto;">
                    <table id="paymentsTable">
                        <thead>
                            <tr>
                                <th>Reservation ID</th>
                                <th>Name</th>
                                <th>Title</th>
                                <th>CID</th>
                                <th>Days Overdue</th>
                                <th>Amount</th>
                                <th>Payment Date</th>
                                <th>Screenshot</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="badge-primary"><?= htmlspecialchars($row['reservation_id']) ?></span></td>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= htmlspecialchars($row['cid']) ?></td>
                                    <td class="<?= $row['days_overdue'] > 0 ? 'overdue' : '' ?>">
                                        <?= htmlspecialchars($row['days_overdue']) ?>
                                        <?= $row['days_overdue'] > 0 ? '<i class="fas fa-exclamation-circle"></i>' : '' ?>
                                    </td>
                                    <td class="amount"><?= htmlspecialchars($row['amount']) ?> Nu</td>
                                    <td><?= date('M d, Y', strtotime($row['payment_date'])) ?></td>
                                    <td>
                                        <?php
                                        // Handle image display
                                        if (isset($row['payment_screenshot']) && !empty($row['payment_screenshot'])) {
                                            // For BLOB data, create a data URI
                                            $image_data = base64_encode($row['payment_screenshot']);
                                            $image_src = 'data:image/jpeg;base64,' . $image_data;
                                            ?>
                                            <img src="<?= $image_src ?>"
                                                alt="Payment Receipt" class="screenshot"
                                                onclick="openModal('<?= $image_src ?>')"
                                                style="width: 50px; height: 50px; object-fit: cover; cursor: pointer; border-radius: 4px;">
                                        <?php } else { ?>
                                            <span style="color:var(--text-light);">No image</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Section -->
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>">&laquo;</a>
                    <?php else: ?>
                        <a href="#" class="disabled">&laquo;</a>
                    <?php endif; ?>

                    <?php
                    // Determine range of pages to show
                    $max_pages_to_show = 5;
                    $start_page = max(1, min($page - floor($max_pages_to_show / 2), $total_pages - $max_pages_to_show + 1));
                    $end_page = min($total_pages, $start_page + $max_pages_to_show - 1);

                    // Always show first page
                    if ($start_page > 1): ?>
                        <a href="?page=1">1</a>
                        <?php if ($start_page > 2): ?>
                            <a href="#" class="disabled">...</a>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?page=<?= $i ?>" <?= $i == $page ? 'class="active"' : '' ?>><?= $i ?></a>
                    <?php endfor; ?>

                    <?php
                    // Always show last page
                    if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <a href="#" class="disabled">...</a>
                        <?php endif; ?>
                        <a href="?page=<?= $total_pages ?>"><?= $total_pages ?></a>
                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>">&raquo;</a>
                    <?php else: ?>
                        <a href="#" class="disabled">&raquo;</a>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-receipt"></i>
                    <p>No payment records found in the system.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <img id="modalImage" class="modal-img" src="" alt="Payment Screenshot">
        </div>
    </div>

    <script>
        // Filter dropdown functionality
        const filterDropdownBtn = document.getElementById('filterDropdownBtn');
        const filterDropdownMenu = document.getElementById('filterDropdownMenu');
        const dropdownOverlay = document.getElementById('dropdownOverlay');

        // Toggle dropdown
        filterDropdownBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleDropdown();
        });

        // Close dropdown when clicking overlay
        dropdownOverlay.addEventListener('click', function() {
            closeDropdown();
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!filterDropdownBtn.contains(e.target) && !filterDropdownMenu.contains(e.target)) {
                closeDropdown();
            }
        });

        function toggleDropdown() {
            const isOpen = filterDropdownMenu.classList.contains('show');
            if (isOpen) {
                closeDropdown();
            } else {
                openDropdown();
            }
        }

        function openDropdown() {
            filterDropdownMenu.classList.add('show');
            filterDropdownBtn.classList.add('active');
            dropdownOverlay.classList.add('show');
        }

        function closeDropdown() {
            filterDropdownMenu.classList.remove('show');
            filterDropdownBtn.classList.remove('active');
            dropdownOverlay.classList.remove('show');
        }

        // Close dropdown when pressing Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDropdown();
            }
        });

        // Live search filter
        document.getElementById('searchInput').addEventListener('keyup', function () {
            const filter = this.value.toLowerCase().trim();
            const table = document.getElementById('paymentsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                // Get all searchable fields in this row
                const reservation_id = rows[i].cells[0].textContent.toLowerCase();
                const name = rows[i].cells[1].textContent.toLowerCase();
                const title = rows[i].cells[2].textContent.toLowerCase();
                const cid = rows[i].cells[3].textContent.toLowerCase();
                const days_overdue = rows[i].cells[4].textContent.toLowerCase();
                const amount = rows[i].cells[5].textContent.toLowerCase();
                const paymentDate = rows[i].cells[6].textContent.toLowerCase();

                // Check if any field contains the search term
                if (reservation_id.includes(filter) ||
                    name.includes(filter) ||
                    title.includes(filter) ||
                    cid.includes(filter) ||
                    days_overdue.includes(filter) ||
                    amount.includes(filter) ||
                    paymentDate.includes(filter)) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        });

        // Image modal logic
        function openModal(src) {
            document.getElementById('modalImage').src = src;
            document.getElementById('imageModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        window.onclick = function (event) {
            if (event.target.id === 'imageModal') {
                closeModal();
            }
        };

        // Enhanced Navbar toggle functionality with smooth container adjustment
        document.getElementById('navbarToggle').addEventListener('click', function () {
            // Toggle the active class on the button and change icon
            this.classList.toggle('active');

            // Toggle the secondary navbar
            const secondaryNavbar = document.getElementById('secondaryNavbar');
            const mainContent = document.getElementById('mainContent');
            const toastContainer = document.getElementById('toastContainer');

            secondaryNavbar.classList.toggle('active');

            if (secondaryNavbar.classList.contains('active')) {
                // Navbar is now visible - expand content positioning
                mainContent.classList.remove('navbar-collapsed');
                toastContainer.style.top = '120px'; // Move toast container down
            } else {
                // Navbar is now hidden - collapse content positioning
                mainContent.classList.add('navbar-collapsed');
                toastContainer.style.top = '60px'; // Return toast container to original position
            }
        });

        // Initialize page with navbar collapsed state
        document.addEventListener('DOMContentLoaded', function() {
            const mainContent = document.getElementById('mainContent');
            const secondaryNavbar = document.getElementById('secondaryNavbar');
            
            // Ensure proper initial state
            if (!secondaryNavbar.classList.contains('active')) {
                mainContent.classList.add('navbar-collapsed');
            }
        });
    </script>

    <?php include('../includes/footer.php'); ?>
</body>

</html>
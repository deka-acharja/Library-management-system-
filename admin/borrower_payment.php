<?php
include('../includes/db.php');
include('../includes/dashboard_header.php');

// Pagination setup
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Get total number of records
$total_sql = "SELECT COUNT(*) AS total FROM payment_borrowers";
$total_result = $conn->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total_pages = ceil($total_row['total'] / $records_per_page);

// Fetch payment records with user details (without verified or remarks)
$sql = "
    SELECT 
        pb.fine_amount,
        pb.payment_date,
        pb.screenshot,
        u.name AS user_name,
        u.username AS user_cid
    FROM payment_borrowers pb
    JOIN borrow_books bb ON pb.borrow_id = bb.borrow_id
    JOIN users u ON bb.cid = u.username
    ORDER BY pb.payment_date DESC
    LIMIT ?, ?
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL prepare error: " . $conn->error);
}
$stmt->bind_param("ii", $offset, $records_per_page);
$stmt->execute();
$result = $stmt->get_result();

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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fine Payments - Modern Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
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
            --shadow-light: 0 2px 10px rgba(46, 106, 51, 0.1);
            --shadow-medium: 0 4px 20px rgba(46, 106, 51, 0.15);
            --shadow-heavy: 0 8px 30px rgba(46, 106, 51, 0.2);
            --border-radius: 12px;
            --border-radius-lg: 16px;
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
            padding-top: 240px;
            /* Space for fixed navbar */
        }

        /* Toast Container - Now positioned fixed and with z-index */
        .toast-container {
            position: fixed;
            top: 60px;
            /* Start position */
            right: 20px;
            z-index: 1005;
            /* Higher than navbar but lower than modal */
            max-width: 320px;
            transition: top 0.3s ease;
            /* Add transition for smooth movement */
        }

        /* Fixed Top Navbar */
        .navbar-toggle-container {
            position: fixed;
            top: 240;
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

        /* Main Content */
        .main-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 2rem;
        }

        .page-header {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-light);
            margin-bottom: 2rem;
            border-left: 5px solid var(--main-green);
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .page-title h2 {
            color: var(--main-green);
            font-size: 2rem;
            font-weight: 700;
        }

        .page-title .icon {
            background: linear-gradient(135deg, var(--main-green), var(--accent-green));
            color: var(--white);
            padding: 1rem;
            border-radius: var(--border-radius);
            font-size: 1.5rem;
        }

        .page-subtitle {
            color: var(--text-medium);
            font-size: 1.1rem;
        }

        /* Filter Section */
        .filter-section {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .filter-dropdown {
            position: relative;
        }

        .filter-btn {
            background: linear-gradient(135deg, var(--accent-green), var(--main-green));
            color: var(--white);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            background: linear-gradient(135deg, var(--main-green), var(--dark-green));
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .filter-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-heavy);
            z-index: 100;
            min-width: 250px;
            margin-top: 0.5rem;
            overflow: hidden;
            transform: translateY(-10px);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .filter-menu.active {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
        }

        .filter-option {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            color: var(--text-dark);
            text-decoration: none;
            transition: all 0.3s ease;
            border-bottom: 1px solid var(--ultra-light-green);
        }

        .filter-option:hover {
            background: var(--ultra-light-green);
            color: var(--main-green);
        }

        .filter-option.active {
            background: var(--light-green);
            color: var(--main-green);
            font-weight: 600;
        }

        /* Table Styles */
        .table-container {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-light);
            overflow: hidden;
        }

        .table-header {
            background: linear-gradient(135deg, var(--light-green) 0%, #d4e6d6 100%);
            padding: 1.5rem 2rem;
            border-bottom: 2px solid var(--accent-green);
        }

        .table-header h3 {
            color: var(--main-green);
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .data-table th {
            background: var(--ultra-light-green);
            color: var(--main-green);
            font-weight: 600;
            padding: 1.25rem 1.5rem;
            text-align: left;
            font-size: 0.95rem;
            border-bottom: 2px solid var(--light-green);
        }

        .data-table td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f0f4f1;
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        .data-table tr:hover {
            background: var(--ultra-light-green);
        }

        .amount-cell {
            font-weight: 700;
            color: var(--error-red);
            font-size: 1.1rem;
        }

        .screenshot-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid var(--light-green);
        }

        .screenshot-img:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow-medium);
        }

        .no-image {
            color: var(--text-light);
            font-style: italic;
            padding: 0.5rem 1rem;
            background: var(--ultra-light-green);
            border-radius: var(--border-radius);
            font-size: 0.9rem;
        }

        /* Modern Pagination */
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            padding: 1.5rem;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
        }

        .pagination-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            border: 2px solid var(--light-green);
            background: var(--white);
            color: var(--main-green);
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .pagination-btn:hover {
            background: var(--accent-green);
            color: var(--white);
            border-color: var(--accent-green);
            transform: translateY(-2px);
        }

        .pagination-btn.current {
            background: var(--main-green);
            color: var(--white);
            border-color: var(--main-green);
        }

        .pagination-btn.nav {
            width: auto;
            padding: 0 1rem;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            display: block;
            margin: 5% auto;
            max-width: 90%;
            max-height: 85%;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-heavy);
        }

        .modal-close {
            position: absolute;
            top: 30px;
            right: 50px;
            color: var(--white);
            font-size: 2.5rem;
            font-weight: 300;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .search-input {
                width: 250px;
            }

            .main-container {
                padding: 1rem;
            }

            .page-header {
                padding: 1.5rem;
            }

            .page-title h2 {
                font-size: 1.5rem;
            }

            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }

            .data-table {
                font-size: 0.9rem;
            }

            .data-table th,
            .data-table td {
                padding: 1rem;
            }

            .nav-container {
                justify-content: flex-start;
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
    <div class="main-container">

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-dropdown">
                <button class="filter-btn" id="filterBtn">
                    <i class="fas fa-filter"></i>
                    Payment Filters
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="filter-menu" id="filterMenu">
                    <a href="payment_details.php" class="filter-option">
                        <i class="fas fa-receipt"></i>
                        Reservation Payments
                    </a>
                    <a href="borrower_payment.php" class="filter-option active">
                        <i class="fas fa-user-check"></i>
                        Borrower Payments
                    </a>
                </div>
            </div>

            <div style="color: var(--text-medium); font-weight: 500;">
                <i class="fas fa-info-circle"></i>
                Showing <?php echo min($offset + 1, $total_row['total']); ?>-<?php echo min($offset + $records_per_page, $total_row['total']); ?> of <?php echo $total_row['total']; ?> records
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container">
            <div class="table-header">
                <h3>
                    <i class="fas fa-table"></i>
                    Payment Records
                </h3>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-user"></i> Name</th>
                        <th><i class="fas fa-id-card"></i> CID</th>
                        <th><i class="fas fa-money-bill-wave"></i> Fine Amount</th>
                        <th><i class="fas fa-calendar-alt"></i> Payment Date</th>
                        <th><i class="fas fa-image"></i> Screenshot</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 35px; height: 35px; background: var(--light-green); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--main-green); font-weight: 600;">
                                        <?php echo strtoupper(substr($row['user_name'], 0, 1)); ?>
                                    </div>
                                    <?php echo htmlspecialchars($row['user_name']); ?>
                                </div>
                            </td>
                            <td>
                                <span style="background: var(--ultra-light-green); padding: 0.25rem 0.75rem; border-radius: 20px; color: var(--main-green); font-weight: 600; font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($row['user_cid']); ?>
                                </span>
                            </td>
                            <td class="amount-cell">
                                <?php echo htmlspecialchars($row['fine_amount']); ?> Nu
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 600;"><?php echo date('M d, Y', strtotime($row['payment_date'])); ?></span>
                                    <span style="font-size: 0.85rem; color: var(--text-light);"><?php echo date('H:i', strtotime($row['payment_date'])); ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($row['screenshot'])): ?>
                                    <img src="data:image/jpeg;base64,<?php echo base64_encode($row['screenshot']); ?>"
                                        class="screenshot-img"
                                        onclick="openModal(this.src)"
                                        alt="Payment Screenshot"
                                        title="Click to view full size">
                                <?php else: ?>
                                    <span class="no-image">
                                        <i class="fas fa-image"></i> No image
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination-container">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="pagination-btn nav">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>

            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);

            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
                <?php if ($i == $page): ?>
                    <span class="pagination-btn current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>" class="pagination-btn"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="pagination-btn nav">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal for Screenshot Preview -->
    <div id="modal" class="modal" onclick="closeModal()">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <img id="modal-content" class="modal-content" alt="Screenshot Preview">
    </div>

    <?php include('../includes/footer.php'); ?>

    <script>
        // Filter dropdown functionality
        document.getElementById('filterBtn').addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = document.getElementById('filterMenu');
            menu.classList.toggle('active');
        });

        // Close filter dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const filterBtn = document.getElementById('filterBtn');
            const filterMenu = document.getElementById('filterMenu');
            if (!filterBtn.contains(e.target) && !filterMenu.contains(e.target)) {
                filterMenu.classList.remove('active');
            }
        });

        // Modal functionality
        function openModal(src) {
            const modal = document.getElementById('modal');
            const modalContent = document.getElementById('modal-content');
            modal.style.display = 'block';
            modalContent.src = src;
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modal = document.getElementById('modal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.data-table tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Add loading state to buttons
        document.querySelectorAll('.pagination-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!this.classList.contains('current')) {
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                }
            });
        });

        // Enhanced Navbar toggle functionality with smooth container adjustment
        document.getElementById('navbarToggle').addEventListener('click', function() {
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
    </script>
</body>

</html>
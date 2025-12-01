<?php
// includes and DB connection
include('../includes/db.php');
include('../includes/dashboard_header.php');

// Get count of new reservations - CORRECTED LINE HERE
$new_reservations_query = "SELECT COUNT(*) as count FROM reservations WHERE status = 'reserved' AND is_viewed = 0";
$new_reservations_result = $conn->query($new_reservations_query);
$new_reservations_count = 0;
if ($new_reservations_result && $row = $new_reservations_result->fetch_assoc()) {
    $new_reservations_count = $row['count'];
}

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

// Check for unread notifications
$notification_query = "SELECT COUNT(*) AS count FROM notifications WHERE status = 'unread'";
$notification_result = $conn->query($notification_query);
$notification_count = $notification_result && $notification_result->num_rows ? $notification_result->fetch_assoc()['count'] : 0;

?>

<!DOCTYPE html>
<html>

<head>
    <title>Payment Details</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="employee.css">
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family:Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
            background-color: #f5f8f5;
            color: var(--text-dark);
            padding-top: 295px;
            /* Space for fixed navbar */
        }

        /* Fixed Top Navbar */
        .navbar-toggle-container {
            position: fixed;
            top: 245px;
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

        /* Main content wrapper */
        .content-wrapper {
            transition: margin-top 0.3s ease;
            margin-top: 20px;

        }

        .content-wrapper.navbar-active {
            margin-top: 80px;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            background-color: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            padding: 25px;
            font-family:Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
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
            z-index: 1000;
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

    <!-- Content Wrapper -->
    <div class="content-wrapper" id="contentWrapper">
        <div class="container">
            <div class="header">
                <h2><i class="fas fa-money-check-alt"></i> Payment Details</h2>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search payments...">
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
                                        <?php if (isset($row['payment_screenshot']) && !empty($row['payment_screenshot'])): ?>
                                            <?php
                                            // For BLOB data, create a data URI
                                            $image_data = base64_encode($row['payment_screenshot']);
                                            $image_src = 'data:image/jpeg;base64,' . $image_data;
                                            ?>
                                            <img src="<?= $image_src ?>"
                                                alt="Receipt" class="screenshot"
                                                onclick="openModal('<?= htmlspecialchars($image_src, ENT_QUOTES) ?>')">
                                        <?php else: ?>
                                            <span style="color:var(--text-light);">No image</span>
                                        <?php endif; ?>
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

        // Updated image modal logic
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

        // Secondary navbar toggle
        document.getElementById('navbarToggle').addEventListener('click', function () {
            this.classList.toggle('active');
            document.getElementById('secondaryNavbar').classList.toggle('active');
            document.getElementById('contentWrapper').classList.toggle('navbar-active');
        });

        // Check if secondary navbar is active on page load
        window.addEventListener('DOMContentLoaded', function () {
            // Add any initialization here if needed
        });
    </script>

    <?php include('../includes/footer.php'); ?>
</body>

</html>
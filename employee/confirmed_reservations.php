<?php
session_start();
include('../includes/db.php');
include('../includes/dashboard_header.php');

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fetch confirmed reservations
$query = "SELECT r.reservation_id, r.book_id, r.cid, r.reservation_date,
                 b.title AS book_title, r.name AS user_name, r.email AS user_email, r.phone AS user_phone
          FROM reservations r
          JOIN books b ON r.book_id = b.id
          WHERE TRIM(r.status) = 'confirmed'
          ORDER BY r.reservation_date DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}

// Check for unread notifications
$notification_query = "SELECT COUNT(*) AS count FROM notifications WHERE status = 'unread'";
$notification_result = $conn->query($notification_query);
$notification_count = 0;
if ($notification_result && $row = $notification_result->fetch_assoc()) {
    $notification_count = $row['count'];
}
// Get count of new reservations - CORRECTED LINE HERE
$new_reservations_query = "SELECT COUNT(*) as count FROM reservations WHERE status = 'reserved' AND is_viewed = 0";
$new_reservations_result = $conn->query($new_reservations_query);
$new_reservations_count = 0;
if ($new_reservations_result && $row = $new_reservations_result->fetch_assoc()) {
    $new_reservations_count = $row['count'];
}

// Debug session messages
if (isset($_SESSION['message'])) {
    echo "<!-- DEBUG: Session message exists: " . $_SESSION['message'] . " -->";
    echo "<!-- DEBUG: Session message type: " . (isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'not set') . " -->";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Confirmed Reservations</title>
    <link rel="stylesheet" href="employee.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
        /* Main Color Palette */
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
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
            background-color: #f5f8f5;
            color: var(--text-dark);
            padding-top: 245px; /* Base padding for fixed navbar + secondary navbar closed state */
            transition: padding-top 0.3s ease; /* Smooth transition when navbar opens/closes */
        }

        /* Body adjustment when secondary navbar is active */
        body.secondary-navbar-active {
            padding-top: 365px; /* Additional space when secondary navbar is open (305px + 60px) */
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
            transition: color 0.3s ease;
        }

        .notification-icon:hover {
            color: var(--light-green);
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
            top: 285px; /* Position right below the main navbar */
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
            flex-wrap: wrap;
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
            white-space: nowrap;
        }

        .navbar-links a i {
            margin-right: 6px;
        }

        .navbar-links a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }

        /* Notification badge for Reserved link */
        .notification-badge {
            background-color: var(--error-red);
            color: white;
            font-size: 10px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
        }

        /* Main Container */
        .container {
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background: var(--ultra-light-green);
            border-radius: 12px;
            box-shadow: 0 0 8px rgba(46, 106, 51, 0.1);
            transition: all 0.3s ease;
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px 15px;
            text-align: left;
            border: 1px solid var(--light-green);
        }

        th {
            background-color: var(--main-green);
            color: var(--white);
            font-weight: 600;
            position: relative;
        }

        /* Alert Styles */
        .alert-success {
            padding: 12px 15px;
            margin-bottom: 20px;
            background-color: rgba(46, 204, 113, 0.2);
            color: var(--success-green);
            border-radius: 6px;
            border-left: 4px solid var(--success-green);
        }

        .alert-error {
            padding: 12px 15px;
            margin-bottom: 20px;
            background-color: rgba(214, 69, 65, 0.2);
            color: var(--error-red);
            border-radius: 6px;
            border-left: 4px solid var(--error-red);
        }

        /* Button Styles */
        .btn-taken {
            padding: 8px 16px;
            background-color: var(--warning-yellow);
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
            text-align: center;
            cursor: pointer;
        }

        .btn-taken:hover {
            background-color: #e67e22;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        /* Heading Styles */
        h2 {
            color: var(--main-green);
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-size: 28px;
            font-weight: 600;
        }

        /* Table Row Styles */
        tbody tr:nth-child(even) {
            background-color: rgba(201, 220, 203, 0.3);
        }

        tbody tr:hover {
            background-color: var(--light-green);
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        /* No data message */
        .no-data {
            text-align: center;
            font-style: italic;
            color: var(--text-medium);
            padding: 40px 20px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal.show {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 450px;
            width: 90%;
            animation: slideIn 0.3s ease;
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: var(--main-green);
            font-size: 24px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .modal-header i {
            color: var(--warning-yellow);
            font-size: 28px;
        }

        .modal-body p {
            color: var(--text-dark);
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 25px;
        }

        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn-confirm {
            background-color: var(--main-green);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-confirm:hover {
            background-color: var(--dark-green);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-cancel {
            background-color: #95a5a6;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background-color: #7f8c8d;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { 
                transform: translateY(-50px);
                opacity: 0;
            }
            to { 
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar-links {
                gap: 10px;
                padding: 0 10px;
            }

            .navbar-links a {
                font-size: 13px;
                padding: 6px 10px;
            }

            .container {
                margin: 20px 10px;
                padding: 15px;
            }

            table {
                font-size: 14px;
            }

            th, td {
                padding: 8px 10px;
            }

            h2 {
                font-size: 24px;
            }

            body {
                padding-top: 280px;
            }

            body.secondary-navbar-active {
                padding-top: 340px;
            }

            .modal-content {
                padding: 20px;
                margin: 20px;
            }

            .modal-buttons {
                flex-direction: column;
            }

            .btn-confirm, .btn-cancel {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .navbar-links {
                gap: 5px;
            }

            .navbar-links a {
                font-size: 12px;
                padding: 5px 8px;
            }

            .navbar-links a i {
                margin-right: 3px;
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
            <a href="add_employee_data.php"><i class="fas fa-user-plus"></i> Add Details</a>
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

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirm Action</h3>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to mark this book reservation as taken?</p>
                <p><strong>This action cannot be undone.</strong></p>
            </div>
            <div class="modal-buttons">
                <a href="#" id="confirmBtn" class="btn-confirm">
                    <i class="fas fa-check"></i> Yes, Mark as Taken
                </a>
                <button type="button" id="cancelBtn" class="btn-cancel">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="container">
        <h2>Confirmed Book Reservations</h2>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info'; ?>">
                <?php
                echo htmlspecialchars($_SESSION['message']);
                unset($_SESSION['message'], $_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Book Title</th>
                    <th>User Name</th>
                    <th>User Email</th>
                    <th>Reserved At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['book_title']); ?></td>
                            <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['user_email']); ?></td>
                            <td><?php echo htmlspecialchars($row['reservation_date']); ?></td>
                            <td>
                                <button class="btn-taken" onclick="showConfirmationModal('<?php echo urlencode($row['reservation_id']); ?>')">
                                    Mark as Taken
                                </button>
                            </td>
                        </tr>
                    <?php endwhile;
                } else { ?>
                    <tr>
                        <td colspan="5" class="no-data">No confirmed reservations found</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <?php 
    // Check if footer file exists before including
    if (file_exists('../includes/footer.php')) {
        include('../includes/footer.php');
    } else {
        echo "<!-- DEBUG: Footer file not found -->";
    }
    ?>
    
    <script>
        // Navbar toggle functionality
        document.getElementById('navbarToggle').addEventListener('click', function () {
            this.classList.toggle('active');
            const secondaryNavbar = document.getElementById('secondaryNavbar');
            secondaryNavbar.classList.toggle('active');
            document.body.classList.toggle('secondary-navbar-active');
        });

        document.addEventListener('click', function(event) {
            const navbarToggle = document.getElementById('navbarToggle');
            const secondaryNavbar = document.getElementById('secondaryNavbar');
            const navbarContainer = document.querySelector('.navbar-toggle-container');
            
            if (!navbarContainer.contains(event.target) && !secondaryNavbar.contains(event.target)) {
                if (secondaryNavbar.classList.contains('active')) {
                    navbarToggle.classList.remove('active');
                    secondaryNavbar.classList.remove('active');
                    document.body.classList.remove('secondary-navbar-active');
                }
            }
        });

        window.addEventListener('resize', function() {
            const secondaryNavbar = document.getElementById('secondaryNavbar');
            if (secondaryNavbar.classList.contains('active')) {
                document.body.classList.add('secondary-navbar-active');
            }
        });

        // Modal functionality
        function showConfirmationModal(reservationId) {
            const modal = document.getElementById('confirmationModal');
            const confirmBtn = document.getElementById('confirmBtn');
            
            // Set the href for the confirm button
            confirmBtn.href = 'mark_as_taken.php?reservation_id=' + reservationId;
            
            // Show the modal
            modal.classList.add('show');
            
            // Prevent body scrolling when modal is open
            document.body.style.overflow = 'hidden';
        }

        function hideConfirmationModal() {
            const modal = document.getElementById('confirmationModal');
            modal.classList.remove('show');
            
            // Restore body scrolling
            document.body.style.overflow = 'auto';
        }

        // Cancel button event listener
        document.getElementById('cancelBtn').addEventListener('click', hideConfirmationModal);

        // Close modal when clicking outside
        document.getElementById('confirmationModal').addEventListener('click', function(event) {
            if (event.target === this) {
                hideConfirmationModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                hideConfirmationModal();
            }
        });
    </script>
</body>
</html>
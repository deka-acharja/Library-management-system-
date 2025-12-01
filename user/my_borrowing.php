<?php
include('../includes/db.php');
include('../includes/dashboard_header.php');

$search_cid = "";
$reservations = [];
$search_performed = false;
$overdue_records = [];
$has_overdue = false;

// Handle search
if (isset($_POST['search']) && !empty($_POST['cid'])) {
    $search_cid = $_POST['cid'];
    $search_performed = true;

    // Prepare and execute query
    $stmt = $conn->prepare("SELECT * FROM reservations WHERE cid = ?");
    $stmt->bind_param("s", $search_cid);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    $stmt->close();

    // Check for overdue records for this CID
    $overdue_stmt = $conn->prepare("SELECT * FROM reservations WHERE cid = ? AND (status = 'due' OR status = 'overdue')");
    $overdue_stmt->bind_param("s", $search_cid);
    $overdue_stmt->execute();
    $overdue_result = $overdue_stmt->get_result();

    while ($overdue_row = $overdue_result->fetch_assoc()) {
        $overdue_records[] = $overdue_row;
    }
    $overdue_stmt->close();

    $has_overdue = !empty($overdue_records);
}

// Get count of new reservations
$new_reservations_query = "SELECT COUNT(*) as count FROM reservations WHERE status IN ('confirmed', 'rejected', 'terminated', 'due') AND is_viewed = 0";
$new_reservations_result = $conn->query($new_reservations_query);
$new_reservations_count = 0;
if ($new_reservations_result && $row = $new_reservations_result->fetch_assoc()) {
    $new_reservations_count = $row['count'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Borrowing</title>
    <style>
        /* Main Color Palette */
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
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0px;
            background: linear-gradient(135deg, var(--ultra-light-green) 0%, var(--light-green) 100%);
            min-height: 100vh;
            color: var(--text-dark);
            padding-top: 250px;
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
            top: 295px;
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

        /* Dashboard Container Adjustments */
        .dashboard-container {
            max-width: 1200px;
            margin: 40px auto;
            background: var(--white);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(46, 106, 51, 0.1);
            border: 1px solid var(--light-green);
            margin-top: 50px;
            transition: margin-top 0.3s ease;
        }

        .dashboard-container.navbar-active {
            margin-top: 110px;
        }

        h1 {
            color: var(--main-green);
            text-align: center;
            margin-bottom: 20px;
            font-size: 2.2rem;
            font-weight: 600;
            position: relative;
        }

        .search-form {
            background: linear-gradient(135deg, var(--ultra-light-green), var(--light-green));
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
            border: 1px solid var(--light-green);
            position: relative;
            overflow: hidden;
        }

        .search-form label {
            font-weight: 600;
            margin-right: 15px;
            color: var(--text-dark);
            font-size: 1.1rem;
        }

        .search-form input[type="text"] {
            padding: 12px 16px;
            border: 2px solid var(--light-green);
            border-radius: 8px;
            width: 250px;
            margin-right: 15px;
            font-size: 14px;
            color: var(--text-dark);
            background: var(--white);
            transition: all 0.3s ease;
        }

        .search-form input[type="text"]:focus {
            outline: none;
            border-color: var(--main-green);
            box-shadow: 0 0 0 3px rgba(46, 106, 51, 0.1);
        }

        .search-form button {
            background: linear-gradient(135deg, var(--main-green), var(--accent-green));
            color: var(--white);
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .search-form button:hover {
            background: linear-gradient(135deg, var(--dark-green), var(--main-green));
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(46, 106, 51, 0.3);
        }

        .results-section {
            margin-top: 30px;
        }

        .no-results {
            text-align: center;
            color: var(--text-medium);
            font-style: italic;
            padding: 40px 20px;
            background: var(--ultra-light-green);
            border-radius: 12px;
            border: 2px dashed var(--light-green);
            font-size: 1.1rem;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px 0;
        }

        .pay-fine-btn {
            background: linear-gradient(135deg, var(--error-red), #e74c3c);
            color: var(--white);
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .pay-fine-btn:hover {
            background: linear-gradient(135deg, #c0392b, var(--error-red));
            color: var(--white);
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(214, 69, 65, 0.3);
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
            border-radius: 12px;
            border: 1px solid var(--light-green);
            background: var(--white);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        th,
        td {
            border: none;
            padding: 15px 12px;
            text-align: left;
            border-bottom: 1px solid var(--light-green);
        }

        th {
            background: linear-gradient(135deg, var(--main-green), var(--accent-green));
            color: var(--white);
            font-weight: 600;
            position: sticky;
            top: 0;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        tr:nth-child(even) {
            background-color: var(--ultra-light-green);
        }

        tr:hover {
            background-color: var(--light-green);
            transition: background-color 0.2s ease;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .status-reserved {
            background: linear-gradient(135deg, var(--success-green), #27ae60);
            color: var(--white);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .status-returned {
            background: linear-gradient(135deg, var(--text-medium), var(--text-light));
            color: var(--white);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .results-count {
            color: var(--text-medium);
            font-size: 15px;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .results-count strong {
            color: var(--main-green);
            font-weight: 600;
        }

        .alert {
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 12px;
            border-left: 4px solid var(--main-green);
            font-size: 15px;
        }

        .alert-info {
            background: linear-gradient(135deg, var(--ultra-light-green), var(--light-green));
            border: 1px solid var(--light-green);
            color: var(--text-dark);
        }

        .alert-info strong {
            color: var(--main-green);
            font-weight: 600;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-container {
                margin: 10px;
                padding: 20px;
            }

            .dashboard-container.navbar-active {
                margin-top: 120px;
            }

            .search-form {
                padding: 20px 15px;
            }

            .search-form label {
                display: block;
                margin-bottom: 10px;
            }

            .search-form input[type="text"] {
                width: 100%;
                margin-right: 0;
                margin-bottom: 15px;
            }

            .table-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .pay-fine-btn {
                text-align: center;
            }

            h1 {
                font-size: 1.8rem;
            }
        }

        /* Loading animation for buttons */
        .search-form button:active {
            transform: translateY(0);
        }

        /* Smooth transitions */
        * {
            transition: color 0.2s ease, background-color 0.2s ease;
        }

        .overdue-section {
            margin-top: 20px;
            padding: 15px;
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
        }

        .overdue-title {
            color: #856404;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .no-overdue {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }

        .pay-fine-btn {
            background-color: #dc3545;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }

        .pay-fine-btn:hover {
            background-color: #c82333;
        }

        .pay-fine-btn.disabled {
            background-color: #6c757d;
            cursor: not-allowed;
            pointer-events: none;
        }

        .status-due,
        .status-overdue {
            background-color: #dc3545;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 5px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .cancel-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }

        .cancel-btn:hover {
            background-color: #c82333;
        }

        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .action-column {
            text-align: center;
            width: 80px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: none;
            border-radius: 8px;
            width: 80%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }

        .modal-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-icon {
            font-size: 24px;
            margin-right: 10px;
        }

        .modal-title {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
        }

        .modal-body {
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .modal-footer {
            text-align: right;
        }

        .modal-btn {
            padding: 10px 20px;
            margin-left: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }

        .btn-confirm {
            background-color: #dc3545;
            color: white;
        }

        .btn-confirm:hover {
            background-color: #c82333;
        }

        .btn-cancel {
            background-color: #6c757d;
            color: white;
        }

        .btn-cancel:hover {
            background-color: #5a6268;
        }

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .confirmation-icon {
            color: #dc3545;
        }

        .success-icon {
            color: #28a745;
        }

        .error-icon {
            color: #dc3545;
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
        <a href="notifications.php" class="notification-icon" title="Notifications" style="position: relative;">
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
            <a href="my_borrowing.php"><i class="fas fa-book"></i> My Borrowing</a>
            <a href="feedback.php"><i class="fas fa-comment-dots"></i> Feedback</a>
            <a href="../login.php"><i class="fas fa-sign-out-alt"></i> LogOut</a>
        </div>
    </div>

    <div class="dashboard-container" id="dashboardContainer">
        <h1>My Borrowing Records</h1>

        <!-- Search Form -->
        <div class="search-form">
            <form method="POST" action="">
                <label for="cid">Customer ID (CID):</label>
                <input type="text" id="cid" name="cid" placeholder="Enter Customer ID"
                    value="<?php echo htmlspecialchars($search_cid); ?>" required>
                <button type="submit" name="search">Search Records</button>
            </form>
        </div>

        <!-- Results Section -->
        <div class="results-section">
            <?php if ($search_performed): ?>
                <?php if (empty($reservations)): ?>
                    <div class="alert alert-info">
                        No borrowing records found for Customer ID:
                        <strong><?php echo htmlspecialchars($search_cid); ?></strong>
                    </div>
                <?php else: ?>
                    <div class="table-header">
                        <div class="results-count">
                            Found <?php echo count($reservations); ?> record(s) for Customer ID:
                            <strong><?php echo htmlspecialchars($search_cid); ?></strong>
                        </div>
                        <button id="payFineBtn" class="pay-fine-btn" onclick="checkOverdue()">
                            Pay Fine
                        </button>
                    </div>

                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Reservation ID</th>
                                    <th>Name</th>
                                    <th>CID</th>
                                    <th>Email</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Serial No</th>
                                    <th>Reservation Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reservations as $reservation): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($reservation['reservation_id']); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['name']); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['cid']); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['email']); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['title']); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['author']); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['serial_no']); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['reservation_date']); ?></td>
                                        <td>
                                            <span class="<?php
                                            if ($reservation['status'] == 'due' || $reservation['status'] == 'overdue') {
                                                echo 'status-due';
                                            } elseif ($reservation['status'] == 'reserved') {
                                                echo 'status-reserved';
                                            } else {
                                                echo 'status-returned';
                                            }
                                            ?>">
                                                <?php echo htmlspecialchars($reservation['status']); ?>
                                            </span>
                                        </td>
                                        <td class="action-column">
                                            <?php if ($reservation['status'] == 'reserved'): ?>
                                                <a href="cancellation.php?reservation_id=<?php echo urlencode($reservation['reservation_id']); ?>" 
                                                   class="cancel-btn" >
                                                   
                                                    Cancel
                                                </a>
                                            <?php else: ?>
                                                <span class="no-action">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-results">
                    Enter a Customer ID above and click "Search Records" to view borrowing records.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal for Overdue Records -->
    <div id="overdueModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <div id="modalContent">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

    <script>
        // Secondary navbar toggle with dashboard container adjustment
        document.getElementById('navbarToggle').addEventListener('click', function () {
            this.classList.toggle('active');
            const secondaryNavbar = document.getElementById('secondaryNavbar');
            const dashboardContainer = document.getElementById('dashboardContainer');

            // Toggle the secondary navbar
            secondaryNavbar.classList.toggle('active');

            // Toggle the dashboard container class to adjust margin
            dashboardContainer.classList.toggle('navbar-active');
        });

        // Overdue records data from PHP
        const overdueRecords = <?php echo json_encode($overdue_records); ?>;
        const hasOverdue = <?php echo json_encode($has_overdue); ?>;
        const searchCid = <?php echo json_encode($search_cid); ?>;

        function checkOverdue() {
            if (!searchCid) {
                alert('Please search for a Customer ID first.');
                return;
            }

            const modal = document.getElementById('overdueModal');
            const modalContent = document.getElementById('modalContent');

            if (hasOverdue && overdueRecords.length > 0) {
                // Show overdue records in modal
                let content = '<h3 style="color: #dc3545;">Overdue Records for CID: ' + searchCid + '</h3>';
                content += '<div style="margin-bottom: 15px; color: #856404;">The following records have outstanding fines that need to be paid:</div>';

                content += '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
                content += '<thead><tr style="background-color: #f8f9fa;">';
                content += '<th style="padding: 8px; border: 1px solid #ddd;">Reservation ID</th>';
                content += '<th style="padding: 8px; border: 1px solid #ddd;">Title</th>';
                content += '<th style="padding: 8px; border: 1px solid #ddd;">Author</th>';
                content += '<th style="padding: 8px; border: 1px solid #ddd;">Status</th>';
                content += '<th style="padding: 8px; border: 1px solid #ddd;">Reservation Date</th>';
                content += '</tr></thead><tbody>';

                overdueRecords.forEach(function (record) {
                    content += '<tr>';
                    content += '<td style="padding: 8px; border: 1px solid #ddd;">' + record.reservation_id + '</td>';
                    content += '<td style="padding: 8px; border: 1px solid #ddd;">' + record.title + '</td>';
                    content += '<td style="padding: 8px; border: 1px solid #ddd;">' + record.author + '</td>';
                    content += '<td style="padding: 8px; border: 1px solid #ddd;"><span class="status-due">' + record.status + '</span></td>';
                    content += '<td style="padding: 8px; border: 1px solid #ddd;">' + record.reservation_date + '</td>';
                    content += '</tr>';
                });

                content += '</tbody></table>';
                content += '<div style="margin-top: 15px; text-align: center;">';
                content += '<a href="payments.php?cid=' + encodeURIComponent(searchCid) + '" class="pay-fine-btn">Proceed to Pay Fine</a>';
                content += '</div>';

                modalContent.innerHTML = content;
            } else {
                // No overdue records
                modalContent.innerHTML = '<h3 style="color: #28a745;">No Outstanding Fines</h3>' +
                    '<div class="no-overdue">Customer ID: <strong>' + searchCid + '</strong> has no overdue records or outstanding fines.</div>' +
                    '<div style="margin-top: 15px; text-align: center;">' +
                    '<button onclick="closeModal()" style="background-color: #28a745; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">Close</button>' +
                    '</div>';
            }

            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('overdueModal').style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function (event) {
            const modal = document.getElementById('overdueModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>

</html>
<?php
// Move the download handling to the very top, before any includes
if (isset($_GET['download'])) {
    // Include only the database connection, not the header
    include('../includes/db.php');

    // Helper function to output beautiful CSV with updated date
    function output_beautiful_csv($filename, $data, $headers = [], $title = '')
    {
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

        // Create output handle
        $output = fopen('php://output', 'w');

        // Add BOM for proper UTF-8 encoding in Excel
        fputs($output, "\xEF\xBB\xBF");

        // Add title section
        if (!empty($title)) {
            fputcsv($output, [$title]);
            fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
            fputcsv($output, ['']); // Empty row for spacing
        }

        // Add headers if provided
        if (!empty($headers)) {
            fputcsv($output, $headers);
        }

        // Add data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        // Add footer with summary
        fputcsv($output, ['']); // Empty row
        fputcsv($output, ['Total Records: ' . count($data)]);
        fputcsv($output, ['Export Date: ' . date('Y-m-d')]);
        fputcsv($output, ['Export Time: ' . date('H:i:s')]);

        fclose($output);
        exit;
    }

    // Handle different download types
    switch ($_GET['download']) {
        case 'all_books':
            $result = $conn->query("SELECT title, author, genre, serialno, pages, created_at FROM books ORDER BY created_at DESC");
            if ($result) {
                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $rows[] = [
                        $row['title'],
                        $row['author'],
                        $row['genre'],
                        $row['serialno'],
                        $row['pages'],
                        date('Y-m-d H:i:s', strtotime($row['created_at']))
                    ];
                }
                output_beautiful_csv(
                    'Library_All_Books_' . date('Y-m-d') . '.csv',
                    $rows,
                    ['Title', 'Author', 'Genre', 'Serial No', 'Pages', 'Created At'],
                    'LIBRARY MANAGEMENT SYSTEM - ALL BOOKS REPORT'
                );
            }
            break;

        case 'reserved_books':
            $query = "
                SELECT b.title, b.author, b.serialno, b.genre, r.name AS reserved_by, r.reservation_date, r.status
                FROM reservations r
                JOIN books b ON r.book_id = b.id
                WHERE r.status = 'reserved'
                ORDER BY r.reservation_date DESC
            ";
            $result = $conn->query($query);
            if ($result) {
                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $rows[] = [
                        $row['title'],
                        $row['author'],
                        $row['serialno'],
                        $row['genre'],
                        $row['reserved_by'] ?: 'Unknown',
                        date('Y-m-d H:i:s', strtotime($row['reservation_date'])),
                        strtoupper($row['status'])
                    ];
                }
                output_beautiful_csv(
                    'Library_Reserved_Books_' . date('Y-m-d') . '.csv',
                    $rows,
                    ['Title', 'Author', 'Serial No', 'Genre', 'Reserved By', 'Reserved At', 'Status'],
                    'LIBRARY MANAGEMENT SYSTEM - RESERVED BOOKS REPORT'
                );
            }
            break;

        case 'employees':
            $result = $conn->query("SELECT full_name, username, email, address, dzongkhag, phone_no, dob, created_at FROM employee_data ORDER BY created_at DESC");
            if ($result) {
                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $rows[] = [
                        $row['full_name'],
                        $row['username'],
                        $row['email'],
                        $row['address'],
                        $row['dzongkhag'],
                        $row['phone_no'],
                        $row['dob'] ? date('Y-m-d', strtotime($row['dob'])) : '',
                        date('Y-m-d H:i:s', strtotime($row['created_at']))
                    ];
                }
                output_beautiful_csv(
                    'Library_Employees_' . date('Y-m-d') . '.csv',
                    $rows,
                    ['Full Name', 'Username', 'Email', 'Address', 'Dzongkhag', 'Phone No', 'Date of Birth', 'Created At'],
                    'LIBRARY MANAGEMENT SYSTEM - EMPLOYEE RECORDS REPORT'
                );
            }
            break;

        case 'feedback':
            $query = "
                SELECT f.id, 
                       COALESCE(u.name, 'Anonymous') as username,
                       f.category, 
                       f.subject, 
                       f.message, 
                       f.rating, 
                       f.priority, 
                       f.created_at 
                FROM feedback f
                LEFT JOIN users u ON f.user_id = u.id
                ORDER BY f.created_at DESC
            ";
            $result = $conn->query($query);
            if ($result) {
                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $rows[] = [
                        $row['id'],
                        $row['name'],
                        $row['category'],
                        $row['subject'] ?: 'N/A',
                        $row['message'],
                        $row['rating'] ?: 'N/A',
                        $row['priority'] ?: 'medium',
                        date('Y-m-d H:i:s', strtotime($row['created_at']))
                    ];
                }
                output_beautiful_csv(
                    'Library_Feedback_' . date('Y-m-d') . '.csv',
                    $rows,
                    ['ID', 'Username', 'Category', 'Subject', 'Message', 'Rating', 'Priority', 'Attachment', 'Submitted At'],
                    'LIBRARY MANAGEMENT SYSTEM - FEEDBACK REPORT'
                );
            }
            break;
    }

    // If we get here, something went wrong
    $conn->close();
    exit;
}

// Now include the headers and continue with regular page display
include('../includes/db.php');
include('../includes/dashboard_header.php');

// Prepare data for summary section
$totalBooksResult = $conn->query("SELECT COUNT(*) AS total_books FROM books");
$totalBooks = ($totalBooksResult) ? $totalBooksResult->fetch_assoc()['total_books'] : 0;

$totalReservedResult = $conn->query("SELECT COUNT(*) AS reserved_books FROM reservations WHERE status = 'reserved'");
$totalReserved = ($totalReservedResult) ? $totalReservedResult->fetch_assoc()['reserved_books'] : 0;

// Feedback query - UPDATED to join with users table and show username
$feedbackQuery = "
    SELECT f.id, 
           f.user_id,
           COALESCE(u.name, 'Anonymous') as username,
           f.category, 
           f.subject, 
           f.message, 
           f.rating, 
           f.priority, 
           f.created_at 
    FROM feedback f
    LEFT JOIN users u ON f.user_id = u.id
    ORDER BY f.created_at DESC
";
$feedbackResult = $conn->query($feedbackQuery);
if (!$feedbackResult) {
    echo "<p><strong>Error loading feedback data:</strong> " . $conn->error . "</p>";
}

// Get count of new reservations (UPDATED to include all statuses)
$new_reservations_query = "SELECT COUNT(*) as count FROM reservations WHERE status IN ('reserved', 'returned', 'cancelled', 'due paid') AND is_viewed = 0";
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
    <title>Library Reports - Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            --border-light: #e8ede9;
            --shadow-light: rgba(46, 106, 51, 0.1);
            --shadow-medium: rgba(46, 106, 51, 0.15);
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
            padding-top: 295px;
            /* Fixed space for both navbars */
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
            /* Right below the first navbar */
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

        /* Main Content - Fixed positioning */
        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 2rem;
            position: relative;
        }

        /* Content adjustment when navbar is active */
        body.navbar-active {
            padding-top: 355px;
            /* Extra space when secondary navbar is open */
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--text-medium);
            font-size: 1.1rem;
        }

        /* Summary Cards */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .summary-card {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px var(--shadow-light);
            border: 1px solid var(--border-light);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--main-green), var(--accent-green));
        }

        .summary-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 32px var(--shadow-medium);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .card-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-medium);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--main-green), var(--accent-green));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .card-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--main-green);
            margin-bottom: 0.5rem;
        }

        .card-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .card-link {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.5rem 0.75rem;
            background: var(--ultra-light-green);
            color: var(--text-medium);
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 1px solid var(--border-light);
        }

        .card-link:hover {
            background: var(--main-green);
            color: white;
            transform: translateY(-1px);
        }

        /* Download link enhancement */
        .card-link.download-link {
            background: linear-gradient(135deg, var(--success-green), var(--accent-green));
            color: white;
            font-weight: 600;
        }

        .card-link.download-link:hover {
            background: linear-gradient(135deg, var(--accent-green), var(--success-green));
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Modern Tables */
        .data-section {
            background: var(--white);
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px var(--shadow-light);
            border: 1px solid var(--border-light);
            overflow: hidden;
        }

        .section-header {
            background: linear-gradient(135deg, var(--ultra-light-green), var(--light-green));
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-light);
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table-container {
            overflow-x: auto;
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .modern-table th {
            background: var(--ultra-light-green);
            color: var(--text-dark);
            font-weight: 600;
            padding: 1rem;
            text-align: left;
            border-bottom: 2px solid var(--border-light);
            white-space: nowrap;
        }

        .modern-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-light);
            color: var(--text-medium);
        }

        .modern-table tbody tr {
            transition: all 0.2s ease;
        }

        .modern-table tbody tr:hover {
            background: var(--ultra-light-green);
        }

        .modern-table tbody tr:last-child td {
            border-bottom: none;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Priority badge styling */
        .priority-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-low {
            background: #e8f5e8;
            color: #2e7d2e;
        }

        .priority-medium {
            background: #fff3cd;
            color: #856404;
        }

        .priority-high {
            background: #f8d7da;
            color: #721c24;
        }

        /* Rating stars */
        .rating-stars {
            color: #ffc107;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding-top: 245px;
            }

            body.navbar-active {
                padding-top: 305px;
            }

            .header {
                padding: 1rem;
            }

            .header-title {
                font-size: 1.2rem;
            }

            .main-content {
                padding: 1rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .nav-links {
                flex-direction: column;
            }

            .nav-link {
                justify-content: center;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .section-header {
                padding: 1rem;
            }

            .modern-table {
                font-size: 0.8rem;
            }

            .modern-table th,
            .modern-table td {
                padding: 0.75rem 0.5rem;
            }

            .navbar-links {
                flex-wrap: wrap;
                gap: 10px;
            }

            .navbar-links a {
                font-size: 14px;
                padding: 6px 10px;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid var(--border-light);
            border-radius: 50%;
            border-top-color: var(--main-green);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Smooth transitions for body padding changes */
        body {
            transition: padding-top 0.3s ease;
        }

        /* Toast notification for downloads */
        .download-toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--success-green);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            opacity: 0;
            transform: translateY(50px);
            transition: all 0.3s ease;
            z-index: 1100;
        }

        .download-toast.show {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>

<body>
    <!-- Toast Container for Notifications -->
    <div id="toastContainer" class="toast-container"></div>


    <!-- Navigation -->
    <div class="navbar-toggle-container">
        <button class="toggle-btn" id="navbarToggle" title="Toggle Menu">
            <i class="fas fa-chevron-down"></i>
        </button>
        <a href="notification.php" class="notification-icon" title="Notifications">
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

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h2 class="page-title">📊 Analytics Dashboard</h2>
            <p class="page-subtitle">Comprehensive overview of library operations with exportable reports • Last
                updated: <?= date('Y-m-d H:i:s') ?></p>
        </div>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="summary-card">
                <div class="card-header">
                    <span class="card-title">Total Books</span>
                    <div class="card-icon">
                        <i class="fas fa-book"></i>
                    </div>
                </div>
                <div class="card-value"><?= htmlspecialchars($totalBooks) ?></div>
                <div class="card-actions">
                    <a href="?view=all_books" class="card-link">
                        <i class="fas fa-eye"></i>
                        View All
                    </a>
                    <a href="?download=all_books" class="card-link download-link"
                        onclick="showDownloadToast('All Books')">
                        <i class="fas fa-download"></i>
                        Export CSV
                    </a>
                </div>
            </div>

            <div class="summary-card">
                <div class="card-header">
                    <span class="card-title">Reserved Books</span>
                    <div class="card-icon">
                        <i class="fas fa-bookmark"></i>
                    </div>
                </div>
                <div class="card-value"><?= htmlspecialchars($totalReserved) ?></div>
                <div class="card-actions">
                    <a href="?view=reserved_books" class="card-link">
                        <i class="fas fa-eye"></i>
                        View All
                    </a>
                    <a href="?download=reserved_books" class="card-link download-link"
                        onclick="showDownloadToast('Reserved Books')">
                        <i class="fas fa-download"></i>
                        Export CSV
                    </a>
                </div>
            </div>

            <div class="summary-card">
                <div class="card-header">
                    <span class="card-title">Feedback Entries</span>
                    <div class="card-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                </div>
                <div class="card-value"><?= $feedbackResult ? $feedbackResult->num_rows : 0 ?></div>
                <div class="card-actions">
                    <a href="?download=feedback" class="card-link download-link"
                        onclick="showDownloadToast('Feedback')">
                        <i class="fas fa-download"></i>
                        Export CSV
                    </a>
                </div>
            </div>
        </div>
        <!-- Feedback Section -->
        <div class="data-section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-comment-dots"></i>
                    Feedback Report
                </h3>
            </div>
            <div class="table-container">
                <?php if ($feedbackResult && $feedbackResult->num_rows > 0): ?>
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Category</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Rating</th>
                                <th>Priority</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($fb = $feedbackResult->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($fb['id']) ?></td>
                                    <td><?= htmlspecialchars($fb['username']) ?></td>
                                    <td><?= htmlspecialchars($fb['category']) ?></td>
                                    <td><?= htmlspecialchars($fb['subject'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($fb['message']) ?></td>
                                    <td><?= $fb['rating'] !== null ? htmlspecialchars($fb['rating']) : 'N/A' ?></td>
                                    <td><?= htmlspecialchars($fb['priority'] ?? 'medium') ?></td>
                                    <td><?= htmlspecialchars($fb['created_at']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-comments"></i>
                        <p>No feedback entries found</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Dynamic Sections Based on View -->
        <?php if (isset($_GET['view'])): ?>
            <div class="data-section">
                <?php if ($_GET['view'] === 'all_books'): ?>
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-book-open"></i>
                            All Books
                        </h3>
                    </div>
                    <div class="table-container">
                        <?php
                        $books = $conn->query("SELECT * FROM books ORDER BY created_at DESC");
                        if ($books && $books->num_rows > 0): ?>
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Genre</th>
                                        <th>Serial No</th>
                                        <th>Pages</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($book = $books->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($book['title']) ?></td>
                                            <td><?= htmlspecialchars($book['author']) ?></td>
                                            <td><?= htmlspecialchars($book['genre']) ?></td>
                                            <td><?= htmlspecialchars($book['serialno']) ?></td>
                                            <td><?= htmlspecialchars($book['pages']) ?></td>
                                            <td><?= htmlspecialchars($book['created_at']) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-book"></i>
                                <p>No books found</p>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($_GET['view'] === 'reserved_books'): ?>
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-bookmark"></i>
                            Reserved Books
                        </h3>
                    </div>
                    <div class="table-container">
                        <?php
                        $reserved = $conn->query("
                            SELECT r.*, b.title, b.author, b.serialno, b.genre, u.name 
                            FROM reservations r 
                            JOIN books b ON r.book_id = b.id 
                            LEFT JOIN users u ON r.name = u.name
                            WHERE r.status = 'reserved' 
                            ORDER BY r.reservation_date DESC
                        ");
                        if ($reserved && $reserved->num_rows > 0): ?>
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Book Title</th>
                                        <th>Author</th>
                                        <th>Serial No</th>
                                        <th>Genre</th>
                                        <th>Reserved By</th>
                                        <th>Reserved At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $reserved->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['title']) ?></td>
                                            <td><?= htmlspecialchars($row['author']) ?></td>
                                            <td><?= htmlspecialchars($row['serialno']) ?></td>
                                            <td><?= htmlspecialchars($row['genre']) ?></td>
                                            <td><?= htmlspecialchars($row['name'] ?? 'Unknown') ?></td>
                                            <td><?= htmlspecialchars($row['reservation_date']) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-bookmark"></i>
                                <p>No reserved books found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Download Toast Notification -->
    <div id="downloadToast" class="download-toast">
        <i class="fas fa-check-circle"></i>
        <span id="downloadMessage">Download started!</span>
    </div>

    <!-- JavaScript -->
    <script>
        // Navbar toggle functionality
        document.getElementById('navbarToggle').addEventListener('click', function () {
            // Toggle the active class on the button and change icon
            this.classList.toggle('active');
            const icon = this.querySelector('i');

            // Toggle the secondary navbar
            const secondaryNavbar = document.getElementById('secondaryNavbar');
            secondaryNavbar.classList.toggle('active');

            // Toggle body class to adjust padding
            document.body.classList.toggle('navbar-active');

            // Change icon based on state
            if (secondaryNavbar.classList.contains('active')) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        });

        // Download toast notification function
        function showDownloadToast(reportType) {
            const toast = document.getElementById('downloadToast');
            const message = document.getElementById('downloadMessage');

            message.innerHTML = `<strong>${reportType}</strong> report is being prepared for download...`;
            toast.classList.add('show');

            // Hide toast after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add loading animation to download links
        document.querySelectorAll('a[href*="download"]').forEach(link => {
            link.addEventListener('click', function () {
                const originalText = this.innerHTML;
                this.innerHTML = '<span class="loading"></span> Preparing...';

                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 2000);
            });
        });

        // Enhanced table interactions
        document.querySelectorAll('.modern-table tbody tr').forEach(row => {
            row.addEventListener('click', function () {
                // Remove active class from all rows
                document.querySelectorAll('.modern-table tbody tr').forEach(r => {
                    r.classList.remove('active');
                });

                // Add active class to clicked row
                this.classList.add('active');
            });
        });

        // Auto-hide menu on mobile after clicking a link
        if (window.innerWidth <= 768) {
            document.querySelectorAll('.navbar-links a').forEach(link => {
                link.addEventListener('click', function () {
                    const secondaryNavbar = document.getElementById('secondaryNavbar');
                    const navbarToggle = document.getElementById('navbarToggle');
                    const icon = navbarToggle.querySelector('i');

                    secondaryNavbar.classList.remove('active');
                    document.body.classList.remove('navbar-active');
                    navbarToggle.classList.remove('active');
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                });
            });
        }

        // Add search functionality to tables
        function addSearchToTable(tableSelector) {
            const table = document.querySelector(tableSelector);
            if (!table) return;

            const searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.placeholder = 'Search table...';
            searchInput.style.cssText = `
                width: 100%;
                padding: 0.75rem;
                margin-bottom: 1rem;
                border: 1px solid var(--border-light);
                border-radius: 8px;
                font-size: 0.9rem;
            `;

            table.parentNode.insertBefore(searchInput, table);

            searchInput.addEventListener('input', function () {
                const filter = this.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            });
        }

        // Initialize search for all tables
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.modern-table').forEach((table, index) => {
                addSearchToTable(`.modern-table:nth-of-type(${index + 1})`);
            });
        });

        // Handle window resize
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                // Reset mobile-specific states on larger screens
                const secondaryNavbar = document.getElementById('secondaryNavbar');
                if (!secondaryNavbar.classList.contains('active')) {
                    document.body.classList.remove('navbar-active');
                }
            }
        });

        // Add print functionality
        function printReport() {
            window.print();
        }

        // Add keyboard shortcuts
        document.addEventListener('keydown', function (e) {
            // Ctrl/Cmd + P for print
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                printReport();
            }

            // Escape to close navbar
            if (e.key === 'Escape') {
                const secondaryNavbar = document.getElementById('secondaryNavbar');
                const navbarToggle = document.getElementById('navbarToggle');
                const icon = navbarToggle.querySelector('i');

                if (secondaryNavbar.classList.contains('active')) {
                    secondaryNavbar.classList.remove('active');
                    document.body.classList.remove('navbar-active');
                    navbarToggle.classList.remove('active');
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                }
            }
        });

        // Auto-refresh data every 5 minutes
        setInterval(function () {
            // Only refresh if no download is in progress
            if (!document.querySelector('.loading')) {
                location.reload();
            }
        }, 300000); // 5 minutes

        // Show success message after download completion
        window.addEventListener('beforeunload', function () {
            if (performance.navigation.type === 1) {
                // Page is being refreshed, show success message
                setTimeout(() => {
                    const toast = document.getElementById('downloadToast');
                    const message = document.getElementById('downloadMessage');
                    message.innerHTML = '✅ <strong>Download completed successfully!</strong>';
                    toast.classList.add('show');
                    setTimeout(() => toast.classList.remove('show'), 3000);
                }, 100);
            }
        });
    </script>
</body>

</html>

<?php
include('../includes/footer.php');
$conn->close();
?>
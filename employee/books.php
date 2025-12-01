<?php
// Include database connection
include('../includes/db.php');
include('../includes/dashboard_header.php');

// Pagination variables
$records_per_page = 50;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$page = max(1, min(100, $page)); // Limit page to range 1-100
$offset = ($page - 1) * $records_per_page;

// Get total number of books
$count_query = "SELECT COUNT(*) as total FROM books";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch paginated books from the database with their inventory data
$query = "SELECT b.*, IFNULL(i.quantity, 0) as quantity 
          FROM books b 
          LEFT JOIN inventory i ON b.id = i.book_id
          LIMIT $offset, $records_per_page";
$result = mysqli_query($conn, $query);

// Check for unread notifications
$notification_query = "SELECT COUNT(*) AS count FROM notifications WHERE status = 'unread'";
$notification_result = $conn->query($notification_query);
$notification_count = 0;
if ($notification_result && $row = $notification_result->fetch_assoc()) {
    $notification_count = $row['count'];
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
    <title>Books List</title>
    <link rel="stylesheet" href="employee.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
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
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
            background-color: #f5f8f5;
            color: var(--text-dark);
            transition: all 0.3s ease;
            padding-top: 315px; /* Initial padding: 255px (from header) + 50px (navbar) */
        }

        /* When secondary navbar is active, add extra padding */
        body.navbar-active {
            padding-top: 365px; /* 255px + 50px + 60px (secondary navbar) */
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
            top: 285px; /* Position below the main navbar */
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

        /* Main content container */
        .container {
            max-width: 1200px;
            margin: 0 auto 2rem auto;
            padding: 2rem;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
        }

        h1 {
            color: var(--main-green);
            text-align: center;
            margin-bottom: 2rem;
            font-weight: 600;
        }

        .books-wrapper {
            overflow-x: auto;
            margin-top: 2rem;
            border-radius: var(--border-radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
        }

        thead {
            background-color: var(--main-green);
            color: var(--white);
        }

        th,
        td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--light-green);
        }

        th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        tbody tr {
            transition: var(--transition);
        }

        tbody tr:hover {
            background-color: var(--ultra-light-green);
        }

        .availability-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .availability-form input[type="number"] {
            width: 70px;
            padding: 0.5rem;
            border: 1px solid var(--light-green);
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .availability-form input[type="number"]:focus {
            border-color: var(--main-green);
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 106, 51, 0.2);
        }

        .availability-form button {
            background-color: var(--main-green);
            color: var(--white);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
        }

        .availability-form button:hover {
            background-color: var(--dark-green);
            transform: translateY(-2px);
        }

        .book-status {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-available {
            background-color: var(--success-green);
            color: var(--white);
        }

        .status-unavailable {
            background-color: var(--error-red);
            color: var(--white);
        }

        .status-count {
            background-color: var(--white);
            color: var(--success-green);
            border-radius: 50%;
            padding: 0.15rem 0.5rem;
            margin-left: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .search-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .search-container input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid var(--light-green);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        .search-container input:focus {
            border-color: var(--main-green);
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 106, 51, 0.2);
        }

        .search-container button {
            background-color: var(--main-green);
            color: var(--white);
            border: none;
            padding: 0 1.5rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-container button:hover {
            background-color: var(--dark-green);
            transform: translateY(-2px);
        }

        /* Alert Messages */
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(46, 204, 113, 0.2);
            color: var(--success-green);
            border-left: 4px solid var(--success-green);
        }

        .alert-danger {
            background-color: rgba(214, 69, 65, 0.2);
            color: var(--error-red);
            border-left: 4px solid var(--error-red);
        }

        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 2rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .pagination-info {
            width: 100%;
            text-align: center;
            margin-bottom: 1rem;
            color: var(--text-medium);
            font-size: 0.9rem;
        }

        .pagination a,
        .pagination span {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 0.75rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 500;
            color: var(--main-green);
            background-color: var(--white);
            border: 1px solid var(--light-green);
            transition: var(--transition);
        }

        .pagination a:hover {
            background-color: var(--ultra-light-green);
            border-color: var(--accent-green);
        }

        .pagination span.current {
            background-color: var(--main-green);
            color: var(--white);
            border-color: var(--main-green);
        }

        .pagination span.ellipsis {
            background: none;
            border: none;
            color: var(--text-medium);
        }

        .pagination-controls {
            display: flex;
            gap: 0.5rem;
        }

        /* Notification styles */
        .notification-icon {
            position: relative;
            color: white;
            font-size: 1.2rem;
            transition: var(--transition);
        }

        .notification-icon:hover {
            color: var(--light-green);
        }

        .notification-badge {
            background-color: var(--error-red);
            color: white;
            border-radius: 50%;
            font-size: 0.7rem;
            padding: 2px 6px;
            margin-left: 5px;
        }

        @media (max-width: 768px) {
            body {
                padding-top: 315px; /* Adjusted for mobile */
            }

            body.navbar-active {
                padding-top: 375px; /* Adjusted for mobile with secondary navbar */
            }

            .container {
                padding: 1.5rem;
                margin: 1rem;
            }

            .navbar-links {
                overflow-x: auto;
                justify-content: flex-start;
                padding: 0 10px;
            }

            .navbar-links a {
                white-space: nowrap;
                padding: 8px 10px;
            }

            th,
            td {
                padding: 0.75rem;
            }

            .availability-form {
                flex-direction: column;
                align-items: flex-start;
            }

            .availability-form button {
                width: 100%;
            }

            .pagination {
                flex-direction: column;
                align-items: center;
            }

            .pagination a,
            .pagination span {
                margin: 0.25rem;
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

    <div class="container">
        <h1>Books Management</h1>
        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Search books by title, author, or genre...">
            <button onclick="searchBooks()">
                <i class="fas fa-search"></i> Search
            </button>
        </div>

        <div class="books-wrapper">
            <table id="booksTable">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Genre</th>
                        <th>Serial Number</th>
                        <th>Pages</th>
                        <th>Availability</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['author']); ?></td>
                            <td><?php echo htmlspecialchars($row['genre']); ?></td>
                            <td><?php echo htmlspecialchars($row['serialno']); ?></td>
                            <td><?php echo htmlspecialchars($row['pages']); ?></td>
                            <td>
                                <?php if ($row['quantity'] > 0): ?>
                                    <span class="book-status status-available">
                                        Available <span class="status-count"><?php echo $row['quantity']; ?></span>
                                    </span>
                                <?php else: ?>
                                    <span class="book-status status-unavailable">
                                        Not Available
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form class="availability-form" method="POST" action="update_availability.php">
                                    <input type="hidden" name="book_id" value="<?php echo $row['id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $row['quantity']; ?>" min="0"
                                        required>
                                    <button type="submit">
                                        <i class="fas fa-sync-alt"></i> Update
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination">
            <div class="pagination-info">
                Showing <?php echo min(($page - 1) * $records_per_page + 1, $total_records); ?> to
                <?php echo min($page * $records_per_page, $total_records); ?> of
                <?php echo $total_records; ?> books
            </div>

            <div class="pagination-controls">
                <?php if ($page > 1): ?>
                    <a href="?page=1"><i class="fas fa-angle-double-left"></i></a>
                    <a href="?page=<?php echo $page - 1; ?>"><i class="fas fa-angle-left"></i></a>
                <?php endif; ?>

                <?php
                // Determine range of page numbers to display
                $range = 5; // Number of pages to show before and after current page
                $start_page = max(1, $page - $range);
                $end_page = min($total_pages, $page + $range);

                // Add first page and ellipsis if necessary
                if ($start_page > 1) {
                    echo '<a href="?page=1">1</a>';
                    if ($start_page > 2) {
                        echo '<span class="ellipsis">...</span>';
                    }
                }

                // Display page numbers
                for ($i = $start_page; $i <= $end_page; $i++) {
                    if ($i == $page) {
                        echo '<span class="current">' . $i . '</span>';
                    } else {
                        echo '<a href="?page=' . $i . '">' . $i . '</a>';
                    }
                }

                // Add last page and ellipsis if necessary
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<span class="ellipsis">...</span>';
                    }
                    echo '<a href="?page=' . $total_pages . '">' . $total_pages . '</a>';
                }
                ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>"><i class="fas fa-angle-right"></i></a>
                    <a href="?page=<?php echo $total_pages; ?>"><i class="fas fa-angle-double-right"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

    <script>
        // Live search functionality
        document.getElementById('searchInput').addEventListener('input', function () {
            searchBooks();
        });

        // Search functionality
        function searchBooks() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll("#booksTable tbody tr");

            rows.forEach(row => {
                const title = row.cells[0].textContent.toLowerCase();
                const author = row.cells[1].textContent.toLowerCase();
                const genre = row.cells[2].textContent.toLowerCase();

                if (title.includes(input) || author.includes(input) || genre.includes(input)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        // Secondary navbar toggle with smooth animation
        document.getElementById('navbarToggle').addEventListener('click', function () {
            this.classList.toggle('active');
            const secondaryNavbar = document.getElementById('secondaryNavbar');
            const body = document.body;

            // Toggle the secondary navbar
            secondaryNavbar.classList.toggle('active');

            // Toggle body class to adjust padding and push content down
            body.classList.toggle('navbar-active');
        });

        // Add event listener for Enter key in search input
        document.getElementById('searchInput').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                searchBooks();
            }
        });
    </script>
</body>

</html>
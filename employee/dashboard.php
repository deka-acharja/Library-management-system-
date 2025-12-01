<?php
// Include database connection file
include('../includes/db.php');
include('../includes/dashboard_header.php');

// Pagination setup
$records_per_page = 8;
$max_pages = 100;

// Get current page from URL parameter, default to page 1
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

// Ensure page is within valid range
if ($current_page < 1)
    $current_page = 1;
if ($current_page > $max_pages)
    $current_page = $max_pages;

// Calculate offset for SQL query
$offset = ($current_page - 1) * $records_per_page;

// Fetch books for current page
$query = "SELECT * FROM books ORDER BY id DESC LIMIT $offset, $records_per_page";
$result = $conn->query($query);

// Get total number of books for pagination
$total_query = "SELECT COUNT(*) AS total FROM books";
$total_result = $conn->query($total_query);
$total_books = 0;
if ($total_result && $row = $total_result->fetch_assoc()) {
    $total_books = $row['total'];
}

// Calculate total pages (cap at max_pages)
$total_pages = ceil($total_books / $records_per_page);
if ($total_pages > $max_pages)
    $total_pages = $max_pages;

// Error handling if query fails
if ($result === false) {
    die("Error in SQL query: " . $conn->error);
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
    <title>Employee Dashboard</title>
    <link rel="stylesheet" href="employee.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Main Color Palette */
        :root {
            --main-green: #2e6a33;
            /* Main green color (was dark-green) */
            --dark-green: #1d4521;
            /* Darker shade for hover/focus (darkened from new main) */
            --light-green: #c9dccb;
            /* Light green for backgrounds (adjusted) */
            --accent-green: #4a9950;
            /* Accent green for highlights (adjusted) */
            --ultra-light-green: #eef5ef;
            /* Ultra light green for subtle backgrounds (adjusted) */
            --text-dark: #263028;
            /* Dark text color (slightly adjusted) */
            --text-medium: #45634a;
            /* Medium text color (adjusted) */
            --text-light: #6b8f70;
            /* Light text color (adjusted) */
            --white: #ffffff;
            /* White (unchanged) */
            --error-red: #d64541;
            /* Error red (unchanged) */
            --success-green: #2ecc71;
            /* Success green (unchanged) */
            --warning-yellow: #f39c12;
            /* Warning yellow (unchanged) */
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
            padding-top: 245px;
            /* Space for fixed navbar */
        }

        /* Fixed Top Navbar */
        .navbar-toggle-container {
            position: fixed;
            top: 255;
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
            padding: 0px;
            margin-top: 50px;
            /* Default margin to account for navbar */
            transition: margin-top 0.3s ease;
        }

        .content-wrapper.navbar-active {
            margin-top: 120px;
            /* Extra margin when secondary navbar is active */
        }

        /* Main Dashboard Container */
        .dashboard-container {
            padding: 0px auto;
            margin: 30px auto;
            background-color: var(--ultra-light-green);
            min-height: 80vh;
            position: relative;
        }

        /* Section Header */
        .section-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }

        .section-header h2 {
            color: var(--text-dark);
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            font-family: 'Georgia', serif;
        }

        .section-header p {
            color: var(--text-medium);
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .section-divider {
            height: 3px;
            width: 80px;
            background: linear-gradient(to right, var(--main-green), var(--dark-green));
            margin: 15px auto 25px;
            border-radius: 2px;
        }

        /* Book Grid Layout */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Individual Book Card */
        .book-card {
            background: var(--white);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(66, 147, 73, 0.08);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
            border: 1px solid rgba(66, 147, 73, 0.1);
        }

        .book-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(66, 147, 73, 0.15);
        }

        /* Image Container */
        .book-image-container {
            position: relative;
            padding-top: 20px;
            height: 220px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(to bottom, var(--light-green), var(--white));
            overflow: hidden;
        }

        .book-card img {
            max-width: 85%;
            max-height: 200px;
            object-fit: contain;
            border-radius: 4px;
            box-shadow: 0 5px 15px rgba(66, 147, 73, 0.15);
            transition: transform 0.5s ease;
        }

        .book-card:hover img {
            transform: scale(1.05);
        }

        /* Book Info Section */
        .book-info {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            border-top: 1px solid var(--light-green);
        }

        .book-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 10px;
            line-height: 1.4;
            font-family: 'Georgia', serif;
        }

        .book-meta {
            display: flex;
            align-items: center;
            margin-bottom: 4px;
            color: var(--text-light);
            font-size: 14px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .meta-label {
            font-weight: 600;
            margin-right: 5px;
            color: var(--text-medium);
        }

        /* Category Badge */
        .book-category {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--main-green);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 5px rgba(66, 147, 73, 0.2);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(66, 147, 73, 0.1);
            margin: 0 auto;
            max-width: 500px;
        }

        .empty-state p {
            font-size: 18px;
            color: var(--text-light);
            margin-bottom: 15px;
        }

        /* Pagination Styles */
        .pagination-container {
            margin-top: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .pagination-info {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 10px;
            text-align: center;
            width: 100%;
        }

        .pagination-link {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-width: 36px;
            height: 36px;
            padding: 0 10px;
            margin: 0 2px;
            background-color: var(--white);
            color: var(--main-green);
            border-radius: 4px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            border: 1px solid var(--light-green);
        }

        .pagination-link:hover {
            background-color: var(--light-green);
            border-color: var(--main-green);
        }

        .pagination-link.active {
            background-color: var(--main-green);
            color: white;
            border-color: var(--main-green);
            box-shadow: 0 2px 5px rgba(66, 147, 73, 0.3);
        }

        .pagination-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .pagination-ellipsis {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-width: 36px;
            height: 36px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-light);
        }

        /* Notification Badge */
        .notification-badge {
            background-color: var(--error-red);
            color: white;
            font-size: 12px;
            font-weight: bold;
            border-radius: 50%;
            padding: 2px 6px;
            margin-left: 6px;
            vertical-align: top;
        }

        /* Notification Icon */
        .notification-icon {
            color: var(--white);
            font-size: 1.2rem;
            text-decoration: none;
            position: relative;
        }

        .notification-icon:hover {
            color: var(--dark-green);
        }

        .badge {
            position: absolute;
            top: -5px;
            right: -10px;
            background-color: var(--error-red);
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 50%;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .books-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .books-grid {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                gap: 15px;
            }

            .section-header h2 {
                font-size: 28px;
            }

            .book-image-container {
                height: 180px;
            }

            .navbar-links {
                flex-wrap: wrap;
                padding: 5px;
            }

            .navbar-links a {
                padding: 5px 10px;
                font-size: 14px;
            }

            .secondary-navbar.active {
                height: auto;
                padding: 10px 0;
            }

            .content-wrapper.navbar-active {
                margin-top: 130px;
                /* Adjust for potential wrapping */
            }

            .pagination-container {
                gap: 5px;
            }

            .pagination-link {
                min-width: 32px;
                height: 32px;
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .books-grid {
                grid-template-columns: 1fr;
                max-width: 320px;
                margin: 0 auto;
            }

            .section-header h2 {
                font-size: 24px;
            }

            .dashboard-container {
                padding: 20px 15px;
            }

            .dashboard-title {
                font-size: 16px;
                max-width: 200px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .pagination-container {
                margin-top: 30px;
            }

            .pagination-mobile-hide {
                display: none;
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

    <!-- Main Content Wrapper -->
    <div class="content-wrapper" id="contentWrapper">
        <div class="dashboard-container">
            <div class="section-header">
                <h2>Library Books</h2>
                <div class="section-divider"></div>
                <p>Explore our collection of books</p>
            </div>

            <div class="books-grid">
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $title = htmlspecialchars($row['title']);
                        $author = htmlspecialchars($row['author']);
                        $page_count = $row['pages'] ? htmlspecialchars($row['pages']) : 'N/A';
                        $genre = htmlspecialchars($row['genre']);

                        // Handle image display
                        if (isset($row['image']) && !empty($row['image'])) {
                            // For BLOB data, create a data URI
                            $image_data = base64_encode($row['image']);
                            $image_src = 'data:image/jpeg;base64,' . $image_data;
                        } else {
                            // Fallback to default image
                            $image_src = '../uploads/default.jpg';
                        }

                        echo '<div class="book-card">';
                        echo '<span class="book-category">' . $genre . '</span>';
                        echo '<div class="book-image-container">';
                        echo '<img src="' . $image_src . '" alt="' . $title . '">';
                        echo '</div>';
                        echo '<div class="book-info">';
                        echo '<h3 class="book-title">' . $title . '</h3>';
                        echo '<div class="book-meta"><span class="meta-label">Author:</span> ' . $author . '</div>';
                        echo '<div class="book-meta"><span class="meta-label">Pages:</span> ' . $page_count . '</div>';
                        echo '</div></div>';
                    }
                } else {
                    echo '<div class="empty-state">';
                    echo '<p>No books have been added to the library yet.</p>';
                    echo '</div>';
                }
                ?>
            </div>

            <!-- Pagination Section -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination-info">
                        Showing page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
                        (<?php echo $total_books; ?> total books)
                    </div>

                    <!-- First and Previous links -->
                    <a href="?page=1" class="pagination-link <?php echo ($current_page == 1) ? 'disabled' : ''; ?>">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="?page=<?php echo max(1, $current_page - 1); ?>"
                        class="pagination-link <?php echo ($current_page == 1) ? 'disabled' : ''; ?>">
                        <i class="fas fa-angle-left"></i>
                    </a>

                    <?php
                    // Calculate range of page numbers to display
                    $range = 100; // Number of pages to show before and after current page
                    $start_page = max(1, $current_page - $range);
                    $end_page = min($total_pages, $current_page + $range);

                    // Always show first page
                    if ($start_page > 1) {
                        echo '<a href="?page=1" class="pagination-link pagination-mobile-hide">1</a>';
                        if ($start_page > 2) {
                            echo '<span class="pagination-ellipsis pagination-mobile-hide">...</span>';
                        }
                    }

                    // Show page links in the range
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $active_class = ($i == $current_page) ? 'active' : '';
                        echo '<a href="?page=' . $i . '" class="pagination-link ' . $active_class . '">' . $i . '</a>';
                    }

                    // Always show last page
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<span class="pagination-ellipsis pagination-mobile-hide">...</span>';
                        }
                        echo '<a href="?page=' . $total_pages . '" class="pagination-link pagination-mobile-hide">' . $total_pages . '</a>';
                    }
                    ?>

                    <!-- Next and Last links -->
                    <a href="?page=<?php echo min($total_pages, $current_page + 1); ?>"
                        class="pagination-link <?php echo ($current_page == $total_pages) ? 'disabled' : ''; ?>">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="?page=<?php echo $total_pages; ?>"
                        class="pagination-link <?php echo ($current_page == $total_pages) ? 'disabled' : ''; ?>">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

    <script>
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

</body>

</html>
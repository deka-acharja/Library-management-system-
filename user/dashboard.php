<?php
// Include the necessary files for database connection and header
include('../includes/db.php');
include('../includes/dashboard_header.php');

// Initialize search variables
$search_query = "";
$status_filter = "all"; // Default status filter
$where_clause = "";

// Pagination settings
$records_per_page = 8;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, min($page, 100)); // Ensure page is between 1 and 100
$offset = ($page - 1) * $records_per_page;

// Check if a search request was made - For initial page load and non-AJAX requests
if (isset($_GET['search'])) {
    $search_query = trim($_GET['search']);
    if (!empty($search_query)) {
        $search_query = $conn->real_escape_string($search_query); // Prevent SQL injection
        $where_clause = "WHERE (b.title LIKE '%$search_query%' 
                         OR b.author LIKE '%$search_query%' 
                         OR b.genre LIKE '%$search_query%' 
                         OR b.pages LIKE '%$search_query%')";
    }
}

// Check if status filter is applied
if (isset($_GET['status']) && $_GET['status'] != 'all') {
    $status_filter = $_GET['status'];

    if (!empty($where_clause)) {
        // Add to existing where clause
        if ($status_filter == 'available') {
            $where_clause .= " AND IFNULL(i.quantity, 0) > 0";
        } else if ($status_filter == 'not-available') {
            $where_clause .= " AND IFNULL(i.quantity, 0) = 0";
        }
    } else {
        // Create new where clause
        if ($status_filter == 'available') {
            $where_clause = "WHERE IFNULL(i.quantity, 0) > 0";
        } else if ($status_filter == 'not-available') {
            $where_clause = "WHERE IFNULL(i.quantity, 0) = 0";
        }
    }
}

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total FROM books b LEFT JOIN inventory i ON b.id = i.book_id $where_clause";
$count_result = $conn->query($count_query);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$total_pages = min($total_pages, 100); // Limit to 100 pages maximum

// Fetch books based on search, status filter and pagination
$query = "SELECT b.*, IFNULL(i.quantity, 0) as available_quantity 
          FROM books b 
          LEFT JOIN inventory i ON b.id = i.book_id 
          $where_clause 
          ORDER BY b.id DESC 
          LIMIT $offset, $records_per_page";
$result = $conn->query($query);

// Error handling if query fails
if ($result === false) {
    die("Error in SQL query: " . $conn->error);
}

// Get count of new reservations
$new_reservations_query = "SELECT COUNT(*) as count FROM reservations WHERE status IN ('confirmed', 'rejected', 'terminated', 'due') AND is_viewed = 0";
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
    <title>Dashboard</title>
    <link rel="stylesheet" href="user.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        body {
            font-family:Georgia, 'Times New Roman', Times, serif;
            color: var(--text-dark);
            background-color: var(--ultra-light-green);
            margin: 0;
            padding: 0;
            line-height: 1.6;
            padding-top: 295px;
            /* Initial padding for primary navbar */
        }

        /* Main container styles */
        .dashboard-container {
            max-width: 1400px;
            margin: 10px auto;
            padding: 20px 30px;
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: margin-top 0.3s ease;
            /* Smooth transition for margin changes */
            margin-top: 20px;
            /* Default top margin */
        }

        /* When secondary navbar is active, add extra top margin */
        .dashboard-container.navbar-active {
            margin-top: 80px;
            /* Extra margin when secondary navbar is visible */
        }

        /* Fixed Top Navbar */
        .navbar-toggle-container {
            position: fixed;
            top: 240px;
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
            font-family:'Times New Roman', Times, serif;
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

        /* Search container styling */
        .search-container {
            background-color: var(--light-green);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            width: 100%;
            display: flex;
            gap: 10px;
        }

        .search-container form {
            display: flex;
            gap: 10px;
            width: 100%;
        }

        .search-container input[type="text"] {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .search-container input[type="text"]:focus {
            border-color: var(--accent-green);
            box-shadow: 0 0 0 2px rgba(74, 153, 80, 0.2);
            outline: none;
        }

        .search-container select {
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 16px;
            background-color: var(--white);
            min-width: 150px;
            cursor: pointer;
        }

        .search-container select:focus {
            border-color: var(--accent-green);
            outline: none;
        }

        /* Books grid styling */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 35px;
            margin-top: 20px;
        }

        .book-card {
            background-color: var(--white);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            cursor: pointer;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }

        .book-category {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--accent-green);
            color: var(--white);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            z-index: 1;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .book-image-container {
            height: 200px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--ultra-light-green);
        }

        .book-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .book-card:hover .book-image-container img {
            transform: scale(1.05);
        }

        .book-info {
            padding: 15px;
        }

        .book-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-dark);
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            height: 50px;
        }

        .book-meta {
            display: flex;
            margin-bottom: 8px;
            font-size: 14px;
            color: var(--text-medium);
        }

        .meta-label {
            font-weight: 600;
            margin-right: 5px;
            color: var(--text-medium);
        }

        .availability-box {
            padding: 8px 12px;
            border-radius: 6px;
            text-align: center;
            font-weight: 600;
            margin-top: 15px;
            font-size: 14px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .available {
            background-color: rgba(56, 161, 105, 0.1);
            color: var(--success-green);
            border: 1px solid rgba(56, 161, 105, 0.3);
        }

        .not-available {
            background-color: rgba(229, 62, 62, 0.1);
            color: var(--error-red);
            border: 1px solid rgba(229, 62, 62, 0.3);
        }

        .availability-count {
            background-color: rgba(255, 255, 255, 0.7);
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 8px;
            font-size: 12px;
            font-weight: 700;
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            background-color: var(--white);
            border-radius: 8px;
            color: var(--text-medium);
        }

        .empty-state p {
            font-size: 18px;
            margin-top: 15px;
        }

        .navbar-links li {
            list-style-type: none;
            position: relative;
        }

        /* Pagination Styling */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 40px;
            margin-bottom: 20px;
            gap: 5px;
        }

        .pagination a,
        .pagination span {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-width: 38px;
            height: 38px;
            padding: 0 10px;
            border-radius: 6px;
            background-color: var(--white);
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        .pagination a:hover {
            background-color: var(--ultra-light-green);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
        }

        .pagination .active {
            background-color: var(--main-green);
            color: var(--white);
            box-shadow: 0 3px 8px rgba(46, 106, 51, 0.3);
        }

        .pagination .ellipsis {
            background: transparent;
            box-shadow: none;
        }

        .pagination .prev,
        .pagination .next {
            padding: 0 15px;
        }

        .pagination .disabled {
            opacity: 0.5;
            pointer-events: none;
            cursor: not-allowed;
        }

        /* Loading indicator */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            border-radius: 8px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top-color: var(--main-green);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
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
        <!-- Search Box with Status Filter (No button) -->
        <div class="search-container">
            <form id="searchForm">
                <input type="text" id="search" name="search" placeholder="Search by Title, Author, Genre, Pages..."
                    value="<?= htmlspecialchars($search_query) ?>">
                <select id="status-filter" name="status">
                    <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>All Books</option>
                    <option value="available" <?= $status_filter == 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="not-available" <?= $status_filter == 'not-available' ? 'selected' : '' ?>>Not Available
                    </option>
                </select>
            </form>
        </div>

        <!-- Books Grid with Loading Indicator -->
        <div style="position: relative;">
            <div id="loading-indicator" class="loading-overlay" style="display: none;">
                <div class="spinner"></div>
            </div>
            <div id="books-grid" class="books-grid">
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $title = $row['title'];
                        $author = $row['author'];
                        $genre = $row['genre'] ?? 'General'; // Set default if genre is null
                        $page_count = $row['pages'] ? $row['pages'] : 'N/A';
                        $book_id = $row['id'];
                        $quantity = $row['available_quantity']; // Fetch available quantity from inventory table
                
                        // Handle image display
                        if (isset($row['image']) && !empty($row['image'])) {
                            // For BLOB data, create a data URI
                            $image_data = base64_encode($row['image']);
                            $image_src = 'data:image/jpeg;base64,' . $image_data;
                        } else {
                            // Fallback to default image
                            $image_src = '../uploads/default.jpg';
                        }

                        echo '<div class="book-card" onclick="goToBookDetails(' . $book_id . ')">';
                        echo '<span class="book-category">' . htmlspecialchars($genre) . '</span>';

                        echo '<div class="book-image-container">';
                        echo '<img src="' . htmlspecialchars($image_src) . '" alt="' . htmlspecialchars($title) . '">';
                        echo '</div>';

                        echo '<div class="book-info">';
                        echo '<h3 class="book-title">' . htmlspecialchars($title) . '</h3>';

                        echo '<div class="book-meta">';
                        echo '<span class="meta-label">Author:</span> ' . htmlspecialchars($author);
                        echo '</div>';

                        echo '<div class="book-meta">';
                        echo '<span class="meta-label">Pages:</span> ' . htmlspecialchars($page_count);
                        echo '</div>';

                        // Check availability based on quantity only
                        if ($quantity > 0) {
                            echo '<div class="availability-box available" data-book-id="' . $book_id . '">';
                            echo 'Available <span class="availability-count">' . $quantity . '</span>';
                            echo '</div>';
                        } else {
                            echo '<div class="availability-box not-available" data-book-id="' . $book_id . '">';
                            echo 'Not Available';
                            echo '</div>';
                        }

                        echo '</div>'; // Close book-info
                        echo '</div>'; // Close book-card
                    }
                } else {
                    echo '<div class="empty-state">';
                    echo '<p>No books found matching your search criteria.</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php
                // Previous page link
                $prev_page = ($page > 1) ? $page - 1 : 1;
                $prev_disabled = ($page == 1) ? 'disabled' : '';
                echo '<a href="?page=' . $prev_page . '&search=' . urlencode($search_query) . '&status=' . $status_filter . '" class="prev ' . $prev_disabled . '"><i class="fas fa-chevron-left"></i></a>';

                // Pages links
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                // Always show first page
                if ($start_page > 1) {
                    echo '<a href="?page=1&search=' . urlencode($search_query) . '&status=' . $status_filter . '">1</a>';
                    if ($start_page > 2) {
                        echo '<span class="ellipsis">...</span>';
                    }
                }

                // Show pages
                for ($i = $start_page; $i <= $end_page; $i++) {
                    $active = ($i == $page) ? 'active' : '';
                    echo '<a href="?page=' . $i . '&search=' . urlencode($search_query) . '&status=' . $status_filter . '" class="' . $active . '">' . $i . '</a>';
                }

                // Always show last page
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<span class="ellipsis">...</span>';
                    }
                    echo '<a href="?page=' . $total_pages . '&search=' . urlencode($search_query) . '&status=' . $status_filter . '">' . $total_pages . '</a>';
                }

                // Next page link
                $next_page = ($page < $total_pages) ? $page + 1 : $total_pages;
                $next_disabled = ($page == $total_pages) ? 'disabled' : '';
                echo '<a href="?page=' . $next_page . '&search=' . urlencode($search_query) . '&status=' . $status_filter . '" class="next ' . $next_disabled . '"><i class="fas fa-chevron-right"></i></a>';
                ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include('../includes/footer.php'); ?>

    <!-- Live Search and Navbar Toggle Scripts -->
    <script>
        // Function to navigate to book details page with book_id
        function goToBookDetails(bookId) {
            window.location.href = "book_details.php?book_id=" + bookId;
        }

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

        // Live search functionality
        $(document).ready(function () {
            // Variables for debounce
            let searchTimeout = null;

            // Function to load search results via AJAX
            function loadSearchResults() {
                const searchQuery = $('#search').val();
                const statusFilter = $('#status-filter').val();

                // Show loading indicator
                $('#loading-indicator').show();

                $.ajax({
                    url: 'search_books.php',
                    type: 'GET',
                    data: {
                        search: searchQuery,
                        status: statusFilter
                    },
                    dataType: 'json',
                    success: function (response) {
                        // Clear current books grid
                        $('#books-grid').empty();

                        if (response.books && response.books.length > 0) {
                            // Loop through books and add them to the grid
                            $.each(response.books, function (index, book) {
                                let bookCard = createBookCard(book);
                                $('#books-grid').append(bookCard);
                            });
                        } else {
                            // Display empty state message
                            $('#books-grid').html(
                                '<div class="empty-state">' +
                                '<p>No books found matching your search criteria.</p>' +
                                '</div>'
                            );
                        }

                        // Hide loading indicator
                        $('#loading-indicator').hide();
                    },
                    error: function (xhr, status, error) {
                        console.error("Error loading search results:", error);
                        $('#books-grid').html(
                            '<div class="empty-state">' +
                            '<p>Error loading results. Please try again later.</p>' +
                            '</div>'
                        );

                        // Hide loading indicator
                        $('#loading-indicator').hide();
                    }
                });
            }

            // Function to create a book card HTML
            function createBookCard(book) {
                const title = book.title || '';
                const author = book.author || '';
                const genre = book.genre || 'General';
                const page_count = book.pages ? book.pages : 'N/A';
                const book_id = book.id;
                const quantity = book.available_quantity || 0;
                const image_src = book.image_src || '../uploads/default.jpg';

                // Create availability class and text
                let availabilityClass = quantity > 0 ? 'available' : 'not-available';
                let availabilityText = quantity > 0 ?
                    'Available <span class="availability-count">' + quantity + '</span>' :
                    'Not Available';

                // Build HTML string for book card
                return `
                    <div class="book-card" onclick="goToBookDetails(${book_id})">
                        <span class="book-category">${escapeHTML(genre)}</span>
                        <div class="book-image-container">
                            <img src="${escapeHTML(image_src)}" alt="${escapeHTML(title)}">
                        </div>
                        <div class="book-info">
                            <h3 class="book-title">${escapeHTML(title)}</h3>
                            <div class="book-meta">
                                <span class="meta-label">Author:</span> ${escapeHTML(author)}
                            </div>
                            <div class="book-meta">
                                <span class="meta-label">Pages:</span> ${escapeHTML(page_count.toString())}
                            </div>
                            <div class="availability-box ${availabilityClass}" data-book-id="${book_id}">
                                ${availabilityText}
                            </div>
                        </div>
                    </div>
                `;
            }

            // Helper function to escape HTML and prevent XSS
            function escapeHTML(str) {
                if (!str) return '';
                return str
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            // Add input event listeners with debounce
            $('#search').on('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(loadSearchResults, 300); // 300ms delay
            });

            // Change event for status filter
            $('#status-filter').on('change', function () {
                loadSearchResults();
            });

            // Initial load if there's a search query
            if ($('#search').val() !== '') {
                loadSearchResults();
            }
        });
    </script>
</body>

</html>
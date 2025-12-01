<?php
include('../includes/db.php');
include('../includes/dashboard_header.php');

// Initialize variables
$bookData = null;
$locationData = null;
$error = "";
$searched = false;

// Process search if form is submitted
if (isset($_POST['search']) && !empty($_POST['searchTerm'])) {
    $searched = true;
    $searchTerm = $conn->real_escape_string($_POST['searchTerm']);

    // Add debugging to check connection and query
    if (!$conn) {
        $error = "Database connection failed: " . mysqli_connect_error();
    } else {
        // Improved query using prepared statements to prevent SQL injection
        $sql = "SELECT * FROM books WHERE 
                id = ? OR 
                title LIKE ? OR 
                author LIKE ? OR 
                serialno = ? OR 
                ISBN = ?
                LIMIT 1";

        // Prepare the statement
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            // Create search patterns for LIKE conditions
            $likePattern = '%' . $searchTerm . '%';

            // Bind the parameters (s = string)
            $stmt->bind_param("sssss", $searchTerm, $likePattern, $likePattern, $searchTerm, $searchTerm);

            // Execute the query
            $stmt->execute();

            // Get the result
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $bookData = $result->fetch_assoc();

                // Now get the location information using prepared statement
                $bookId = $bookData['id'];
                $locationSql = "SELECT section, call_no, rack_no FROM books_information WHERE book_id = ?";
                $locationStmt = $conn->prepare($locationSql);

                if ($locationStmt) {
                    $locationStmt->bind_param("s", $bookId);
                    $locationStmt->execute();
                    $locationResult = $locationStmt->get_result();

                    if ($locationResult && $locationResult->num_rows > 0) {
                        $locationData = $locationResult->fetch_assoc();
                    } else {
                        $error = "Location information not found for this book.";
                    }

                    $locationStmt->close();
                } else {
                    $error = "Database query preparation failed: " . $conn->error;
                }
            } else {
                $error = "No book found matching your search criteria.";
            }

            $stmt->close();
        } else {
            $error = "Database query preparation failed: " . $conn->error;
        }
    }
}

// Add debugging information if needed
$debug = "";
if (isset($_POST['search']) && empty($_POST['searchTerm'])) {
    $debug = "Search term was empty. Please enter a search term.";
}

// Get count of new reservations (UPDATED to include all statuses)
$new_reservations_query = "SELECT COUNT(*) as count FROM reservations WHERE status IN ('reserved', 'returned', 'cancelled', 'due paid') AND is_viewed = 0";
$new_reservations_result = $conn->query($new_reservations_query);
$new_reservations_count = 0;
if ($new_reservations_result && $row = $new_reservations_result->fetch_assoc()) {
    $new_reservations_count = $row['count'];
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="employee.css">
    <title>Book Location Finder</title>
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
            font-family:Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
            background-color: var(--ultra-light-green);
            margin: 0;
            padding: 0;
            color: var(--text-dark);
            background-image: linear-gradient(135deg, var(--ultra-light-green) 0%, var(--light-green) 100%);
            min-height: 100vh;
            padding-top: 245px; /* Reduced to match navbar height */
            transition: padding-top 0.3s ease; /* Add transition for smooth padding change */
        }

        /* Fixed Top Navbar */
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

        /* Secondary Navbar */
        .secondary-navbar {
            position: fixed;
            top: 285px; /* Position right below the top navbar */
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

        /* Content wrapper with transition */
        .content-wrapper {
            transition: padding-top 0.3s ease;
        }

        .content-wrapper.navbar-active {
            padding-top: 60px; /* Add padding when navbar is active */
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

        /* Fixed notification badge in navbar */
        .notification-badge {
            background-color: var(--error-red);
            color: white;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
        }

        .container {
            max-width: 800px;
            margin: 60px auto;
            background-color: var(--white);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(46, 106, 51, 0.1);
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
        }

        h1 {
            text-align: center;
            color: var(--main-green);
            margin-bottom: 30px;
        }

        .search-box {
            display: flex;
            margin-bottom: 30px;
        }

        .search-box input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid var(--light-green);
            border-right: none;
            border-radius: 4px 0 0 4px;
            font-size: 16px;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--main-green);
        }

        .search-box button {
            background-color: var(--main-green);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
        }

        .search-box button:hover {
            background-color: var(--dark-green);
        }

        .search-instructions {
            text-align: center;
            color: var(--text-light);
            margin-bottom: 20px;
        }

        .results-container {
            margin-top: 30px;
        }

        .book-info {
            padding: 20px;
            border-radius: 8px;
            background-color: var(--ultra-light-green);
            margin-bottom: 20px;
            border-left: 4px solid var(--main-green);
        }

        .book-title {
            font-size: 20px;
            font-weight: bold;
            color: var(--text-dark);
            margin-bottom: 15px;
        }

        .book-details {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .detail-item {
            background-color: var(--light-green);
            padding: 10px 15px;
            border-radius: 4px;
            font-size: 14px;
        }

        .detail-label {
            font-weight: bold;
            color: var(--text-medium);
        }

        .location-info {
            margin-top: 20px;
            padding: 15px;
            background-color: var(--light-green);
            border-radius: 8px;
            border-left: 4px solid var(--accent-green);
        }

        .location-title {
            color: var(--accent-green);
            font-weight: bold;
            margin-bottom: 10px;
        }

        .location-details {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .location-item {
            background-color: var(--ultra-light-green);
            padding: 10px 15px;
            border-radius: 4px;
            font-size: 14px;
        }

        .no-results {
            text-align: center;
            padding: 30px;
            background-color: var(--ultra-light-green);
            border-radius: 8px;
            color: var(--text-medium);
        }

        .debug-info {
            margin-top: 20px;
            padding: 10px;
            background-color: #ffe6e6;
            border-radius: 4px;
            font-size: 14px;
            color: var(--error-red);
        }
    </style>
</head>

<body>
    <!-- Navigation Bar Container -->
    <div class="navbar-toggle-container">
        <!-- Left section with toggle button and dashboard title -->
        <div class="navbar-brand">
            <button class="toggle-btn" id="navbarToggle" title="Toggle Menu">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>

        <!-- Right section with notification icon -->
        <div class="navbar-actions">
            <a href="notification.php" class="notification-icon" title="Notifications">
                <i class="fas fa-bell"></i>
                <?php if ($new_reservations_count > 0): ?>
                    <span class="badge"><?php echo $new_reservations_count; ?></span>
                <?php endif; ?>
            </a>
        </div>
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


    <!-- Content Wrapper - This div is crucial for the padding adjustments -->
    <div class="content-wrapper" id="contentWrapper">
        <div class="container">
            <h1>Book Location Finder</h1>

            <div class="search-instructions">
                Enter any book information (ID, Title, Author, Serial Number, or ISBN)
            </div>

            <form method="post" action="">
                <div class="search-box">
                    <input type="text" id="searchInput" name="searchTerm"
                        placeholder="Enter book ID, title, author, serial no, or ISBN..." autofocus
                        value="<?php echo isset($_POST['searchTerm']) ? htmlspecialchars($_POST['searchTerm']) : ''; ?>">
                    <button type="submit" id="searchButton" name="search">Find Location</button>
                </div>
            </form>

            <?php if (!empty($debug)): ?>
                <div class="debug-info"><?php echo $debug; ?></div>
            <?php endif; ?>

            <?php if ($searched): ?>
                <div class="results-container">
                    <?php if ($bookData && $locationData): ?>
                        <div class="book-info">
                            <div class="book-title"><?php echo htmlspecialchars($bookData['title']); ?></div>

                            <div class="book-details">
                                <div class="detail-item">
                                    <span class="detail-label">Author:</span>
                                    <span><?php echo htmlspecialchars($bookData['author']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">ISBN:</span>
                                    <span><?php echo htmlspecialchars($bookData['ISBN']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Serial No:</span>
                                    <span><?php echo htmlspecialchars($bookData['serialno']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Availability:</span>
                                    <span><?php echo htmlspecialchars($bookData['availability']); ?></span>
                                </div>
                            </div>

                            <div class="location-info">
                                <div class="location-title">Where to Find This Book:</div>
                                <div class="location-details">
                                    <div class="location-item">
                                        <span class="detail-label">Section:</span>
                                        <span><?php echo htmlspecialchars($locationData['section']); ?></span>
                                    </div>
                                    <div class="location-item">
                                        <span class="detail-label">Call Number:</span>
                                        <span><?php echo htmlspecialchars($locationData['call_no']); ?></span>
                                    </div>
                                    <div class="location-item">
                                        <span class="detail-label">Rack Number:</span>
                                        <span><?php echo htmlspecialchars($locationData['rack_no']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif (!empty($error)): ?>
                        <div class="no-results">
                            <h3>Book Information Not Found</h3>
                            <p><?php echo $error; ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include('../includes/footer.php'); ?>
    
    <script>
        // Navbar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('navbarToggle');
            const secondaryNavbar = document.getElementById('secondaryNavbar');
            const contentWrapper = document.getElementById('contentWrapper');
            
            if (toggleBtn && secondaryNavbar && contentWrapper) {
                toggleBtn.addEventListener('click', function() {
                    // Toggle the active class on the button and change icon
                    this.classList.toggle('active');
                    
                    // Toggle the secondary navbar
                    secondaryNavbar.classList.toggle('active');
                    
                    // Toggle the content-wrapper padding
                    contentWrapper.classList.toggle('navbar-active');
                });
            }
        });
    </script>
</body>

</html>
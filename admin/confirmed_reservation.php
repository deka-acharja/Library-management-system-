<?php
include('../includes/db.php');
include('../includes/dashboard_header.php');

// Get count of new reservations
$new_reservations_query = "SELECT COUNT(*) as count FROM reservations WHERE status IN ('reserved', 'returned', 'cancelled', 'due paid') AND is_viewed = 0";
$new_reservations_result = $conn->query($new_reservations_query);
$new_reservations_count = 0;
if ($new_reservations_result && $row = $new_reservations_result->fetch_assoc()) {
    $new_reservations_count = $row['count'];
}

// Count unread employee registrations
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
    <title>Library Management Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            --font-main: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --border-radius: 12px;
            --border-radius-lg: 20px;
            --box-shadow: 0 8px 32px rgba(46, 106, 51, 0.1);
            --box-shadow-hover: 0 12px 40px rgba(46, 106, 51, 0.15);
            --transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-main);
            background: linear-gradient(135deg, var(--ultra-light-green) 0%, var(--light-green) 50%, var(--ultra-light-green) 100%);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
        }
      /* Fixed Top Navbar */
        .navbar-toggle-container {
            position: fixed;
            top: 240px;
            left: 0;
            right: 0;
            z-index: 1000;
            background: linear-gradient(135deg, var(--main-green) 0%, var(--accent-green) 100%);
            box-shadow: var(--box-shadow);
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: var(--navbar-height);
            backdrop-filter: blur(10px);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
        }

        .toggle-btn {
            background: rgba(255, 255, 255, 0.2);
            color: var(--white);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: var(--transition);
            backdrop-filter: blur(5px);
        }

        .toggle-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: scale(1.05);
        }

        .toggle-btn i {
            transition: transform 0.3s ease;
        }

        .toggle-btn.active i {
            transform: rotate(180deg);
        }

        .dashboard-title {
            font-family: var(--font-main);
            font-size: 20px;
            color: var(--white);
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
            color: var(--white);
            font-size: 18px;
            margin-left: 15px;
            text-decoration: none;
            padding: 8px;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .notification-icon:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: linear-gradient(135deg, var(--error-red) 0%, #c0392b 100%);
            color: var(--white);
            font-size: 11px;
            font-weight: bold;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 2px solid var(--white);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(214, 69, 65, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(214, 69, 65, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(214, 69, 65, 0);
            }
        }

        /* Secondary Navbar */
        .secondary-navbar {
            position: fixed;
            top: 285px;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, var(--dark-green) 0%, var(--main-green) 100%);
            height: 0;
            overflow: hidden;
            transition: height 0.3s ease;
            z-index: 999;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
        }

        .secondary-navbar.active {
            height: 50px;
        }

        .navbar-links {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            gap: 5px;
            padding: 0 20px;
            flex-wrap: wrap;
        }

        .navbar-links a {
            color: var(--white);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 8px 12px;
            border-radius: var(--border-radius);
            transition: var(--transition);
            display: flex;
            align-items: center;
            white-space: nowrap;
        }

        .navbar-links a i {
            margin-right: 6px;
            font-size: 12px;
        }

        .navbar-links a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Toast notifications */
        .toast-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1100;
        }

        .toast {
            background: var(--white);
            border-left: 4px solid var(--main-green);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 15px;
            opacity: 0;
            padding: 15px 20px;
            transform: translateX(100%);
            transition: var(--transition);
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

        /* Main Content */
        .main-content {
            margin-left: 0;
            padding: 120px 2rem 2rem;
            transition: var(--transition);
            min-height: 100vh;
        }

        .content-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .content-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--main-green);
            margin-bottom: 0.5rem;
        }

        .content-subtitle {
            font-size: 1.1rem;
            color: var(--text-medium);
            font-weight: 400;
        }

        /* Action Cards */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .action-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 2.5rem;
            text-align: center;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--main-green), var(--accent-green));
        }

        .action-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--box-shadow-hover);
        }

        .card-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 32px;
            color: white;
            position: relative;
        }

        .issue-card .card-icon {
            background: linear-gradient(135deg, var(--success-green), #27ae60);
        }

        .return-card .card-icon {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .card-description {
            color: var(--text-medium);
            margin-bottom: 2rem;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            color: white;
            min-width: 180px;
            justify-content: center;
        }

        .issue-btn {
            background: linear-gradient(135deg, var(--success-green), #27ae60);
        }

        .issue-btn:hover {
            background: linear-gradient(135deg, #27ae60, var(--success-green));
            transform: scale(1.05);
        }

        .return-btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .return-btn:hover {
            background: linear-gradient(135deg, #2980b9, #3498db);
            transform: scale(1.05);
        }

        /* Overlay */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
            z-index: 998;
        }

        .overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header {
                padding: 0 1rem;
            }

            .main-content {
                padding: 100px 1rem 1rem;
            }

            .content-title {
                font-size: 2rem;
            }

            .action-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .action-card {
                padding: 2rem;
            }

            .sidebar {
                width: 280px;
                left: -280px;
            }
        }

        /* Loading Animation */
        .loading {
            opacity: 0;
            animation: fadeIn 0.6s ease-out forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Decorative Elements */
        .decorative-bg {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: -1;
        }

        .decorative-bg::before {
            content: '';
            position: absolute;
            top: 10%;
            right: 10%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, var(--accent-green) 0%, transparent 70%);
            opacity: 0.1;
            border-radius: 50%;
        }

        .decorative-bg::after {
            content: '';
            position: absolute;
            bottom: 10%;
            left: 10%;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, var(--main-green) 0%, transparent 70%);
            opacity: 0.1;
            border-radius: 50%;
        }
    </style>
</head>

<body>
        <!-- Toast Container for Notifications -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Fixed Top Navigation Bar -->
    <div class="navbar-toggle-container">
        <div class="navbar-brand">
           <button class="toggle-btn" id="navbarToggle" title="Toggle Menu">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>

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
    <!-- Overlay -->
    <div class="overlay" id="overlay"></div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-header loading">
            <h1 class="content-title">Library Management</h1>
            <p class="content-subtitle">Manage your library operations efficiently</p>
        </div>

        <div class="action-grid">
            <div class="action-card issue-card loading">
                <div class="card-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <h3 class="card-title">Issue Books</h3>
                <p class="card-description">Issue books to members and manage lending records with ease</p>
                <a href="issue_books.php" class="action-btn issue-btn">
                    <i class="fas fa-arrow-right"></i>
                    <span>Issue Books</span>
                </a>
            </div>

            <div class="action-card return-card loading">
                <div class="card-icon">
                    <i class="fas fa-undo-alt"></i>
                </div>
                <h3 class="card-title">Return Books</h3>
                <p class="card-description">Process book returns and update member records seamlessly</p>
                <a href="return_borrow_books.php" class="action-btn return-btn">
                    <i class="fas fa-arrow-right"></i>
                    <span>Return Books</span>
                </a>
            </div>
        </div>
    </main>

    <?php include('../includes/footer.php'); ?>

    <script>
         // Navbar toggle functionality
        document.getElementById('navbarToggle').addEventListener('click', function() {
            this.classList.toggle('active');
            const icon = this.querySelector('i');
            if (icon.classList.contains('fa-bars')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }

            const secondaryNavbar = document.getElementById('secondaryNavbar');
            secondaryNavbar.classList.toggle('active');

            const contentWrapper = document.getElementById('contentWrapper');
            contentWrapper.classList.toggle('navbar-active');
        });
        // Add loading animation stagger
        const loadingElements = document.querySelectorAll('.loading');
        loadingElements.forEach((element, index) => {
            element.style.animationDelay = `${index * 0.1}s`;
        });

        // Smooth scroll for better UX
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

        // Add hover effect to nav links
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(8px)';
            });
            
            link.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });
    </script>
    
</body>

</html>
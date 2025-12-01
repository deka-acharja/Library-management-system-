<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Library System - User Guide</title>
    <link rel="stylesheet" href="styles.css">
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--ultra-light-green) 0%, var(--light-green) 100%);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 2rem;
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
            background: var(--white);
            padding: 3rem 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(46, 106, 51, 0.1);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--main-green), var(--accent-green), var(--main-green));
        }

        .header h1 {
            color: var(--main-green);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(46, 106, 51, 0.1);
        }

        .header .subtitle {
            color: var(--text-medium);
            font-size: 1.2rem;
            font-weight: 400;
        }

        .section-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .section {
            background: var(--white);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(46, 106, 51, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--light-green);
        }

        .section:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(46, 106, 51, 0.15);
        }

        .section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-green), var(--main-green));
        }

        .section h2 {
            color: var(--main-green);
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-icon {
            font-size: 1.6rem;
            padding: 0.5rem;
            background: var(--ultra-light-green);
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .section p {
            color: var(--text-medium);
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        .feature-list {
            list-style: none;
            padding: 0;
        }

        .feature-list li {
            position: relative;
            padding: 0.8rem 0 0.8rem 2.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-medium);
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .feature-list li:hover {
            background: var(--ultra-light-green);
            transform: translateX(5px);
        }

        .feature-list li::before {
            content: '✓';
            position: absolute;
            left: 0.8rem;
            top: 0.8rem;
            color: var(--accent-green);
            font-weight: bold;
            font-size: 1.1rem;
        }

        .nested-list {
            margin-top: 0.5rem;
            margin-left: 1rem;
        }

        .nested-list li {
            padding-left: 1.5rem;
            font-size: 0.95rem;
            color: var(--text-light);
        }

        .nested-list li::before {
            content: '→';
            color: var(--text-light);
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--light-green);
            color: var(--main-green);
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .user-role {
            background: var(--ultra-light-green);
            border: 2px solid var(--accent-green);
        }

        .admin-role {
            background: var(--light-green);
            border: 2px solid var(--main-green);
        }

        .highlight-section {
            background: linear-gradient(135deg, var(--ultra-light-green), var(--white));
            border: 2px solid var(--light-green);
        }

        .notification-types {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .notification-card {
            background: var(--ultra-light-green);
            padding: 1rem;
            border-radius: 10px;
            border-left: 4px solid var(--accent-green);
            text-align: center;
        }

        .notification-card h4 {
            color: var(--main-green);
            margin-bottom: 0.5rem;
        }

        .notification-card p {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .header h1 {
                font-size: 2rem;
            }

            .section-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        .footer {
            text-align: center;
            padding: 2rem;
            color: var(--text-light);
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <?php include('includes/header.php'); ?>
    
    <div class="container">
        <div class="header">
            <h1>📚 Library Management System</h1>
            <p class="subtitle">Complete User Guide & Feature Overview</p>
        </div>

        <div class="section-grid">
            <div class="section">
                <h2><span class="section-icon">🏠</span>Home Page</h2>
                <p>The homepage provides a comprehensive overview of the library system with intuitive navigation to all essential sections including gallery, authentication, and user dashboard.</p>
            </div>

            <div class="section">
                <h2><span class="section-icon">🎨</span>Gallery</h2>
                <ul class="feature-list">
                    <li>Browse extensive book categories and collections</li>
                    <li>Interactive category navigation with detailed book listings</li>
                    <li>Comprehensive book information including descriptions, titles, and real-time availability</li>
                </ul>
            </div>

            <div class="section">
                <h2><span class="section-icon">🔐</span>Authentication System</h2>
                <ul class="feature-list">
                    <li>Streamlined registration process with role selection</li>
                    <li>Choose between <strong>User</strong> and <strong>Admin</strong> roles</li>
                    <li>Secure login system with username and password authentication</li>
                </ul>
            </div>
        </div>

        <div class="section-grid">
            <div class="section">
                <div class="role-badge user-role">
                    👤 User Role Features
                </div>
                <ul class="feature-list">
                    <li>Browse and search available books in the library catalog</li>
                    <li>Reserve books with real-time availability checking</li>
                    <li>Manage reservations with cancellation options (pre-confirmation)</li>
                    <li>Personal dashboard showing reserved, overdue, and borrowed books</li>
                    <li>Online fine payment system for overdue books</li>
                    <li>Direct feedback submission to library administration</li>
                </ul>
            </div>

            <div class="section">
                <div class="role-badge admin-role">
                    🛠️ Admin Role Features
                </div>
                <ul class="feature-list">
                    <li>Complete book management: view, add, edit, delete (individual & bulk operations)</li>
                    <li>Book circulation: issue books to users and process returns</li>
                    <li>Inventory management: mark books as lost or damaged</li>
                    <li>Reservation management: confirm, reject, and issue reserved books</li>
                    <li>Comprehensive oversight of all reservations and status tracking</li>
                    <li>Overdue book management and fine administration</li>
                    <li>Automated email notification system:
                        <ul class="nested-list">
                            <li>Reservation confirmations and rejections</li>
                            <li>Book issue and return notifications</li>
                            <li>Automatic overdue reminders (3+ days past due)</li>
                        </ul>
                    </li>
                    <li>Complete payment and book status tracking</li>
                </ul>
            </div>
        </div>

        <div class="section-grid">
            <div class="section highlight-section">
                <h2><span class="section-icon">📧</span>Notification System</h2>
                <ul class="feature-list">
                    <li>Automated email notifications for all critical library actions</li>
                    <li>Smart reminder system: automatic emails sent 3+ days after due date</li>
                    <li>Admin dashboard notifications for real-time system monitoring</li>
                </ul>
                
                <div class="notification-types">
                    <div class="notification-card">
                        <h4>User Actions</h4>
                        <p>Registration, reservations, cancellations</p>
                    </div>
                    <div class="notification-card">
                        <h4>Book Management</h4>
                        <p>Issues, returns, overdue alerts</p>
                    </div>
                    <div class="notification-card">
                        <h4>Financial</h4>
                        <p>Fine payments, fee notifications</p>
                    </div>
                    <div class="notification-card">
                        <h4>Feedback</h4>
                        <p>User submissions, admin responses</p>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2><span class="section-icon">💬</span>Feedback System</h2>
                <ul class="feature-list">
                    <li>24/7 user feedback submission for issues and suggestions</li>
                    <li>Admin feedback management: view, resolve, and archive</li>
                    <li>Streamlined communication between users and administration</li>
                </ul>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>
</body>

</html>
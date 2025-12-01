<?php
include('../includes/db.php');
include('../includes/dashboard_header.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Issue Options - Library Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
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

        body {
            background: linear-gradient(135deg, var(--ultra-light-green) 0%, var(--light-green) 100%);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
            background: linear-gradient(135deg, var(--main-green) 0%, var(--dark-green) 100%);
            color: var(--white);
            padding: 50px 20px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(46, 106, 51, 0.2);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
            transform: translateX(-100%);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .page-title {
            font-size: 2.8rem;
            margin: 0 0 15px 0;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .page-subtitle {
            font-size: 1.3rem;
            margin: 0;
            opacity: 0.95;
            font-weight: 300;
        }

        .options-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 35px;
            margin-top: 40px;
        }

        .option-card {
            background: var(--white);
            border-radius: 20px;
            padding: 45px 35px;
            text-align: center;
            box-shadow: 0 12px 35px rgba(46, 106, 51, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 3px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .option-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--main-green), var(--accent-green));
        }

        .option-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, var(--ultra-light-green) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 0;
        }

        .option-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 25px 50px rgba(46, 106, 51, 0.2);
            border-color: var(--accent-green);
        }

        .option-card:hover::after {
            opacity: 1;
        }

        .option-card > * {
            position: relative;
            z-index: 1;
        }

        .option-icon {
            font-size: 4.5rem;
            margin-bottom: 30px;
            background: linear-gradient(135deg, var(--main-green), var(--accent-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(2px 2px 4px rgba(46, 106, 51, 0.2));
        }

        .option-title {
            font-size: 2rem;
            margin-bottom: 20px;
            color: var(--text-dark);
            font-weight: 700;
        }

        .option-description {
            font-size: 1.15rem;
            color: var(--text-medium);
            margin-bottom: 35px;
            line-height: 1.7;
            font-weight: 400;
        }

        .option-button {
            display: inline-block;
            background: linear-gradient(135deg, var(--main-green) 0%, var(--accent-green) 100%);
            color: var(--white);
            text-decoration: none;
            padding: 18px 40px;
            border-radius: 50px;
            font-size: 1.15rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(46, 106, 51, 0.3);
            position: relative;
            overflow: hidden;
        }

        .option-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .option-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(46, 106, 51, 0.4);
            text-decoration: none;
            color: var(--white);
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--main-green) 100%);
        }

        .option-button:hover::before {
            left: 100%;
        }

        .option-button i {
            margin-right: 12px;
        }

        .features-list {
            list-style: none;
            padding: 0;
            margin: 25px 0 35px 0;
            text-align: left;
        }

        .features-list li {
            padding: 10px 0;
            color: var(--text-medium);
            position: relative;
            padding-left: 30px;
            font-weight: 500;
        }

        .features-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--success-green);
            font-weight: bold;
            font-size: 1.1rem;
            background: var(--ultra-light-green);
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .options-container {
                grid-template-columns: 1fr;
            }
            
            .page-title {
                font-size: 2.2rem;
            }
            
            .option-card {
                padding: 35px 25px;
            }

            .options-container {
                grid-template-columns: 1fr;
                gap: 25px;
            }
        }

        .breadcrumb {
            margin-bottom: 25px;
            color: var(--text-light);
            background: var(--white);
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(46, 106, 51, 0.1);
            font-weight: 500;
        }

        .breadcrumb a {
            color: var(--main-green);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .breadcrumb a:hover {
            color: var(--accent-green);
            text-decoration: underline;
        }

        .breadcrumb i {
            color: var(--accent-green);
            margin-right: 8px;
        }

        /* Enhanced animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .option-card {
            animation: fadeInUp 0.6s ease forwards;
        }

        /* Floating animation for icons */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .option-icon {
            animation: float 3s ease-in-out infinite;
        }

        .option-card:nth-child(2) .option-icon {
            animation-delay: 1.5s;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="breadcrumb">
            <i class="fas fa-home"></i>
            <a href="../dashboard.php">Dashboard</a> / Book Issue Options
        </div>

        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-books"></i>
                Book Issue Management
            </h1>
            <p class="page-subtitle">Choose how you want to issue books to library users</p>
        </div>

        <div class="options-container">
            <!-- Issue Books for Borrower -->
            <div class="option-card">
                <div class="option-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <h2 class="option-title">Issue Books for Borrower</h2>
                <p class="option-description">
                    Issue books directly to library users for immediate borrowing. Perfect for walk-in users who want to borrow books right away.
                </p>
                <ul class="features-list">
                    <li>Search users by CID</li>
                    <li>Select available books</li>
                    <li>Set borrowing and return dates</li>
                    <li>Instant book availability update</li>
                </ul>
                <a href="issue_books.php" class="option-button">
                    <i class="fas fa-arrow-right"></i>
                    Issue for Borrower
                </a>
            </div>

            <!-- Issue Books for Reservation -->
            <div class="option-card">
                <div class="option-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h2 class="option-title">Issue Books for Reservation</h2>
                <p class="option-description">
                    Process reserved books and issue them to users who have made advance reservations. Manage your reservation queue efficiently.
                </p>
                <ul class="features-list">
                    <li>View pending reservations</li>
                    <li>Process reservation queue</li>
                    <li>Convert reservations to issues</li>
                    <li>Automated notification system</li>
                </ul>
                <a href="reserved_books_issue.php" class="option-button">
                    <i class="fas fa-arrow-right"></i>
                    Issue for Reservation
                </a>
            </div>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

    <script>
        // Add smooth scrolling and animation effects
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.option-card');
            
            // Add staggered animation to cards
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(40px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 300);
            });

            // Add click tracking for analytics (optional)
            document.querySelectorAll('.option-button').forEach(button => {
                button.addEventListener('click', function(e) {
                    const option = this.textContent.trim();
                    console.log(`User selected: ${option}`);
                    
                    // Add loading state with green theme
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                    this.style.pointerEvents = 'none';
                    this.style.background = 'linear-gradient(135deg, var(--text-light), var(--text-medium))';
                    
                    // Allow navigation after brief delay
                    setTimeout(() => {
                        window.location.href = this.href;
                    }, 600);
                    
                    e.preventDefault();
                });
            });

            // Add subtle parallax effect to header
            window.addEventListener('scroll', function() {
                const header = document.querySelector('.page-header');
                const scrolled = window.pageYOffset;
                const rate = scrolled * -0.5;
                header.style.transform = `translateY(${rate}px)`;
            });
        });

        // Add keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === '1') {
                window.location.href = 'issue_books.php';
            } else if (e.key === '2') {
                window.location.href = 'reserved_books_issue.php';
            }
        });

        // Add hover sound effect (optional)
        document.querySelectorAll('.option-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                // You can add a subtle sound effect here if desired
                this.style.transform = 'translateY(-15px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>

</html>
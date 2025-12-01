<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Royal Institute of Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            /* Add top padding to account for fixed navigation bars */
            padding-top: 260px; /* Adjust this value based on your total nav height */
        }

        /* Top contact bar - Fixed */
        .top-contact {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(135deg,rgb(17, 73, 17),rgb(11, 65, 25));
            color: white;
            padding: 8px 20px;
            text-align: center;
            font-size: 14px;
            z-index: 1003; /* Highest z-index */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .contact-text a {
            text-decoration: none;
            color: inherit;
            transition: color 0.3s ease;
        }

        .contact-text a:hover {
            color: #ffd700;
        }

        .contact-text i {
            margin-right: 5px;
        }

        /* Main Navigation bar - Fixed */
        .navbar {
            position: fixed;
            top: 34px; /* Height of top-contact bar */
            left: 0;
            width: 100%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1002;
            border-bottom: 3px solid #1e3c72;
        }

        .nav-left, .nav-right {
            flex: 0 0 auto;
        }

        .logo {
            height: 60px;
            width: auto;
            object-fit: contain;
        }

        .nav-center {
            flex: 1;
            text-align: center;
            padding: 0 20px;
        }

        .dzongkha-text {
            font-size: 18px;
            color:rgb(7, 7, 7);
            margin-bottom: 5px;
            font-weight: bold;
        }

        .site-title {
            font-size: 32px;
            font-weight: bold;
            color:rgb(0, 12, 3);
            margin-bottom: 5px;
            letter-spacing: 1px;
        }

        .highlight {
            color: #e74c3c;
        }

        .subtitle {
            font-size: 14px;
            color:rgb(51, 156, 76) ;
            font-style: italic;
            text-transform: lowercase;
        }

        /* Small Navigation Bar - Fixed */
        .small-nav {
            position: fixed;
            top: 232px; /* Adjust based on combined height of top bars */
            left: 0;
            width: 100%;
            background: linear-gradient(135deg,rgb(34, 83, 42),rgb(38, 99, 43));
            display: flex;
            justify-content: center;
            padding: 12px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 1001;
            font-family:Georgia, 'Times New Roman', Times, serif;
        }

        .small-nav a {
            color: white;
            text-decoration: none;
            padding: 10px 25px;
            margin: 0 5px;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .small-nav a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .small-nav a i {
            font-size: 16px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding-top: 200px; /* Increase for mobile */
            }

            .navbar {
                flex-direction: column;
                padding: 10px 15px;
                top: 34px;
            }

            .nav-center {
                padding: 10px 0;
            }

            .site-title {
                font-size: 24px;
            }

            .dzongkha-text {
                font-size: 16px;
            }

            .logo {
                height: 40px;
            }

            .small-nav {
                top: 360px; /* Adjust for mobile layout */
                flex-wrap: wrap;
                padding: 8px;
            }

            .small-nav a {
                padding: 8px 15px;
                margin: 2px;
                font-size: 14px;
            }

            .content {
                padding: 20px 15px;
            }
        }

        @media (max-width: 480px) {
            .small-nav {
                flex-direction: column;
                align-items: center;
            }

            .small-nav a {
                margin: 2px 0;
                width: 200px;
                justify-content: center;
            }

            body {
                padding-top: 250px;
            }

            .small-nav {
                top: 160px;
            }
        }
    </style>
</head>
<body>
    <!-- Top contact info bar -->
    <div class="top-contact">
        <span class="contact-text">
            <a href="https://www.rim.edu.bt/" target="_blank">
                <i class="fas fa-envelope"></i> www.rim.edu.bt |
            </a>
            <i class="fas fa-phone"></i> +975-12345678
        </span>
    </div>

    <!-- Main Navigation bar -->
    <div class="navbar">
        <div class="nav-left">
            <a href="index.php">
                <img src="images/left-logo.png" alt="Left Logo" class="logo">
            </a>
        </div>
        <div class="nav-center">
            <h2 class="dzongkha-text">༄༅།། རྒྱལ་གཞུང་འཛིན་སྐྱོང་སློབ་སྡེ།</h2>
            <h1 class="site-title">
                ROYAL INSTITUTE OF <span class="highlight">MANAGEMENT</span>
            </h1>
            <p class="subtitle">management for growth &amp; development</p>
        </div>
        <div class="nav-right">
            <img src="images/right-logo.png" alt="Right Logo" class="logo">
        </div>
    </div>

    <!-- Small Navigation Bar with Icons -->
    <div class="small-nav">
        <a href="index.php"><i class="fas fa-home"></i> Home</a>
        <a href="gallery.php"><i class="fas fa-images"></i> Gallery</a>
        <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
        <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
        <a href="guide.php"><i class="fas fa-book"></i> User Guide</a>
    </div>

</body>
</html>
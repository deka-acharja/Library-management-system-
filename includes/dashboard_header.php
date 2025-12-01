<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Royal Institute of Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* General Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            color: #333;
            background-color: #f8f9fa;
            /* Add padding to prevent content from being hidden under fixed header */
            padding-top: 245px;
            /* Increased to accommodate contact bar */
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        h1,
        h2,
        h3,
        p {
            margin-bottom: 15px;
        }

        /* Fixed Header Styles */
        .header-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 9999;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: top 0.3s;
        }

        /* Top Contact Bar - FIXED */
        .top-contact {
            background: linear-gradient(135deg, rgb(38, 124, 38), rgb(11, 65, 25));
            color: white;
            padding: 10px 30px;
            width: 100%;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .contact-text {
            font-size: 14px;
        }

        .contact-text i {
            margin-right: 8px;
            margin-left: 15px;
        }

        .contact-text i:first-child {
            margin-left: 0;
        }

        /* Navigation Bar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #fdfeff;
            color: rgb(10, 10, 10);
            padding: 15px 30px;
            border-bottom: 2px solid var( --accent-green);
        }

        .nav-left img,
        .nav-right img {
            height: 160px;
        }

        .nav-center {
            text-align: center;
            color: #080808;
        }

        .site-title {
            font-size: 2rem;
            font-weight: bold;
        }

        .dzongkha-text {
            font-size: 1.6rem;
            margin-bottom: 10px;
            text-align: center;
        }

        .subtitle {
            font-size: 1.1rem;
            font-style: normal;
            color: #1e7e34;  /* Medium green */
        }

        .highlight {
            color: #155724;  /* Dark green */
            font-weight: bold;
        }

        /* Main Content Section */
        .main-content {
            padding: 20px;
            margin-top: 20px;
            border-bottom: 2px solid var( --accent-green);
        }

        /* Demo content to show the fixed header working */
        .demo-content {
            height: 2000px;
            background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
            padding: 40px;
            margin: 20px;
            border-radius: 10px;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            body {
                padding-top: 190px;
            }

            .navbar {
                flex-direction: column;
                text-align: center;
                padding: 10px 15px;
            }

            .nav-left img,
            .nav-right img {
                height: 120px;
            }

            .nav-center {
                margin: 10px 0;
            }

            .site-title {
                font-size: 1.5rem;
            }

            .dzongkha-text {
                font-size: 1.2rem;
            }

            .top-contact {
                padding: 8px 15px;
                justify-content: center;
                text-align: center;
            }

            .contact-text {
                font-size: 12px;
            }

            .contact-text i {
                margin-left: 10px;
            }
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <div class="header-wrapper">
        <!-- Top contact info bar - FIXED -->
        <div class="top-contact">
            <div class="contact-text">
                <i class="fas fa-envelope"></i>www.rim.edu.bt
                <i class="fas fa-phone"></i>+975-12345678
            </div>
        </div>

        <!-- Main Navigation bar -->
        <div class="navbar">
            <div class="nav-left">
                <img src="../images/left-logo.png" alt="Left Logo" class="logo">
            </div>
            <div class="nav-center">
                <h2 class="dzongkha-text"> ༄༅།། རྒྱལ་གཞུང་འཛིན་སྐྱོང་སློབ་སྡེ། </h2>
                <h1 class="site-title">
                    ROYAL INSTITUTE OF <span class="highlight">MANAGEMENT</span>
                </h1>
                <p class="subtitle">management for growth &amp; development</p>
            </div>
            <div class="nav-right">
                <img src="../images/right-logo1.png" alt="Right Logo" class="logo">
            </div>
        </div>
    </div>

    <!-- JavaScript for keeping header visible -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Always keep the header visible regardless of scroll position
            window.onscroll = function () {
                document.querySelector(".header-wrapper").style.top = "0";
            };
        });
    </script>
</body>

</html>
<?php
session_start(); // Start the session to handle flash messages

// Always generate a new CSRF token to prevent reuse
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Library Management System</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="app.css">
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
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--ultra-light-green);
            color: var(--text-dark);
            line-height: 1.6;
        }

        .forgot-page {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 3rem 1rem;
        }

        .forgot-box {
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.1);
            padding: 2.5rem;
            width: 100%;
            max-width: 450px;
            transition: all 0.3s ease;
        }

        .forgot-box:hover {
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
        }

        .forgot-box h2 {
            color: var(--main-green);
            text-align: center;
            margin-bottom: 1.5rem;
            font-weight: 600;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .forgot-box h2 i {
            margin-right: 10px;
        }

        .forgot-box p {
            text-align: center;
            margin-bottom: 1.5rem;
            color: var(--text-medium);
        }

        .input-container {
            margin-bottom: 1.25rem;
            position: relative;
        }

        .input-container label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-dark);
        }

        .input-container input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--light-green);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .input-container input:focus {
            border-color: var(--main-green);
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 106, 51, 0.2);
        }

        .submit-btn {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--main-green);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .submit-btn:hover {
            background-color: var(--dark-green);
            transform: translateY(-2px);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .error-message {
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            background-color: rgba(214, 69, 65, 0.1);
            color: var(--error-red);
            border-left: 4px solid var(--error-red);
            text-align: center;
        }

        .success-message {
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-green);
            border-left: 4px solid var(--success-green);
            text-align: center;
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-medium);
        }

        .back-link a {
            color: var(--main-green);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .back-link a:hover {
            color: var(--dark-green);
            text-decoration: underline;
        }

        @media (max-width: 576px) {
            .forgot-box {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>

    <!-- Include Header -->
    <?php include('../includes/dashboard_header.php'); ?>

    <div class="forgot-page">
        <div class="forgot-box">
            <h2><i class="fas fa-key"></i> Forgot Password</h2>
            <p>Please enter your username and email address to reset your password.</p>

            <!-- Error Message -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <!-- Success Message -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <form action="send_verification.php" method="POST" id="forgotPasswordForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="verification_method" value="email">

                <div class="input-container">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" required placeholder="Enter your username">
                </div>

                <div class="input-container">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" required placeholder="Enter your email address">
                </div>

                <button type="submit" class="submit-btn">Send Verification Code</button>
            </form>

            <div class="back-link">
                <a href="../login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
            </div>
        </div>
    </div>
    <?php include('../includes/footer.php'); ?>
</body>
</html>
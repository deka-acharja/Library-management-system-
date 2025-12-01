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
    <title>Login - Library Management System</title>
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
            /* font-family: 'Poppins', sans-serif; */
        }
        
        body {
            background-color: var(--ultra-light-green);
            color: var(--text-dark);
            line-height: 1.6;
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
        }
        
        .login-page {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 3.5rem 1rem;
        }
        
        .login-box {
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.1);
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            transition: all 0.3s ease;
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
        }
        
        .login-box:hover {
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
        }
        
        .login-box h2 {
            color: var(--main-green);
            text-align: center;
            margin-bottom: 1.5rem;
            font-weight: 600;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-box h2 i {
            margin-right: 10px;
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
        
        .login-btn {
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
            margin-top: 0.5rem;
        }
        
        .login-btn:hover {
            background-color: var(--dark-green);
            transform: translateY(-2px);
        }
        
        .login-btn:active {
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
        
        .forgot-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-medium);
        }
        
        .forgot-link a {
            color: var(--main-green);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .forgot-link a:hover {
            color: var(--dark-green);
            text-decoration: underline;
        }
        
        /* Modal Styles */
        .modal {
            position: fixed;
            top: 80px;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background-color: var(--white);
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            text-align: center;
            transform: translateY(-20px);
            transition: all 0.3s ease;
        }
        
        .modal.active .modal-content {
            transform: translateY(0);
        }
        
        .modal-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: inline-block;
            padding: 1rem;
            border-radius: 50%;
        }
        
        .warning-icon {
            color: var(--warning-yellow);
            background-color: rgba(243, 156, 18, 0.1);
        }
        
        .error-icon {
            color: var(--error-red);
            background-color: rgba(214, 69, 65, 0.1);
        }
        
        .success-icon {
            color: var(--success-green);
            background-color: rgba(46, 204, 113, 0.1);
        }
        
        .info-icon {
            color: var(--text-light);
            background-color: rgba(107, 143, 112, 0.1);
        }
        
        .modal-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-dark);
        }
        
        .modal-text {
            margin-bottom: 1.5rem;
            color: var(--text-medium);
        }
        
        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        
        .btn-primary {
            background-color: var(--main-green);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background-color: var(--dark-green);
        }
        
        .btn-danger {
            background-color: var(--error-red);
            color: var(--white);
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .btn-secondary {
            background-color: var(--light-green);
            color: var(--text-dark);
        }
        
        .btn-secondary:hover {
            background-color: #b8d4bb;
        }
        
        @media (max-width: 576px) {
            .login-box {
                padding: 1.5rem;
            }
            
            .modal-content {
                padding: 1.5rem;
            }
            
            .modal-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

    <!-- Include Header -->
    <?php include('includes/header.php'); ?>

    <div class="login-page">
        <div class="login-box">
            <h2><i class="fas fa-sign-in-alt"></i> Log in</h2>

            <form action="login_submit.php" method="POST">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="input-container">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" required placeholder="Enter your username">
                </div>

                <div class="input-container">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required placeholder="Enter your password">
                </div>

                <button type="submit" class="login-btn">Log in</button>
            </form>

            <p class="forgot-link"><a href="forgot_password/forgot_password.php">Forgotten your username or password?</a></p>
        </div>
    </div>

    <!-- Status Modal -->
    <?php if (isset($_SESSION['error_type']) && isset($_SESSION['error_title']) && isset($_SESSION['error_message'])): ?>
    <div id="statusModal" class="modal active">
        <div class="modal-content">
            <div class="modal-icon <?php echo $_SESSION['error_type']; ?>-icon">
                <i class="fas <?php 
                    if ($_SESSION['error_type'] === 'error') {
                        echo 'fa-exclamation-circle';
                    } elseif ($_SESSION['error_type'] === 'warning') {
                        echo 'fa-exclamation-triangle';
                    } elseif ($_SESSION['error_type'] === 'success') {
                        echo 'fa-check-circle';
                    } else {
                        echo 'fa-info-circle';
                    }
                ?>"></i>
            </div>
            <h3 class="modal-title"><?php echo htmlspecialchars($_SESSION['error_title']); ?></h3>
            <p class="modal-text"><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
            <div class="modal-buttons">
                <button type="button" id="closeModalBtn" class="btn btn-primary">OK</button>
            </div>
        </div>
    </div>
    <?php 
        // Clear the error session variables
        unset($_SESSION['error_type']);
        unset($_SESSION['error_title']);
        unset($_SESSION['error_message']);
    ?>
    <?php endif; ?>

    <!-- Include Footer -->
    <?php include('includes/footer.php'); ?>

    <script>
        // Modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            const statusModal = document.getElementById('statusModal');
            if (statusModal) {
                const closeModalBtn = document.getElementById('closeModalBtn');
                
                closeModalBtn.addEventListener('click', function() {
                    statusModal.classList.remove('active');
                });
            }
        });
    </script>

</body>

</html>
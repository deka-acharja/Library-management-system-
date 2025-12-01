<?php
session_start();
// Include PHPMailer classes
require_once '../PHPMailer/src/Exception.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if user has a valid reset session
if (!isset($_SESSION['reset_user_id'])) {
    $_SESSION['error'] = "Invalid session. Please start the password reset process again.";
    header("Location: forgot_password.php");
    exit();
}

// Generate a new CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// DB Connection
include('../includes/db.php');

// Check DB connection
if (mysqli_connect_errno()) {
    $_SESSION['error'] = "Failed to connect to database: " . mysqli_connect_error();
    header("Location: verify_code.php");
    exit();
}

// Get user information
$user_id = $_SESSION['reset_user_id'];
$query = "SELECT id, email, name FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    $_SESSION['error'] = "Database error: " . mysqli_error($conn);
    header("Location: verify_code.php");
    exit();
}
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    $_SESSION['error'] = "User not found. Please start over.";
    header("Location: forgot_password.php");
    exit();
}

// Handle form submission (resend request)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF Token check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Security validation failed. Please try again.";
        header("Location: resend.php");
        exit();
    }

    // Generate new verification code and expiry time (1 minute)
    $verification_code = sprintf("%06d", mt_rand(100000, 999999));
    $expiry_time = date('Y-m-d H:i:s', strtotime('+1 minute'));

    // Update code and expiry in database
    $update_query = "UPDATE users SET reset_code = ?, reset_expires = ? WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    if (!$update_stmt) {
        $_SESSION['error'] = "Failed to prepare update query: " . mysqli_error($conn);
        header("Location: resend.php");
        exit();
    }
    mysqli_stmt_bind_param($update_stmt, "ssi", $verification_code, $expiry_time, $user['id']);
    $update_result = mysqli_stmt_execute($update_stmt);

    if (!$update_result) {
        $_SESSION['error'] = "Failed to update reset information: " . mysqli_stmt_error($update_stmt);
        header("Location: resend.php");
        exit();
    }

    /**
     * Function to send email using PHPMailer
     */
    function sendEmail($recipientEmail, $recipientName, $subject, $body) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = '11206005480@rim.edu.bt'; // Replace with your email
            $mail->Password = 'wsgm gyrg bgbl vynx';    // Replace with your app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('11206005480@rim.edu.bt', 'Library Management System');
            $mail->addAddress($recipientEmail, $recipientName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();
            error_log("Email sent to $recipientEmail: $subject");
            return true;
        } catch (Exception $e) {
            error_log("Email Error: " . $mail->ErrorInfo);
            return false;
        }
    }

    // Prepare email content
    $subject = "New Password Reset Verification Code - Library Management System";
    $body = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color:rgb(60, 104, 49);
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .header h1 {
            color: white;
            margin: 0;
            font-size: 24px;
        }
        .content {
            background-color: #ffffff;
            padding: 30px;
            border-left: 1px solid #eeeeee;
            border-right: 1px solid #eeeeee;
        }
        .verification-code {
            background-color: #f5f5f5;
            font-size: 28px;
            font-weight: bold;
            color:rgb(21, 77, 44);
            padding: 15px;
            text-align: center;
            margin: 20px 0;
            letter-spacing: 5px;
            border-radius: 5px;
        }
        .note {
            font-size: 14px;
            color: #777777;
            margin-top: 20px;
        }
        .footer {
            background-color: #f5f5f5;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #777777;
            border-radius: 0 0 5px 5px;
        }
        .resend-notice {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Library Management System</h1>
        </div>
        <div class="content">
            <div class="resend-notice">
                <strong>New Verification Code</strong> - This is a resent verification code.
            </div>
            
            <p>Hello ' . ($user['name'] ?? 'User') . ',</p>
            <p>Here is your new verification code for password reset:</p>
            
            <div class="verification-code">' . $verification_code . '</div>
            
            <p>This code will expire in <strong>1 minute</strong>.</p>
            
            <p class="note">If you didn\'t request this password reset, you can safely ignore this email.</p>
        </div>
        <div class="footer">
            <p>&copy; ' . date('Y') . ' Library Management System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
';

    // Send email
    if (sendEmail($user['email'], $user['name'] ?? 'User', $subject, $body)) {
        $_SESSION['success'] = "A new verification code has been sent to your email.";
        header("Location: verify_code.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to send email. Please try again later.";
        header("Location: resend.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Code - Library Management System</title>
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
        
        .resend-page {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
            padding: 3rem 1rem;
        }
        
        .resend-box {
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.1);
            padding: 2.5rem;
            width: 100%;
            max-width: 450px;
            transition: all 0.3s ease;
        }
        
        .resend-box:hover {
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
        }
        
        .resend-box h2 {
            color: var(--main-green);
            text-align: center;
            margin-bottom: 1.5rem;
            font-weight: 600;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .resend-box h2 i {
            margin-right: 10px;
        }
        
        .resend-box p {
            text-align: center;
            margin-bottom: 1.5rem;
            color: var(--text-medium);
        }
        
        .email-info {
            background-color: var(--ultra-light-green);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            border-left: 4px solid var(--main-green);
        }
        
        .email-info strong {
            color: var(--main-green);
            font-weight: 600;
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
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .submit-btn:hover:not(:disabled) {
            background-color: var(--dark-green);
            transform: translateY(-2px);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .submit-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .submit-btn .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 8px;
            display: none;
        }
        
        .submit-btn.loading .spinner {
            display: inline-block;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
            animation: fadeIn 0.3s ease;
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
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .back-link {
            text-align: center;
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
        
        .cooldown {
            text-align: center;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: var(--text-medium);
        }
        
        .cooldown.active {
            color: var(--warning-yellow);
            font-weight: 600;
        }
        
        @media (max-width: 576px) {
            .resend-box {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .resend-box h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>

    <!-- Include Header -->
    <?php include('../includes/dashboard_header.php'); ?>

    <div class="resend-page">
        <div class="resend-box">
            <h2><i class="fas fa-paper-plane"></i> Resend Code</h2>

            <!-- Show error message if any -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message" id="errorMessage">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Show success message if any -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-message" id="successMessage">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <p>Your verification code has expired. Click the button below to receive a new 6-digit verification code.</p>

            <div class="email-info">
                <i class="fas fa-envelope"></i>
                A new code will be sent to: <strong><?php echo htmlspecialchars($user['email']); ?></strong>
            </div>

            <div class="cooldown" id="cooldownTimer" style="display: none;">
                Please wait <span id="countdown">30</span> seconds before requesting another code.
            </div>

            <form action="resend.php" method="POST" id="resendForm">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <button type="submit" class="submit-btn" id="submitBtn">
                    <span class="spinner"></span>
                    <span class="btn-text"><i class="fas fa-paper-plane"></i> Send New Code</span>
                </button>
            </form>

            <p class="back-link">
                <a href="verify_code.php">
                    <i class="fas fa-arrow-left"></i> Back to Verification
                </a>
            </p>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include('../includes/footer.php'); ?>

    <script>
        // Cooldown functionality to prevent spam
        function startCooldown() {
            var cooldownTimer = document.getElementById('cooldownTimer');
            var countdown = document.getElementById('countdown');
            var submitBtn = document.getElementById('submitBtn');
            var timeLeft = 30; // 30 seconds cooldown
            
            cooldownTimer.style.display = 'block';
            cooldownTimer.classList.add('active');
            submitBtn.disabled = true;
            
            var interval = setInterval(function() {
                countdown.textContent = timeLeft;
                timeLeft--;
                
                if (timeLeft < 0) {
                    clearInterval(interval);
                    cooldownTimer.style.display = 'none';
                    cooldownTimer.classList.remove('active');
                    submitBtn.disabled = false;
                }
            }, 1000);
        }

        // Document ready
        document.addEventListener('DOMContentLoaded', function() {
            var form = document.getElementById('resendForm');
            var submitBtn = document.getElementById('submitBtn');
            
            // Handle form submission
            form.addEventListener('submit', function(e) {
                // Show loading state
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
                submitBtn.querySelector('.btn-text').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                
                // Start cooldown after form submission
                setTimeout(function() {
                    startCooldown();
                }, 1000);
            });
            
            // Auto-hide messages after 5 seconds
            setTimeout(function() {
                var errorMsg = document.getElementById('errorMessage');
                var successMsg = document.getElementById('successMessage');
                
                if (errorMsg) {
                    errorMsg.style.opacity = '0';
                    setTimeout(function() {
                        errorMsg.remove();
                    }, 300);
                }
                
                if (successMsg) {
                    successMsg.style.opacity = '0';
                    setTimeout(function() {
                        successMsg.remove();
                    }, 300);
                }
            }, 5000);
        });
    </script>
</body>

</html>
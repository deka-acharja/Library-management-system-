<?php
session_start();
// Include PHPMailer classes
require_once '../PHPMailer/src/Exception.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: forgot_password.php");
    exit();
}

// CSRF Token check
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    $_SESSION['error'] = "Security validation failed. Please try again.";
    header("Location: forgot_password.php");
    exit();
}

// Get form values
$username = trim($_POST['username'] ?? '');
$verification_method = $_POST['verification_method'] ?? 'email';

// Validate username
if (empty($username)) {
    $_SESSION['error'] = "Please enter your username.";
    header("Location: forgot_password.php");
    exit();
}

// DB Connection
include('../includes/db.php'); // Assumes $conn = new mysqli(...);

// Check DB connection
if (mysqli_connect_errno()) {
    $_SESSION['error'] = "Failed to connect to database: " . mysqli_connect_error();
    header("Location: forgot_password.php");
    exit();
}

// Check if user exists
$query = "SELECT id, email, name FROM users WHERE username = ?";
$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    $_SESSION['error'] = "Database error: " . mysqli_error($conn);
    header("Location: forgot_password.php");
    exit();
}
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    $_SESSION['error'] = "If your username exists, a verification code will be sent.";
    header("Location: forgot_password.php");
    exit();
}

// Generate code & expiry - CHANGED FROM 30 MINUTES TO 1 MINUTE
$verification_code = sprintf("%06d", mt_rand(100000, 999999));
$expiry_time = date('Y-m-d H:i:s', strtotime('+1 minute'));

// Check if reset_code and reset_expires columns exist
$check_columns_query = "SHOW COLUMNS FROM users LIKE 'reset_code'";
$check_columns_result = mysqli_query($conn, $check_columns_query);
if (mysqli_num_rows($check_columns_result) == 0) {
    // Add the missing columns
    $alter_query = "ALTER TABLE users 
                   ADD COLUMN reset_code VARCHAR(10) NULL,
                   ADD COLUMN reset_expires DATETIME NULL";
    if (!mysqli_query($conn, $alter_query)) {
        $_SESSION['error'] = "Failed to update database structure: " . mysqli_error($conn);
        header("Location: forgot_password.php");
        exit();
    }
}

// Save code and expiry to DB
$update_query = "UPDATE users SET reset_code = ?, reset_expires = ? WHERE id = ?";
$update_stmt = mysqli_prepare($conn, $update_query);
if (!$update_stmt) {
    $_SESSION['error'] = "Failed to prepare update query: " . mysqli_error($conn);
    header("Location: forgot_password.php");
    exit();
}
mysqli_stmt_bind_param($update_stmt, "ssi", $verification_code, $expiry_time, $user['id']);
$update_result = mysqli_stmt_execute($update_stmt);

if (!$update_result) {
    $_SESSION['error'] = "Failed to update reset information: " . mysqli_stmt_error($update_stmt);
    header("Location: forgot_password.php");
    exit();
}

$_SESSION['reset_user_id'] = $user['id'];

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

if ($verification_method === 'email') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please enter a valid email address.";
        header("Location: forgot_password.php");
        exit();
    }

    if (strtolower($email) !== strtolower($user['email'])) {
        $_SESSION['error'] = "If your username and email match our records, a verification code will be sent.";
        header("Location: forgot_password.php");
        exit();
    }

    // Modern email template
    $subject = "Password Reset Verification Code - Library Management System";
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
            background-color:rgb(30, 100, 53);
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
            color:rgb(25, 68, 19);
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
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Library Management System</h1>
        </div>
        <div class="content">
            <p>Hello ' . ($user['name'] ?? 'User') . ',</p>
            <p>We received a request to reset your password. Use the verification code below to continue:</p>
            
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

    // Send email using PHPMailer
    if (sendEmail($email, $user['name'] ?? 'User', $subject, $body)) {
        $_SESSION['success'] = "A verification code has been sent to your email.";
        header("Location: verify_code.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to send email. Please try again later.";
        header("Location: forgot_password.php");
        exit();
    }

} elseif ($verification_method === 'phone') {
    $phone = trim($_POST['phone'] ?? '');

    if (empty($phone)) {
        $_SESSION['error'] = "Please enter your phone number.";
        header("Location: forgot_password.php");
        exit();
    }

    // Fixed variable name to match the field from database
    if ($phone !== $user['phone_number']) {
        $_SESSION['error'] = "If your username and phone match our records, a verification code will be sent.";
        header("Location: forgot_password.php");
        exit();
    }

    // Simulate SMS sending here (or use an actual SMS API)
    $_SESSION['success'] = "A verification code has been sent to your phone.";
    header("Location: verify_code.php");
    exit();

} else {
    $_SESSION['error'] = "Invalid verification method selected.";
    header("Location: forgot_password.php");
    exit();
}
?>
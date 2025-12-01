<?php
session_start();

// Check if user has been redirected properly
if (!isset($_SESSION['reset_user_id'])) {
    $_SESSION['error'] = "Invalid password reset session. Please start over.";
    header("Location: forgot_password.php");
    exit();
}

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: verify_code.php");
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Invalid request. Please try again.";
    header("Location: verify_code.php");
    exit();
}

// Get and validate the verification code
$entered_code = trim($_POST['verification_code']);

if (empty($entered_code)) {
    $_SESSION['error'] = "Please enter the verification code.";
    header("Location: verify_code.php");
    exit();
}

if (!preg_match('/^[0-9]{6}$/', $entered_code)) {
    $_SESSION['error'] = "Please enter a valid 6-digit verification code.";
    header("Location: verify_code.php");
    exit();
}

include('../includes/db.php');

// Prepare the statement
$stmt = $conn->prepare("
    SELECT reset_code, reset_expires 
    FROM users 
    WHERE id = ? AND reset_code = ? AND reset_expires IS NOT NULL AND status='approved'
");

if (!$stmt) {
    error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    $_SESSION['error'] = "Unable to verify code at this time. Please try again.";
    header("Location: verify_code.php");
    exit();
}

$stmt->bind_param("is", $_SESSION['reset_user_id'], $entered_code);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $_SESSION['error'] = "Invalid verification code. Please check your email and try again.";
    header("Location: verify_code.php");
    exit();
}

// Bind the result
$stmt->bind_result($db_code, $db_expires);
$stmt->fetch();

// Check if the code has expired (assuming 5-minute expiry)
$current_time = new DateTime();
$expires_at = new DateTime($db_expires);

if ($current_time > $expires_at) {
    $_SESSION['error'] = "Verification code has expired. Please request a new one.";

    // Clear expired code
    $clear_stmt = $conn->prepare("UPDATE users SET reset_code=NULL, reset_expires=NULL WHERE id=?");
    if ($clear_stmt) {
        $clear_stmt->bind_param("i", $_SESSION['reset_user_id']);
        $clear_stmt->execute();
        $clear_stmt->close();
    }

    header("Location: verify_code.php");
    exit();
}

// Verification successful - clear code and mark as verified
$update_stmt = $conn->prepare("UPDATE users SET reset_code=NULL, reset_expires=NULL, is_viewed=1 WHERE id=?");
if ($update_stmt) {
    $update_stmt->bind_param("i", $_SESSION['reset_user_id']);
    $update_stmt->execute();
    $update_stmt->close();
}

$stmt->close();
$conn->close();

// Set session flags for password reset
$_SESSION['code_verified'] = true;
$_SESSION['verification_time'] = time();
$_SESSION['success'] = "Code verified successfully! You can now reset your password.";

// Redirect to reset password page
header("Location: reset_password.php");
exit();
?>

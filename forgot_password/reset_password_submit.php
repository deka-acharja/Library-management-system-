<?php
session_start();
include('../includes/db.php');

// Check if user is allowed to reset password
if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['code_verified']) || $_SESSION['code_verified'] !== true) {
    $_SESSION['error'] = "Invalid session. Please verify your code again.";
    header("Location: forgot_password.php");
    exit();
}

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: reset_password.php");
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Invalid CSRF token. Please try again.";
    header("Location: reset_password.php");
    exit();
}

// Get form data
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$user_id = $_SESSION['reset_user_id'];

// Validate input
if (empty($new_password) || empty($confirm_password)) {
    $_SESSION['error'] = "Please fill in all fields.";
    header("Location: reset_password.php");
    exit();
}

if (strlen($new_password) < 8) {
    $_SESSION['error'] = "Password must be at least 8 characters long.";
    header("Location: reset_password.php");
    exit();
}

// Validate password strength
if (!preg_match('/[A-Z]/', $new_password)) {
    $_SESSION['error'] = "Password must contain at least one uppercase letter.";
    header("Location: reset_password.php");
    exit();
}

if (!preg_match('/[0-9]/', $new_password)) {
    $_SESSION['error'] = "Password must contain at least one number.";
    header("Location: reset_password.php");
    exit();
}

if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`]/', $new_password)) {
    $_SESSION['error'] = "Password must contain at least one symbol.";
    header("Location: reset_password.php");
    exit();
}

if ($new_password !== $confirm_password) {
    $_SESSION['error'] = "Passwords do not match.";
    header("Location: reset_password.php");
    exit();
}

// Hash the new password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update the user's password in the database using MySQLi
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");

if ($stmt) {
    $stmt->bind_param("si", $hashed_password, $user_id);
    $result = $stmt->execute();
    
    if ($result && $stmt->affected_rows > 0) {
        // Password updated successfully
        
        // Optional: Clear any existing password reset tokens for this user
        $stmt_tokens = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?");
        if ($stmt_tokens) {
            $stmt_tokens->bind_param("i", $user_id);
            $stmt_tokens->execute();
            $stmt_tokens->close();
        }
        
        // Set success flag
        $_SESSION['password_reset_success'] = true;
        
        // Clear reset session variables
        unset($_SESSION['reset_user_id']);
        unset($_SESSION['code_verified']);
        unset($_SESSION['csrf_token']);
        
        $stmt->close();
        
        // Redirect back to reset_password.php to show the modal
        header("Location: reset_password.php");
        exit();
        
    } else {
        $_SESSION['error'] = "Failed to update password. Please try again.";
        $stmt->close();
        header("Location: reset_password.php");
        exit();
    }
} else {
    // Log the error (in production, don't show the actual error to users)
    error_log("Password reset error: " . $conn->error);
    $_SESSION['error'] = "An error occurred while updating your password. Please try again.";
    header("Location: reset_password.php");
    exit();
}
?>
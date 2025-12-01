<?php 
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_type'] = "error";
        $_SESSION['error_title'] = "Security Error";
        $_SESSION['error_message'] = "Invalid CSRF token.";
        header("Location: login.php");
        exit;
    }
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Database connection
    include('includes/db.php'); // Make sure you have this file with your database connection
    
    // Use prepared statement to prevent SQL injection
    // Add status column to the query
    $stmt = $conn->prepare("SELECT id, username, role, password, status FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password (assuming you're using password_hash in registration)
        if (password_verify($password, $user['password'])) {
            // Check account status before allowing login
            if ($user['status'] === 'approved') {
                // Successful login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect based on role
                switch ($user['role']) {
                    case 'admin':
                        header("Location: admin/dashboard.php");
                        break;
                    case 'user':
                        header("Location: user/dashboard.php");
                        break;
                    default:
                        header("Location: index.php");
                }
                exit;
            } elseif ($user['status'] === 'pending') {
                $_SESSION['error_type'] = "warning";
                $_SESSION['error_title'] = "Account Pending";
                $_SESSION['error_message'] = "Your account is pending approval. Please try again later.";
                header("Location: login.php");
                exit;
            } elseif ($user['status'] === 'disapproved') {
                $_SESSION['error_type'] = "error";
                $_SESSION['error_title'] = "Account Disapproved";
                $_SESSION['error_message'] = "Your account has been disapproved. You will not be able to login. Please contact an administrator for assistance.";
                header("Location: login.php");
                exit;
            } else {
                $_SESSION['error_type'] = "error";
                $_SESSION['error_title'] = "Account Error";
                $_SESSION['error_message'] = "Account status issue. Please contact an administrator.";
                header("Location: login.php");
                exit;
            }
        } else {
            $_SESSION['error_type'] = "error";
            $_SESSION['error_title'] = "Authentication Failed";
            $_SESSION['error_message'] = "Invalid password.";
            header("Location: login.php");
            exit;
        }
    } else {
        $_SESSION['error_type'] = "error";
        $_SESSION['error_title'] = "Authentication Failed";
        $_SESSION['error_message'] = "User not found.";
        header("Location: login.php");
        exit;
    }
    
    $stmt->close();
    $conn->close();
    
} else {
    header("Location: login.php");
    exit;
}
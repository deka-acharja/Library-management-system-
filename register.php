<?php
session_start();
include('includes/db.php'); // database connection
include('includes/header.php'); 

// Generate CSRF token if not already set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$show_modal = false;
$modal_type = '';
$modal_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $show_modal = true;
        $modal_type = 'error';
        $modal_message = "Invalid CSRF token.";
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $password_confirmation = $_POST['password_confirmation'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        
        // Set status based on role - automatically approve users with 'user' role
        $status = ($role === 'user') ? 'approved' : 'pending';

        // Validate password match
        if ($password !== $password_confirmation) {
            $show_modal = true;
            $modal_type = 'error';
            $modal_message = "Passwords do not match.";
        } else {
            // Check if username already exists
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                $show_modal = true;
                $modal_type = 'error';
                $modal_message = "Username already exists.";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                // Insert user with status
                $stmt = $conn->prepare("INSERT INTO users (name, email, username, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $name, $email, $username, $hashed_password, $role, $status);

                if ($stmt->execute()) {
                    $show_modal = true;
                    $modal_type = 'success';
                    if ($role === 'user') {
                        $modal_message = "Registration successful! Your account has been automatically approved.";
                    } else {
                        $modal_message = "Registration successful! Your account is pending approval.";
                    }
                } else {
                    $show_modal = true;
                    $modal_type = 'error';
                    $modal_message = "Registration failed. Try again.";
                }
                
                if (isset($stmt)) $stmt->close();
            }
            
            $check_stmt->close();
        }
    }
    
    if (isset($conn)) $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register</title>
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
        }

        body {
            background-color: var(--ultra-light-green);
            color: var(--text-dark);
            line-height: 1.6;
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
        }

        .register-page {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 120px);
            padding: 2rem;
            margin-top: 50px;
        }

        .register-container {
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.1);
            padding: 2.5rem;
            width: 100%;
            max-width: 600px;
            transition: all 0.3s ease;
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
        }

        .register-container:hover {
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
        }

        .register-container h2 {
            color: var(--main-green);
            text-align: center;
            margin-bottom: 1.5rem;
            font-weight: 600;
            font-size: 1.8rem;
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

        .input-container input,
        .input-container select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--light-green);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .input-container input:focus,
        .input-container select:focus {
            border-color: var(--main-green);
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 106, 51, 0.2);
        }

        .register-btn {
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
            position: relative;
        }

        .register-btn:hover:not(:disabled) {
            background-color: var(--dark-green);
            transform: translateY(-2px);
        }

        .register-btn:active:not(:disabled) {
            transform: translateY(0);
        }

        .register-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .register-btn .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-medium);
        }

        .login-link a {
            color: var(--main-green);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .login-link a:hover {
            color: var(--dark-green);
            text-decoration: underline;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 80px;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(2px);
        }

        .modal.show {
            display: block;
        }

        .modal-content {
            background-color: var(--white);
            margin: 15% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 1.5rem 2rem 1rem 2rem;
            text-align: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 600;
        }

        .modal-body {
            padding: 0 2rem 1.5rem 2rem;
            text-align: center;
        }

        .modal-body p {
            margin: 0;
            font-size: 1rem;
            line-height: 1.5;
        }

        .modal-footer {
            padding: 1rem 2rem 2rem 2rem;
            text-align: center;
        }

        .modal-btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            min-width: 100px;
        }

        .modal-btn:hover {
            transform: translateY(-2px);
        }

        /* Success Modal */
        .success-modal .modal-header h3 {
            color: var(--success-green);
        }

        .success-modal .modal-body {
            color: var(--text-dark);
        }

        .success-modal .modal-btn {
            background-color: var(--success-green);
            color: var(--white);
        }

        .success-modal .modal-btn:hover {
            background-color: #27ae60;
        }

        /* Error Modal */
        .error-modal .modal-header h3 {
            color: var(--error-red);
        }

        .error-modal .modal-body {
            color: var(--text-dark);
        }

        .error-modal .modal-btn {
            background-color: var(--error-red);
            color: var(--white);
        }

        .error-modal .modal-btn:hover {
            background-color: #c0392b;
        }

        /* Icon styles */
        .modal-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .success-icon {
            color: var(--success-green);
        }

        .error-icon {
            color: var(--error-red);
        }

        @media (max-width: 576px) {
            .register-page {
                padding: 1rem;
            }
            
            .register-container {
                padding: 1.5rem;
            }
            
            .modal-content {
                margin: 20% auto;
                width: 95%;
            }
        }
    </style>
</head>

<body>

    <div class="register-page">
        <div class="register-container">
            <h2>Create Your Account</h2>

            <form name="registerForm" action="register.php" method="POST" onsubmit="return handleSubmit(event)">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="input-container">
                    <label for="name">Full Name</label>
                    <input type="text" name="name" required placeholder="Enter your full name" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>

                <div class="input-container">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" required placeholder="Enter your email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="input-container">
                    <label for="username">CID (Username)</label>
                    <input type="text" name="username" required pattern="\d{11}" title="CID must be 11 digits" placeholder="Enter 11-digit CID" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>

                <div class="input-container">
                    <label for="password">Password</label>
                    <input type="password" name="password" required placeholder="Create a password">
                </div>

                <div class="input-container">
                    <label for="password_confirmation">Confirm Password</label>
                    <input type="password" name="password_confirmation" required placeholder="Confirm your password">
                </div>

                <div class="input-container">
                    <label for="role">Account Type</label>
                    <select name="role" required>
                        <option value="" disabled <?= !isset($_POST['role']) ? 'selected' : ''; ?>>Select your role</option>
                        <option value="user" <?= isset($_POST['role']) && $_POST['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?= isset($_POST['role']) && $_POST['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>

                <button type="submit" class="register-btn" id="submitBtn">
                    <span class="spinner" id="spinner"></span>
                    <span id="btnText">Register Now</span>
                </button>

                <p class="login-link">Already have an account? <a href="login.php">Sign in</a></p>
            </form>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="modal success-modal <?= $show_modal && $modal_type === 'success' ? 'show' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon success-icon">✓</div>
                <h3>Registration Successful!</h3>
            </div>
            <div class="modal-body">
                <p id="successMessage"><?= $show_modal && $modal_type === 'success' ? htmlspecialchars($modal_message) : ''; ?></p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn" onclick="closeSuccessModal()">Continue</button>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div id="errorModal" class="modal error-modal <?= $show_modal && $modal_type === 'error' ? 'show' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon error-icon">✕</div>
                <h3>Registration Failed</h3>
            </div>
            <div class="modal-body">
                <p id="errorMessage"><?= $show_modal && $modal_type === 'error' ? htmlspecialchars($modal_message) : ''; ?></p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn" onclick="closeErrorModal()">Try Again</button>
            </div>
        </div>
    </div>

    <script>
        function handleSubmit(event) {
            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            const spinner = document.getElementById('spinner');
            const btnText = document.getElementById('btnText');
            
            // Validate form first
            if (!validateForm()) {
                return false;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';
            btnText.textContent = 'Registering...';
            
            // Let form submit normally
            return true;
        }

        function validateForm() {
            const username = document.forms["registerForm"]["username"].value;
            const password = document.forms["registerForm"]["password"].value;
            const password_confirmation = document.forms["registerForm"]["password_confirmation"].value;
            const passwordPattern = /^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+={}\[\]|\\:;"'<>?,./]).{8,}$/;

            if (!/^\d{11}$/.test(username)) {
                showErrorModal("Username must be a valid CID with exactly 11 digits.");
                return false;
            }

            if (!passwordPattern.test(password)) {
                showErrorModal("Password must be at least 8 characters long, with at least one uppercase letter, one number, and one special character.");
                return false;
            }

            if (password !== password_confirmation) {
                showErrorModal("Passwords do not match.");
                return false;
            }

            return true;
        }

        function showSuccessModal(message) {
            document.getElementById('successMessage').textContent = message;
            document.getElementById('successModal').classList.add('show');
        }

        function showErrorModal(message) {
            document.getElementById('errorMessage').textContent = message;
            document.getElementById('errorModal').classList.add('show');
            
            // Reset button state
            resetButtonState();
        }

        function resetButtonState() {
            const submitBtn = document.getElementById('submitBtn');
            const spinner = document.getElementById('spinner');
            const btnText = document.getElementById('btnText');
            
            submitBtn.disabled = false;
            spinner.style.display = 'none';
            btnText.textContent = 'Register Now';
        }

        function closeSuccessModal() {
            document.getElementById('successModal').classList.remove('show');
            // Redirect to login page after successful registration
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 300);
        }

        function closeErrorModal() {
            document.getElementById('errorModal').classList.remove('show');
            resetButtonState();
        }

        // Close modals when clicking outside of them
        window.onclick = function(event) {
            const successModal = document.getElementById('successModal');
            const errorModal = document.getElementById('errorModal');
            
            if (event.target === successModal) {
                closeSuccessModal();
            }
            if (event.target === errorModal) {
                closeErrorModal();
            }
        }

        // Auto-show modal if there's a message from PHP
        <?php if ($show_modal): ?>
            document.addEventListener('DOMContentLoaded', function() {
                <?php if ($modal_type === 'success'): ?>
                    // Modal is already shown via PHP, just ensure it's visible
                    document.getElementById('successModal').classList.add('show');
                <?php else: ?>
                    // Modal is already shown via PHP, just ensure it's visible
                    document.getElementById('errorModal').classList.add('show');
                    resetButtonState();
                <?php endif; ?>
            });
        <?php endif; ?>
    </script>

    <?php include('includes/footer.php'); ?>
</body>

</html>
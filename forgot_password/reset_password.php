<?php  
session_start(); 

// Check if user is allowed to reset password 
$should_redirect = false;
if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['code_verified']) || $_SESSION['code_verified'] !== true) {     
    $_SESSION['error'] = "Invalid session. Please verify your code again.";     
    $should_redirect = true;
}  

// Generate a CSRF token for form submission 
if (empty($_SESSION['csrf_token'])) {     
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
} 

// Include header AFTER all potential redirects
include('../includes/dashboard_header.php');  
?>  

<!DOCTYPE html> 
<html lang="en">  
<head>     
    <meta charset="UTF-8">     
    <title>Reset Password</title>     
    <style>
        /* Color Palette */
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
            font-family:Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
            background: linear-gradient(135deg, var(--ultra-light-green) 0%, var(--light-green) 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--text-dark);
        }

        .container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin-top: 30px;
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
        }

        .reset-card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(46, 106, 51, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            border: 1px solid var(--light-green);
        }

        .reset-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .reset-header h2 {
            color: var(--main-green);
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .reset-header p {
            color: var(--text-medium);
            font-size: 18px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: var(--text-dark);
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 18px;
        }

        input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--light-green);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: var(--white);
            color: var(--text-dark);
        }

        input[type="password"]:focus {
            outline: none;
            border-color: var(--main-green);
            box-shadow: 0 0 0 3px rgba(46, 106, 51, 0.1);
        }

        .password-hint {
            color: var(--text-light);
            font-size: 12px;
            margin-top: 6px;
            line-height: 1.4;
        }

        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--main-green) 0%, var(--accent-green) 100%);
            color: var(--white);
            border: none;
            padding: 16px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, var(--dark-green) 0%, var(--main-green) 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(46, 106, 51, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .error {
            background: rgba(214, 69, 65, 0.1);
            color: var(--error-red);
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid rgba(214, 69, 65, 0.2);
            margin-bottom: 20px;
            font-size: 14px;
        }

        .success {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-green);
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid rgba(46, 204, 113, 0.2);
            margin-bottom: 20px;
            font-size: 14px;
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
            background-color: rgba(29, 69, 33, 0.6);
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background-color: var(--white);
            margin: 10% auto;
            padding: 40px;
            border: none;
            border-radius: 16px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 30px 60px rgba(46, 106, 51, 0.2);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-header h3 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .modal-header h3.success {
            color: var(--success-green);
        }

        .modal-header h3.error {
            color: var(--error-red);
        }

        .success-icon {
            width: 60px;
            height: 60px;
            background: var(--success-green);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--white);
            font-size: 28px;
        }

        .error-icon {
            width: 60px;
            height: 60px;
            background: var(--error-red);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--white);
            font-size: 28px;
        }

        .modal-body {
            margin-bottom: 30px;
            color: var(--text-medium);
            font-size: 16px;
            line-height: 1.5;
        }

        .modal-button {
            background: linear-gradient(135deg, var(--main-green) 0%, var(--accent-green) 100%);
            color: var(--white);
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .modal-button:hover {
            background: linear-gradient(135deg, var(--dark-green) 0%, var(--main-green) 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(46, 106, 51, 0.3);
        }

        .modal-button.error {
            background: linear-gradient(135deg, var(--error-red) 0%, #c0392b 100%);
        }

        .modal-button.error:hover {
            background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
            box-shadow: 0 8px 25px rgba(214, 69, 65, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .reset-card {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .reset-header h2 {
                font-size: 24px;
            }
            
            .modal-content {
                margin: 20% auto;
                padding: 30px 20px;
            }
        }
    </style> 
</head>  

<body>
    <div class="container">
        <div class="reset-card">
            <div class="reset-header">
                <h2>Reset Your Password</h2>
                <p>Enter your new password below</p>
            </div>
            
            <?php     
            // Show any error messages     
            if (isset($_SESSION['error'])) {         
                echo '<div class="error">' . htmlspecialchars($_SESSION['error']) . '</div>';         
                unset($_SESSION['error']);     
            }      
            ?>      
            
            <form id="resetPasswordForm" action="reset_password_submit.php" method="POST">         
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">         
                
                <div class="form-group">
                    <label for="new_password">New Password</label>         
                    <input type="password" name="new_password" id="new_password" required minlength="8">
                    <div class="password-hint">Password must be at least 8 characters with 1 uppercase letter, 1 number, and 1 symbol</div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>         
                    <input type="password" name="confirm_password" id="confirm_password" required minlength="8">
                </div>
                
                <button type="submit" class="submit-btn">Reset Password</button>     
            </form>
        </div>
    </div>
    
    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <div class="success-icon">
                ✓
            </div>
            <div class="modal-header">
                <h3 class="success">Success!</h3>
            </div>
            <div class="modal-body">
                <p>Your password has been reset successfully!</p>
                <p>You can now login with your new password.</p>
            </div>
            <button class="modal-button" onclick="redirectToForgotPassword()">Continue</button>
        </div>
    </div>

    <!-- Error Modal -->
    <div id="errorModal" class="modal">
        <div class="modal-content">
            <div class="error-icon">
                ✗
            </div>
            <div class="modal-header">
                <h3 class="error">Error!</h3>
            </div>
            <div class="modal-body">
                <p id="errorMessage"></p>
            </div>
            <button class="modal-button error" onclick="closeErrorModal()">Try Again</button>
        </div>
    </div>
    
    <script>
        // Handle redirect if session is invalid
        <?php if (isset($should_redirect) && $should_redirect): ?>
            window.location.href = 'forgot_password.php';
        <?php endif; ?>
        
        // Check if there's a success message from the session
        <?php if (isset($_SESSION['password_reset_success']) && $_SESSION['password_reset_success'] === true): ?>
            document.getElementById('successModal').style.display = 'block';
            <?php unset($_SESSION['password_reset_success']); ?>
        <?php endif; ?>
        
        function redirectToForgotPassword() {
            window.location.href = 'forgot_password.php';
        }
        
        function closeErrorModal() {
            document.getElementById('errorModal').style.display = 'none';
        }
        
        function showErrorModal(message) {
            document.getElementById('errorMessage').textContent = message;
            document.getElementById('errorModal').style.display = 'block';
        }
        
        // Form validation
        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                showErrorModal('Passwords do not match. Please try again.');
                return false;
            }
            
            // Password strength validation
            if (!validatePassword(newPassword)) {
                e.preventDefault();
                const errorMsg = getPasswordValidationError(newPassword);
                showErrorModal(errorMsg);
                return false;
            }
        });
        
        function validatePassword(password) {
            // At least 8 characters
            if (password.length < 8) {
                return false;
            }
            
            // At least one uppercase letter
            if (!/[A-Z]/.test(password)) {
                return false;
            }
            
            // At least one number
            if (!/[0-9]/.test(password)) {
                return false;
            }
            
            // At least one symbol (special character)
            if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(password)) {
                return false;
            }
            
            return true;
        }
        
        function getPasswordValidationError(password) {
            if (password.length < 8) {
                return 'Password must be at least 8 characters long.';
            }
            
            if (!/[A-Z]/.test(password)) {
                return 'Password must contain at least one uppercase letter.';
            }
            
            if (!/[0-9]/.test(password)) {
                return 'Password must contain at least one number.';
            }
            
            if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(password)) {
                return 'Password must contain at least one symbol (!@#$%^&* etc.).';
            }
            
            return 'Password does not meet the requirements.';
        }
    </script>
    
    <?php include('../includes/footer.php'); ?> 
</body>  
</html>
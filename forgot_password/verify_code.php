<?php
session_start();

// Check if user has been redirected properly with a reset_user_id
if (!isset($_SESSION['reset_user_id'])) {
    $_SESSION['error'] = "Invalid password reset session. Please start over.";
    header("Location: forgot_password.php");
    exit();
}

// Generate a new CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code - Library Management System</title>
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
        
        .verify-page {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
            padding: 3rem 1rem;
        }
        
        .verify-box {
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.1);
            padding: 2.5rem;
            width: 100%;
            max-width: 450px;
            transition: all 0.3s ease;
        }
        
        .verify-box:hover {
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
        }
        
        .verify-box h2 {
            color: var(--main-green);
            text-align: center;
            margin-bottom: 1.5rem;
            font-weight: 600;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .verify-box h2 i {
            margin-right: 10px;
        }
        
        .verify-box p {
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
            font-size: 1.2rem;
            transition: all 0.3s;
            letter-spacing: 0.5rem;
            text-align: center;
            font-weight: 600;
            background-color: #fafafa;
        }
        
        .input-container input:focus {
            border-color: var(--main-green);
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 106, 51, 0.2);
            background-color: var(--white);
        }
        
        .input-container input.error {
            border-color: var(--error-red);
            background-color: rgba(214, 69, 65, 0.05);
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
        
        .resend-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-medium);
        }
        
        .resend-link a {
            color: var(--main-green);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .resend-link a:hover {
            color: var(--dark-green);
            text-decoration: underline;
        }
        
        .resend-link a.disabled {
            color: #ccc;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .timer {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: var(--text-medium);
            font-weight: 500;
        }
        
        .timer.expired {
            color: var(--error-red);
            font-weight: 600;
        }
        
        .timer.warning {
            color: var(--warning-yellow);
            font-weight: 600;
        }
        
        .code-input-helper {
            font-size: 0.8rem;
            color: var(--text-light);
            text-align: center;
            margin-top: 0.5rem;
        }
        
        @media (max-width: 576px) {
            .verify-box {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .verify-box h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>

    <!-- Include Header -->
    <?php include('../includes/dashboard_header.php'); ?>

    <div class="verify-page">
        <div class="verify-box">
            <h2><i class="fas fa-shield-alt"></i> Verify Code</h2>
            <p>Please enter the 6-digit verification code sent to your email address.</p>

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

            <form action="verify_code_submit.php" method="POST" id="verifyForm">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="input-container">
                    <label for="verification_code">
                        <i class="fas fa-key"></i> Verification Code
                    </label>
                    <input type="text" 
                           name="verification_code" 
                           id="verification_code" 
                           required 
                           placeholder="000000" 
                           maxlength="6" 
                           pattern="[0-9]{6}" 
                           title="Please enter the 6-digit verification code"
                           autocomplete="off"
                           inputmode="numeric">
                    <div class="code-input-helper">Enter the 6-digit code from your email</div>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <span class="spinner"></span>
                    <span class="btn-text">Verify Code</span>
                </button>
            </form>

            <div class="timer" id="timerContainer">
                <i class="fas fa-clock"></i>
                Code expires in: <span id="timer">01:00</span>
            </div>

            <p class="resend-link">
                Didn't receive a code? 
                <a href="resend.php" id="resendLink">
                    <i class="fas fa-paper-plane"></i> Resend Code
                </a>
            </p>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include('../includes/footer.php'); ?>

    <script>
        // Timer functionality
        function startTimer(duration, display) {
            var timer = duration, minutes, seconds;
            var submitBtn = document.getElementById('submitBtn');
            var timerContainer = document.getElementById('timerContainer');
            var form = document.getElementById('verifyForm');
            var resendLink = document.getElementById('resendLink');
            
            var interval = setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                display.textContent = minutes + ":" + seconds;
                
                // Warning when less than 30 seconds (for 1 minute timer)
                if (timer <= 30 && timer > 0) {
                    timerContainer.classList.add('warning');
                }

                if (--timer < 0) {
                    clearInterval(interval);
                    display.textContent = "Expired";
                    timerContainer.classList.remove('warning');
                    timerContainer.classList.add('expired');
                    
                    // Disable form
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-times"></i> Code Expired';
                    form.style.opacity = '0.6';
                    
                    // Enable resend link and make it more prominent
                    resendLink.style.fontWeight = 'bold';
                    resendLink.style.color = 'var(--main-green)';
                    resendLink.innerHTML = '<i class="fas fa-paper-plane"></i> Get New Code';
                    
                    // Show expiration message
                    showMessage('Verification code has expired. Please click "Get New Code" to receive a new code.', 'error');
                }
            }, 1000);
        }

        // Show message function
        function showMessage(message, type) {
            var existingMessage = document.querySelector('.error-message, .success-message');
            if (existingMessage) {
                existingMessage.remove();
            }
            
            var messageDiv = document.createElement('div');
            messageDiv.className = type + '-message';
            messageDiv.innerHTML = '<i class="fas fa-' + (type === 'error' ? 'exclamation-triangle' : 'check-circle') + '"></i> ' + message;
            
            var form = document.getElementById('verifyForm');
            form.parentNode.insertBefore(messageDiv, form);
        }

        // Document ready
        document.addEventListener('DOMContentLoaded', function() {
            var verificationInput = document.getElementById('verification_code');
            var submitBtn = document.getElementById('submitBtn');
            var form = document.getElementById('verifyForm');
            
            // Auto-focus on verification code input
            verificationInput.focus();
            
            // Only allow numbers and format input
            verificationInput.addEventListener('input', function(e) {
                // Remove any non-numeric characters
                var value = this.value.replace(/[^0-9]/g, '');
                this.value = value;
                
                // Remove error styling when user starts typing
                this.classList.remove('error');
                
                // Auto-submit when 6 digits are entered
                if (value.length === 6) {
                    setTimeout(function() {
                        form.submit();
                    }, 300); // Small delay for better UX
                }
            });
            
            // Handle form submission
            form.addEventListener('submit', function(e) {
                var code = verificationInput.value.trim();
                
                if (code.length !== 6 || !/^[0-9]{6}$/.test(code)) {
                    e.preventDefault();
                    verificationInput.classList.add('error');
                    verificationInput.focus();
                    showMessage('Please enter a valid 6-digit verification code.', 'error');
                    return false;
                }
                
                // Show loading state
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
                submitBtn.querySelector('.btn-text').textContent = 'Verifying...';
            });
            
            // Handle paste event
            verificationInput.addEventListener('paste', function(e) {
                setTimeout(function() {
                    var value = verificationInput.value.replace(/[^0-9]/g, '').substring(0, 6);
                    verificationInput.value = value;
                    
                    if (value.length === 6) {
                        form.submit();
                    }
                }, 10);
            });
            
            // Start the timer - Changed to 1 minute (60 seconds)
            var oneMinute = 60; // 1 minute = 60 seconds
            var display = document.querySelector('#timer');
            startTimer(oneMinute, display);
        });
    </script>
</body>

</html>
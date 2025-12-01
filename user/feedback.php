<?php
session_start();
include('../includes/db.php');
include('../includes/dashboard_header.php');

$success = '';
$error = '';

// Handle feedback submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $error = "You must be logged in to submit feedback.";
    } else {
        $user_id = $_SESSION['user_id'];
        $category = $_POST['category'] ?? '';
        $other_category = $_POST['other_category'] ?? '';
        $message = $_POST['message'] ?? '';
        $rating = $_POST['rating'] ?? null;
        $priority = $_POST['priority'] ?? 'medium';
        $subject = $_POST['subject'] ?? '';

        // If category is "Others", use the custom category input
        if ($category === 'Others' && !empty($other_category)) {
            $category = $other_category;
        }

        // Validation
        if (!empty($subject) && !empty($category) && !empty($message)) {
            // Fixed the bind_param - changed "isssiss" to "isssss" since rating can be null
            $stmt = $conn->prepare("INSERT INTO feedback (user_id, category, subject, message, rating, priority, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            
            // Convert rating to string for consistency, or handle null
            $rating_value = ($rating !== null) ? (string)$rating : null;
            
            $stmt->bind_param("isssss", $user_id, $category, $subject, $message, $rating_value, $priority);
            
            if ($stmt->execute()) {
                $success = "Your feedback has been submitted successfully! We'll review it and get back to you soon.";
                // Clear form data after successful submission
                $_POST = array();
            } else {
                $error = "Error submitting feedback. Please try again.";
            }
            $stmt->close();
        } else {
            $error = "Please fill in all required fields.";
        }
    }
}

// Count new notifications - only if user is logged in
$new_reservations_count = 0;
if (isset($_SESSION['user_id'])) {
    $new_reservations_query = "SELECT COUNT(*) as count FROM reservations WHERE status IN ('confirmed', 'rejected', 'terminated', 'due') AND is_viewed = 0";
    $new_reservations_result = $conn->query($new_reservations_query);
    if ($new_reservations_result && $row = $new_reservations_result->fetch_assoc()) {
        $new_reservations_count = $row['count'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Feedback - Library Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
         
        :root {
            --main-green: #2e6a33;
            --dark-green: #1d4521;
            --light-green: #c9dccb;
            --accent-green: #4a9950;
            --ultra-light-green: #eef5ef;
            --text-dark: #263028;
            --white: #ffffff;
            --error-red: #d64541;
            --success-green: #2ecc71;
            --warning-orange: #f39c12;
            --shadow: rgba(0, 0, 0, 0.1);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        *, *::before, *::after {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--ultra-light-green) 0%, #f8fbf8 100%);
            margin: 0;
            color: var(--text-dark);
            padding-top: 245px;
            transition: var(--transition);
            line-height: 1.6;
        }

        body.nav-open {
            padding-top: 305px;
        }

        .navbar-toggle-container {
            position: fixed;
            top: 240px;
            left: 0;
            right: 0;
            z-index: 1000;
            background: linear-gradient(135deg, var(--main-green) 0%, var(--accent-green) 100%);
            box-shadow: 0 4px 20px var(--shadow);
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 50px;
            backdrop-filter: blur(10px);
        }

        .toggle-btn {
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--main-green) 100%);
            color: var(--white);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            font-size: 18px;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(46, 106, 51, 0.3);
        }

        .toggle-btn:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 6px 20px rgba(46, 106, 51, 0.4);
        }

        .toggle-btn.active i {
            transform: rotate(180deg);
        }

        .notification-icon {
            position: relative;
            color: var(--white);
            font-size: 18px;
            margin-left: 15px;
            text-decoration: none;
            transition: var(--transition);
        }

        .notification-icon:hover {
            transform: scale(1.1);
        }

        .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(135deg, var(--error-red) 0%, #c0392b 100%);
            color: var(--white);
            font-size: 11px;
            font-weight: bold;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 2px solid var(--white);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .secondary-navbar {
            position: fixed;
            top: 285px;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, var(--dark-green) 0%, #0f2412 100%);
            height: 0;
            overflow: hidden;
            transition: var(--transition);
            z-index: 999;
            backdrop-filter: blur(10px);
            font-family: 'Times New Roman', Times, serif;
        }

        .secondary-navbar.active {
            height: 60px;
        }

        .navbar-links {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            gap: 20px;
        }

        .navbar-links a {
            color: var(--white);
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            padding: 10px 16px;
            border-radius: 8px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .navbar-links a:hover {
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--main-green) 100%);
            transform: translateY(-2px);
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            background: var(--white);
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: 0 20px 40px var(--shadow);
            color: var(--text-dark);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            margin-top: 100px;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--main-green) 0%, var(--accent-green) 100%);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h2 {
            color: var(--main-green);
            margin-bottom: 10px;
            font-weight: 700;
            font-size: 28px;
            font-family: 'Georgia', serif;
        }

        .header p {
            color: #666;
            font-size: 16px;
            margin: 0;
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 14px;
        }

        .required {
            color: var(--error-red);
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 14px 16px;
            margin: 0;
            border-radius: 8px;
            border: 2px solid #e1e8e3;
            font-size: 15px;
            transition: var(--transition);
            background: #fafafa;
            font-family: inherit;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--main-green);
            box-shadow: 0 0 0 4px rgba(46, 106, 51, 0.1);
            background: var(--white);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        /* Other Category Input Styles */
        .other-category-input {
            margin-top: 12px;
            opacity: 0;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .other-category-input.show {
            opacity: 1;
            max-height: 80px;
        }

        .other-category-input input {
            border-color: var(--accent-green);
            background: #f8fbf8;
        }

        .other-category-input input:focus {
            border-color: var(--main-green);
            box-shadow: 0 0 0 4px rgba(46, 106, 51, 0.15);
        }

        .priority-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 8px;
        }

        .priority-option {
            position: relative;
        }

        .priority-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .priority-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            border: 2px solid #e1e8e3;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            font-size: 14px;
            background: #fafafa;
        }

        .priority-option input[type="radio"]:checked + .priority-label {
            background: var(--main-green);
            color: var(--white);
            border-color: var(--main-green);
        }

        .priority-low .priority-label:hover {
            border-color: var(--success-green);
        }

        .priority-medium .priority-label:hover {
            border-color: var(--warning-orange);
        }

        .priority-high .priority-label:hover {
            border-color: var(--error-red);
        }

        .file-upload {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-upload input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            border: 2px dashed #e1e8e3;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            background: #fafafa;
            color: #666;
            font-size: 14px;
        }

        .file-upload-label:hover {
            border-color: var(--main-green);
            background: var(--ultra-light-green);
            color: var(--main-green);
        }

        .file-upload-label i {
            margin-right: 8px;
            font-size: 16px;
        }

        .submit-btn {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, var(--main-green) 0%, var(--accent-green) 100%);
            color: var(--white);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 106, 51, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn.loading {
            pointer-events: none;
        }

        .submit-btn.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid transparent;
            border-top: 2px solid var(--white);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .alert {
            padding: 16px 20px;
            margin-bottom: 24px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert i {
            margin-right: 10px;
            font-size: 16px;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .feedback-tips {
            background: var(--ultra-light-green);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid var(--main-green);
        }

        .feedback-tips h4 {
            color: var(--main-green);
            margin-bottom: 12px;
            font-size: 16px;
        }

        .feedback-tips ul {
            margin: 0;
            padding-left: 20px;
            color: #555;
        }

        .feedback-tips li {
            margin-bottom: 6px;
            font-size: 14px;
        }

        .login-required {
            text-align: center;
            padding: 40px;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            margin: 20px;
        }

        .login-required h3 {
            color: #856404;
            margin-bottom: 15px;
        }

        .login-required a {
            background: var(--main-green);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            body {
                padding-top: 260px;
            }

            body.nav-open {
                padding-top: 320px;
            }

            .container {
                margin: 20px;
                padding: 24px;
            }

            .header h2 {
                font-size: 24px;
            }

            .priority-selector {
                grid-template-columns: 1fr;
            }

            .navbar-links {
                flex-wrap: wrap;
                gap: 10px;
            }

            .navbar-links a {
                font-size: 14px;
                padding: 8px 12px;
            }
        }

    
        /* Rating Stars Styling */
        .rating-stars {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
        }

        .rating-stars input[type="radio"] {
            display: none;
        }

        .rating-stars label {
            font-size: 24px;
            color: #ddd;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }

        .rating-stars label:hover {
            transform: scale(1.1);
        }

        .rating-stars input[type="radio"]:checked ~ .star-icon,
        .rating-stars label.active {
            color: #ffd700;
        }

        .rating-text {
            margin-left: 10px;
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }

        .rating-group {
            display: flex;
            align-items: center;
            margin-top: 8px;
        }
    </style>
</head>
<body>

    <div class="navbar-toggle-container">
        <button class="toggle-btn" id="navbarToggle" title="Toggle Menu">
            <i class="fas fa-chevron-down"></i>
        </button>
        <a href="notifications.php" class="notification-icon" title="Notifications">
            <i class="fas fa-bell"></i>
            <?php if ($new_reservations_count > 0): ?>
                <span class="badge"><?php echo $new_reservations_count; ?></span>
            <?php endif; ?>
        </a>
    </div>

    <div class="secondary-navbar" id="secondaryNavbar">
        <div class="navbar-links">
            <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
            <a href="my_borrowing.php"><i class="fas fa-book"></i> My Borrowing</a>
            <a href="feedback.php"><i class="fas fa-comment-dots"></i> Feedback</a>
            <a href="../login.php"><i class="fas fa-sign-out-alt"></i> LogOut</a>
        </div>
    </div>

    <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="login-required">
            <h3><i class="fas fa-lock"></i> Login Required</h3>
            <p>You must be logged in to submit feedback.</p>
            <a href="../login.php"><i class="fas fa-sign-in-alt"></i> Login Now</a>
        </div>
    <?php else: ?>
    <div class="container">
        <div class="header">
            <h2><i class="fas fa-comments"></i> Send Feedback</h2>
            <p>Help us improve our library services by sharing your thoughts and suggestions</p>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="feedback-tips">
            <h4><i class="fas fa-lightbulb"></i> Tips for Better Feedback</h4>
            <ul>
                <li>Be specific about the issue or suggestion</li>
                <li>Include steps to reproduce any problems</li>
                <li>Mention which section/feature you're referring to</li>
                <li>Attach screenshots if relevant</li>
            </ul>
        </div>

        <form method="post" action="" enctype="multipart/form-data" id="feedbackForm">
            <div class="form-group">
                <label for="subject">Subject <span class="required">*</span></label>
                <input type="text" name="subject" id="subject" required 
                       placeholder="Brief summary of your feedback" maxlength="100"
                       value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
                <div class="char-counter" id="subjectCounter">0/100</div>
            </div>

            <div class="form-group">
                <label for="category">Category <span class="required">*</span></label>
                <select name="category" id="category" required>
                    <option value="">-- Select Category --</option>
                    <option value="Book Collection" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Book Collection') ? 'selected' : ''; ?>>📚 Book Collection</option>
                    <option value="System Issue" <?php echo (isset($_POST['category']) && $_POST['category'] == 'System Issue') ? 'selected' : ''; ?>>🔧 Technical Issue</option>
                    <option value="Late Fee Complaint" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Late Fee Complaint') ? 'selected' : ''; ?>>💰 Late Fee Concern</option>
                    <option value="User Experience" <?php echo (isset($_POST['category']) && $_POST['category'] == 'User Experience') ? 'selected' : ''; ?>>✨ User Experience</option>
                    <option value="Feature Request" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Feature Request') ? 'selected' : ''; ?>>💡 Feature Request</option>
                    <option value="Staff Service" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Staff Service') ? 'selected' : ''; ?>>👥 Staff Service</option>
                    <option value="Others" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Others') ? 'selected' : ''; ?>>📝 Others</option>
                </select>
                
                <!-- Other Category Input Field -->
                <div class="other-category-input" id="otherCategoryInput">
                    <input type="text" name="other_category" id="other_category" 
                           placeholder="Please specify your category..." maxlength="50"
                           value="<?php echo isset($_POST['other_category']) ? htmlspecialchars($_POST['other_category']) : ''; ?>">
                    <div class="char-counter" id="otherCategoryCounter">0/50</div>
                </div>
            </div>

            <div class="form-group">
                <label>Priority Level</label>
                <div class="priority-selector">
                    <div class="priority-option priority-low">
                        <input type="radio" name="priority" value="low" id="priority-low" 
                               <?php echo (!isset($_POST['priority']) || $_POST['priority'] == 'low') ? 'checked' : ''; ?>>
                        <label for="priority-low" class="priority-label">
                            <i class="fas fa-circle" style="color: #27ae60; margin-right: 6px;"></i>
                            Low
                        </label>
                    </div>
                    <div class="priority-option priority-medium">
                        <input type="radio" name="priority" value="medium" id="priority-medium"
                               <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'medium') ? 'checked' : ''; ?>>
                        <label for="priority-medium" class="priority-label">
                            <i class="fas fa-circle" style="color: #f39c12; margin-right: 6px;"></i>
                            Medium
                        </label>
                    </div>
                    <div class="priority-option priority-high">
                        <input type="radio" name="priority" value="high" id="priority-high"
                               <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'high') ? 'checked' : ''; ?>>
                        <label for="priority-high" class="priority-label">
                            <i class="fas fa-circle" style="color: #e74c3c; margin-right: 6px;"></i>
                            High
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="message">Message <span class="required">*</span></label>
                <textarea name="message" id="message" required 
                          placeholder="Please provide detailed feedback. The more information you provide, the better we can assist you..." 
                          maxlength="1000"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                <div class="char-counter" id="messageCounter">0/1000</div>
            </div>

            <div class="form-group">
                <label>Overall Rating (Optional)</label>
                <div class="rating-group">
                    <div class="rating-stars" id="ratingStars">
                        <input type="radio" name="rating" value="1" id="star1" 
                               <?php echo (isset($_POST['rating']) && $_POST['rating'] == '1') ? 'checked' : ''; ?>>
                        <label for="star1" class="star-icon">⭐</label>
                        
                        <input type="radio" name="rating" value="2" id="star2"
                               <?php echo (isset($_POST['rating']) && $_POST['rating'] == '2') ? 'checked' : ''; ?>>
                        <label for="star2" class="star-icon">⭐</label>
                        
                        <input type="radio" name="rating" value="3" id="star3"
                               <?php echo (isset($_POST['rating']) && $_POST['rating'] == '3') ? 'checked' : ''; ?>>
                        <label for="star3" class="star-icon">⭐</label>
                        
                        <input type="radio" name="rating" value="4" id="star4"
                               <?php echo (isset($_POST['rating']) && $_POST['rating'] == '4') ? 'checked' : ''; ?>>
                        <label for="star4" class="star-icon">⭐</label>
                        
                        <input type="radio" name="rating" value="5" id="star5"
                               <?php echo (isset($_POST['rating']) && $_POST['rating'] == '5') ? 'checked' : ''; ?>>
                        <label for="star5" class="star-icon">⭐</label>
                    </div>
                    <span class="rating-text" id="ratingText">Click to rate</span>
                </div>
            </div>

            <button type="submit" class="submit-btn" id="submitBtn">
                <span class="btn-text">Submit Feedback</span>
            </button>
        </form>
    </div>
    <?php endif; ?>

    <?php include('../includes/footer.php'); ?>

    <script>
        // Navbar toggle functionality
        const toggleBtn = document.getElementById('navbarToggle');
        const secondaryNavbar = document.getElementById('secondaryNavbar');

        toggleBtn.addEventListener('click', function () {
            this.classList.toggle('active');
            secondaryNavbar.classList.toggle('active');
            document.body.classList.toggle('nav-open');
        });

        // Category selection handler for "Others" option
        const categorySelect = document.getElementById('category');
        const otherCategoryInput = document.getElementById('otherCategoryInput');
        const otherCategoryField = document.getElementById('other_category');

        // Check on page load if "Others" is selected
        if (categorySelect && categorySelect.value === 'Others') {
            otherCategoryInput.classList.add('show');
            otherCategoryField.required = true;
        }

        categorySelect.addEventListener('change', function() {
            if (this.value === 'Others') {
                otherCategoryInput.classList.add('show');
                otherCategoryField.required = true;
                otherCategoryField.focus();
            } else {
                otherCategoryInput.classList.remove('show');
                otherCategoryField.required = false;
                otherCategoryField.value = '';
            }
        });

        // Character counters
        function setupCharCounter(inputId, counterId, maxLength) {
            const input = document.getElementById(inputId);
            const counter = document.getElementById(counterId);
            
            if (input && counter) {
                // Initialize counter on page load
                const initialLength = input.value.length;
                counter.textContent = `${initialLength}/${maxLength}`;
                
                input.addEventListener('input', function() {
                    const length = this.value.length;
                    counter.textContent = `${length}/${maxLength}`;
                    
                    if (length > maxLength * 0.9) {
                        counter.classList.add('danger');
                        counter.classList.remove('warning');
                    } else if (length > maxLength * 0.75) {
                        counter.classList.add('warning');
                        counter.classList.remove('danger');
                    } else {
                        counter.classList.remove('warning', 'danger');
                    }
                });
            }
        }

        setupCharCounter('subject', 'subjectCounter', 100);
        setupCharCounter('message', 'messageCounter', 1000);
        setupCharCounter('other_category', 'otherCategoryCounter', 50);

        // File upload enhancement
        const fileInput = document.getElementById('attachment');
        if (fileInput) {
            const fileLabel = document.querySelector('.file-upload-label span');

            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    const fileName = this.files[0].name;
                    const fileSize = (this.files[0].size / 1024 / 1024).toFixed(2);
                    fileLabel.textContent = `${fileName} (${fileSize}MB)`;
                } else {
                    fileLabel.textContent = 'Choose file or drag here (Max 5MB)';
                }
            });
        }

        // Form submission with loading state
        const form = document.getElementById('feedbackForm');
        const submitBtn = document.getElementById('submitBtn');

        if (form && submitBtn) {
            form.addEventListener('submit', function(e) {
                // Additional validation for "Others" category
                if (categorySelect.value === 'Others' && !otherCategoryField.value.trim()) {
                    e.preventDefault();
                    alert('Please specify your category when selecting "Others".');
                    otherCategoryField.focus();
                    return;
                }

                submitBtn.classList.add('loading');
                submitBtn.querySelector('.btn-text').textContent = 'Submitting...';
            });
        }

        // Enhanced Rating Stars System
        const ratingStars = document.querySelectorAll('.rating-stars input[type="radio"]');
        const starLabels = document.querySelectorAll('.rating-stars .star-icon');
        const ratingText = document.getElementById('ratingText');

        const ratingTexts = {
            1: 'Poor',
            2: 'Fair', 
            3: 'Good',
            4: 'Very Good',
            5: 'Excellent'
        };

        function updateStarDisplay(rating) {
            starLabels.forEach((label, index) => {
                if (index < rating) {
                    label.style.color = '#ffd700';
                } else {
                    label.style.color = '#ddd';
                }
            });
            
            if (rating > 0) {
                ratingText.textContent = ratingTexts[rating];
            } else {
                ratingText.textContent = 'Click to rate';
            }
        }

        // Handle star hover effects
        starLabels.forEach((label, index) => {
            label.addEventListener('mouseenter', function() {
                updateStarDisplay(index + 1);
            });
            
            label.addEventListener('click', function() {
                const ratingValue = index + 1;
                document.getElementById(`star${ratingValue}`).checked = true;
                updateStarDisplay(ratingValue);
            });
        });

        // Handle mouse leave to restore selected rating
        const ratingContainer = document.querySelector('.rating-stars');
        if (ratingContainer) {
            ratingContainer.addEventListener('mouseleave', function() {
                const checkedStar = document.querySelector('.rating-stars input[type="radio"]:checked');
                if (checkedStar) {
                    const rating = parseInt(checkedStar.value);
                    updateStarDisplay(rating);
                } else {
                    updateStarDisplay(0);
                }
            });
        }

        // Initialize rating display on page load
        document.addEventListener('DOMContentLoaded', function() {
            const checkedStar = document.querySelector('.rating-stars input[type="radio"]:checked');
            if (checkedStar) {
                const rating = parseInt(checkedStar.value);
                updateStarDisplay(rating);
            }
        });

        // Form validation enhancement
        const requiredFields = document.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            field.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    this.style.borderColor = 'var(--error-red)';
                } else {
                    this.style.borderColor = 'var(--main-green)';
                }
            });

            field.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.style.borderColor = '#e1e8e3';
                }
            });
        });

        // Auto-resize textarea
        const textarea = document.getElementById('message');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
            
            // Initialize height on page load
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        }

        // Initialize character counters on page load
        document.addEventListener('DOMContentLoaded', function() {
            setupCharCounter('subject', 'subjectCounter', 100);
            setupCharCounter('message', 'messageCounter', 1000);
            setupCharCounter('other_category', 'otherCategoryCounter', 50);
        });
    </script>
</body>
</html>
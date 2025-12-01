<?php
// Include database connection file
include('../includes/db.php');
include('../includes/dashboard_header.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $full_name = $_POST['full_name'];
    $cid_no = $_POST['cid_no'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $dzongkhag = $_POST['dzongkhag'];
    $phone_no = $_POST['phone_no'];
    $dob = $_POST['dob'];

    // Check if username already exists in employee_data table
    $check_query = "SELECT COUNT(*) as count FROM employee_data WHERE username = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    $check_stmt->close();

    if ($row['count'] > 0) {
        $error_message = 'Username already exists: Details for this username have already been added.';
    } elseif (!preg_match('/^\d{11}$/', $cid_no)) {
        $error_message = 'Invalid CID: Must be 11 digits.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid Email: Must contain @.';
    } elseif (!preg_match('/^(77|17)\d{6}$/', $phone_no)) {
        $error_message = 'Invalid Phone No: Must start with 77 or 17 and be 8 digits.';
    } elseif (calculateAge($dob) < 18) {
        $error_message = 'You must be 18 years or older to submit this form.';
    } else {
        // Insert data into database
        $query = "INSERT INTO employee_data (username, full_name, cid_no, email, address, dzongkhag, phone_no, dob) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssss", $username, $full_name, $cid_no, $email, $address, $dzongkhag, $phone_no, $dob);

        if ($stmt->execute()) {
            $success_message = 'Employee data added successfully!';
        } else {
            $error_message = 'Error: ' . $stmt->error;
        }

        $stmt->close();
    }
}

// Function to calculate age
function calculateAge($dob)
{
    $dob = new DateTime($dob);
    $today = new DateTime();
    $age = $today->diff($dob);
    return $age->y;  // Returns the age in years
}

// Check for unread notifications
$notification_query = "SELECT COUNT(*) AS count FROM notifications WHERE status = 'unread'";
$notification_result = $conn->query($notification_query);
$notification_count = 0;
if ($notification_result && $row = $notification_result->fetch_assoc()) {
    $notification_count = $row['count'];
}
// Get count of new reservations - CORRECTED LINE HERE
$new_reservations_query = "SELECT COUNT(*) as count FROM reservations WHERE status = 'reserved' AND is_viewed = 0";
$new_reservations_result = $conn->query($new_reservations_query);
$new_reservations_count = 0;
if ($new_reservations_result && $row = $new_reservations_result->fetch_assoc()) {
    $new_reservations_count = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Details</title>
    <link rel="stylesheet" href="employee.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
       <style>
        /* Main Color Palette */
        :root {
            --main-green: #2e6a33;
            /* Main green color */
            --dark-green: #1d4521;
            /* Darker shade for hover/focus */
            --light-green: #c9dccb;
            /* Light green for backgrounds */
            --accent-green: #4a9950;
            /* Accent green for highlights */
            --ultra-light-green: #eef5ef;
            /* Ultra light green for subtle backgrounds */
            --text-dark: #263028;
            /* Dark text color */
            --text-medium: #45634a;
            /* Medium text color */
            --text-light: #6b8f70;
            /* Light text color */
            --white: #ffffff;
            /* White */
            --error-red: #d64541;
            /* Error red */
            --success-green: #2ecc71;
            /* Success green */
            --warning-yellow: #f39c12;
            /* Warning yellow */

            /* Additional variables for consistency */
            --primary-color: var(--main-green);
            --secondary-color: var(--dark-green);
            --accent-color: var(--accent-green);
            --light-text: var(--text-light);
            --dark-text: var(--text-dark);
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
            background-color: var(--ultra-light-green);
            margin: 0;
            padding: 0;
            color: var(--text-dark);
            background-image: linear-gradient(135deg, var(--ultra-light-green) 0%, var(--light-green) 100%);
            min-height: 100vh;
            padding-top: 245px; /* Reduced to accommodate navbar properly */
        }

        .main-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Fixed Top Navbar */
        .navbar-toggle-container {
            position: fixed;
            top: 10;
            left: 0;
            right: 0;
            z-index: 1000;
            background-color: var(--accent-green);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 50px;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
        }

        .toggle-btn {
            background-color: var(--main-green);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .toggle-btn:hover {
            background-color: var(--dark-green);
            transform: scale(1.05);
        }

        .toggle-btn i {
            transition: transform 0.3s ease;
        }

        .toggle-btn.active i {
            transform: rotate(180deg);
        }

        .dashboard-title {
            font-family: 'Georgia', serif;
            font-size: 20px;
            color: white;
            margin: 0 15px;
            font-weight: 600;
        }

        /* Notification icon styles */
        .navbar-actions {
            display: flex;
            align-items: center;
        }

        .notification-icon {
            position: relative;
            color: white;
            font-size: 18px;
            margin-left: 15px;
            text-decoration: none;
        }

        .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--error-red);
            color: white;
            font-size: 11px;
            font-weight: bold;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 2px solid var(--accent-green);
        }

        /* Secondary Navbar */
        .secondary-navbar {
            position: fixed;
            top: 285px; /* Position right below the top navbar */
            left: 0;
            right: 0;
            background-color: var(--dark-green);
            height: 0;
            overflow: hidden;
            transition: height 0.3s ease;
            z-index: 999;
            font-family: 'Times New Roman', Times, serif;
        }

        .secondary-navbar.active {
            height: 60px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .navbar-links {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            gap: 20px;
            padding: 0 20px;
        }

        .navbar-links a {
            color: white;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 4px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }

        .navbar-links a i {
            margin-right: 6px;
        }

        .navbar-links a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }


        /* Form Container Styles */
        .content-wrapper {
            padding-top: 50px;
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            transition: padding-top 0.3s ease; /* Added transition for smooth movement */
        }

        /* Add this class to adjust content when navbar is active */
        .content-wrapper.navbar-active {
            padding-top: 80px; /* Adjusted to accommodate the expanded navbar */
        }

        .form-container {
            width: 100%;
            max-width: 700px;
            margin: 20px;
            background-color: var(--white);
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h2 {
            color: var(--main-green);
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .form-header p {
            color: var(--text-light);
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 15px;
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: var(--transition);
            background-color: #f8fafc;
            color: var(--text-dark);
        }

        .form-control:focus {
            border-color: var(--accent-green);
            box-shadow: 0 0 0 3px rgba(46, 106, 51, 0.15);
            outline: none;
            background-color: var(--white);
        }

        .form-control::placeholder {
            color: #a0aec0;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-col {
            flex: 1;
        }

        .btn-submit {
            background-color: var(--main-green);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: var(--transition);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background-color: var(--dark-green);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }

        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-green);
            border-left: 4px solid var(--success-green);
        }

        .alert-danger {
            background-color: rgba(214, 69, 65, 0.1);
            color: var(--error-red);
            border-left: 4px solid var(--error-red);
        }

        .input-icon-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #cbd5e0;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .form-container {
                padding: 25px;
                margin: 15px;
            }

            .content-wrapper.navbar-active {
                padding-top: 110px; /* Adjusted for mobile */
            }
        }
    </style>
</head>

<body>
    <!-- Navigation Bar Container -->
    <div class="navbar-toggle-container"
        style="display: flex; justify-content: space-between; align-items: center; padding: 10px;">

        <!-- Toggle Button (Left) -->
        <button class="toggle-btn" id="navbarToggle" title="Toggle Menu">
            <i class="fas fa-chevron-down"></i>
        </button>

        <!-- Notification Icon (Right) -->
        <a href="notification.php" class="notification-icon" title="Notifications" style="position: relative;">
            <i class="fas fa-bell"></i>
            <?php if ($new_reservations_count > 0): ?>
                <span class="badge"><?php echo $new_reservations_count; ?></span>
            <?php endif; ?>
        </a>

    </div>

    <!-- Secondary Navigation Bar (Initially Hidden) -->
    <div class="secondary-navbar" id="secondaryNavbar">
        <div class="navbar-links">
            <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
            <a href="add_employee_data.php"><i class="fas fa-user-plus"></i> Add Detials</a>
            <a href="add_books.php"><i class="fas fa-book"></i> Add Books</a>
            <a href="books.php"><i class="fas fa-book"></i> Books</a>
            <a href="track_books.php"><i class="fas fa-search"></i> Track Books</a>
            <a href="reserved_details.php">
                <i class="fas fa-users"></i> Reserved
                <?php if ($new_reservations_count > 0): ?>
                    <span class="notification-badge"><?php echo $new_reservations_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="confirmed_reservations.php"><i class="fas fa-check-circle"></i> Confirmed</a>
            <a href="payment_details.php"><i class="fas fa-money-check-alt"></i> Payment</a>
            <a href="report.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="../login.php"><i class="fas fa-sign-out-alt"></i> LogOut</a>
        </div>
    </div>

    <div class="content-wrapper" id="contentWrapper">
        <div class="form-container">
            <div class="form-header">
                <h2>Add Details</h2>
                <p>Enter your information to add to the system</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="employeeForm">
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <div class="input-icon-wrapper">
                                <input type="text" name="username" class="form-control" required 
                                       placeholder="Enter username" id="usernameInput"
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                <span class="input-icon"><i class="fas fa-user"></i></span>
                            </div>
                            <div id="usernameStatus" class="username-status"></div>
                        </div>
                    </div>

                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <div class="input-icon-wrapper">
                                <input type="text" name="full_name" class="form-control" required
                                    placeholder="Enter full name"
                                    value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                                <span class="input-icon"><i class="fas fa-id-card"></i></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label">CID Number</label>
                            <div class="input-icon-wrapper">
                                <input type="text" name="cid_no" class="form-control" required placeholder="11 digits"
                                       value="<?php echo isset($_POST['cid_no']) ? htmlspecialchars($_POST['cid_no']) : ''; ?>">
                                <span class="input-icon"><i class="fas fa-id-badge"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <div class="input-icon-wrapper">
                                <input type="date" name="dob" class="form-control" required
                                       value="<?php echo isset($_POST['dob']) ? $_POST['dob'] : ''; ?>">
                                <span class="input-icon"><i class="fas fa-calendar-alt"></i></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="input-icon-wrapper">
                        <input type="email" name="email" class="form-control" required placeholder="example@domain.com"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Address</label>
                    <div class="input-icon-wrapper">
                        <input type="text" name="address" class="form-control" required
                            placeholder="Enter complete address"
                            value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                        <span class="input-icon"><i class="fas fa-map-marker-alt"></i></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label">Dzongkhag</label>
                            <div class="input-icon-wrapper">
                                <select name="dzongkhag" class="form-control" required>
                                    <option value="">Select Dzongkhag</option>
                                    <?php
                                    $result = $conn->query("SELECT dzo_id, dzo_name FROM dzongkhag");
                                    while ($row = $result->fetch_assoc()) {
                                        $selected = (isset($_POST['dzongkhag']) && $_POST['dzongkhag'] == $row['dzo_id']) ? 'selected' : '';
                                        echo '<option value="' . $row['dzo_id'] . '" ' . $selected . '>' . $row['dzo_name'] . '</option>';
                                    }
                                    ?>
                                </select>
                                <span class="input-icon"><i class="fas fa-map"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <div class="input-icon-wrapper">
                                <input type="text" name="phone_no" class="form-control" required
                                    placeholder="77XXXXXX or 17XXXXXX"
                                    value="<?php echo isset($_POST['phone_no']) ? htmlspecialchars($_POST['phone_no']) : ''; ?>">
                                <span class="input-icon"><i class="fas fa-phone"></i></span>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    <i class="fas fa-user-plus"></i> Submit
                </button>
            </form>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

    <style>
        .username-status {
            margin-top: 5px;
            font-size: 0.85em;
            padding: 5px;
            border-radius: 3px;
            display: none;
        }
        
        .username-status.available {
            color: #28a745;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            display: block;
        }
        
        .username-status.taken {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            display: block;
        }
        
        .username-status.checking {
            color: #007bff;
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            display: block;
        }
        
        .form-control:disabled {
            background-color: #f8f9fa;
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .btn-submit:disabled {
            background-color: #6c757d;
            border-color: #6c757d;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .form-group.disabled {
            opacity: 0.6;
        }
    </style>

    <script>
        let usernameTimeout;
        let isUsernameAvailable = false;

        // Username availability check
        document.getElementById('usernameInput').addEventListener('input', function() {
            const username = this.value.trim();
            const statusDiv = document.getElementById('usernameStatus');
            const submitBtn = document.getElementById('submitBtn');
            
            // Clear previous timeout
            clearTimeout(usernameTimeout);
            
            if (username.length === 0) {
                statusDiv.style.display = 'none';
                statusDiv.className = 'username-status';
                isUsernameAvailable = false;
                disableAllFields(false); // Enable fields when username is cleared
                return;
            }
            
            // Show checking status
            statusDiv.className = 'username-status checking';
            statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking username...';
            statusDiv.style.display = 'block';
            
            // Debounce the check
            usernameTimeout = setTimeout(function() {
                checkUsernameAvailability(username);
            }, 500);
        });

        function checkUsernameAvailability(username) {
            const formData = new FormData();
            formData.append('check_username', username);
            
            fetch('check_username.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const statusDiv = document.getElementById('usernameStatus');
                
                if (data.exists) {
                    statusDiv.className = 'username-status taken';
                    statusDiv.innerHTML = '<i class="fas fa-times-circle"></i> Username already exists - Details for <strong>' + data.full_name + '</strong> already added';
                    isUsernameAvailable = false;
                    disableAllFields(true);
                } else {
                    statusDiv.className = 'username-status available';
                    statusDiv.innerHTML = '<i class="fas fa-check-circle"></i> Username available';
                    isUsernameAvailable = true;
                    disableAllFields(false);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const statusDiv = document.getElementById('usernameStatus');
                statusDiv.className = 'username-status';
                statusDiv.style.display = 'none';
                isUsernameAvailable = true; // Allow form submission if check fails
                disableAllFields(false);
            });
        }

        function disableAllFields(disable) {
            // Get all form controls except username field
            const formControls = document.querySelectorAll('.form-control:not(#usernameInput)');
            const submitBtn = document.getElementById('submitBtn');
            const formGroups = document.querySelectorAll('.form-group:not(:first-child)');
            
            formControls.forEach(control => {
                control.disabled = disable;
                if (disable) {
                    control.value = ''; // Clear values when disabling
                }
            });
            
            // Disable/Enable submit button
            submitBtn.disabled = disable;
            
            // Add visual indication to form groups
            formGroups.forEach(group => {
                if (disable) {
                    group.classList.add('disabled');
                } else {
                    group.classList.remove('disabled');
                }
            });
        }

        // Navbar toggle functionality
        document.getElementById('navbarToggle').addEventListener('click', function () {
            // Toggle the active class on the button and change icon
            this.classList.toggle('active');

            // Toggle the secondary navbar
            const secondaryNavbar = document.getElementById('secondaryNavbar');
            secondaryNavbar.classList.toggle('active');

            // Adjust content wrapper spacing - THIS IS THE KEY FIX
            const contentWrapper = document.getElementById('contentWrapper');
            contentWrapper.classList.toggle('navbar-active');
        });

        // Form validation
        document.getElementById('employeeForm').addEventListener('submit', function (e) {
            // Username availability check
            if (!isUsernameAvailable) {
                e.preventDefault();
                alert('Please enter a valid and available username.');
                document.getElementById('usernameInput').focus();
                return false;
            }

            // CID validation
            const cidInput = document.querySelector('input[name="cid_no"]');
            const cidPattern = /^\d{11}$/;
            if (!cidPattern.test(cidInput.value)) {
                e.preventDefault();
                alert('Invalid CID: Must be exactly 11 digits.');
                cidInput.focus();
                return false;
            }

            // Email validation
            const emailInput = document.querySelector('input[name="email"]');
            if (!emailInput.value.includes('@')) {
                e.preventDefault();
                alert('Invalid Email: Must contain @.');
                emailInput.focus();
                return false;
            }

            // Phone validation
            const phoneInput = document.querySelector('input[name="phone_no"]');
            const phonePattern = /^(77|17)\d{6}$/;
            if (!phonePattern.test(phoneInput.value)) {
                e.preventDefault();
                alert('Invalid Phone No: Must start with 77 or 17 and be 8 digits.');
                phoneInput.focus();
                return false;
            }

            // Age validation
            const dobInput = document.querySelector('input[name="dob"]');
            const birthDate = new Date(dobInput.value);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }

            if (age < 18) {
                e.preventDefault();
                alert('You must be 18 years or older to submit the form.');
                dobInput.focus();
                return false;
            }
        });

        // Add input field animations
        const formControls = document.querySelectorAll('.form-control');
        formControls.forEach(control => {
            control.addEventListener('focus', function () {
                this.parentElement.querySelector('.input-icon').style.color = '#2e6a33';
            });

            control.addEventListener('blur', function () {
                this.parentElement.querySelector('.input-icon').style.color = '#cbd5e0';
            });
        });
    </script>
</body>

</html>
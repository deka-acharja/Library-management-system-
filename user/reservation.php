<?php
include('../includes/db.php');
include('../includes/dashboard_header.php');

// Initialize messages
$success_message = "";
$error_message = "";
$show_success_modal = false;

// Check if user is logged in (assuming session management exists)
if (!isset($_SESSION['user_id'])) {
    // Redirect to login or set a guest ID as needed
    // Uncomment the next line if you want to force login
    // header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
}

// Admin: Handle reservation confirmation via GET
if (isset($_GET['confirm_id'])) {
    $reservation_id = $conn->real_escape_string($_GET['confirm_id']);

    // Use prepared statement for security
    $res_query = $conn->prepare("SELECT book_id FROM reservations WHERE reservation_id = ? AND status = 'reserved'");
    $res_query->bind_param("s", $reservation_id);
    $res_query->execute();
    $res_result = $res_query->get_result();

    if ($res_result && $res_result->num_rows > 0) {
        $row = $res_result->fetch_assoc();
        $book_id = $row['book_id'];

        // Use prepared statement for security
        $book_check = $conn->prepare("SELECT quantity FROM inventory WHERE book_id = ?");
        $book_check->bind_param("i", $book_id);
        $book_check->execute();
        $book_result = $book_check->get_result();

        if ($book_result && $book_result->num_rows > 0) {
            $book_data = $book_result->fetch_assoc();

            if ($book_data['quantity'] > 0) {
                // Use transactions for data consistency
                $conn->begin_transaction();
                try {
                    // Update inventory
                    $update_inventory = $conn->prepare("UPDATE inventory SET quantity = quantity - 1 WHERE book_id = ? AND quantity > 0");
                    $update_inventory->bind_param("i", $book_id);
                    $update_inventory->execute();

                    if ($update_inventory->affected_rows == 0) {
                        throw new Exception("Failed to update inventory - no stock available");
                    }

                    // Update reservation status
                    $update_reservation = $conn->prepare("UPDATE reservations SET status = 'confirmed' WHERE reservation_id = ?");
                    $update_reservation->bind_param("s", $reservation_id);
                    $update_reservation->execute();

                    if ($update_reservation->affected_rows == 0) {
                        throw new Exception("Failed to update reservation status");
                    }

                    $conn->commit();
                    echo "<script>alert('Reservation confirmed and book quantity updated.'); window.location.href='reservations_list.php';</script>";
                    exit();
                } catch (Exception $e) {
                    $conn->rollback();
                    echo "<script>alert('Error: " . $e->getMessage() . "'); window.location.href='reservations_list.php';</script>";
                    exit();
                }
            } else {
                echo "<script>alert('Book quantity is zero. Cannot confirm.'); window.location.href='reservations_list.php';</script>";
                exit();
            }
        } else {
            echo "<script>alert('Book not found in inventory.'); window.location.href='reservations_list.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Reservation not found or already confirmed.'); window.location.href='reservations_list.php';</script>";
        exit();
    }
}

// User: Handle reservation form display and submission
if (isset($_GET['book_id']) && is_numeric($_GET['book_id'])) {
    $book_id = intval($_GET['book_id']);

    // Use prepared statement for security
    $book_query = $conn->prepare("SELECT b.title, b.author, b.serialno, i.quantity 
              FROM books b 
              JOIN inventory i ON b.id = i.book_id 
              WHERE b.id = ?");
    $book_query->bind_param("i", $book_id);
    $book_query->execute();
    $result = $book_query->get_result();

    if ($result && $result->num_rows > 0) {
        $book = $result->fetch_assoc();
        $title = $book['title'];
        $author = $book['author'];
        $serial_no = $book['serialno'];
        $quantity = $book['quantity'];

        if ($quantity <= 0) {
            die("This book is currently not available for reservation.");
        }
    } else {
        die("Book not found.");
    }
} else {
    die("Invalid Book ID.");
}

// Handle reservation submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Server-side validation
    $validation_errors = [];

    $name = $conn->real_escape_string($_POST['name']);
    $cid = $conn->real_escape_string($_POST['cid']);
    $email = $conn->real_escape_string($_POST['email']);
    $address = $conn->real_escape_string($_POST['address']);
    $phone = $conn->real_escape_string($_POST['phone']);

    // Email validation - must contain @
    if (strpos($email, '@') === false) {
        $validation_errors[] = "Email must contain @ symbol";
    }

    // CID validation - must be exactly 11 digits
    if (!preg_match('/^\d{11}$/', $cid)) {
        $validation_errors[] = "CID must be exactly 11 digits";
    }

    // Phone validation - must be 8 digits starting with 17 or 77
    if (!preg_match('/^(17|77)\d{6}$/', $phone)) {
        $validation_errors[] = "Phone number must be 8 digits starting with 17 or 77";
    }

    if (empty($validation_errors)) {
        $reservation_id = 'RES-' . sprintf("%04d", rand(1000, 9999));
        $status = 'reserved';

        // Use transaction for data consistency
        $conn->begin_transaction();
        try {
            // Insert reservation
            $insert_query = $conn->prepare("INSERT INTO reservations (reservation_id, name, cid, email, address, phone, book_id, title, author, serial_no, status) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert_query->bind_param("ssssssissss", $reservation_id, $name, $cid, $email, $address, $phone, $book_id, $title, $author, $serial_no, $status);
            $insert_query->execute();

            if ($insert_query->affected_rows == 0) {
                throw new Exception("Failed to create reservation record");
            }

            // Update inventory - moved here from previous location to avoid duplicate updates
            // This will happen only if the form is submitted, not when viewing the form
            $update_inventory = $conn->prepare("UPDATE inventory SET quantity = quantity - 1 WHERE book_id = ? AND quantity > 0");
            $update_inventory->bind_param("i", $book_id);
            $update_inventory->execute();

            if ($update_inventory->affected_rows == 0) {
                throw new Exception("No books available in inventory");
            }

            // Add notification
            $user_id = $_SESSION['user_id'] ?? null;
            $message = "$name has reserved the book: $title.";

            // Check if notification function exists and call it
            if (function_exists('add_user_notification')) {
                add_notification($conn, $user_id, $message, "success");
            }

            $conn->commit();
            $success_message = "Reservation Successful! Your reservation ID is: $reservation_id. Book quantity has been updated.";
            $show_success_modal = true;
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error: " . $e->getMessage();
        }
    } else {
        $error_message = implode(". ", $validation_errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Book Reservation</title>
    <link rel="stylesheet" href="user.css">
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
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
            background-color: var(--ultra-light-green);
            margin: 0;
            padding: 0;
        }

        .reservation-form {
            width: 60%;
            margin: 30px auto;
            padding: 25px;
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-top: 4px solid var(--main-green);
            margin-top: 275px;
        }
        
        .reservation-form h2 {
            text-align: center;
            margin-bottom: 25px;
            color: var(--main-green);
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--text-medium);
        }
        
        .reservation-form input[type="text"],
        .reservation-form input[type="email"],
        .reservation-form textarea {
            width: 100%;
            padding: 12px;
            margin: 6px 0;
            border: 1px solid var(--light-green);
            border-radius: 5px;
            font-size: 15px;
            transition: border 0.3s ease;
            box-sizing: border-box;
        }
        
        .reservation-form input[type="text"]:focus,
        .reservation-form input[type="email"]:focus,
        .reservation-form textarea:focus {
            border-color: var(--accent-green);
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 153, 80, 0.2);
        }
        
        .reservation-form input[type="text"]:read-only {
            background-color: var(--light-green);
            border: 1px solid var(--light-green);
            color: var(--text-medium);
            font-weight: 500;
        }
        
        .reservation-form input[type="submit"] {
            padding: 12px 20px;
            background-color: var(--main-green);
            color: var(--white);
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.5px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            transition: background-color 0.3s ease;
        }
        
        .reservation-form input[type="submit"]:hover {
            background-color: var(--dark-green);
        }
        
        .message {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 5px;
            font-weight: 500;
        }
        
        .success {
            background-color: rgba(46, 204, 113, 0.15);
            color: var(--success-green);
            border: 1px solid rgba(46, 204, 113, 0.3);
        }
        
        .error {
            background-color: rgba(214, 69, 65, 0.15);
            color: var(--error-red);
            border: 1px solid rgba(214, 69, 65, 0.3);
        }

        .book-details {
            background-color: var(--ultra-light-green);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid var(--accent-green);
        }

        .book-details h3 {
            margin-top: 0;
            color: var(--text-medium);
            font-size: 18px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 50px;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease-in-out;
        }

        .modal-content {
            background-color: var(--white);
            margin: 15% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease-in-out;
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: var(--success-green);
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .modal-body {
            margin: 20px 0;
        }

        .modal-body p {
            color: var(--text-dark);
            font-size: 16px;
            line-height: 1.5;
            margin: 10px 0;
        }

        .modal-footer {
            margin-top: 25px;
        }

        .modal-btn {
            background-color: var(--success-green);
            color: var(--white);
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .modal-btn:hover {
            background-color: #27ae60;
        }

        .success-icon {
            font-size: 48px;
            color: var(--success-green);
            margin-bottom: 15px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { 
                opacity: 0;
                transform: translateY(-50px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .reservation-form {
                width: 90%;
                padding: 15px;
            }
            
            .modal-content {
                margin: 20% auto;
                padding: 20px;
            }
        }
    </style>


</head>

<body>
    <div class="reservation-form">
        <h2>Book Reservation Form</h2>

        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="book-details">
            <h3>Book Information</h3>
            <div class="form-group">
                <label>Title:</label>
                <input type="text" value="<?php echo htmlspecialchars($title); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Author:</label>
                <input type="text" value="<?php echo htmlspecialchars($author); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Serial Number:</label>
                <input type="text" value="<?php echo htmlspecialchars($serial_no); ?>" readonly>
            </div>
        </div>

        <?php if (!$show_success_modal): ?>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?book_id=' . $book_id; ?>"
                onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="name">Full Name:</label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                    <div class="validation-error" id="name-error"></div>
                </div>

                <div class="form-group">
                    <label for="cid">CID:</label>
                    <input type="text" id="cid" name="cid" placeholder="Enter your 11-digit CID" required maxlength="11"
                        pattern="\d{11}">
                    <div class="validation-error" id="cid-error"></div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address:</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email address" required>
                    <div class="validation-error" id="email-error"></div>
                </div>

                <div class="form-group">
                    <label for="address">Address:</label>
                    <textarea id="address" name="address" placeholder="Enter your address" rows="3" required></textarea>
                    <div class="validation-error" id="address-error"></div>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number:</label>
                    <input type="text" id="phone" name="phone" placeholder="Enter phone number (17xxxxxx or 77xxxxxx)"
                        required maxlength="8" pattern="(17|77)\d{6}">
                    <div class="validation-error" id="phone-error"></div>
                </div>

                <input type="submit" value="Submit Reservation">
            </form>
        <?php endif; ?>
    </div>

    <!-- Success Modal -->
    <?php if ($show_success_modal): ?>
        <div id="successModal" class="modal" style="display: block;">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="success-icon">✓</div>
                    <h3>Reservation Successful!</h3>
                </div>
                <div class="modal-body">
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                    <p><strong>Book:</strong> <?php echo htmlspecialchars($title); ?></p>
                    <p><strong>Author:</strong> <?php echo htmlspecialchars($author); ?></p>
                </div>
                <div class="modal-footer">
                    <button class="modal-btn" onclick="redirectToBookDetails()">OK</button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function validateForm() {
            let isValid = true;

            // Clear previous errors
            clearErrors();

            // Get form values
            const name = document.getElementById('name').value.trim();
            const cid = document.getElementById('cid').value.trim();
            const email = document.getElementById('email').value.trim();
            const address = document.getElementById('address').value.trim();
            const phone = document.getElementById('phone').value.trim();

            // Validate name
            if (name === '') {
                showError('name', 'Full name is required');
                isValid = false;
            }

            // Validate CID - must be exactly 11 digits
            if (!/^\d{11}$/.test(cid)) {
                showError('cid', 'CID must be exactly 11 digits');
                isValid = false;
            }

            // Validate email - must contain @
            if (!email.includes('@')) {
                showError('email', 'Email must contain @ symbol');
                isValid = false;
            }

            // Validate address
            if (address === '') {
                showError('address', 'Address is required');
                isValid = false;
            }

            // Validate phone - must be 8 digits starting with 17 or 77
            if (!/^(17|77)\d{6}$/.test(phone)) {
                showError('phone', 'Phone number must be 8 digits starting with 17 or 77');
                isValid = false;
            }

            return isValid;
        }

        function showError(fieldId, message) {
            const field = document.getElementById(fieldId);
            const errorDiv = document.getElementById(fieldId + '-error');

            field.classList.add('input-error');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }

        function clearErrors() {
            const errorDivs = document.querySelectorAll('.validation-error');
            const inputFields = document.querySelectorAll('input, textarea');

            errorDivs.forEach(div => {
                div.style.display = 'none';
                div.textContent = '';
            });

            inputFields.forEach(field => {
                field.classList.remove('input-error');
            });
        }

        // Real-time validation
        document.getElementById('cid').addEventListener('input', function (e) {
            // Only allow digits
            this.value = this.value.replace(/\D/g, '');

            // Clear error if valid
            if (this.value.length === 11) {
                this.classList.remove('input-error');
                document.getElementById('cid-error').style.display = 'none';
            }
        });

        document.getElementById('phone').addEventListener('input', function (e) {
            // Only allow digits
            this.value = this.value.replace(/\D/g, '');

            // Clear error if valid format
            if (/^(17|77)\d{6}$/.test(this.value)) {
                this.classList.remove('input-error');
                document.getElementById('phone-error').style.display = 'none';
            }
        });

        document.getElementById('email').addEventListener('input', function (e) {
            // Clear error if contains @
            if (this.value.includes('@')) {
                this.classList.remove('input-error');
                document.getElementById('email-error').style.display = 'none';
            }
        });

        function redirectToBookDetails() {
            window.location.href = 'book_details.php?book_id=<?php echo $book_id; ?>';
        }

        // Optional: Close modal when clicking outside
        window.onclick = function (event) {
            var modal = document.getElementById('successModal');
            if (event.target == modal) {
                redirectToBookDetails();
            }
        }

        // Optional: Close modal with Escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                var modal = document.getElementById('successModal');
                if (modal && modal.style.display === 'block') {
                    redirectToBookDetails();
                }
            }
        });
    </script>

    <?php include('../includes/footer.php'); ?>
</body>

</html>
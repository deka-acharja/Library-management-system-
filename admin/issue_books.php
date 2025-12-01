<?php
include('../includes/db.php');
include('../includes/dashboard_header.php');

// Include PHPMailer classes
require_once '../PHPMailer/src/Exception.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$user = $book = null;
$errors = [];
$success_message = '';

// Email sending function
function sendBookIssueEmail($recipientEmail, $recipientName, $bookTitle, $issueDate, $returnDate) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = '11206005480@rim.edu.bt';
        $mail->Password = 'wsgm gyrg bgbl vynx';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('11206005480@rim.edu.bt', 'Library Management System');
        $mail->addAddress($recipientEmail, $recipientName);
        
        $mail->isHTML(true);
        $mail->Subject = 'Book Issued - Library Management System';
        
        $emailBody = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #eef5ef; padding: 20px;'>
            <div style='background-color: #2e6a33; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                <h1 style='margin: 0;'>Library Management System</h1>
                <h2 style='margin: 10px 0 0 0; font-weight: normal;'>Book Issue Confirmation</h2>
            </div>
            <div style='background-color: white; padding: 30px; border-radius: 0 0 8px 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <p style='color: #263028; font-size: 16px; margin-bottom: 20px;'>Dear <strong>{$recipientName}</strong>,</p>
                
                <p style='color: #263028; font-size: 16px; line-height: 1.6;'>
                    A book has been successfully issued to you. Please find the details below:
                </p>
                
                <div style='background-color: #c9dccb; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2e6a33;'>
                    <h3 style='color: #1d4521; margin-top: 0;'>Book Details:</h3>
                    <p style='color: #263028; margin: 8px 0;'><strong>Book Title:</strong> {$bookTitle}</p>
                    <p style='color: #263028; margin: 8px 0;'><strong>Issue Date:</strong> {$issueDate}</p>
                    <p style='color: #263028; margin: 8px 0;'><strong>Return Due Date:</strong> {$returnDate}</p>
                </div>
                
                <div style='background-color: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f39c12;'>
                    <p style='color: #856404; margin: 0; font-weight: bold;'>
                        ⚠️ Important Reminder: Please return the book by the due date to avoid late fees.
                    </p>
                </div>
                
                <p style='color: #45634a; font-size: 14px; margin-top: 30px;'>
                    Thank you for using our library services!<br>
                    <strong>Library Management Team</strong>
                </p>
            </div>
        </div>";
        
        $mail->Body = $emailBody;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle CID search
    if (isset($_POST['search_cid'])) {
        $cid = trim($_POST['cid']);
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = 'user'");
        $stmt->bind_param("s", $cid);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if (!$user) {
            $errors[] = "User with CID '$cid' not found or not a regular user.";
        }
    }

    // Handle Serial Number Search
    if (isset($_POST['search_serial'])) {
        $serial = trim($_POST['serialno']);
        $stmt = $conn->prepare("SELECT * FROM books WHERE serialno = ?");
        $stmt->bind_param("s", $serial);
        $stmt->execute();
        $book = $stmt->get_result()->fetch_assoc();
        if (!$book) {
            $errors[] = "Book with Serial Number '$serial' not found.";
        }
        
        // Re-fetch user data for display
        if (isset($_POST['cid'])) {
            $cid = $_POST['cid'];
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = 'user'");
            $stmt->bind_param("s", $cid);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        }
    }

    // Handle Issue Book
    if (isset($_POST['issue_book'])) {
        $cid = $_POST['cid'];
        $serial = $_POST['serialno'];
        $issue_date = $_POST['issue_date'];
        $return_date = $_POST['return_date'];

        // Validate dates
        if (strtotime($return_date) <= strtotime($issue_date)) {
            $errors[] = "Return date must be after issue date.";
        } else {
            // Check if book is available
            $stmt = $conn->prepare("SELECT id, availability, title FROM books WHERE serialno = ?");
            $stmt->bind_param("s", $serial);
            $stmt->execute();
            $bookData = $stmt->get_result()->fetch_assoc();

            if ($bookData && $bookData['availability'] === 'Available') {
                $book_id = $bookData['id'];

                // Get user email for notification
                $stmt = $conn->prepare("SELECT name, email FROM users WHERE username = ?");
                $stmt->bind_param("s", $cid);
                $stmt->execute();
                $userData = $stmt->get_result()->fetch_assoc();

                // Insert into borrow_books table
                $stmt = $conn->prepare("INSERT INTO borrow_books (book_id, cid, taken_date, return_due_date, status) VALUES (?, ?, ?, ?, 'borrowed')");
                $stmt->bind_param("isss", $book_id, $cid, $issue_date, $return_date);
                
                if ($stmt->execute()) {
                    // Update book availability
                    $stmt = $conn->prepare("UPDATE books SET availability = 'Not Available' WHERE id = ?");
                    $stmt->bind_param("i", $book_id);
                    $stmt->execute();

                    // Send email notification
                    $emailSent = sendBookIssueEmail(
                        $userData['email'], 
                        $userData['name'], 
                        $bookData['title'], 
                        date('F j, Y', strtotime($issue_date)), 
                        date('F j, Y', strtotime($return_date))
                    );

                    $success_message = "Book issued successfully!" . ($emailSent ? " Email notification sent to user." : " (Email notification failed to send)");
                } else {
                    $errors[] = "Failed to issue book. Please try again.";
                }
            } else {
                $errors[] = "Book is not available for issue.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Book - Library Management System</title>
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--ultra-light-green) 0%, var(--light-green) 100%);
            min-height: 100vh;
            color: var(--text-dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, var(--main-green) 0%, var(--dark-green) 100%);
            color: var(--white);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(46, 106, 51, 0.3);
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 300;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .card {
            background: var(--white);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--light-green);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .card h2 {
            color: var(--main-green);
            margin-bottom: 20px;
            font-size: 1.5rem;
            border-bottom: 2px solid var(--light-green);
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--light-green);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: var(--ultra-light-green);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--main-green);
            background-color: var(--white);
            box-shadow: 0 0 0 3px rgba(46, 106, 51, 0.1);
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--main-green) 0%, var(--accent-green) 100%);
            color: var(--white);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--dark-green) 0%, var(--main-green) 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 106, 51, 0.4);
        }

        .btn-success {
            background: var(--success-green);
            color: var(--white);
        }

        .btn-success:hover {
            background: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.4);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert-error {
            background-color: #ffeaea;
            border-color: var(--error-red);
            color: #721c24;
        }

        .alert-success {
            background-color: #eafaf1;
            border-color: var(--success-green);
            color: #0f5132;
        }

        .alert ul {
            margin: 0;
            padding-left: 20px;
        }

        .user-details, .book-details {
            background: var(--ultra-light-green);
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid var(--main-green);
        }

        .user-details h3, .book-details h3 {
            color: var(--main-green);
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .detail-item {
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid var(--light-green);
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-item strong {
            color: var(--text-dark);
            display: inline-block;
            width: 120px;
        }

        .detail-item span {
            color: var(--text-medium);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .step {
            display: flex;
            align-items: center;
            margin: 0 15px;
        }

        .step-number {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: var(--light-green);
            color: var(--text-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }

        .step.active .step-number {
            background: var(--main-green);
            color: var(--white);
        }

        .step.completed .step-number {
            background: var(--success-green);
            color: var(--white);
        }

        .availability-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .available {
            background-color: #d4edda;
            color: #155724;
        }

        .not-available {
            background-color: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .step-indicator {
                flex-direction: column;
                align-items: center;
            }
            
            .step {
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 Issue a Book</h1>
            <p>Library Management System - Book Issue Portal</p>
        </div>

        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step <?= $user ? 'completed' : 'active' ?>">
                <div class="step-number">1</div>
                <span>Search User</span>
            </div>
            <div class="step <?= $book ? 'completed' : ($user ? 'active' : '') ?>">
                <div class="step-number">2</div>
                <span>Find Book</span>
            </div>
            <div class="step <?= ($user && $book) ? 'active' : '' ?>">
                <div class="step-number">3</div>
                <span>Issue Book</span>
            </div>
        </div>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>⚠️ Error:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Success Message -->
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <strong>✅ Success:</strong> <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <!-- Step 1: Search User -->
        <div class="card">
            <h2>🔍 Step 1: Search User by CID</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="cid">Enter Student/User CID:</label>
                    <input type="text" 
                           id="cid" 
                           name="cid" 
                           class="form-control" 
                           required 
                           placeholder="Enter CID number..."
                           value="<?= isset($_POST['cid']) ? htmlspecialchars($_POST['cid']) : '' ?>">
                </div>
                <button type="submit" name="search_cid" class="btn btn-primary">
                    🔍 Search User
                </button>
            </form>
        </div>

        <!-- User Details Display -->
        <?php if ($user): ?>
            <div class="card">
                <div class="user-details">
                    <h3>👤 User Information</h3>
                    <div class="detail-item">
                        <strong>Name:</strong>
                        <span><?= htmlspecialchars($user['name']) ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Email:</strong>
                        <span><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>CID:</strong>
                        <span><?= htmlspecialchars($user['username']) ?></span>
                    </div>
                </div>

                <!-- Step 2: Search Book -->
                <h2>📖 Step 2: Search Book by Serial Number</h2>
                <form method="POST">
                    <input type="hidden" name="cid" value="<?= htmlspecialchars($user['username']) ?>">
                    <div class="form-group">
                        <label for="serialno">Enter Book Serial Number:</label>
                        <input type="text" 
                               id="serialno" 
                               name="serialno" 
                               class="form-control" 
                               required 
                               placeholder="Enter book serial number...">
                    </div>
                    <button type="submit" name="search_serial" class="btn btn-primary">
                        📚 Search Book
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Book Details and Issue Form -->
        <?php if ($book): ?>
            <div class="card">
                <div class="book-details">
                    <h3>📚 Book Information</h3>
                    <div class="detail-item">
                        <strong>Title:</strong>
                        <span><?= htmlspecialchars($book['title']) ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Author:</strong>
                        <span><?= htmlspecialchars($book['author']) ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Genre:</strong>
                        <span><?= htmlspecialchars($book['genre']) ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Serial No:</strong>
                        <span><?= htmlspecialchars($book['serialno']) ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Availability:</strong>
                        <span class="availability-badge <?= strtolower(str_replace(' ', '-', $book['availability'])) ?>">
                            <?= htmlspecialchars($book['availability']) ?>
                        </span>
                    </div>
                </div>

                <?php if ($book['availability'] === 'Available'): ?>
                    <!-- Step 3: Issue Book Form -->
                    <h2>✅ Step 3: Issue Book</h2>
                    <form method="POST">
                        <input type="hidden" name="cid" value="<?= htmlspecialchars($_POST['cid']) ?>">
                        <input type="hidden" name="serialno" value="<?= htmlspecialchars($_POST['serialno']) ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="issue_date">Issue Date:</label>
                                <input type="date" 
                                       id="issue_date" 
                                       name="issue_date" 
                                       class="form-control" 
                                       required 
                                       value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="form-group">
                                <label for="return_date">Return Due Date:</label>
                                <input type="date" 
                                       id="return_date" 
                                       name="return_date" 
                                       class="form-control" 
                                       required 
                                       value="<?= date('Y-m-d', strtotime('+14 days')) ?>">
                            </div>
                        </div>
                        
                        <button type="submit" name="issue_book" class="btn btn-success">
                            📤 Issue Book & Send Email Notification
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-error">
                        <strong>❌ Book Not Available:</strong> This book is currently not available for issue.
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include('../includes/footer.php'); ?>

    <script>
        // Auto-focus on the first input field
        document.addEventListener('DOMContentLoaded', function() {
            const firstInput = document.querySelector('input[type="text"]');
            if (firstInput) {
                firstInput.focus();
            }
        });

        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.style.borderColor = 'var(--error-red)';
                    } else {
                        field.style.borderColor = 'var(--light-green)';
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                }
            });
        });

        // Date validation
        const issueDateInput = document.getElementById('issue_date');
        const returnDateInput = document.getElementById('return_date');

        if (issueDateInput && returnDateInput) {
            issueDateInput.addEventListener('change', function() {
                const issueDate = new Date(this.value);
                const returnDate = new Date(returnDateInput.value);
                
                if (returnDate <= issueDate) {
                    const newReturnDate = new Date(issueDate);
                    newReturnDate.setDate(newReturnDate.getDate() + 14);
                    returnDateInput.value = newReturnDate.toISOString().split('T')[0];
                }
            });

            returnDateInput.addEventListener('change', function() {
                const issueDate = new Date(issueDateInput.value);
                const returnDate = new Date(this.value);
                
                if (returnDate <= issueDate) {
                    alert('Return date must be after issue date.');
                    this.focus();
                }
            });
        }
    </script>
</body>
</html>
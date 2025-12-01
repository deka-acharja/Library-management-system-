<?php
// Include database connection first
include('../includes/db.php');

// Include PHPMailer classes
require_once '../PHPMailer/src/Exception.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Error logging setup
function logError($message)
{
    error_log("[LIBRARY SYSTEM] " . date('Y-m-d H:i:s') . " - " . $message);
}

// Email sending function
function sendNotification($recipientEmail, $recipientName, $subject, $body, $status = '')
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = '11206005480@rim.edu.bt';
        $mail->Password = 'wsgm gyrg bgbl vynx';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Enable verbose debug output for troubleshooting
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = function ($str, $level) {
            logError("SMTP Debug: $str");
        };

        // Recipients
        $mail->setFrom('11206005480@rim.edu.bt', 'Library Management System');
        $mail->addAddress($recipientEmail, $recipientName);
        $mail->addReplyTo('11206005480@rim.edu.bt', 'Library Management System');

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->CharSet = 'UTF-8';

        $emailTemplate = getEmailTemplate($status, $recipientName, $body);
        $mail->Body = $emailTemplate;

        // Alternative plain text body
        $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));

        $mail->send();
        logError("Email sent successfully to: $recipientEmail - Subject: $subject");
        return true;
    } catch (Exception $e) {
        logError("Email Error: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());
        return false;
    }
}

// Email template function
function getEmailTemplate($status, $name, $content)
{
    $baseStyles = '
        body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #eef5ef; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 30px; border-radius: 10px 10px 0 0; }
        .content { background-color: #ffffff; padding: 40px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 20px rgba(46, 106, 51, 0.1); }
        .footer { text-align: center; margin-top: 30px; padding: 20px; font-size: 12px; color: #6b8f70; }
        .button { display: inline-block; padding: 12px 30px; margin: 25px 0; text-decoration: none; border-radius: 25px; font-weight: bold; text-align: center; }
        .signature { margin-top: 40px; padding-top: 20px; border-top: 1px solid #c9dccb; }
        .urgent-notice { background-color: #eef5ef; border: 2px solid #4a9950; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .fine-box { background-color: #fef3f3; border: 1px solid #d64541; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .status-badge { display: inline-block; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .issued-notice { background-color: #c9dccb; border: 2px solid #2e6a33; padding: 20px; margin: 20px 0; border-radius: 8px; }
    ';
    $year = date('Y');
    $footer = "
        <div class='footer'>
            <p>© $year Library Management System. All rights reserved.</p>
            <p>📧 For assistance, contact: library@example.com | 📞 Phone: +975-XXXXXXXX</p>
        </div>
    ";

    $headerColor = '#2e6a33';
    $header = "📚 Book Issued Successfully!";
    $specialStyles = '
        .header { background: linear-gradient(135deg, #2e6a33, #1d4521); color: white; }
        .button { background-color: #2e6a33; color: white; }
        .issued-notice { background: linear-gradient(135deg, #c9dccb, #eef5ef); border: 2px solid #2e6a33; }
    ';

    $styles = '<style>' . $baseStyles . $specialStyles . '</style>';
    $template = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($header) . '</title>
        ' . $styles . '
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1 style="margin: 0; font-size: 24px;">' . $header . '</h1>
            </div>
            <div class="content">
                <p style="font-size: 16px; margin-bottom: 20px; color: #263028;">Dear <strong>' . htmlspecialchars($name) . '</strong>,</p>
                <div class="issued-notice">
                    <h3 style="color: #1d4521; margin-top: 0;">📖 Book Successfully Issued!</h3>
                    ' . $content . '
                </div>
                <div class="signature">
                    <p><strong style="color: #263028;">Best regards,</strong></p>
                    <p><strong style="color: #2e6a33;">📚 Library Management Team</strong></p>
                    <p style="font-style: italic; color: #6b8f70;">Your trusted partner in knowledge and learning</p>
                </div>
            </div>
            ' . $footer . '
        </div>
    </body>
    </html>
    ';
    return $template;
}

// Check if reservation ID is provided
if (!isset($_GET['id'])) {
    header("Location: reservations_list.php?error=No reservation ID provided");
    exit;
}

$reservation_id = $_GET['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Start transaction
        $conn->begin_transaction();

        // Get form data
        $taken_date = $_POST['taken_date'];
        $return_due_date = $_POST['return_due_date'];
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';

        // Validate dates
        if (empty($taken_date) || empty($return_due_date)) {
            throw new Exception("Both taken date and return due date are required.");
        }

        if (strtotime($return_due_date) <= strtotime($taken_date)) {
            throw new Exception("Return due date must be after the taken date.");
        }

        // Get reservation details first
        $reservationStmt = $conn->prepare("SELECT r.*, b.title as book_title, b.author 
            FROM reservations r
            JOIN books b ON r.book_id = b.id
            WHERE r.reservation_id = ? AND r.status = 'reserved'");
        
        if ($reservationStmt === false) {
            throw new Exception("Database prepare error: " . $conn->error);
        }

        $reservationStmt->bind_param("s", $reservation_id);
        $reservationStmt->execute();
        $result = $reservationStmt->get_result();
        $reservation = $result->fetch_assoc();
        $reservationStmt->close();

        if (!$reservation) {
            throw new Exception("No reservation found with ID: $reservation_id or reservation is not in 'reserved' status");
        }

        // Insert into taken_books table - CORRECTED to match your table structure
        $insertTakenStmt = $conn->prepare("INSERT INTO taken_books (reservation_id, book_id, cid, taken_date, return_due_date, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        
        if ($insertTakenStmt === false) {
            throw new Exception("Database prepare error for taken_books: " . $conn->error);
        }

        $insertTakenStmt->bind_param("sssss", 
            $reservation_id, 
            $reservation['book_id'], 
            $reservation['cid'], 
            $taken_date, 
            $return_due_date
        );
        
        if (!$insertTakenStmt->execute()) {
            throw new Exception("Failed to insert into taken_books: " . $insertTakenStmt->error);
        }

        $insertTakenStmt->close();

        // Update reservation status to 'taken'
        $updateStmt = $conn->prepare("UPDATE reservations SET status = 'taken' WHERE reservation_id = ? AND status = 'reserved'");
        
        if ($updateStmt === false) {
            throw new Exception("Database prepare error for reservations update: " . $conn->error);
        }

        $updateStmt->bind_param("s", $reservation_id);
        
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update reservation status: " . $updateStmt->error);
        }

        if ($updateStmt->affected_rows == 0) {
            throw new Exception("No reservation was updated. It may have already been processed.");
        }

        $updateStmt->close();

        // Prepare email content
        $subject = '📚 Book Issued - ' . $reservation['book_title'];
        $body = "Your reserved book has been successfully issued!<br><br>
                 📖 <strong>Book Title:</strong> " . htmlspecialchars($reservation['book_title']) . "<br>
                 ✍️ <strong>Author:</strong> " . htmlspecialchars($reservation['author']) . "<br>
                 📅 <strong>Issue Date:</strong> " . date('F j, Y', strtotime($taken_date)) . "<br>
                 ⏰ <strong>Return Due Date:</strong> " . date('F j, Y', strtotime($return_due_date)) . "<br><br>
                 <strong>⚠️ Important Reminders:</strong><br>
                 • Please return the book by the due date to avoid late fees<br>
                 • Take good care of the book during the loan period<br>
                 • Contact us if you need to extend the return date<br><br>";

        if (!empty($notes)) {
            $body .= "<strong>📝 Additional Notes:</strong><br>" . htmlspecialchars($notes) . "<br><br>";
        }

        $body .= "Thank you for using our library services. Happy reading! 📚";

        // Send notification email
        $emailSent = sendNotification(
            $reservation['email'], 
            $reservation['name'], 
            $subject, 
            $body, 
            'issued'
        );

        // Commit transaction
        $conn->commit();
        
        logError("Book issued successfully for reservation: $reservation_id");

        // Prepare success message
        $successMsg = "Book issued successfully to " . $reservation['name'];
        if (!$emailSent) {
            $successMsg .= " (Note: Email notification failed to send)";
        }

        // Redirect with success message
        header("Location: reservations_list.php?msg=" . urlencode($successMsg) . "&status=taken");
        exit;

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error = "Error issuing book: " . $e->getMessage();
        logError($error);
    }
}

// Fetch reservation details
$stmt = $conn->prepare("SELECT r.*, b.title as book_title, b.author, b.isbn, b.serialno 
    FROM reservations r
    JOIN books b ON r.book_id = b.id
    WHERE r.reservation_id = ? AND r.status = 'reserved'");

if ($stmt === false) {
    die("Database prepare error: " . $conn->error);
}

$stmt->bind_param("s", $reservation_id);
$stmt->execute();
$result = $stmt->get_result();
$reservation = $result->fetch_assoc();
$stmt->close();

if (!$reservation) {
    header("Location: reservations_list.php?error=Reservation not found or not in reserved status");
    exit;
}

// Include header
include('../includes/dashboard_header.php');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Book - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
    /* Main content styles */
    .main-content {
        padding: 20px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
    }

    /* Page header */
    .page-header {
        background: linear-gradient(135deg, #2e6a33, #1d4521);
        color: white;
        padding: 40px 20px;
        border-radius: 15px;
        margin-bottom: 30px;
        box-shadow: 0 8px 32px rgba(46, 106, 51, 0.3);
    }

    .page-header h1 {
        margin: 0;
        font-size: 2.5rem;
        font-weight: 700;
    }

    .subtitle {
        margin: 10px 0 0 0;
        opacity: 0.9;
        font-size: 1.1rem;
    }

    /* Info cards */
    .info-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
        border: 1px solid #e9ecef;
    }

    .info-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
    }

    .card-header {
        background: linear-gradient(135deg, #2e6a33, #4a9950);
        color: white;
        padding: 20px;
        border-bottom: none;
    }

    .card-header h3 {
        margin: 0;
        font-size: 1.3rem;
        font-weight: 600;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        border-bottom: 1px solid #f8f9fa;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: #495057;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .info-value {
        color: #2e6a33;
        font-weight: 500;
    }

    /* Form card */
    .form-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        border: 1px solid #e9ecef;
    }

    .date-section, .notes-section {
        padding: 25px;
    }

    .notes-section {
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
    }

    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .required-field {
        color: #dc3545;
        font-weight: bold;
    }

    .form-control {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 12px 15px;
        transition: all 0.3s ease;
        font-size: 1rem;
    }

    .form-control:focus {
        border-color: #2e6a33;
        box-shadow: 0 0 0 0.2rem rgba(46, 106, 51, 0.25);
    }

    /* Button styles */
    .button-group {
        padding: 25px;
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .btn-issue {
        background: linear-gradient(135deg, #2e6a33, #4a9950);
        border: none;
        color: white;
        padding: 12px 30px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(46, 106, 51, 0.3);
    }

    .btn-issue:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(46, 106, 51, 0.4);
        color: white;
    }

    .btn-secondary {
        background: #6c757d;
        border: none;
        color: white;
        padding: 12px 30px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-2px);
        color: white;
    }

    /* Breadcrumb */
    .breadcrumb {
        background: white;
        border-radius: 10px;
        padding: 15px 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .breadcrumb-item a {
        color: #2e6a33;
        text-decoration: none;
        font-weight: 500;
    }

    .breadcrumb-item a:hover {
        color: #1d4521;
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .page-header h1 {
            font-size: 2rem;
        }
        
        .button-group {
            flex-direction: column;
        }
        
        .btn-issue, .btn-secondary {
            width: 100%;
        }
    }
    </style>
</head>

<body>
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header text-center">
                <div class="container">
                    <h1><i class="fas fa-hand-paper me-3"></i>Issue Book</h1>
                    <p class="subtitle">Complete the book issuance process for reserved items</p>
                </div>
            </div>

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="reservations_list.php">
                            <i class="fas fa-list me-1"></i>Reservations
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <i class="fas fa-hand-paper me-1"></i>Issue Book
                    </li>
                </ol>
            </nav>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Reservation Details -->
                <div class="col-lg-6 mb-4">
                    <div class="info-card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-user-circle me-2"></i>
                                Reservation Details
                            </h3>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-id-card"></i>Reservation ID
                            </span>
                            <span class="info-value"><?= htmlspecialchars($reservation['reservation_id']) ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-user"></i>Full Name
                            </span>
                            <span class="info-value"><?= htmlspecialchars($reservation['name']) ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-id-badge"></i>Citizen ID
                            </span>
                            <span class="info-value"><?= htmlspecialchars($reservation['cid']) ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-envelope"></i>Email Address
                            </span>
                            <span class="info-value"><?= htmlspecialchars($reservation['email']) ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-phone"></i>Phone Number
                            </span>
                            <span class="info-value"><?= htmlspecialchars($reservation['phone']) ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-calendar"></i>Reservation Date
                            </span>
                            <span class="info-value"><?= date('F j, Y', strtotime($reservation['reservation_date'])) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Book Details -->
                <div class="col-lg-6 mb-4">
                    <div class="info-card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-book me-2"></i>
                                Book Information
                            </h3>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-bookmark"></i>Book Title
                            </span>
                            <span class="info-value"><?= htmlspecialchars($reservation['book_title']) ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-pen"></i>Author
                            </span>
                            <span class="info-value"><?= htmlspecialchars($reservation['author']) ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-barcode"></i>ISBN Number
                            </span>
                            <span class="info-value"><?= htmlspecialchars($reservation['isbn']) ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-hashtag"></i>Serial Number
                            </span>
                            <span class="info-value"><?= htmlspecialchars($reservation['serialno']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Issue Form -->
            <div class="row">
                <div class="col-12">
                    <div class="form-card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-hand-paper me-2"></i>
                                Complete Book Issuance
                            </h3>
                        </div>
                        
                        <form method="POST" id="issueForm">
                            <div class="date-section">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="taken_date" class="form-label">
                                            <i class="fas fa-calendar-alt"></i>
                                            Issue Date <span class="required-field">*</span>
                                        </label>
                                        <input type="date" 
                                               class="form-control" 
                                               id="taken_date" 
                                               name="taken_date" 
                                               value="<?= date('Y-m-d') ?>" 
                                               required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="return_due_date" class="form-label">
                                            <i class="fas fa-calendar-check"></i>
                                            Return Due Date <span class="required-field">*</span>
                                        </label>
                                        <input type="date" 
                                               class="form-control" 
                                               id="return_due_date" 
                                               name="return_due_date" 
                                               min="<?= date('Y-m-d', strtotime('+1 day')) ?>" 
                                               required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="notes-section">
                                <label for="notes" class="form-label">
                                    <i class="fas fa-sticky-note"></i>
                                    Additional Notes & Instructions
                                </label>
                                <textarea class="form-control" 
                                          id="notes" 
                                          name="notes" 
                                          rows="4" 
                                          placeholder="Enter any special instructions, conditions, or notes for this book issuance..."></textarea>
                                <small class="text-muted mt-2 d-block">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Optional: Add any special handling instructions or conditions for this book loan.
                                </small>
                            </div>
                            
                            <div class="button-group">
                                <button type="submit" class="btn btn-issue">
                                    <i class="fas fa-hand-paper me-2"></i>
                                    Issue Book Now
                                </button>
                                <a href="reservations_list.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Back to Reservations
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('menuToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('active');
            document.getElementById('mainContent')?.classList.toggle('expanded');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');
            
            if (window.innerWidth <= 768 && 
                sidebar?.classList.contains('active') && 
                !sidebar.contains(event.target) && 
                event.target !== menuToggle) {
                sidebar.classList.remove('active');
                document.getElementById('mainContent')?.classList.remove('expanded');
            }
        });

        // Set default return due date (14 days from issue date)
        document.getElementById('taken_date').addEventListener('change', function() {
            const takenDate = new Date(this.value);
            const returnDate = new Date(takenDate);
            returnDate.setDate(returnDate.getDate() + 14); // 14 days loan period
            
            const returnDateInput = document.getElementById('return_due_date');
            returnDateInput.value = returnDate.toISOString().split('T')[0];
            returnDateInput.min = new Date(takenDate.getTime() + 86400000).toISOString().split('T')[0]; // Next day
        });

        // Form validation with enhanced UX
        document.getElementById('issueForm').addEventListener('submit', function(e) {
            const takenDate = new Date(document.getElementById('taken_date').value);
            const returnDate = new Date(document.getElementById('return_due_date').value);
            
            if (returnDate <= takenDate) {
                e.preventDefault();
                showAlert('Return due date must be after the issue date.', 'error');
                return false;
            }
            
            // Enhanced confirmation dialog
            const bookTitle = "<?= htmlspecialchars($reservation['book_title']) ?>";
            const userName = "<?= htmlspecialchars($reservation['name']) ?>";
            const confirmMessage = `Are you sure you want to issue "${bookTitle}" to ${userName}?\n\nThis will:\n• Update the reservation status to 'taken'\n• Create a new record in taken_books table\n• Send an email notification to the user\n• Set the return due date to ${returnDate.toLocaleDateString()}`;
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalContent = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            submitBtn.disabled = true;
            
            // Re-enable after 5 seconds (fallback)
            setTimeout(() => {
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            }, 5000);
        });

        // Initialize default return date on page load
        document.addEventListener('DOMContentLoaded', function() {
            const takenDateInput = document.getElementById('taken_date');
            if (takenDateInput.value) {
                takenDateInput.dispatchEvent(new Event('change'));
            }
            
            // Add smooth scroll behavior
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
            
            // Enhanced hover effects for info cards
            document.querySelectorAll('.info-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });

        // Utility function for alerts
        function showAlert(message, type = 'info') {
            const alertClass = type === 'error' ? 'alert-danger' : 'alert-info';
            const icon = type === 'error' ? 'fas fa-exclamation-triangle' : 'fas fa-info-circle';
            
            const alertHTML = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <i class="${icon} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            const alertContainer = document.querySelector('.container-fluid');
            alertContainer.insertAdjacentHTML('afterbegin', alertHTML);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alert = alertContainer.querySelector('.alert');
                if (alert) {
                    alert.classList.remove('show');
                    setTimeout(() => alert.remove(), 300);
                }
            }, 5000);
        }

        // Form field animations
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
                this.parentElement.style.transition = 'transform 0.3s ease';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Auto-save draft to prevent data loss (using memory storage instead of sessionStorage)
        let formData = {};
        const formInputs = document.querySelectorAll('#issueForm input, #issueForm textarea');
        formInputs.forEach(input => {
            // Save data on change
            input.addEventListener('input', function() {
                formData[this.name] = this.value;
            });
        });
    </script>
    
    <?php include('../includes/footer.php'); ?>
</body>
</html>

<?php
$conn->close();
?>
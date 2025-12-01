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

// Enhanced Email sending function with better error handling
function sendNotification($recipientEmail, $recipientName, $subject, $body, $status = '')
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = '11206005480@rim.edu.bt';
        $mail->Password = 'wsgm gyrg bgbl vynx'; // Consider using environment variables
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Enable verbose debug output for troubleshooting
        $mail->SMTPDebug = 0; // Set to 2 for debugging
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

// Enhanced email template function
function getEmailTemplate($status, $name, $content)
{
    $baseStyles = '
        body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f5f7fa; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 30px; border-radius: 10px 10px 0 0; }
        .content { background-color: #ffffff; padding: 40px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .footer { text-align: center; margin-top: 30px; padding: 20px; font-size: 12px; color: #666; }
        .button { display: inline-block; padding: 12px 30px; margin: 25px 0; text-decoration: none; border-radius: 25px; font-weight: bold; text-align: center; }
        .signature { margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; }
        .urgent-notice { background-color: #fff3cd; border: 2px solid #ffc107; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .fine-box { background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .status-badge { display: inline-block; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .thank-you { font-size: 18px; color: #17a2b8; text-align: center; margin: 25px 0; padding: 20px; background-color: #d1ecf1; border-radius: 10px; }
    ';
    $year = date('Y');
    $footer = "
        <div class='footer'>
            <p>© $year Library Management System. All rights reserved.</p>
            <p>📧 For assistance, contact: library@example.com | 📞 Phone: +975-XXXXXXXX</p>
        </div>
    ";

    $headerColor = '#17a2b8';
    $header = "📚 Book Return Confirmed";
    $specialStyles = '
        .header { background: linear-gradient(135deg, #17a2b8, #138496); color: white; }
        .button { background-color: #17a2b8; color: white; }
    ';

    $content = $content . "<div class='thank-you'>🙏 Thank you for being a responsible library user!</div>";

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
                <p style="font-size: 16px; margin-bottom: 20px;">Dear <strong>' . htmlspecialchars($name) . '</strong>,</p>
                <div style="line-height: 1.6;">
                    ' . $content . '
                </div>
                <div class="signature">
                    <p><strong>Best regards,</strong></p>
                    <p><strong>📚 Library Management Team</strong></p>
                    <p style="font-style: italic; color: #666;">Your trusted partner in knowledge and learning</p>
                </div>
            </div>
            ' . $footer . '
        </div>
    </body>
    </html>
    ';
    return $template;
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: reservations_list.php?error=" . urlencode("No reservation ID provided"));
    exit;
}

$reservation_id = $_GET['id'];

// Handle return book action
if (isset($_POST['return_book'])) {
    try {
        // Start transaction
        $conn->begin_transaction();

        // Fetch reservation details with book information and taken book details
        $stmt = $conn->prepare("SELECT r.*, b.title, b.id as book_id, b.availability, 
                               tb.taken_date, tb.return_due_date, tb.id as taken_book_id
                               FROM reservations r
                               JOIN books b ON r.book_id = b.id
                               LEFT JOIN taken_books tb ON r.reservation_id = tb.reservation_id
                               WHERE r.reservation_id = ?");

        if ($stmt === false) {
            throw new Exception("Database prepare error: " . $conn->error);
        }

        $stmt->bind_param("s", $reservation_id);
        if (!$stmt->execute()) {
            throw new Exception("Database execute error: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $reservation = $result->fetch_assoc();
        $stmt->close();

        if (!$reservation) {
            throw new Exception("Reservation not found with ID: " . $reservation_id);
        }

        // Check if the book can be returned (must be 'taken' or 'due' status)
        if (!in_array($reservation['status'], ['taken', 'due'])) {
            throw new Exception("Only books with 'taken' or 'due' status can be returned. Current status: " . $reservation['status']);
        }

        // Calculate fine if overdue
        $fine_amount = 0;
        $return_date = date('Y-m-d');
        $taken_date = $reservation['taken_date'];
        $due_date = $reservation['return_due_date'];

        if ($due_date && $return_date > $due_date) {
            $days_overdue = (strtotime($return_date) - strtotime($due_date)) / (60 * 60 * 24);
            $fine_amount = $days_overdue * 10; // 10 Nu per day fine
        }

        $updateStmt = $conn->prepare("UPDATE reservations SET status = 'returned' WHERE reservation_id = ?");
        if ($updateStmt === false) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        $updateStmt->bind_param("s", $reservation_id);
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update reservation status: " . $updateStmt->error);
        }
        $updateStmt->close();

        // Update book availability - increase available quantity
        $updateBookStmt = $conn->prepare("UPDATE inventory SET quantity = quantity + 1 WHERE book_id = ?");
        if ($updateBookStmt === false) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        $updateBookStmt->bind_param("i", $reservation['book_id']);
        if (!$updateBookStmt->execute()) {
            throw new Exception("Failed to update book availability: " . $updateBookStmt->error);
        }
        $updateBookStmt->close();

        // Remove or update the taken_books record
        if ($reservation['taken_book_id']) {
            $deleteTakenStmt = $conn->prepare("DELETE FROM taken_books WHERE id = ?");
            if ($deleteTakenStmt === false) {
                throw new Exception("Database prepare error: " . $conn->error);
            }
            $deleteTakenStmt->bind_param("i", $reservation['taken_book_id']);
            if (!$deleteTakenStmt->execute()) {
                throw new Exception("Failed to remove taken book record: " . $deleteTakenStmt->error);
            }
            $deleteTakenStmt->close();
        }

        // Prepare email content
        $email = $reservation['email'];
        $name = $reservation['name'];
        $bookTitle = $reservation['title'];
        $subject = '📚 Book Return Confirmation';

        $body = "Your book <strong>\"$bookTitle\"</strong> has been successfully returned to the library.<br><br>
                 📅 <strong>Return Date:</strong> " . date('d/m/Y', strtotime($return_date)) . "<br>
                 📋 <strong>Reservation ID:</strong> $reservation_id<br>";

        if ($fine_amount > 0) {
            $body .= "💰 <strong>Fine Amount:</strong> Nu. " . number_format($fine_amount, 2) . " (for " . ceil($days_overdue) . " days overdue)<br>";
            $body .= "⚠️ <strong>Note:</strong> Please settle the fine amount at your earliest convenience.<br><br>";
        } else {
            $body .= "✅ <strong>Status:</strong> No fine applicable - returned on time<br><br>";
        }

        $body .= "We appreciate your responsible use of library resources!";

        // Send notification email
        $emailSent = sendNotification($email, $name, $subject, $body, 'returned');
        if (!$emailSent) {
            logError("Failed to send return notification email to $email");
        }

        // Commit transaction
        $conn->commit();
        logError("Book return processed successfully for reservation: $reservation_id");

        // Prepare success message
        $successMsg = "Book '$bookTitle' has been successfully returned";
        if ($fine_amount > 0) {
            $successMsg .= " with a fine of Nu. " . number_format($fine_amount, 2);
        }
        if (!$emailSent) {
            $successMsg .= " (Note: Email notification failed to send)";
        }

        // Redirect with success message
        header("Location: reservations_list.php?msg=" . urlencode($successMsg) . "&status=returned");
        exit;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $errorMsg = "Error processing book return: " . $e->getMessage();
        logError($errorMsg);
        $error = $errorMsg;
    }
}

// Fetch reservation details for display with taken book information
$stmt = $conn->prepare("SELECT r.*, b.title, b.author, b.isbn, b.serialno,
                       tb.taken_date, tb.return_due_date
                       FROM reservations r
                       JOIN books b ON r.book_id = b.id
                       LEFT JOIN taken_books tb ON r.reservation_id = tb.reservation_id
                       WHERE r.reservation_id = ?");

if ($stmt === false) {
    die("SQL prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $reservation_id);
$stmt->execute();
$result = $stmt->get_result();
$reservation = $result->fetch_assoc();

if (!$reservation) {
    header("Location: reservations_list.php?error=" . urlencode("Reservation not found"));
    exit;
}

// Check if the book can be returned
$canReturn = in_array($reservation['status'], ['taken', 'due']);

// Calculate fine if overdue
$fine_amount = 0;
$days_overdue = 0;
if ($reservation['return_due_date'] && $reservation['status'] == 'due') {
    $current_date = date('Y-m-d');
    $due_date = $reservation['return_due_date'];
    if ($current_date > $due_date) {
        $days_overdue = (strtotime($current_date) - strtotime($due_date)) / (60 * 60 * 24);
        $fine_amount = $days_overdue * 10; // 10 Nu per day fine
    }
}

// Include header
include('../includes/dashboard_header.php');

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Book - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
        }

        .status-taken {
            background-color: #d4edda;
            color: #155724;
        }

        .status-due {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-returned {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .fine-alert {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }

        .book-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: bold;
            color: #495057;
        }

        .return-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: bold;
            color: white;
            transition: all 0.3s ease;
        }

        .return-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .back-btn {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            border: none;
            padding: 10px 25px;
            border-radius: 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            transform: translateY(-1px);
            color: white;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- Back Button -->
                    <div class="mb-3">
                        <a href="reservations_list.php?status=<?= $reservation['status'] ?>" class="back-btn">
                            <i class="fas fa-arrow-left me-2"></i>Back to Reservations
                        </a>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-undo me-2"></i>Return Book
                            </h3>
                        </div>
                        <div class="card-body">
                            <!-- Book and User Details -->
                            <div class="book-details">
                                <h5 class="mb-3"><i class="fas fa-book me-2"></i>Book Details</h5>
                                <div class="detail-row">
                                    <span class="detail-label">Title:</span>
                                    <span><?= htmlspecialchars($reservation['title']) ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Author:</span>
                                    <span><?= htmlspecialchars($reservation['author']) ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">ISBN:</span>
                                    <span><?= htmlspecialchars($reservation['isbn']) ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Serial No:</span>
                                    <span><?= htmlspecialchars($reservation['serialno']) ?></span>
                                </div>
                            </div>

                            <div class="book-details">
                                <h5 class="mb-3"><i class="fas fa-user me-2"></i>User Details</h5>
                                <div class="detail-row">
                                    <span class="detail-label">Name:</span>
                                    <span><?= htmlspecialchars($reservation['name']) ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">CID:</span>
                                    <span><?= htmlspecialchars($reservation['cid']) ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Email:</span>
                                    <span><?= htmlspecialchars($reservation['email']) ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Phone:</span>
                                    <span><?= htmlspecialchars($reservation['phone']) ?></span>
                                </div>
                            </div>

                            <div class="book-details">
                                <h5 class="mb-3"><i class="fas fa-calendar me-2"></i>Reservation Details</h5>
                                <div class="detail-row">
                                    <span class="detail-label">Reservation ID:</span>
                                    <span><?= htmlspecialchars($reservation['reservation_id']) ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Taken Date:</span>
                                    <span><?= $reservation['taken_date'] ? date('d/m/Y', strtotime($reservation['taken_date'])) : 'N/A' ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Due Date:</span>
                                    <span><?= $reservation['return_due_date'] ? date('d/m/Y', strtotime($reservation['return_due_date'])) : 'N/A' ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Status:</span>
                                    <span class="status-badge status-<?= $reservation['status'] ?>">
                                        <?= htmlspecialchars($reservation['status']) ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Fine Alert if overdue -->
                            <?php if ($fine_amount > 0): ?>
                                <div class="fine-alert">
                                    <h5 class="text-warning mb-3">
                                        <i class="fas fa-exclamation-triangle me-2"></i>Overdue Fine
                                    </h5>
                                    <p class="mb-2">
                                        <strong>Days Overdue:</strong> <?= ceil($days_overdue) ?> days
                                    </p>
                                    <p class="mb-2">
                                        <strong>Fine Rate:</strong> Nu. 10 per day
                                    </p>
                                    <p class="mb-0">
                                        <strong>Total Fine:</strong> <span class="text-danger fs-5">Nu. <?= number_format($fine_amount, 2) ?></span>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <!-- Return Form -->
                            <?php if ($canReturn): ?>
                                <form method="POST" onsubmit="return confirmReturn()">
                                    <div class="text-center mt-4">
                                        <button type="submit" name="return_book" class="return-btn">
                                            <i class="fas fa-check me-2"></i>Process Return
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-warning text-center mt-4">
                                    <i class="fas fa-info-circle me-2"></i>
                                    This book cannot be returned. Current status: <strong><?= htmlspecialchars($reservation['status']) ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('mainContent').classList.toggle('expanded');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');

            if (window.innerWidth <= 768 &&
                sidebar.classList.contains('active') &&
                !sidebar.contains(event.target) &&
                event.target !== menuToggle) {
                sidebar.classList.remove('active');
                document.getElementById('mainContent').classList.remove('expanded');
            }
        });

        function confirmReturn() {
            const bookTitle = "<?= addslashes($reservation['title']) ?>";
            <?php if ($fine_amount > 0): ?>
                const fineAmount = "Nu. <?= number_format($fine_amount, 2) ?>";
                return confirm(`Are you sure you want to process the return for "${bookTitle}"?\n\nFine Amount: ${fineAmount}\n\nThis action will:\n- Update the book status to 'returned'\n- Send email confirmation to the user\n- Update book availability\n- Remove the taken book record`);
            <?php else: ?>
                return confirm(`Are you sure you want to process the return for "${bookTitle}"?\n\nThis action will:\n- Update the book status to 'returned'\n- Send email confirmation to the user\n- Update book availability\n- Remove the taken book record`);
            <?php endif; ?>
        }
    </script>
    <?php include('../includes/footer.php'); ?>
</body>

</html>
<?php
$stmt->close();
$conn->close();
?>
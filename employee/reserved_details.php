<?php
// Include PHPMailer classes
require_once '../PHPMailer/src/Exception.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Database connection
include('../includes/db.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Error logging setup
function logError($message)
{
    error_log("[LIBRARY SYSTEM] " . date('Y-m-d H:i:s') . " - " . $message);
}

// Enhanced function to check for overdue books and automatically mark them as due
function checkOverdueBooks($conn)
{
    $today = date('Y-m-d');

    // Find books that are past their due date and still have status 'taken'
    $overdueQuery = "SELECT r.reservation_id, r.name, r.email, b.title, t.return_due_date,
                     DATEDIFF(CURRENT_DATE(), t.return_due_date) as days_overdue
                     FROM reservations r
                     JOIN books b ON r.book_id = b.id
                     JOIN taken_books t ON r.reservation_id = t.reservation_id
                     WHERE r.status = 'taken' 
                     AND t.return_due_date < ?";

    $stmt = $conn->prepare($overdueQuery);
    if ($stmt === false) {
        logError("Failed to prepare overdue books query: " . $conn->error);
        return 0;
    }

    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();

    $markedCount = 0;

    while ($overdue = $result->fetch_assoc()) {
        $reservation_id = $overdue['reservation_id'];
        $email = $overdue['email'];
        $name = $overdue['name'];
        $bookTitle = $overdue['title'];
        $daysOverdue = $overdue['days_overdue'];
        $returnDueDate = $overdue['return_due_date'];

        // Update the status to 'due'
        $updateStmt = $conn->prepare("UPDATE reservations SET status = 'due', is_viewed = 0 WHERE reservation_id = ?");
        if ($updateStmt === false) {
            logError("Failed to prepare update statement: " . $conn->error);
            continue;
        }

        $updateStmt->bind_param("s", $reservation_id);
        if ($updateStmt->execute()) {
            // Calculate fine (Nu 100 per day)
            $fine = $daysOverdue * 100;

            // Send overdue notification email
            $subject = 'Book Return Overdue Notice';
            $body = "Your book <strong>$bookTitle</strong> was due for return on <strong>$returnDueDate</strong>.<br>
                     It is now <strong>$daysOverdue day(s)</strong> overdue.<br>
                     A fine of <strong>Nu. $fine</strong> has been applied at Nu. 100 per day.<br>
                     Please return the book and pay the fine as soon as possible to avoid additional penalties.";

            sendNotification($email, $name, $subject, $body, 'due');
            logError("Book automatically marked as due: $reservation_id ($bookTitle) - $daysOverdue days overdue");
            $markedCount++;
        } else {
            logError("Failed to update status for overdue book: " . $updateStmt->error);
        }

        $updateStmt->close();
    }

    $stmt->close();
    return $markedCount;
}

// Enhanced function to send daily reminders for overdue books
function sendDailyReminders($conn)
{
    $today = date('Y-m-d');

    // Find books with 'due' status that need daily reminders
    $reminderQuery = "SELECT r.reservation_id, r.name, r.email, b.title, t.return_due_date,
                      DATEDIFF(CURRENT_DATE(), t.return_due_date) as days_overdue,
                      r.last_reminder_date
                      FROM reservations r
                      JOIN books b ON r.book_id = b.id
                      JOIN taken_books t ON r.reservation_id = t.reservation_id
                      WHERE r.status = 'due'
                      AND (r.last_reminder_date IS NULL OR DATE(r.last_reminder_date) < CURRENT_DATE())";

    $result = $conn->query($reminderQuery);
    if ($result === false) {
        logError("Failed to query for reminder books: " . $conn->error);
        return 0;
    }

    $remindersCount = 0;

    while ($book = $result->fetch_assoc()) {
        $reservation_id = $book['reservation_id'];
        $email = $book['email'];
        $name = $book['name'];
        $bookTitle = $book['title'];
        $returnDueDate = $book['return_due_date'];
        $daysOverdue = $book['days_overdue'];

        // Calculate current fine
        $fine = $daysOverdue * 100;

        // Create daily reminder message
        $subject = 'Daily Overdue Book Reminder';
        $body = "This is your daily reminder that your book <strong>$bookTitle</strong> is overdue.<br>
                 <strong>Original Due Date:</strong> $returnDueDate<br>
                 <strong>Days Overdue:</strong> $daysOverdue day(s)<br>
                 <strong>Current Fine:</strong> Nu. $fine (Nu. 100 per day)<br><br>
                 Please return the book and pay the fine immediately to avoid additional charges.<br>
                 You will continue to receive daily reminders until the book is returned.";

        // Send the daily reminder
        if (sendNotification($email, $name, $subject, $body, 'due_reminder')) {
            // Update the last_reminder_date
            $updateStmt = $conn->prepare("UPDATE reservations SET last_reminder_date = NOW() WHERE reservation_id = ?");
            if ($updateStmt) {
                $updateStmt->bind_param("s", $reservation_id);
                if ($updateStmt->execute()) {
                    $remindersCount++;
                    logError("Daily reminder sent: $reservation_id ($bookTitle) - $daysOverdue days overdue");
                } else {
                    logError("Failed to update reminder date: " . $updateStmt->error);
                }
                $updateStmt->close();
            }
        }
    }

    if ($remindersCount > 0) {
        logError("Total daily reminders sent: $remindersCount");
    }

    return $remindersCount;
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
    ';

    $year = date('Y');
    $footer = "
        <div class='footer'>
            <p>© $year Library Management System. All rights reserved.</p>
            <p>📧 For assistance, contact: library@example.com | 📞 Phone: +975-XXXXXXXX</p>
        </div>
    ";

    switch ($status) {
        case 'confirmed':
            $headerColor = '#28a745';
            $header = "✅ Reservation Confirmed!";
            $specialStyles = '
                .header { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
                .button { background-color: #28a745; color: white; }
                .status-badge { background-color: #d4edda; color: #155724; }
            ';
            break;

        case 'rejected':
            $headerColor = '#dc3545';
            $header = "❌ Reservation Rejected";
            $specialStyles = '
                .header { background: linear-gradient(135deg, #dc3545, #c82333); color: white; }
                .button { background-color: #6c757d; color: white; }
                .status-badge { background-color: #f8d7da; color: #721c24; }
            ';
            break;

        case 'terminated':
            $headerColor = '#dc3545';
            $header = "🚫 Reservation Terminated";
            $specialStyles = '
                .header { background: linear-gradient(135deg, #dc3545, #c82333); color: white; }
                .button { background-color: #6c757d; color: white; }
                .status-badge { background-color: #f8d7da; color: #721c24; }
            ';
            break;

        case 'due':
        case 'due_reminder':
            $headerColor = '#dc3545';
            $header = $status == 'due_reminder' ? "⚠️ URGENT: Daily Overdue Reminder" : "⚠️ Return Overdue Notice";
            $specialStyles = '
                .header { background: linear-gradient(135deg, #dc3545, #bd2130); color: white; }
                .button { background-color: #dc3545; color: white; }
                .urgent-notice { background: linear-gradient(135deg, #fff3cd, #ffeaa7); border: 2px solid #ffc107; }
            ';
            if ($status == 'due_reminder') {
                $content = "<div class='urgent-notice'><h3 style='color: #856404; margin-top: 0;'>📢 DAILY REMINDER</h3>" . $content . "</div>";
            } else {
                $content = "<div class='urgent-notice'><h3 style='color: #856404; margin-top: 0;'>⚠️ OVERDUE NOTICE</h3>" . $content . "</div>";
            }
            break;

        case 'returned':
            $headerColor = '#17a2b8';
            $header = "📚 Book Return Confirmed";
            $specialStyles = '
                .header { background: linear-gradient(135deg, #17a2b8, #138496); color: white; }
                .button { background-color: #17a2b8; color: white; }
                .thank-you { font-size: 18px; color: #17a2b8; text-align: center; margin: 25px 0; padding: 20px; background-color: #d1ecf1; border-radius: 10px; }
            ';
            $content = $content . "<div class='thank-you'>🙏 Thank you for being a responsible library user!</div>";
            break;

        default:
            $headerColor = '#2e6a33';
            $header = "📖 Library Notification";
            $specialStyles = '
                .header { background: linear-gradient(135deg, #2e6a33, #28a745); color: white; }
                .button { background-color: #2e6a33; color: white; }
            ';
            break;
    }

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

// Check if action is requested
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];

    try {
        // Start transaction
        $conn->begin_transaction();

        // Fetch reservation details with better error handling
        $stmt = $conn->prepare("SELECT r.*, b.title, b.id as book_id, t.return_due_date 
            FROM reservations r
            JOIN books b ON r.book_id = b.id
            LEFT JOIN taken_books t ON r.reservation_id = t.reservation_id
            WHERE r.reservation_id = ?");

        if ($stmt === false) {
            throw new Exception("Database prepare error: " . $conn->error);
        }

        $stmt->bind_param("s", $id);
        if (!$stmt->execute()) {
            throw new Exception("Database execute error: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $reservation = $result->fetch_assoc();
        $stmt->close();

        if (!$reservation) {
            throw new Exception("Reservation not found with ID: " . $id);
        }

        // Extract reservation data
        $email = $reservation['email'];
        $name = $reservation['name'];
        $bookTitle = $reservation['title'];
        $book_id = $reservation['book_id'];
        $currentStatus = $reservation['status'];
        $returnDate = !empty($reservation['return_due_date']) ? new DateTime($reservation['return_due_date']) : null;
        $today = new DateTime();

        $status = '';
        $subject = '';
        $body = '';

        // Process different actions
        switch ($action) {
            case 'confirm':
                if ($currentStatus != 'reserved') {
                    throw new Exception("Only reserved books can be confirmed. Current status: $currentStatus");
                }

                $status = 'confirmed';
                $subject = '✅ Reservation Confirmed - Action Required';
                $body = "Great news! Your reservation for <strong>\"$bookTitle\"</strong> has been <strong>confirmed</strong>.<br><br>
             📅 <strong>Important:</strong> Please collect the book within <strong>2 days</strong> from today to avoid automatic cancellation.<br>
             🕒 <strong>Library Hours:</strong> Monday-Friday: 9:00 AM - 6:00 PM<br>
             📍 <strong>Collection Point:</strong> Main Library Counter<br><br>
             Please bring a valid ID for book collection.";

                // Also update is_viewed to 0 in reservations table
                $updateReservation = $conn->prepare("UPDATE reservations SET is_viewed = 0 WHERE reservation_id = ?");
                if ($updateReservation) {
                    $updateReservation->bind_param("i", $reservation_id);
                    if (!$updateReservation->execute()) {
                        echo "Failed to update is_viewed: " . $conn->error;
                    }
                    $updateReservation->close();
                } else {
                    echo "Failed to prepare reservation update statement: " . $conn->error;
                }
                break;

            case 'reject':
                if ($currentStatus != 'reserved') {
                    throw new Exception("Only reserved books can be rejected. Current status: $currentStatus");
                }
                $status = 'rejected';
                $subject = '❌ Reservation Request Declined';
                $body = "We regret to inform you that your reservation request for <strong>\"$bookTitle\"</strong> has been <strong>declined</strong>.<br><br>
                         This could be due to:<br>
                         • Book is currently unavailable<br>
                         • Maximum reservation limit reached<br>
                         • Technical issues<br><br>
                         You may try reserving another copy or check back later when the book becomes available.";

                // Update inventory if table exists
                $checkTable = $conn->query("SHOW TABLES LIKE 'inventory'");
                if ($checkTable && $checkTable->num_rows > 0) {
                    $updateInventory = $conn->prepare("UPDATE inventory SET quantity = quantity + 1 WHERE book_id = ?");
                    if ($updateInventory) {
                        $updateInventory->bind_param("i", $book_id);
                        $updateInventory->execute();
                        $updateInventory->close();

                        // Also update is_viewed to 0 in reservations table
                        $updateReservation = $conn->prepare("UPDATE reservations SET is_viewed = 0 WHERE reservation_id = ?");
                        if ($updateReservation) {
                            $updateReservation->bind_param("i", $reservation_id);
                            $updateReservation->execute();
                            $updateReservation->close();
                        } else {
                            echo "Failed to prepare reservation update statement: " . $conn->error;
                        }
                    } else {
                        echo "Failed to prepare inventory update statement: " . $conn->error;
                    }
                }
                break;

            case 'terminate':
                if ($currentStatus != 'confirmed') {
                    throw new Exception("Only confirmed reservations can be terminated. Current status: $currentStatus");
                }
                $status = 'terminated';
                $subject = '🚫 Reservation Terminated Due to Delay';
                $body = "Your reservation for <strong>\"$bookTitle\"</strong> has been <strong>terminated</strong> due to delay in collection.<br><br>
                         📋 <strong>Reason:</strong> Book was not collected within the specified timeframe<br>
                         🔄 <strong>Next Steps:</strong> You may reserve the book again if it's still available<br><br>
                         We appreciate your understanding and encourage you to collect books promptly in future reservations.";

                // Update inventory if table exists
                $checkTable = $conn->query("SHOW TABLES LIKE 'inventory'");
                if ($checkTable && $checkTable->num_rows > 0) {
                    $updateInventory = $conn->prepare("UPDATE inventory SET quantity = quantity + 1 WHERE book_id = ?");
                    if ($updateInventory) {
                        $updateInventory->bind_param("i", $book_id);
                        $updateInventory->execute();
                        $updateInventory->close();

                        // Also update is_viewed to 0 in reservations table
                        $updateReservation = $conn->prepare("UPDATE reservations SET is_viewed = 0 WHERE reservation_id = ?");
                        if ($updateReservation) {
                            $updateReservation->bind_param("i", $reservation_id);
                            $updateReservation->execute();
                            $updateReservation->close();
                        } else {
                            echo "Failed to prepare reservation update statement: " . $conn->error;
                        }
                    } else {
                        echo "Failed to prepare inventory update statement: " . $conn->error;
                    }
                }
                break;

            case 'due':
                if ($currentStatus != 'taken') {
                    throw new Exception("Only taken books can be marked as due. Current status: $currentStatus");
                }
                $status = 'due';
                $subject = '⚠️ Book Return Overdue - Immediate Action Required';

                if ($returnDate) {
                    $interval = $today->diff($returnDate);
                    $daysOverdue = $interval->days;
                    $fine = $daysOverdue * 100;
                    $body = "Your book <strong>\"$bookTitle\"</strong> was due for return on <strong>" . $returnDate->format('Y-m-d') . "</strong>.<br><br>
                             ⏰ <strong>Days Overdue:</strong> $daysOverdue day(s)<br>
                             💰 <strong>Fine Amount:</strong> Nu. $fine (Nu. 100 per day)<br><br>
                             Please return the book and pay the fine immediately to avoid additional penalties.<br>
                             Daily reminder emails will be sent until the book is returned.";
                } else {
                    $body = "Your book <strong>\"$bookTitle\"</strong> is now marked as overdue.<br>
                             Please return it immediately to avoid penalties.";
                }
                break;

            case 'return':
                if (!in_array($currentStatus, ['taken', 'due', 'due paid'])) {
                    throw new Exception("Only taken books or books with paid fines can be returned. Current status: $currentStatus");
                }

                $status = 'returned';

                // Check if book was returned on time or late
                $wasOverdue = false;
                $daysLate = 0;

                if ($returnDate) {
                    $interval = $today->diff($returnDate);
                    $daysLate = $interval->days;
                    $wasOverdue = ($today > $returnDate);
                }

                if ($wasOverdue) {
                    $subject = '📚 Book Return Confirmation - Late Return';
                    $body = "Thank you for returning <strong>\"$bookTitle\"</strong>.<br><br>
                             📅 <strong>Return Status:</strong> Returned $daysLate day(s) late<br>
                             ⚠️ <strong>Note:</strong> Please remember to return books on time in the future to avoid fines.<br><br>
                             We appreciate your use of our library services and look forward to serving you again!";
                } else {
                    $subject = '📚 Book Return Confirmation - On Time';
                    $body = "Thank you for returning <strong>\"$bookTitle\"</strong> on time!<br><br>
                             ✅ <strong>Return Status:</strong> Returned within due date<br>
                             🌟 <strong>Excellent!</strong> You are a responsible library user.<br><br>
                             Thank you for using our library services. We look forward to serving you again!";
                }

                // Update inventory if table exists
                $checkTable = $conn->query("SHOW TABLES LIKE 'inventory'");
                if ($checkTable && $checkTable->num_rows > 0) {
                    $updateInventory = $conn->prepare("UPDATE inventory SET quantity = quantity + 1 WHERE book_id = ?");
                    if ($updateInventory) {
                        $updateInventory->bind_param("i", $book_id);
                        $updateInventory->execute();
                        $updateInventory->close();
                        logError("Inventory updated: Book ID $book_id quantity increased by 1");
                    }
                }
                break;

            case 'remind':
                $subject = '🔔 Book Return Reminder';

                if ($returnDate) {
                    $interval = $today->diff($returnDate);
                    $daysDiff = $interval->days;
                    $isOverdue = ($today > $returnDate);

                    if (!$isOverdue) {
                        // Book is not yet overdue
                        $body = "This is a friendly reminder that your book <strong>\"$bookTitle\"</strong> is due for return in <strong>$daysDiff day(s)</strong>.<br><br>
                                 📅 <strong>Due Date:</strong> " . $returnDate->format('Y-m-d') . "<br>
                                 ⏰ <strong>Time Remaining:</strong> $daysDiff day(s)<br><br>
                                 Please return the book on or before the due date to avoid late fees.";
                    } else {
                        // Book is overdue
                        $fine = $daysDiff * 100;
                        $body = "Your book <strong>\"$bookTitle\"</strong> was due <strong>$daysDiff day(s)</strong> ago.<br><br>
                                 📅 <strong>Original Due Date:</strong> " . $returnDate->format('Y-m-d') . "<br>
                                 💰 <strong>Current Fine:</strong> Nu. $fine (Nu. 100 per day)<br><br>
                                 Please return the book and pay the fine as soon as possible to avoid additional charges.";
                    }
                } else {
                    $body = "This is a reminder about your book <strong>\"$bookTitle\"</strong>.<br>
                             Please check the return date and ensure timely return.";
                }

                // Update last reminder date
                $updateReminderStmt = $conn->prepare("UPDATE reservations SET last_reminder_date = NOW() WHERE reservation_id = ?");
                if ($updateReminderStmt) {
                    $updateReminderStmt->bind_param("s", $id);
                    $updateReminderStmt->execute();
                    $updateReminderStmt->close();
                }
                break;

            default:
                throw new Exception("Invalid action specified: $action");
        }

        // Update status if not a reminder
        if ($action !== 'remind') {
            $updateStmt = $conn->prepare("UPDATE reservations SET status = ? WHERE reservation_id = ?");
            if ($updateStmt === false) {
                throw new Exception("Database prepare error: " . $conn->error);
            }
            $updateStmt->bind_param("ss", $status, $id);
            if (!$updateStmt->execute()) {
                throw new Exception("Failed to update reservation status: " . $updateStmt->error);
            }
            $updateStmt->close();
            logError("Status updated for reservation $id: $currentStatus -> $status");
        }

        // Send notification email
        $emailSent = sendNotification($email, $name, $subject, $body, $action);
        if (!$emailSent) {
            logError("Failed to send notification email for action: $action to $email");
            // Don't throw exception here - the action was successful even if email failed
        }

        // Commit transaction
        $conn->commit();
        logError("Action '$action' completed successfully for reservation: $id");

        // Prepare success message
        $successMsg = "Action '$action' completed successfully for '$bookTitle'";
        if (!$emailSent) {
            $successMsg .= " (Note: Email notification failed to send)";
        }

        // Redirect with success message
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
        $redirectUrl = "reserved_details.php?msg=" . urlencode($successMsg);

        if ($page > 1) {
            $redirectUrl .= "&page=" . $page;
        }
        if ($statusFilter) {
            $redirectUrl .= "&status=" . urlencode($statusFilter);
        }

        header("Location: $redirectUrl");
        exit;

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $errorMsg = "Error processing action '$action': " . $e->getMessage();
        logError($errorMsg);

        // Redirect with error message
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
        $redirectUrl = "reserved_details.php?error=" . urlencode($errorMsg);

        if ($page > 1) {
            $redirectUrl .= "&page=" . $page;
        }
        if ($statusFilter) {
            $redirectUrl .= "&status=" . urlencode($statusFilter);
        }

        header("Location: $redirectUrl");
        exit;
    }
}

// Run automated processes (should be called by cron job daily)
$autoMarkedCount = checkOverdueBooks($conn);
$dailyReminderCount = sendDailyReminders($conn);

// Get notification count for new reservations
$new_reservations_query = "SELECT COUNT(*) as count FROM reservations WHERE status = 'reserved' AND is_viewed = 0";
$new_reservations_result = $conn->query($new_reservations_query);
$new_reservations_count = 0;
if ($new_reservations_result && $row = $new_reservations_result->fetch_assoc()) {
    $new_reservations_count = $row['count'];
}

// Mark new reservations as viewed
if ($new_reservations_count > 0) {
    $mark_as_viewed = "UPDATE reservations SET is_viewed = 1 WHERE status = 'reserved' AND is_viewed = 0";
    $conn->query($mark_as_viewed);
}

// Pagination and filtering setup
$records_per_page = 25;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Build WHERE clause
$where_conditions = [];
$params = [];
$param_types = '';

if ($status_filter && $status_filter != 'all') {
    $where_conditions[] = "r.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if ($search_term) {
    $where_conditions[] = "(b.title LIKE ? OR r.name LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $param_types .= 'ss';
}

$where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total 
                FROM reservations r
                JOIN books b ON r.book_id = b.id
                LEFT JOIN taken_books t ON r.reservation_id = t.reservation_id
                $where_clause";

if (!empty($params)) {
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param($param_types, ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_records = $count_result->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $count_result = $conn->query($count_query);
    $total_records = $count_result->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $records_per_page);
// Get all reservation info with pagination
$reservations_query = "
    SELECT r.reservation_id, b.title, r.name, r.status, r.reservation_date, t.return_due_date, 
           r.last_reminder_date
    FROM reservations r
    JOIN books b ON r.book_id = b.id
    LEFT JOIN taken_books t ON r.reservation_id = t.reservation_id
    $where_clause
    ORDER BY r.reservation_date DESC
    LIMIT $offset, $records_per_page
";
$reservations_result = $conn->query($reservations_query);

if (!$reservations_result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation List</title>
    <link rel="stylesheet" href="employee.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
            background-color: var(--ultra-light-green);
            color: var(--text-dark);
            padding-top: 255px;
            /* Initial space for fixed navbar */
            transition: padding-top 0.3s ease;
        }

        /* When secondary navbar is active, add more padding */
        body.navbar-active {
            padding-top: 300px;
        }

        /* Fixed Top Navbar */
        .navbar-toggle-container {
            position: fixed;
            top: 245px;
            left: 0;
            right: 0;
            z-index: 1000;
            background-color: var(--main-green);
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
            background-color: var(--accent-green);
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
            border: 2px solid var(--main-green);
        }

        /* Secondary Navbar */
        .secondary-navbar {
            position: fixed;
            top: 295px;
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
            flex-wrap: wrap;
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
            position: relative;
        }

        .navbar-links a i {
            margin-right: 6px;
        }

        .navbar-links a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .notification-badge {
            background-color: var(--error-red);
            color: white;
            font-size: 10px;
            font-weight: bold;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-left: 5px;
        }

        /* Main Container */
        .container {
            max-width: 98%;
            margin: 20px auto;
            margin-top: 100px;
            padding: 25px;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(46, 106, 51, 0.1);
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
            transition: margin-top 0.3s ease;
        }

        .page-header h2 {
            color: var(--main-green);
            font-size: 28px;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--light-green);
            font-weight: 600;
        }

        /* Alert Styles */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 15px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .alert-success {
            background-color: var(--ultra-light-green);
            color: var(--main-green);
            border-left: 4px solid var(--success-green);
        }

        .alert-error {
            background-color: #ffebee;
            color: var(--error-red);
            border-left: 4px solid var(--error-red);
        }

        .alert-info {
            background-color: var(--light-green);
            color: var(--text-dark);
            border-left: 4px solid var(--accent-green);
        }

        .alert i {
            margin-right: 10px;
            font-size: 18px;
        }

        /* Filter and Search Row */
        .filter-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .filter-container,
        .search-container {
            flex: 1;
            min-width: 250px;
        }

        .filter-select,
        .search-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--light-green);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background-color: var(--white);
        }

        .filter-select:focus,
        .search-input:focus {
            outline: none;
            border-color: var(--main-green);
            box-shadow: 0 0 0 2px rgba(46, 106, 51, 0.2);
            background-color: var(--white);
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(46, 106, 51, 0.1);
            border: 1px solid var(--light-green);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
        }

        th {
            background-color: var(--main-green);
            color: white;
            font-weight: 600;
            text-align: left;
            padding: 15px;
            position: sticky;
            top: 0;
            border-bottom: 2px solid var(--dark-green);
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--light-green);
            color: var(--text-dark);
        }

        tr:hover {
            background-color: var(--ultra-light-green);
        }

        tr:nth-child(even) {
            background-color: #f9fcf9;
        }

        /* Button Styles */
        .btn {
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            margin-right: 5px;
            margin-bottom: 3px;
            display: inline-block;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .confirm {
            background-color: var(--success-green);
            color: white;
        }

        .confirm:hover {
            background-color: #27ae60;
        }

        .reject {
            background-color: var(--error-red);
            color: white;
        }

        .reject:hover {
            background-color: #c0392b;
        }

        .terminate {
            background-color: var(--warning-yellow);
            color: white;
        }

        .terminate:hover {
            background-color: #e67e22;
        }

        .mark-due {
            background-color: #9b59b6;
            color: white;
        }

        .mark-due:hover {
            background-color: #8e44ad;
        }

        .send-reminder {
            background-color: var(--accent-green);
            color: white;
        }

        .send-reminder:hover {
            background-color: var(--main-green);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .payment-required {
            color: var(--error-red);
            font-size: 12px;
            font-weight: 600;
            font-style: italic;
        }

        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 25px;
            gap: 5px;
            flex-wrap: wrap;
        }

        .pagination a {
            padding: 10px 15px;
            background-color: var(--light-green);
            color: var(--text-dark);
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.2s;
            font-weight: 500;
        }

        .pagination a:hover {
            background-color: var(--main-green);
            color: white;
            transform: translateY(-1px);
        }

        .pagination .active {
            background-color: var(--main-green);
            color: white;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                padding-top: 60px;
            }

            body.navbar-active {
                padding-top: 120px;
            }

            .container {
                padding: 15px;
                max-width: 95%;
            }

            .filter-row {
                flex-direction: column;
            }

            .filter-container,
            .search-container {
                width: 100%;
                min-width: unset;
            }

            th,
            td {
                padding: 10px 8px;
                font-size: 14px;
            }

            .btn {
                padding: 6px 8px;
                font-size: 12px;
                margin-bottom: 5px;
            }

            .navbar-links {
                gap: 10px;
                padding: 0 10px;
            }

            .navbar-links a {
                padding: 6px 10px;
                font-size: 13px;
            }

            .page-header h2 {
                font-size: 24px;
            }

            .secondary-navbar.active {
                height: auto;
                min-height: 60px;
            }
        }

        @media (max-width: 480px) {
            .navbar-links {
                justify-content: flex-start;
                overflow-x: auto;
            }

            .toggle-btn {
                width: 35px;
                height: 35px;
            }

            .dashboard-title {
                font-size: 16px;
            }
        }
    </style>
</head>

<body>
    <?php include('../includes/dashboard_header.php'); ?>

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

    <div class="container">
        <div class="page-header">
            <h2><i class="fas fa-clipboard-list"></i> Reservation Management</h2>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <?php
            $message = htmlspecialchars($_GET['msg']);
            $isError = strpos($message, 'Error:') === 0;
            $alertClass = $isError ? 'alert-error' : 'alert-success';
            ?>
            <div class="alert <?php echo $alertClass; ?>">
                <i class="fas <?php echo $isError ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- System Status Info - Shows automated actions taken -->
        <?php
        // Count how many books were automatically marked as due
        $autoCount = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE status = 'due' AND DATE CURDATE()");
        if ($autoCount && $row = $autoCount->fetch_assoc()) {
            $autoMarkCount = $row['count'];
            if ($autoMarkCount > 0) {
                echo "<div class='alert alert-info'>
                    <i class='fas fa-info-circle'></i> System has automatically marked $autoMarkCount book(s) as overdue today.
                </div>";
            }
        }
        ?>

        <!-- Filter and Search Bar -->
        <div class="filter-row">
            <div class="filter-container">
                <select id="statusFilter" class="filter-select" onchange="filterTable()">
                    <option value="all">🔍 All Status</option>
                    <option value="reserved">📋 Reserved</option>
                    <option value="confirmed">✅ Confirmed</option>
                    <option value="taken">📚 Taken</option>
                    <option value="due">⚠️ Due</option>
                    <option value="due paid">💰 Due Paid</option>
                    <option value="cancelled">❌ Cancelled</option>
                    <option value="rejected">🚫 Rejected</option>
                    <option value="returned">📖 Returned</option>
                    <option value="terminated">❌Terminated</option>
                </select>
            </div>
            <div class="search-container">
                <input type="text" id="searchInput" class="search-input" placeholder="🔍 Search for books, users..."
                    oninput="filterTable()">
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th><i class="fas fa-book"></i> Book Title</th>
                        <th><i class="fas fa-user"></i> User Name</th>
                        <th><i class="fas fa-info-circle"></i> Status</th>
                        <th><i class="fas fa-calendar-plus"></i> Reservation Date</th>
                        <th><i class="fas fa-calendar-times"></i> Return Due Date</th>
                        <th><i class="fas fa-cogs"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = ($page - 1) * $records_per_page + 1;
                    while ($row = $reservations_result->fetch_assoc()) {
                        $reservation_id = $row['reservation_id'];
                        $status = $row['status'];
                        $reservation_date = date('Y-m-d', strtotime($row['reservation_date']));
                        $return_due_date = $row['return_due_date'] ? date('Y-m-d', strtotime($row['return_due_date'])) : 'N/A';

                        // Add status styling
                        $statusClass = '';
                        $statusIcon = '';
                        switch ($status) {
                            case 'reserved':
                                $statusClass = 'style="color: #2196f3; font-weight: 600;"';
                                $statusIcon = '📋';
                                break;
                            case 'confirmed':
                                $statusClass = 'style="color: #4caf50; font-weight: 600;"';
                                $statusIcon = '✅';
                                break;
                            case 'taken':
                                $statusClass = 'style="color: #ff9800; font-weight: 600;"';
                                $statusIcon = '📚';
                                break;
                            case 'due':
                                $statusClass = 'style="color: #f44336; font-weight: 600;"';
                                $statusIcon = '⚠️';
                                break;
                            case 'due paid':
                                $statusClass = 'style="color: #9c27b0; font-weight: 600;"';
                                $statusIcon = '💰';
                                break;
                            case 'returned':
                                $statusClass = 'style="color: #2e6a33; font-weight: 600;"';
                                $statusIcon = '📖';
                                break;
                            case 'rejected':
                                $statusClass = 'style="color: #d64541; font-weight: 600;"';
                                $statusIcon = '🚫';
                                break;
                            default:
                                $statusClass = 'style="color: #666; font-weight: 600;"';
                                $statusIcon = '❓';
                                break;
                        }

                        echo "<tr>
                            <td><strong>{$i}</strong></td>
                            <td><strong>{$row['title']}</strong></td>
                            <td>{$row['name']}</td>
                            <td $statusClass>$statusIcon {$status}</td>
                            <td>{$reservation_date}</td>
                            <td>{$return_due_date}</td>
                            <td>";

                        // Action buttons based on reservation status
                        if ($status == 'reserved') {
                            echo "<a href='?action=confirm&id={$reservation_id}' class='btn confirm'><i class='fas fa-check'></i> Confirm</a>
                            <a href='?action=reject&id={$reservation_id}' class='btn reject'><i class='fas fa-times'></i> Reject</a>";
                        } elseif ($status == 'confirmed') {
                            echo "<a href='?action=terminate&id={$reservation_id}' class='btn terminate'><i class='fas fa-ban'></i> Terminate</a>";
                        } elseif ($status == 'taken') {
                            echo "<a href='?action=due&id={$reservation_id}' class='btn mark-due'><i class='fas fa-exclamation-triangle'></i> Mark Due</a>
                            <a href='?action=return&id={$reservation_id}' class='btn confirm'><i class='fas fa-undo'></i> Return</a>";
                        } elseif ($status == 'due') {
                            echo "<span class='payment-required'>💳 Payment required before return</span>";
                        } elseif ($status == 'due paid') {
                            echo "<a href='?action=return&id={$reservation_id}' class='btn confirm'><i class='fas fa-undo'></i> Return Book</a>";
                        } else {
                            echo "<span style='color: #666;'><i class='fas fa-check-circle'></i> No action needed</span>";
                        }
                        echo "</td>
                        </tr>";
                        $i++;
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a
                        href="?page=1<?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $search_term ? '&search=' . $search_term : ''; ?>">
                        <i class="fas fa-angle-double-left"></i> First
                    </a>
                    <a
                        href="?page=<?php echo $page - 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $search_term ? '&search=' . $search_term : ''; ?>">
                        <i class="fas fa-angle-left"></i> Prev
                    </a>
                <?php endif; ?>

                <?php
                // Show page numbers
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);

                for ($i = $start; $i <= $end; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $search_term ? '&search=' . $search_term : ''; ?>"
                        <?php echo ($i == $page) ? 'class="active"' : ''; ?>>
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a
                        href="?page=<?php echo $page + 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $search_term ? '&search=' . $search_term : ''; ?>">
                        Next <i class="fas fa-angle-right"></i>
                    </a>
                    <a
                        href="?page=<?php echo $total_pages; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $search_term ? '&search=' . $search_term : ''; ?>">
                        Last <i class="fas fa-angle-double-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Display alerts for 5 seconds and then fade them out
        document.addEventListener('DOMContentLoaded', function () {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function (alert) {
                setTimeout(function () {
                    alert.style.transition = 'opacity 1s';
                    alert.style.opacity = '0';
                    setTimeout(function () {
                        alert.style.display = 'none';
                    }, 1000);
                }, 5000);
            });

            // Set the status filter dropdown to match URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const statusParam = urlParams.get('status');
            if (statusParam) {
                document.getElementById('statusFilter').value = statusParam;
            }
        });

        // Secondary navbar toggle with body class management
        document.getElementById('navbarToggle').addEventListener('click', function () {
            this.classList.toggle('active');
            const secondaryNavbar = document.getElementById('secondaryNavbar');
            const body = document.body;

            secondaryNavbar.classList.toggle('active');
            body.classList.toggle('navbar-active');
        });

        // Table filtering function
        function filterTable() {
            // Get filter values
            const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
            const searchQuery = document.getElementById('searchInput').value.toLowerCase();

            // Get all table rows except header
            const tbody = document.querySelector('table tbody');
            const rows = tbody.querySelectorAll('tr');

            let visibleRows = 0;

            // Loop through rows and hide/show based on filter
            rows.forEach(row => {
                const title = row.cells[1].textContent.toLowerCase();
                const userName = row.cells[2].textContent.toLowerCase();
                const status = row.cells[3].textContent.toLowerCase();

                // Check if row matches both status filter and search query
                const matchesStatus = (statusFilter === 'all' || status.includes(statusFilter));
                const matchesSearch = (title.includes(searchQuery) || userName.includes(searchQuery));

                // Show/hide row based on filter criteria
                if (matchesStatus && matchesSearch) {
                    row.style.display = '';
                    visibleRows++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Show/hide no results message
            let noResultsRow = document.getElementById('no-results-row');
            if (visibleRows === 0) {
                if (!noResultsRow) {
                    noResultsRow = document.createElement('tr');
                    noResultsRow.id = 'no-results-row';
                    noResultsRow.innerHTML = '<td colspan="7" style="text-align: center; padding: 30px; color: #666; font-style: italic;"><i class="fas fa-search"></i> No reservations found matching your criteria.</td>';
                    tbody.appendChild(noResultsRow);
                }
                noResultsRow.style.display = '';
            } else if (noResultsRow) {
                noResultsRow.style.display = 'none';
            }
        }

        // Add smooth scrolling for better UX
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

        // Add loading states for action buttons
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('btn') && e.target.tagName === 'A') {
                e.target.style.opacity = '0.7';
                e.target.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }
        });
    </script>

    <?php include('../includes/footer.php'); ?>
</body>

</html>

<?php
$conn->close();
?>
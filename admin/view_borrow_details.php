<?php
include('../includes/db.php');
include('../includes/dashboard_header.php');

// Include PHPMailer classes
require_once '../PHPMailer/src/Exception.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// First, create notification_logs table if it doesn't exist
$createTableQuery = "
CREATE TABLE IF NOT EXISTS notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cid VARCHAR(50) NOT NULL,
    book_id INT NOT NULL,
    notification_type ENUM('overdue', 'reminder') NOT NULL,
    sent_date DATE NOT NULL,
    email_address VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cid_book_date (cid, book_id, sent_date),
    INDEX idx_notification_type (notification_type)
)";

if (!$conn->query($createTableQuery)) {
    error_log("Error creating notification_logs table: " . $conn->error);
}

// Email sending function
function sendEmail($recipientEmail, $recipientName, $subject, $body)
{
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
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// Function to log notification
function logNotification($conn, $cid, $bookId, $notificationType, $emailAddress)
{
    $today = date('Y-m-d');
    $query = "INSERT INTO notification_logs (cid, book_id, notification_type, sent_date, email_address) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("sisss", $cid, $bookId, $notificationType, $today, $emailAddress);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}

// Function to check if notification was already sent today
function wasNotificationSentToday($conn, $cid, $bookId, $notificationType)
{
    $today = date('Y-m-d');
    $query = "SELECT COUNT(*) as count FROM notification_logs WHERE cid = ? AND book_id = ? AND notification_type = ? AND sent_date = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("siss", $cid, $bookId, $notificationType, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['count'] > 0;
    }
    return false;
}

// Function to check if it's time to send reminder (every 3 days)
function shouldSendReminder($conn, $cid, $bookId, $dueDate)
{
    $today = date('Y-m-d');

    // Don't send reminder if already sent today
    if (wasNotificationSentToday($conn, $cid, $bookId, 'reminder')) {
        return false;
    }

    // Get the last reminder date
    $query = "SELECT MAX(sent_date) as last_reminder FROM notification_logs WHERE cid = ? AND book_id = ? AND notification_type = 'reminder'";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("si", $cid, $bookId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        $lastReminderDate = $row['last_reminder'];

        if ($lastReminderDate) {
            // Calculate days since last reminder
            $daysSinceLastReminder = (strtotime($today) - strtotime($lastReminderDate)) / 86400;

            // Send reminder every 3 days
            if ($daysSinceLastReminder >= 3) {
                return true;
            }
        } else {
            // No previous reminder sent, check if it's time for first reminder
            // Send first reminder 1 day after due date
            $daysPastDue = (strtotime($today) - strtotime($dueDate)) / 86400;
            if ($daysPastDue >= 1) {
                return true;
            }
        }
    }

    return false;
}

// Function to get days since last reminder
function getDaysSinceLastReminder($conn, $cid, $bookId)
{
    $today = date('Y-m-d');
    $query = "SELECT MAX(sent_date) as last_reminder FROM notification_logs WHERE cid = ? AND book_id = ? AND notification_type = 'reminder'";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("si", $cid, $bookId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row['last_reminder']) {
            return (strtotime($today) - strtotime($row['last_reminder'])) / 86400;
        }
    }
    return 0;
}

// Function to send overdue notification
function sendOverdueNotification($userEmail, $userName, $bookTitle, $author, $dueDate)
{
    $subject = "📚 Book Overdue Notice - Library Management System";
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f8f9fa; }
            .book-details { background-color: white; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545; }
            .footer { text-align: center; padding: 20px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>📚 Book Overdue Notice</h2>
            </div>
            <div class='content'>
                <p>Dear <strong>{$userName}</strong>,</p>
                <p>This is to inform you that the following book is now <strong style='color: #dc3545;'>OVERDUE</strong>:</p>
                
                <div class='book-details'>
                    <h4>📖 Book Details:</h4>
                    <p><strong>Title:</strong> {$bookTitle}</p>
                    <p><strong>Author:</strong> {$author}</p>
                    <p><strong>Due Date:</strong> {$dueDate}</p>
                </div>
                
                <p><strong style='color: #dc3545;'>⚠️ IMPORTANT:</strong> Please return the book as soon as possible to avoid additional late fees.</p>
                <p>If you have already returned the book, please contact the library to update your record.</p>
            </div>
            <div class='footer'>
                <p>Thank you,<br><strong>Library Management System</strong></p>
            </div>
        </div>
    </body>
    </html>";

    return sendEmail($userEmail, $userName, $subject, $body);
}

// Function to send reminder notification
function sendReminderNotification($userEmail, $userName, $bookTitle, $author, $dueDate, $daysPastDue, $reminderNumber = 1)
{
    $subject = "🔔 Reminder #$reminderNumber: Book Still Overdue - Library Management System";
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #ffc107; color: #333; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f8f9fa; }
            .book-details { background-color: white; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107; }
            .urgent { background-color: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; }
            .footer { text-align: center; padding: 20px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🔔 Book Return Reminder #$reminderNumber</h2>
            </div>
            <div class='content'>
                <p>Dear <strong>{$userName}</strong>,</p>
                <p>This is <strong>REMINDER #$reminderNumber</strong> that the following book is still overdue:</p>
                
                <div class='book-details'>
                    <h4>📖 Book Details:</h4>
                    <p><strong>Title:</strong> {$bookTitle}</p>
                    <p><strong>Author:</strong> {$author}</p>
                    <p><strong>Due Date:</strong> {$dueDate}</p>
                    <p><strong style='color: #dc3545;'>Days Past Due:</strong> {$daysPastDue} days</p>
                </div>
                
                <div class='urgent'>
                    <p><strong>⚠️ URGENT:</strong> Please return this book immediately to avoid further late fees.</p>
                    <p>Continued delays may result in suspension of borrowing privileges.</p>
                    <p><em>Note: You will receive reminder emails every 3 days until the book is returned.</em></p>
                </div>
                
                <p>If you have questions or need assistance, please contact the library.</p>
            </div>
            <div class='footer'>
                <p>Thank you,<br><strong>Library Management System</strong></p>
            </div>
        </div>
    </body>
    </html>";

    return sendEmail($userEmail, $userName, $subject, $body);
}

$today = date('Y-m-d');

// Initialize counters for notification summary
$newOverdueCount = 0;
$reminderCount = 0;

// First, get books that are becoming overdue today (status change from 'borrowed' to 'due')
$newOverdueQuery = "
    SELECT 
        bb.cid, bb.book_id, bb.return_due_date,
        b.title AS book_title, b.author AS book_author,
        u.name AS user_name, u.email AS user_email
    FROM borrow_books bb
    JOIN books b ON bb.book_id = b.id
    JOIN users u ON bb.cid = u.username
    WHERE bb.status = 'borrowed' AND bb.return_due_date < ?
";

$stmt = $conn->prepare($newOverdueQuery);
if (!$stmt) {
    die("Prepare failed for newOverdueQuery: " . $conn->error);
}

$stmt->bind_param("s", $today);
$stmt->execute();
$newOverdueResult = $stmt->get_result();

// Send initial overdue notifications (only if not sent today)
while ($row = $newOverdueResult->fetch_assoc()) {
    if (!empty($row['user_email'])) {
        // Check if overdue notification was already sent today
        if (!wasNotificationSentToday($conn, $row['cid'], $row['book_id'], 'overdue')) {
            $emailSent = sendOverdueNotification(
                $row['user_email'],
                $row['user_name'],
                $row['book_title'],
                $row['book_author'],
                $row['return_due_date']
            );

            if ($emailSent) {
                // Log the notification
                logNotification($conn, $row['cid'], $row['book_id'], 'overdue', $row['user_email']);
                $newOverdueCount++;
            }
        }
    }
}
$stmt->close();

// Update overdue books to 'due' status
$updateQuery = "UPDATE borrow_books SET status = 'due' WHERE status = 'borrowed' AND return_due_date < ?";
$stmt = $conn->prepare($updateQuery);
if (!$stmt) {
    die("Prepare failed for updateQuery: " . $conn->error);
}
$stmt->bind_param("s", $today);
$stmt->execute();
$stmt->close();

// Send reminder notifications for books that are due (following the 3-day rule)
$reminderQuery = "
    SELECT 
        bb.cid, bb.book_id, bb.return_due_date,
        b.title AS book_title, b.author AS book_author,
        u.name AS user_name, u.email AS user_email,
        DATEDIFF(?, bb.return_due_date) as days_overdue
    FROM borrow_books bb
    JOIN books b ON bb.book_id = b.id
    JOIN users u ON bb.cid = u.username
    WHERE bb.status = 'due' AND DATEDIFF(?, bb.return_due_date) > 0
";

$stmt = $conn->prepare($reminderQuery);
if (!$stmt) {
    die("Prepare failed for reminderQuery: " . $conn->error);
}

$stmt->bind_param("ss", $today, $today);
$stmt->execute();
$reminderResult = $stmt->get_result();

// Send reminder notifications (every 3 days)
while ($row = $reminderResult->fetch_assoc()) {
    if (!empty($row['user_email'])) {
        // Check if it's time to send reminder (every 3 days)
        if (shouldSendReminder($conn, $row['cid'], $row['book_id'], $row['return_due_date'])) {

            // Calculate reminder number
            $reminderCountQuery = "SELECT COUNT(*) as count FROM notification_logs WHERE cid = ? AND book_id = ? AND notification_type = 'reminder'";
            $reminderStmt = $conn->prepare($reminderCountQuery);
            $reminderStmt->bind_param("si", $row['cid'], $row['book_id']);
            $reminderStmt->execute();
            $reminderCountResult = $reminderStmt->get_result();
            $reminderCountRow = $reminderCountResult->fetch_assoc();
            $reminderNumber = $reminderCountRow['count'] + 1;
            $reminderStmt->close();

            $emailSent = sendReminderNotification(
                $row['user_email'],
                $row['user_name'],
                $row['book_title'],
                $row['book_author'],
                $row['return_due_date'],
                $row['days_overdue'],
                $reminderNumber
            );

            if ($emailSent) {
                // Log the notification
                logNotification($conn, $row['cid'], $row['book_id'], 'reminder', $row['user_email']);
                $reminderCount++;
            }
        }
    }
}
$stmt->close();

// Fetch borrowed books with book & user details
$query = "
    SELECT 
        bb.cid, bb.book_id, bb.taken_date, bb.return_due_date, bb.status,
        b.title AS book_title, b.author AS book_author,
        u.name AS user_name, u.email AS user_email,
        DATEDIFF(?, bb.return_due_date) as days_overdue
    FROM borrow_books bb
    JOIN books b ON bb.book_id = b.id
    JOIN users u ON bb.cid = u.username
    ORDER BY bb.taken_date DESC
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed for main query: " . $conn->error);
}

$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Query Failed: " . $conn->error);
}

// Function to get last notification info
function getLastNotificationInfo($conn, $cid, $bookId)
{
    $query = "SELECT notification_type, sent_date, COUNT(*) as reminder_count FROM notification_logs WHERE cid = ? AND book_id = ? GROUP BY notification_type ORDER BY sent_date DESC";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("si", $cid, $bookId);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[$row['notification_type']] = $row;
        }
        $stmt->close();
        return $notifications;
    }
    return null;
}

// Get count of new reservations
$new_reservations_query = "SELECT COUNT(*) as count FROM reservations WHERE status IN ('reserved', 'returned', 'cancelled', 'due paid') AND is_viewed = 0";
$new_reservations_result = $conn->query($new_reservations_query);
$new_reservations_count = 0;
if ($new_reservations_result && $row = $new_reservations_result->fetch_assoc()) {
    $new_reservations_count = $row['count'];
}

// Count unread employee registrations
$new_employees_query = "SELECT COUNT(*) as count FROM users WHERE role = 'employee' AND is_viewed = 0";
$new_employees_result = $conn->query($new_employees_query);
$new_employees_count = 0;
if ($new_employees_result && $row = $new_employees_result->fetch_assoc()) {
    $new_employees_count = $row['count'];
}

// Add both counts together for total notifications
$total_notifications_count = $new_reservations_count + $new_employees_count;
?>

<!DOCTYPE html>
<html>

<head>
    <title>Borrowed Books</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--ultra-light-green);
            color: var(--text-dark);
        }

        /* Fixed Top Navbar */
        .navbar-toggle-container {
            position: fixed;
            top: 242px;
            left: 0;
            right: 0;
            z-index: 1000;
            background: linear-gradient(135deg, var(--main-green) 0%, var(--accent-green) 100%);
            box-shadow: var(--box-shadow);
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 50px;
            backdrop-filter: blur(10px);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
        }

        .toggle-btn {
            background: rgba(255, 255, 255, 0.2);
            color: var(--white);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: var(--transition);
            backdrop-filter: blur(5px);
        }

        .toggle-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: scale(1.05);
        }

        .toggle-btn i {
            transition: transform 0.3s ease;
        }

        .toggle-btn.active i {
            transform: rotate(180deg);
        }

        .dashboard-title {
            font-family: var(--font-main);
            font-size: 20px;
            color: var(--white);
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
            color: var(--white);
            font-size: 18px;
            margin-left: 15px;
            text-decoration: none;
            padding: 8px;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .notification-icon:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .badge {
            position: absolute;
            top: -2px;
            right: -2px;
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
            0% {
                box-shadow: 0 0 0 0 rgba(214, 69, 65, 0.7);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(214, 69, 65, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(214, 69, 65, 0);
            }
        }

        /* Secondary Navbar */
        .secondary-navbar {
            position: fixed;
            top: 290px;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, var(--dark-green) 0%, var(--main-green) 100%);
            height: 0;
            overflow: hidden;
            transition: height 0.3s ease;
            z-index: 999;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
        }

        .secondary-navbar.active {
            height: 50px;
        }

        .navbar-links {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            gap: 5px;
            padding: 0 20px;
            flex-wrap: wrap;
        }

        .navbar-links a {
            color: var(--white);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 8px 12px;
            border-radius: var(--border-radius);
            transition: var(--transition);
            display: flex;
            align-items: center;
            white-space: nowrap;
        }

        .navbar-links a i {
            margin-right: 6px;
            font-size: 12px;
        }

        .navbar-links a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Toast notifications */
        .toast-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1100;
        }

        .toast {
            background: var(--white);
            border-left: 4px solid var(--main-green);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 15px;
            opacity: 0;
            padding: 15px 20px;
            transform: translateX(100%);
            transition: var(--transition);
            width: 300px;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .toast-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .toast-title {
            font-weight: 600;
            font-size: 16px;
            color: var(--text-dark);
        }

        .toast-close {
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 16px;
        }

        .toast-body {
            color: var(--text-medium);
            font-size: 14px;
        }

        .content-wrapper {
            padding: 30px;
            max-width: 1400px;
            margin: 50px auto;
            margin-top: 270px;
            background-color: var(--ultra-light-green);
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(46, 106, 51, 0.1);
            margin-top: 320px;
        }

        .content-wrapper h2 {
            color: var(--main-green);
            margin-bottom: 25px;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(46, 106, 51, 0.1);
        }

        .content-wrapper h2 i {
            color: var(--accent-green);
            background: rgba(201, 220, 203, 0.3);
            padding: 10px;
            border-radius: 50%;
        }

        /* Enhanced Notification Summary */
        .notification-summary {
            background-color: white;
            border-left: 4px solid var(--accent-green);
            padding: 18px;
            margin-bottom: 25px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: var(--text-medium);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(46, 106, 51, 0.1);
        }

        .notification-summary i {
            color: var(--main-green);
            font-size: 1.5rem;
            background: rgba(201, 220, 203, 0.3);
            padding: 12px;
            border-radius: 50%;
        }

        /* Enhanced Info Notice */
        .info-notice {
            background-color: white;
            border-left: 4px solid var(--warning-yellow);
            padding: 18px;
            margin-bottom: 30px;
            border-radius: 8px;
            color: var(--text-medium);
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 193, 7, 0.1);
        }

        .info-notice i {
            color: var(--warning-yellow);
            font-size: 1.5rem;
            background: rgba(255, 243, 205, 0.3);
            padding: 12px;
            border-radius: 50%;
        }

        /* Enhanced Search and Filter Section */
        .search-filter-container {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            align-items: center;
            flex-wrap: wrap;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(46, 106, 51, 0.1);
        }

        .search-box {
            flex: 1;
            min-width: 300px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 2px solid var(--light-green);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            box-sizing: border-box;
            background-color: var(--ultra-light-green);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent-green);
            box-shadow: 0 0 0 3px rgba(74, 153, 80, 0.1);
            background-color: white;
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 18px;
        }

        .filter-dropdown {
            position: relative;
        }

        .filter-btn {
            background: var(--main-green);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            min-width: 140px;
            justify-content: center;
        }

        .filter-btn:hover {
            background: var(--dark-green);
            transform: translateY(-2px);
        }

        .filter-dropdown-content {
            display: none;
            position: absolute;
            background: white;
            min-width: 180px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            border-radius: 8px;
            overflow: hidden;
            top: 100%;
            left: 0;
            margin-top: 5px;
            border: 1px solid rgba(46, 106, 51, 0.1);
        }

        .filter-dropdown-content.show {
            display: block;
        }

        .filter-option {
            color: var(--text-dark);
            padding: 12px 16px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-option:hover {
            background-color: var(--ultra-light-green);
        }

        .filter-option.active {
            background-color: var(--main-green);
            color: white;
        }

        .clear-filters-btn {
            background: var(--text-light);
            color: white;
            border: none;
            padding: 12px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .clear-filters-btn:hover {
            background: var(--text-medium);
        }

        .search-results-info {
            margin-bottom: 15px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border-left: 4px solid var(--accent-green);
            font-size: 14px;
            color: var(--text-medium);
            display: none;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(46, 106, 51, 0.1);
        }

        /* Enhanced Table Container */
        .table-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.08);
            overflow-x: auto;
            border: 1px solid rgba(46, 106, 51, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: var(--main-green);
            color: var(--white);
            padding: 15px;
            text-align: left;
            font-weight: 500;
            position: sticky;
            top: 0;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid var(--ultra-light-green);
            vertical-align: top;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background-color: rgba(201, 220, 203, 0.2);
        }

        /* Status badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }

        .status-borrowed {
            background: rgba(46, 106, 51, 0.1);
            color: var(--main-green);
        }

        .status-due {
            background: rgba(214, 69, 65, 0.1);
            color: var(--error-red);
        }

        .status-returned {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-green);
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 3.5rem;
            color: var(--light-green);
            margin-bottom: 20px;
            opacity: 0.7;
        }

        .empty-state h3 {
            color: var(--text-medium);
            margin: 15px 0 10px;
            font-size: 1.3rem;
        }

        .empty-state p {
            max-width: 500px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .content-wrapper {
                padding: 20px;
                margin-top: 250px;
            }

            .search-filter-container {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                min-width: unset;
            }

            .filter-btn,
            .clear-filters-btn {
                width: 100%;
                justify-content: center;
            }

            th,
            td {
                padding: 12px 10px;
                font-size: 0.9rem;
            }
        }
    </style>

</head>

<body>
    <!-- Toast Container for Notifications -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Fixed Top Navigation Bar -->
    <div class="navbar-toggle-container">
        <div class="navbar-brand">
            <button class="toggle-btn" id="navbarToggle" title="Toggle Menu">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>

        <div class="navbar-actions">
            <a href="notifiactions.php" class="notification-icon" title="Notifications">
                <i class="fas fa-bell"></i>
                <?php if ($total_notifications_count > 0): ?>
                    <span class="badge"><?php echo $total_notifications_count; ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>

    <!-- Secondary Navigation Bar (Initially Hidden) -->
    <div class="secondary-navbar" id="secondaryNavbar">
        <div class="navbar-links">
            <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
            <a href="add_books.php"><i class="fas fa-book"></i> Add Books</a>
            <a href="all_books.php"><i class="fas fa-book"></i> Books</a>
            <a href="confirmed_reservation.php"><i class="fas fa-check-circle"></i> Track Reservation</a>
            <a href="reservations_list.php"><i class="fas fa-chart-bar"></i> Reservation</a>
            <a href="view_borrow_details.php"><i class="fas fa-chart-bar"></i> Borrowed Book</a>
            <a href="payment_details.php"><i class="fas fa-money-check-alt"></i> Payment</a>
            <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="../login.php"><i class="fas fa-sign-out-alt"></i> LogOut</a>
        </div>
    </div>

    <div class="content-wrapper">
        <h2><i class="fas fa-book-reader"></i> Borrowed Books List</h2>

        <?php
        // Display notification summary
        $totalSent = $newOverdueCount + $reminderCount;
        if ($totalSent > 0): ?>
            <div class="notification-summary">
                <i class="fas fa-envelope"></i> <strong>Email Notifications Sent:</strong>
                <?= $totalSent ?> notifications sent successfully
                (<?= $newOverdueCount ?> overdue alerts, <?= $reminderCount ?> reminders).
            </div>
        <?php endif; ?>

        <div class="info-notice">
            <i class="fas fa-info-circle"></i> <strong>Reminder Schedule:</strong>
            Overdue notices are sent immediately when books become overdue. Reminder emails are sent every 3 days after the due date until the book is returned.
        </div>

        <!-- Search and Filter Section -->
        <div class="search-filter-container">
            <div class="search-box">
                <input type="text" id="searchInput" class="search-input" placeholder="Search by CID, Name, Book Title, or Author...">
                <i class="fas fa-search search-icon"></i>
            </div>

            <div class="filter-dropdown">
                <button class="filter-btn" id="filterBtn">
                    <i class="fas fa-filter"></i>
                    <span id="filterText">All Books</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="filter-dropdown-content" id="filterDropdown">
                    <div class="filter-option active" data-status="all">
                        <i class="fas fa-list"></i> All Books
                    </div>
                    <div class="filter-option" data-status="borrowed">
                        <i class="fas fa-book-open"></i> Borrowed
                    </div>
                    <div class="filter-option" data-status="due">
                        <i class="fas fa-exclamation-triangle"></i> Due
                    </div>
                    <div class="filter-option" data-status="returned">
                        <i class="fas fa-check-circle"></i> Returned
                    </div>
                </div>
            </div>

            <button class="clear-filters-btn" id="clearFilters">
                <i class="fas fa-times"></i> Clear
            </button>
        </div>

        <!-- Search Results Info -->
        <div class="search-results-info" id="searchResultsInfo" style="display: none;">
            <i class="fas fa-info-circle"></i>
            <span id="resultsText"></span>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>CID</th>
                        <th>Name</th>
                        <th>Book Title</th>
                        <th>Author</th>
                        <th>Taken Date</th>
                        <th>Return Due Date</th>
                        <th>Status</th>
                        <th>Notification Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                            // Get notification info for this book
                            $notificationInfo = getLastNotificationInfo($conn, $row['cid'], $row['book_id']);
                            $daysSinceLastReminder = getDaysSinceLastReminder($conn, $row['cid'], $row['book_id']);
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['cid']); ?></td>
                                <td>
                                    <?= htmlspecialchars($row['user_name']); ?>
                                    <?php if (!empty($row['user_email'])): ?>
                                        <br><small class="email-status">📧 <?= htmlspecialchars($row['user_email']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['book_title']); ?></td>
                                <td><?= htmlspecialchars($row['book_author']); ?></td>
                                <td><?= htmlspecialchars($row['taken_date']); ?></td>
                                <td>
                                    <?= htmlspecialchars($row['return_due_date']); ?>
                                    <?php if ($row['status'] === 'due' && $row['days_overdue'] > 0): ?>
                                        <br><span class="days-overdue"><?= $row['days_overdue']; ?> days overdue</span>
                                    <?php endif; ?>
                                </td>
                                <td class="<?php
                                            echo $row['status'] === 'borrowed' ? 'status-borrowed' : ($row['status'] === 'due' ? 'status-due' : 'status-returned');
                                            ?>">
                                    <?= ucfirst($row['status']); ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'due' && $notificationInfo): ?>
                                        <div class="notification-status">
                                            <?php if (isset($notificationInfo['overdue'])): ?>
                                                <small><i class="fas fa-exclamation-triangle"></i> Overdue notice sent</small><br>
                                            <?php endif; ?>
                                            <?php if (isset($notificationInfo['reminder'])): ?>
                                                <small><i class="fas fa-envelope"></i> <?= $notificationInfo['reminder']['reminder_count']; ?> reminder(s) sent</small><br>
                                                <small>Last: <?= date('M j', strtotime($notificationInfo['reminder']['sent_date'])); ?></small><br>
                                                <?php
                                                $nextReminder = 3 - $daysSinceLastReminder;
                                                if ($nextReminder > 0): ?>
                                                    <small class="next-reminder">Next in <?= $nextReminder; ?> day(s)</small>
                                                <?php else: ?>
                                                    <small class="next-reminder">Next reminder due</small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <small class="next-reminder">First reminder in <?= max(0, 1 - $row['days_overdue']); ?> day(s)</small>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif ($row['status'] === 'borrowed'): ?>
                                        <small class="status-normal">No notifications needed</small>
                                    <?php elseif ($row['status'] === 'returned'): ?>
                                        <small class="status-returned">Book returned</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'borrowed'): ?>
                                        <em style="color: var(--text-light);">Active Loan</em>
                                    <?php elseif ($row['status'] === 'due'): ?>
                                        <span class="payment-message">
                                            <span><i class="fas fa-exclamation-triangle"></i> Payment Required</span>
                                        </span>
                                    <?php elseif ($row['status'] === 'returned'): ?>
                                        <span class="returned-msg"><i class="fas fa-check-circle"></i> Returned</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <i class="fas fa-book-open"></i>
                                    <h3>No borrowed books found</h3>
                                    <p>There are currently no books in the borrowed books list.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        // Navbar toggle functionality
        document.getElementById('navbarToggle').addEventListener('click', function() {
            this.classList.toggle('active');
            const icon = this.querySelector('i');

            if (this.classList.contains('active')) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }

            const secondaryNavbar = document.getElementById('secondaryNavbar');
            secondaryNavbar.classList.toggle('active');
        });

        // Add smooth scrolling for better UX
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        // Live Search Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const filterBtn = document.getElementById('filterBtn');
            const filterDropdown = document.getElementById('filterDropdown');
            const filterOptions = document.querySelectorAll('.filter-option');
            const filterText = document.getElementById('filterText');
            const clearFiltersBtn = document.getElementById('clearFilters');
            const searchResultsInfo = document.getElementById('searchResultsInfo');
            const resultsText = document.getElementById('resultsText');
            const tableRows = document.querySelectorAll('tbody tr');

            let currentFilter = 'all';
            let currentSearchTerm = '';

            // Toggle filter dropdown
            filterBtn.addEventListener('click', function() {
                filterDropdown.classList.toggle('show');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!filterBtn.contains(e.target) && !filterDropdown.contains(e.target)) {
                    filterDropdown.classList.remove('show');
                }
            });

            // Filter option selection
            filterOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remove active class from all options
                    filterOptions.forEach(opt => opt.classList.remove('active'));

                    // Add active class to selected option
                    this.classList.add('active');

                    // Update filter text
                    const statusText = this.textContent.trim();
                    filterText.textContent = statusText;

                    // Get the filter value
                    currentFilter = this.getAttribute('data-status');

                    // Apply filters
                    applyFilters();

                    // Close dropdown
                    filterDropdown.classList.remove('show');
                });
            });

            // Clear all filters
            clearFiltersBtn.addEventListener('click', function() {
                // Reset search input
                searchInput.value = '';
                currentSearchTerm = '';

                // Reset filter to 'all'
                filterOptions.forEach(opt => opt.classList.remove('active'));
                filterOptions[0].classList.add('active');
                filterText.textContent = 'All Books';
                currentFilter = 'all';

                // Apply filters (which will show all rows)
                applyFilters();
            });

            // Live search functionality
            searchInput.addEventListener('input', function() {
                currentSearchTerm = this.value.toLowerCase();
                applyFilters();
            });

            // Function to apply both search and filter
            function applyFilters() {
                let visibleCount = 0;
                const totalCount = tableRows.length;

                tableRows.forEach(row => {
                    const rowText = row.textContent.toLowerCase();
                    const status = row.querySelector('td:nth-child(7)').textContent.toLowerCase();

                    // Check if row matches search term
                    const matchesSearch = currentSearchTerm === '' ||
                        rowText.includes(currentSearchTerm);

                    // Check if row matches filter
                    const matchesFilter = currentFilter === 'all' ||
                        (currentFilter === 'borrowed' && status.includes('borrowed')) ||
                        (currentFilter === 'due' && status.includes('due')) ||
                        (currentFilter === 'returned' && status.includes('returned'));

                    if (matchesSearch && matchesFilter) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Update search results info
                if (currentSearchTerm !== '' || currentFilter !== 'all') {
                    let filterText = '';

                    if (currentFilter !== 'all') {
                        const filterDisplayText = document.querySelector(`.filter-option[data-status="${currentFilter}"]`).textContent.trim();
                        filterText = `Filter: ${filterDisplayText}`;
                    }

                    if (currentSearchTerm !== '') {
                        if (filterText !== '') filterText += ' | ';
                        filterText += `Search: "${currentSearchTerm}"`;
                    }

                    resultsText.innerHTML = `Showing ${visibleCount} of ${totalCount} records. ${filterText}`;
                    searchResultsInfo.style.display = 'block';
                } else {
                    searchResultsInfo.style.display = 'none';
                }

                // Highlight search term in visible rows
                if (currentSearchTerm !== '') {
                    highlightSearchTerm();
                } else {
                    removeHighlights();
                }
            }

            // Function to highlight search term
            function highlightSearchTerm() {
                const searchTerm = currentSearchTerm.toLowerCase();
                if (searchTerm.length < 2) return;

                tableRows.forEach(row => {
                    if (row.style.display !== 'none') {
                        const cells = row.querySelectorAll('td');
                        cells.forEach(cell => {
                            const originalText = cell.textContent;
                            const lowerText = originalText.toLowerCase();

                            if (lowerText.includes(searchTerm)) {
                                const regex = new RegExp(searchTerm, 'gi');
                                const highlightedText = originalText.replace(regex, match =>
                                    `<span class="highlight">${match}</span>`);
                                cell.innerHTML = highlightedText;
                            }
                        });
                    }
                });
            }

            // Function to remove highlights
            function removeHighlights() {
                tableRows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    cells.forEach(cell => {
                        if (cell.querySelector('.highlight')) {
                            cell.innerHTML = cell.textContent;
                        }
                    });
                });
            }

            // Initialize filters
            applyFilters();
        });
    </script>

    <?php
    $stmt->close();
    include('../includes/footer.php');
    ?>
</body>

</html>
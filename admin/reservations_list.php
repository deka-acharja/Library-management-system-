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

// Handle AJAX search request
if (isset($_GET['ajax_search'])) {
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? 'reserved';
    
    header('Content-Type: application/json');
    
    $sql = "SELECT reservation_id, name, cid, email, phone, title, author, serial_no, reservation_date, status 
            FROM reservations
            WHERE status = ? AND (
                name LIKE ? OR 
                cid LIKE ? OR 
                email LIKE ? OR 
                title LIKE ? OR 
                author LIKE ? OR 
                serial_no LIKE ? OR 
                reservation_id LIKE ?
            )
            ORDER BY reservation_date DESC";
    
    $stmt = $conn->prepare($sql);
    $searchParam = "%$search%";
    $stmt->bind_param("ssssssss", $status, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode($data);
    exit;
}

// Handle action requests with email notifications (ONLY for terminate action now)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];

    try {
        // Start transaction
        $conn->begin_transaction();

        // Fetch reservation details
        $stmt = $conn->prepare("SELECT r.*, b.title, b.id as book_id 
            FROM reservations r
            JOIN books b ON r.book_id = b.id
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

        $status = '';
        $subject = '';
        $body = '';

        // Process only terminate action (return is now handled by return_books.php)
        switch ($action) {
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
                break;

            default:
                throw new Exception("Invalid action specified: $action");
        }

        // Update status
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

        // Send notification email
        $emailSent = sendNotification($email, $name, $subject, $body, $action);
        if (!$emailSent) {
            logError("Failed to send notification email for action: $action to $email");
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
        $statusFilter = isset($_GET['status']) ? $_GET['status'] : 'reserved';
        $redirectUrl = "reservations_list.php?msg=" . urlencode($successMsg) . "&status=" . urlencode($statusFilter);
        header("Location: $redirectUrl");
        exit;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $errorMsg = "Error processing action '$action': " . $e->getMessage();
        logError($errorMsg);

        // Redirect with error message
        $statusFilter = isset($_GET['status']) ? $_GET['status'] : 'reserved';
        $redirectUrl = "reservations_list.php?error=" . urlencode($errorMsg) . "&status=" . urlencode($statusFilter);
        header("Location: $redirectUrl");
        exit;
    }
}

// NOW include the header after all potential redirects
include('../includes/dashboard_header.php');

$status = isset($_GET['status']) ? $_GET['status'] : 'reserved';
$valid_statuses = ['reserved', 'confirmed', 'returned', 'cancelled', 'terminated', 'due', 'taken', 'rejected'];
if (!in_array($status, $valid_statuses)) {
    $status = 'reserved';
}

$sql = "SELECT reservation_id, name, cid, email, phone, title, author, serial_no, reservation_date, status 
        FROM reservations
        WHERE status = ?
        ORDER BY reservation_date DESC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL prepare failed: " . $conn->error);
}
$stmt->bind_param("s", $status);
$stmt->execute();
$result = $stmt->get_result();

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ucfirst($status) ?> Book Records - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
            background-color: var(--ultra-light-green);
            color: var(--text-dark);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Fixed Top Navbar */
        .navbar-toggle-container {
            position: fixed;
            top: 220px;
            left: 0;
            right: 0;
            z-index: 1000;
            background: linear-gradient(135deg, var(--main-green) 0%, var(--accent-green) 100%);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
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
            transition: all 0.3s ease;
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
            border-radius: 50%;
            transition: all 0.3s ease;
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
            top: 270px;
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
            border-radius: 4px;
            transition: all 0.3s ease;
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
            border-radius: 4px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
            opacity: 0;
            padding: 15px 20px;
            transform: translateX(100%);
            transition: all 0.3s ease;
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

        /* ==================== UPDATED CONTAINER INTERFACE DESIGN ==================== */
        .main-content {
            margin-left: 0px;
            margin-top: 70px;
            padding: 30px;
            transition: all 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 0;
        }

        .container-fluid {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0;
        }

        /* Card Design */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            background-color: var(--white);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background: linear-gradient(135deg, var(--main-green) 0%, var(--accent-green) 100%);
            color: white;
            border-bottom: none;
            padding: 18px 25px;
            border-radius: 12px 12px 0 0 !important;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            color: var(--white);
            margin: 0;
            font-weight: 600;
            font-size: 1.4rem;
            letter-spacing: 0.5px;
        }

        .card-body {
            padding: 25px;
        }

        /* Form Elements */
        .form-select, .form-control {
            border: 1px solid var(--light-green);
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-select:focus, .form-control:focus {
            border-color: var(--accent-green);
            box-shadow: 0 0 0 0.25rem rgba(46, 106, 51, 0.25);
        }

        /* Search Container */
        .search-container {
            position: relative;
            margin-bottom: 25px;
        }

        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            z-index: 2;
        }

        .search-input {
            padding-left: 45px !important;
            border-radius: 30px !important;
            background-color: var(--ultra-light-green);
            border: 1px solid var(--light-green) !important;
            height: 45px;
            font-size: 0.95rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .search-input:focus {
            background-color: var(--white);
        }

        .loading-spinner {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            display: none;
            z-index: 2;
        }

        /* Search Statistics */
        .search-stats {
            background-color: var(--light-green);
            color: var(--main-green);
            padding: 8px 18px;
            border-radius: 30px;
            display: inline-block;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .search-stats i {
            margin-right: 8px;
        }

        /* Table Design */
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        }

        .table {
            color: var(--text-dark);
            margin-bottom: 0;
        }

        .table th {
            background-color: var(--main-green);
            color: var(--white);
            font-weight: 600;
            border-bottom: none;
            padding: 15px;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        .table td {
            border-bottom: 1px solid var(--ultra-light-green);
            vertical-align: middle;
            padding: 12px 15px;
            background-color: var(--white);
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:hover td {
            background-color: rgba(201, 220, 203, 0.3);
        }

        /* Badges */
        .badge {
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            text-transform: capitalize;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .bg-reserved {
            background-color: #f8f9fa;
            color: var(--text-dark);
        }

        .bg-confirmed {
            background-color: var(--success-green);
            color: white;
        }

        .bg-returned {
            background-color: #17a2b8;
            color: white;
        }

        .bg-cancelled {
            background-color: #6c757d;
            color: white;
        }

        .bg-terminated {
            background-color: var(--error-red);
            color: white;
        }

        .bg-due {
            background-color: var(--warning-yellow);
            color: white;
        }

        .bg-taken {
            background-color: #6610f2;
            color: white;
        }

        .bg-rejected {
            background-color: #dc3545;
            color: white;
        }

        /* Buttons */
        .btn {
            border-radius: 6px;
            font-weight: 500;
            padding: 8px 14px;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }

        .btn-primary {
            background-color: var(--main-green);
        }

        .btn-primary:hover {
            background-color: var(--dark-green);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
        }

        .btn-warning {
            background-color: var(--warning-yellow);
            color: white;
        }

        .btn-warning:hover {
            background-color: #e67e22;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
        }

        .btn-danger {
            background-color: var(--error-red);
        }

        .btn-danger:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
        }

        .btn i {
            margin-right: 5px;
        }

        /* No Results */
        .no-results {
            color: var(--text-light);
            text-align: center;
            padding: 30px;
            font-size: 1rem;
        }

        .no-results i {
            font-size: 1.5rem;
            margin-bottom: 10px;
            display: block;
            color: var(--text-light);
        }

        /* Status Filter Dropdown */
        #statusSelect {
            background-color: var(--white);
            color: var(--text-dark);
            border: none;
            border-radius: 6px;
            padding: 8px 15px;
            width: 180px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            #statusSelect {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .table th, .table td {
                padding: 10px 8px;
                font-size: 0.85rem;
            }
            
            .btn {
                padding: 6px 10px;
                font-size: 0.8rem;
            }
            
            .search-input {
                height: 40px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px;
            }
            
            .card-header {
                padding: 15px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
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

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <div class="container-fluid">
            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_GET['msg']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_GET['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title mb-0"><?= ucfirst($status) ?> Reservations</h2>
                    <form method="GET" class="form-inline">
                        <select name="status" class="form-select" onchange="this.form.submit()" id="statusSelect">
                            <?php foreach ($valid_statuses as $s): ?>
                                <option value="<?= $s ?>" <?= ($status == $s) ? 'selected' : '' ?>>
                                    <?= ucfirst($s) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                <div class="card-body">
                    <!-- Search Container -->
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" 
                               class="form-control search-input" 
                               id="searchInput" 
                               placeholder="Search by Name, CID, Email, Phone, Book Title, or Reservation ID..."
                               autocomplete="off">
                        <div class="loading-spinner" id="loadingSpinner">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                    </div>
                    
                    <!-- Search Statistics -->
                    <div class="search-stats" id="searchStats" style="display: none;">
                        <i class="fas fa-info-circle"></i>
                        <span id="searchResultsCount">0</span> results found
                    </div>

                    <!-- Table Container -->
                    <div class="table-responsive">
                        <table class="table" id="reservationsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Reservation ID</th>
                                    <th>Name</th>
                                    <th>CID</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Book Title</th>
                                    <th>Author</th>
                                    <th>Serial No</th>
                                    <th>Reservation Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="reservationsTableBody">
                                <?php if ($result->num_rows > 0): ?>
                                    <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($row['reservation_id']) ?></td>
                                            <td><?= htmlspecialchars($row['name']) ?></td>
                                            <td><?= htmlspecialchars($row['cid']) ?></td>
                                            <td><?= htmlspecialchars($row['email']) ?></td>
                                            <td><?= htmlspecialchars($row['phone']) ?></td>
                                            <td><?= htmlspecialchars($row['title']) ?></td>
                                            <td><?= htmlspecialchars($row['author']) ?></td>
                                            <td><?= htmlspecialchars($row['serial_no']) ?></td>
                                            <td><?= htmlspecialchars($row['reservation_date']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $row['status'] ?>">
                                                    <?= htmlspecialchars($row['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <?php if ($row['status'] == 'reserved'): ?>
                                                        <a href="reserved_books_issue.php?id=<?= $row['reservation_id'] ?>"
                                                            class="btn btn-warning btn-sm"
                                                            title="Issue Book">
                                                            <i class="fas fa-hand-paper"></i> Issues
                                                        </a>
                                                    <?php elseif ($row['status'] == 'confirmed'): ?>
                                                        <a href="?action=terminate&id=<?= $row['reservation_id'] ?>&status=<?= $status ?>"
                                                            class="btn btn-warning btn-sm"
                                                            onclick="return confirm('Are you sure you want to terminate this reservation? An email notification will be sent.')">
                                                            <i class="fas fa-ban"></i> Terminate
                                                        </a>
                                                    <?php elseif ($row['status'] == 'taken'): ?>
                                                        <a href="return_books.php?id=<?= $row['reservation_id'] ?>"
                                                            class="btn btn-primary btn-sm"
                                                            title="Process Return">
                                                            <i class="fas fa-undo"></i> Return
                                                        </a>
                                                    <?php elseif ($row['status'] == 'due paid'): ?>
                                                        <a href="return_books.php?id=<?= $row['reservation_id'] ?>"
                                                            class="btn btn-danger btn-sm"
                                                            title="Process Overdue Return">
                                                            <i class="fas fa-undo"></i> Return
                                                        </a>
                                                      <?php elseif ($row['status'] == 'due'): ?>
                                                       <span class="text-warning"><i class="fas fa-exclamation-circle"></i> Payment required first</span>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">No Action</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>  
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr id="noResultsRow">
                                        <td colspan="12" class="no-results">
                                            <i class="fas fa-info-circle me-2"></i>
                                            No reservations found with status '<?= htmlspecialchars($status) ?>'.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Navbar toggle functionality
        document.getElementById('navbarToggle').addEventListener('click', function() {
            this.classList.toggle('active');
            const icon = this.querySelector('i');
            if (icon.classList.contains('fa-bars')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }

            const secondaryNavbar = document.getElementById('secondaryNavbar');
            secondaryNavbar.classList.toggle('active');

            const contentWrapper = document.getElementById('contentWrapper');
            contentWrapper.classList.toggle('navbar-active');
        });


        // Show toast notifications if any
        <?php if (isset($_GET['msg'])): ?>
            showToast('Success', '<?= addslashes($_GET['msg']) ?>', 'success');
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            showToast('Error', '<?= addslashes($_GET['error']) ?>', 'error');
        <?php endif; ?>

        function showToast(title, message, type) {
            // Create toast element
            const toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 end-0 p-3';
            toast.style.zIndex = '1100';
            
            const toastInner = document.createElement('div');
            toastInner.className = `toast show align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
            toastInner.setAttribute('role', 'alert');
            toastInner.setAttribute('aria-live', 'assertive');
            toastInner.setAttribute('aria-atomic', 'true');
            
            toastInner.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${title}</strong><br>${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            toast.appendChild(toastInner);
            document.body.appendChild(toast);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                toast.remove();
            }, 5000);
            
            // Add click to dismiss
            toastInner.querySelector('.btn-close').addEventListener('click', () => {
                toast.remove();
            });
        }
    </script>
    <?php include('../includes/footer.php'); ?>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
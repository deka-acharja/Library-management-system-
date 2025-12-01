<?php
include('../includes/db.php');
include('../includes/dashboard_header.php');

// Include PHPMailer classes
require_once '../PHPMailer/src/Exception.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Email sending function
function sendEmail($recipientEmail, $recipientName, $subject, $body) {
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

$cid = '';
$borrowed_books = [];
$message = '';
$success = '';

// Handle confirmation form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['confirm_return'])) {
    $borrow_id = $_POST['borrow_id'];

    // Get book and user details for email
    $book_query = "SELECT bb.*, b.title as book_title, u.name as user_name, u.email as user_email 
                   FROM borrow_books bb 
                   JOIN books b ON bb.book_id = b.book_id 
                   JOIN users u ON bb.cid = u.cid 
                   WHERE bb.borrow_id = ?";
    $book_stmt = $conn->prepare($book_query);
    $book_stmt->bind_param("i", $borrow_id);
    $book_stmt->execute();
    $book_result = $book_stmt->get_result();
    $book_data = $book_result->fetch_assoc();

    $update = $conn->prepare("UPDATE borrow_books SET status = 'returned' WHERE borrow_id = ?");
    $update->bind_param("i", $borrow_id);

    if ($update->execute()) {
        $success = "Book successfully marked as returned.";
        
        // Send email notification
        if ($book_data && !empty($book_data['user_email'])) {
            $subject = "Book Return Confirmation - Library Management System";
            $email_body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; color: #263028; }
                    .header { background-color: #2e6a33; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background-color: #eef5ef; }
                    .footer { background-color: #c9dccb; padding: 10px; text-align: center; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='header'>
                    <h2>Book Return Confirmation</h2>
                </div>
                <div class='content'>
                    <p>Dear " . htmlspecialchars($book_data['user_name']) . ",</p>
                    <p>This is to confirm that you have successfully returned the following book:</p>
                    <ul>
                        <li><strong>Book Title:</strong> " . htmlspecialchars($book_data['book_title']) . "</li>
                        <li><strong>Book ID:</strong> " . htmlspecialchars($book_data['book_id']) . "</li>
                        <li><strong>Return Date:</strong> " . date('Y-m-d H:i:s') . "</li>
                    </ul>
                    <p>Thank you for using our library services!</p>
                </div>
                <div class='footer'>
                    <p>Library Management System - Automated Message</p>
                </div>
            </body>
            </html>";
            
            $email_sent = sendEmail($book_data['user_email'], $book_data['user_name'], $subject, $email_body);
            if ($email_sent) {
                $success .= " Email notification sent to user.";
            } else {
                $success .= " However, email notification could not be sent.";
            }
        }
    } else {
        $message = "Failed to update book status.";
    }
}

// Handle search
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search'])) {
    $cid = trim($_POST['cid']);

    if (!empty($cid)) {
        $query = "SELECT * FROM borrow_books WHERE cid = ? AND (status = 'borrowed' OR status = 'due' OR status = 'due paid')";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $cid);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $borrowed_books[] = $row;
            }
        } else {
            $message = "No borrowed books found for CID: $cid.<br>Please verify the CID number and try again.";
        }

        $stmt->close();
    } else {
        $message = "Please enter a CID number.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Return Book / Pay Fine</title>
    <link rel="stylesheet" href="../assets/admin.css">
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
            background: linear-gradient(135deg, var(--ultra-light-green) 0%, var(--light-green) 100%);
            color: var(--text-dark);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
            margin-top: 250px;
        }

        h2 {
            color: var(--text-dark);
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 2rem;
            text-shadow: 0 2px 4px rgba(46, 106, 51, 0.1);
        }

        .search-section {
            background: var(--white);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(46, 106, 51, 0.1);
            margin-bottom: 2rem;
            border: 1px solid var(--light-green);
        }

        .search-form {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        label {
            font-weight: 600;
            color: var(--text-medium);
            font-size: 1.1rem;
        }

        input[type="text"] {
            flex: 1;
            min-width: 200px;
            padding: 12px 16px;
            border: 2px solid var(--light-green);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--ultra-light-green);
        }

        input[type="text"]:focus {
            outline: none;
            border-color: var(--main-green);
            box-shadow: 0 0 0 3px rgba(46, 106, 51, 0.1);
            background: var(--white);
        }

        button {
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--main-green) 0%, var(--accent-green) 100%);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(46, 106, 51, 0.3);
        }

        button:hover {
            background: linear-gradient(135deg, var(--dark-green) 0%, var(--main-green) 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46, 106, 51, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        .results-section {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(46, 106, 51, 0.1);
            border: 1px solid var(--light-green);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: linear-gradient(135deg, var(--main-green) 0%, var(--accent-green) 100%);
            color: var(--white);
            padding: 16px 12px;
            font-weight: 600;
            text-align: left;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
        }

        td {
            padding: 16px 12px;
            border-bottom: 1px solid var(--light-green);
            color: var(--text-dark);
            vertical-align: middle;
        }

        tr:nth-child(even) {
            background: var(--ultra-light-green);
        }

        tr:hover {
            background: var(--light-green);
            transition: background-color 0.3s ease;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-borrowed {
            background: var(--accent-green);
            color: var(--white);
        }

        .status-due {
            background: var(--warning-yellow);
            color: var(--white);
        }

        .status-due-paid {
            background: var(--success-green);
            color: var(--white);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-return {
            background: linear-gradient(135deg, var(--success-green) 0%, #27ae60 100%);
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        .btn-pay {
            background: linear-gradient(135deg, var(--warning-yellow) 0%, #e67e22 100%);
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        .btn-return:hover {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
        }

        .btn-pay:hover {
            background: linear-gradient(135deg, #e67e22 0%, var(--warning-yellow) 100%);
        }

        form.inline {
            display: inline;
        }

        .msg-success {
            background: linear-gradient(135deg, var(--success-green) 0%, #27ae60 100%);
            color: var(--white);
            padding: 16px 20px;
            border-radius: 10px;
            margin: 1rem 0;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(46, 204, 113, 0.3);
        }

        .msg-error {
            background: linear-gradient(135deg, var(--error-red) 0%, #c0392b 100%);
            color: var(--white);
            padding: 16px 20px;
            border-radius: 10px;
            margin: 1rem 0;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(214, 69, 65, 0.3);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--main-green);
            text-decoration: none;
            font-weight: 600;
            padding: 12px 20px;
            border-radius: 8px;
            background: var(--ultra-light-green);
            border: 2px solid var(--light-green);
            transition: all 0.3s ease;
            margin-top: 2rem;
        }

        .back-link:hover {
            background: var(--light-green);
            border-color: var(--main-green);
            transform: translateX(-4px);
        }

        .no-data {
            text-align: center;
            padding: 3rem;
            color: var(--text-medium);
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .container {
                margin: 1rem;
                padding: 0 0.5rem;
            }

            h2 {
                font-size: 2rem;
            }

            .search-form {
                flex-direction: column;
                align-items: stretch;
            }

            input[type="text"] {
                min-width: unset;
            }

            table {
                font-size: 0.9rem;
            }

            th, td {
                padding: 12px 8px;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Return Book </h2>

    <div class="search-section">
        <form method="POST" class="search-form">
            <label for="cid">Enter CID:</label>
            <input type="text" name="cid" id="cid" value="<?= htmlspecialchars($cid); ?>" required placeholder="Enter student/member CID">
            <button type="submit" name="search">Search Records</button>
        </form>
    </div>

    <?php if (!empty($message)): ?>
        <div class="msg-error"><?= $message; ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="msg-success"><?= $success; ?></div>
    <?php endif; ?>

    <?php if (count($borrowed_books) > 0): ?>
        <div class="results-section">
            <table>
                <thead>
                    <tr>
                        <th>Book ID</th>
                        <th>CID</th>
                        <th>Taken Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($borrowed_books as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['book_id']); ?></td>
                            <td><?= htmlspecialchars($row['cid']); ?></td>
                            <td><?= htmlspecialchars($row['taken_date']); ?></td>
                            <td><?= htmlspecialchars($row['return_due_date']); ?></td>
                            <td>
                                <span class="status-badge status-<?= str_replace(' ', '-', $row['status']); ?>">
                                    <?= htmlspecialchars($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($row['status'] == 'borrowed'): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to return this book?');">
                                            <input type="hidden" name="borrow_id" value="<?= $row['borrow_id']; ?>">
                                            <button type="submit" name="confirm_return" class="btn-return">Return Book</button>
                                        </form>
                                    <?php elseif ($row['status'] == 'due'): ?>
                                        <form method="GET" action="pay_fine.php" class="inline">
                                            <input type="hidden" name="borrow_id" value="<?= $row['borrow_id']; ?>">
                                            <button type="submit" name="pay_fine" class="btn-pay">Pay Fine</button>
                                        </form>
                                    <?php elseif ($row['status'] == 'due paid'): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to return this book?');">
                                            <input type="hidden" name="borrow_id" value="<?= $row['borrow_id']; ?>">
                                            <button type="submit" name="confirm_return" class="btn-return">Return Book</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif (!empty($cid)): ?>
        <div class="results-section">
            <div class="no-data">
                <p>No borrowed books found for the entered CID.</p>
            </div>
        </div>
    <?php endif; ?>

    <a href="confirmed_reservation.php" class="back-link">←</a>
</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>
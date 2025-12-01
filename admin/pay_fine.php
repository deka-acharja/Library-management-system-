<?php
include('../includes/db.php');
include('../includes/dashboard_header.php');

require_once '../PHPMailer/src/Exception.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($recipientEmail, $recipientName, $subject, $body)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = '11206005480@rim.edu.bt'; // better use env vars in production
        $mail->Password = 'wsgm gyrg bgbl vynx';     // better use env vars in production
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

$borrow_id = $_GET['borrow_id'] ?? '';
$message = '';
$success = '';
$data = null;
$fine_per_day = 100;
$days_overdue = 0;
$fine_amount = 0;

if ($borrow_id) {
    $query = "
        SELECT bb.borrow_id, bb.book_id, bb.cid, bb.return_due_date, 
               u.name AS user_name, u.email AS user_email, b.title AS book_title 
        FROM borrow_books bb
        JOIN users u ON bb.cid = u.username
        JOIN books b ON bb.book_id = b.id
        WHERE bb.borrow_id = ?
    ";

    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $borrow_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        if ($data) {
            $today = new DateTime();
            $due_date = new DateTime($data['return_due_date']);

            if ($today > $due_date) {
                $days_overdue = $today->diff($due_date)->days;
                $fine_amount = $days_overdue * $fine_per_day;
            }
        } else {
            $message = "No borrow record found for ID: $borrow_id.";
        }
    } else {
        $message = "Failed to prepare statement: " . $conn->error;
    }
} else {
    $message = "No borrow ID provided.";
}

// Handle screenshot upload and payment saving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment']) && isset($_FILES['screenshot']) && $data) {
    $upload_dir = "../uploads/payments/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_tmp = $_FILES['screenshot']['tmp_name'];
    $file_name = basename($_FILES['screenshot']['name']);
    $file_name = preg_replace("/[^a-zA-Z0-9\._-]/", "", $file_name);
    $target_file = $upload_dir . time() . '_' . $file_name;

    if (move_uploaded_file($file_tmp, $target_file)) {
        // Insert payment record
        $insert_query = "INSERT INTO payment_borrowers (borrow_id, fine_amount, screenshot, payment_date) VALUES (?, ?, ?, NOW())";
        $stmt_insert = $conn->prepare($insert_query);

        if ($stmt_insert) {
            $stmt_insert->bind_param("ids", $borrow_id, $fine_amount, $target_file);
            if ($stmt_insert->execute()) {
                $stmt_insert->close();

                // Update borrow_books status to 'due paid'
                $update_query = "UPDATE borrow_books SET status = 'due paid' WHERE borrow_id = ?";
                $stmt_update = $conn->prepare($update_query);
                if ($stmt_update) {
                    $stmt_update->bind_param("i", $borrow_id);
                    $stmt_update->execute();
                    $stmt_update->close();

                    // Send confirmation email to user
                    $subject = "Library Fine Payment Confirmation";
                    $body = "
                        <p>Dear " . htmlspecialchars($data['user_name']) . ",</p>
                        <p>We have received your payment for the fine related to the book <strong>" . htmlspecialchars($data['book_title']) . "</strong>.</p>
                        <p>Amount Paid: Nu. " . $fine_amount . "</p>
                        <p>Thank you for your prompt payment.</p>
                        <p>Regards,<br>Library Management System</p>
                    ";

                    $emailSent = sendEmail($data['user_email'], $data['user_name'], $subject, $body);

                    if ($emailSent) {
                        $success = "Payment screenshot uploaded, payment recorded, status updated, and confirmation email sent.";
                    } else {
                        $message = "Payment saved and status updated, but failed to send confirmation email.";
                    }
                } else {
                    $message = "Payment saved but failed to update borrow status: " . $conn->error;
                }
            } else {
                $message = "Failed to save payment details: " . $stmt_insert->error;
            }
        } else {
            $message = "Failed to prepare insert statement: " . $conn->error;
        }
    } else {
        $message = "Failed to upload screenshot.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Fine - Library Management System</title>
    <link rel="stylesheet" href="../assets/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, var(--ultra-light-green) 0%, var(--light-green) 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            color: var(--text-dark);
        }

        .page-container {
            max-width: 900px;
            margin: 30px auto;
            margin-top: 260px;
            padding: 0 20px;
        }

        .page-header {
            background: linear-gradient(135deg, var(--main-green), var(--accent-green));
            color: var(--white);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(46, 106, 51, 0.3);
            text-align: center;
        }

        .page-header h1 {
            margin: 0;
            font-size: 2.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .page-header .icon {
            background: rgba(255, 255, 255, 0.2);
            padding: 10px;
            border-radius: 12px;
            font-size: 1.8rem;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .alert-success {
            background: linear-gradient(135deg, var(--success-green), #27ae60);
            color: var(--white);
        }

        .alert-error {
            background: linear-gradient(135deg, var(--error-red), #c0392b);
            color: var(--white);
        }

        .alert i {
            font-size: 1.2rem;
        }

        .payment-card {
            background: var(--white);
            border-radius: 20px;
            padding: 0;
            box-shadow: 0 15px 40px rgba(46, 106, 51, 0.15);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .card-header {
            background: linear-gradient(135deg, var(--accent-green), var(--main-green));
            color: var(--white);
            padding: 25px 30px;
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-body {
            padding: 30px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .detail-item {
            background: var(--ultra-light-green);
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid var(--main-green);
        }

        .detail-label {
            font-size: 0.9rem;
            color: var(--text-medium);
            font-weight: 500;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: 1.1rem;
            color: var(--text-dark);
            font-weight: 600;
        }

        .fine-amount {
            background: linear-gradient(135deg, var(--warning-yellow), #e67e22);
            color: var(--white);
            font-size: 1.4rem;
            text-align: center;
            padding: 20px;
            border-radius: 12px;
            margin: 25px 0;
            box-shadow: 0 6px 20px rgba(243, 156, 18, 0.3);
        }

        .payment-section {
            background: var(--ultra-light-green);
            padding: 30px;
            border-radius: 15px;
            margin-top: 25px;
        }

        .payment-section h3 {
            color: var(--main-green);
            margin: 0 0 20px 0;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .qr-container {
            text-align: center;
            margin: 25px 0;
        }

        .qr-image {
            width: 180px;
            height: 180px;
            border: 3px solid var(--main-green);
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(46, 106, 51, 0.2);
            transition: transform 0.3s ease;
        }

        .qr-image:hover {
            transform: scale(1.05);
        }

        .upload-form {
            margin-top: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 10px;
            font-size: 1rem;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-input {
            width: 100%;
            padding: 15px;
            border: 2px dashed var(--light-green);
            border-radius: 12px;
            background: var(--white);
            font-size: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-input:hover {
            border-color: var(--main-green);
            background: var(--ultra-light-green);
        }

        .file-input:focus {
            outline: none;
            border-color: var(--accent-green);
            box-shadow: 0 0 0 3px rgba(46, 106, 51, 0.1);
        }

        .btn {
            background: linear-gradient(135deg, var(--main-green), var(--accent-green));
            color: var(--white);
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 6px 20px rgba(46, 106, 51, 0.3);
        }

        .btn:hover {
            background: linear-gradient(135deg, var(--dark-green), var(--main-green));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 106, 51, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--main-green);
            text-decoration: none;
            font-weight: 600;
            padding: 12px 20px;
            border-radius: 10px;
            background: var(--ultra-light-green);
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .back-link:hover {
            background: var(--light-green);
            transform: translateX(-5px);
        }

        .instruction-box {
            background: linear-gradient(135deg, var(--light-green), var(--ultra-light-green));
            border: 1px solid var(--main-green);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .instruction-box h4 {
            color: var(--main-green);
            margin: 0 0 15px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .instruction-box ol {
            margin: 0;
            padding-left: 20px;
            color: var(--text-medium);
        }

        .instruction-box li {
            margin-bottom: 8px;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .page-container {
                padding: 0 15px;
            }

            .page-header {
                padding: 20px;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .card-body {
                padding: 20px;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .payment-section {
                padding: 20px;
            }

            .qr-image {
                width: 150px;
                height: 150px;
            }
        }
    </style>
</head>
<body>

<div class="page-container">
    <div class="page-header">
        <h1>
            <span class="icon"><i class="fas fa-money-bill-wave"></i></span>
            Library Fine Payment
        </h1>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?= htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($data): ?>
        <div class="payment-card">
            <div class="card-header">
                <i class="fas fa-user-clock"></i>
                Fine Details
            </div>
            <div class="card-body">
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">User Name</div>
                        <div class="detail-value"><?= htmlspecialchars($data['user_name']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Book Title</div>
                        <div class="detail-value"><?= htmlspecialchars($data['book_title']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Days Overdue</div>
                        <div class="detail-value"><?= $days_overdue; ?> days</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Fine Rate</div>
                        <div class="detail-value">Nu. <?= $fine_per_day; ?> per day</div>
                    </div>
                </div>

                <div class="fine-amount">
                    <i class="fas fa-calculator"></i>
                    Total Fine Amount: Nu. <?= $fine_amount; ?>
                </div>

                <div class="payment-section">
                    <h3><i class="fas fa-qrcode"></i> Payment Instructions</h3>
                    
                    <div class="instruction-box">
                        <h4><i class="fas fa-info-circle"></i> How to Pay</h4>
                        <ol>
                            <li>Scan the QR code below using your mobile banking app</li>
                            <li>Pay the exact fine amount: Nu. <?= $fine_amount; ?></li>
                            <li>Take a screenshot of the successful payment</li>
                            <li>Upload the screenshot using the form below</li>
                        </ol>
                    </div>

                    <div class="qr-container">
                        <img src="../images/qr_code.png" alt="Payment QR Code" class="qr-image">
                        <p style="color: var(--text-medium); margin-top: 15px; font-weight: 500;">
                            <i class="fas fa-mobile-alt"></i> Scan with your banking app
                        </p>
                    </div>

                    <form method="POST" enctype="multipart/form-data" class="upload-form">
                        <div class="form-group">
                            <label for="screenshot" class="form-label">
                                <i class="fas fa-camera"></i> Upload Payment Screenshot
                            </label>
                            <input type="file" 
                                   name="screenshot" 
                                   id="screenshot" 
                                   accept="image/*" 
                                   required 
                                   class="file-input">
                        </div>
                        <button type="submit" name="submit_payment" class="btn">
                            <i class="fas fa-upload"></i>
                            Submit Payment Proof
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <a href="return_borrow_books.php" class="back-link">
        <i class="fas fa-arrow-left"></i>
        Back to Borrow Records
    </a>
</div>

<?php include('../includes/footer.php'); ?>

<script>
    // File input enhancement
    document.getElementById('screenshot').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name;
        if (fileName) {
            // You could add a preview or filename display here
            console.log('Selected file:', fileName);
        }
    });

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const fileInput = document.getElementById('screenshot');
        if (!fileInput.files.length) {
            e.preventDefault();
            alert('Please select a payment screenshot to upload.');
            return false;
        }
        
        // Optional: Check file size (e.g., max 5MB)
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (fileInput.files[0].size > maxSize) {
            e.preventDefault();
            alert('File size must be less than 5MB.');
            return false;
        }
    });
</script>

</body>
</html>
<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if required files exist before including
if (!file_exists('../includes/db.php')) {
    die("Error: Database connection file not found. Please check the path '../includes/db.php'");
}

if (!file_exists('../includes/dashboard_header.php')) {
    die("Error: Dashboard header file not found. Please check the path '../includes/dashboard_header.php'");
}

include('../includes/db.php');
include('../includes/dashboard_header.php');

// Check if database connection exists
if (!isset($conn) || $conn === null) {
    die("Error: Database connection not established. Please check your database configuration.");
}

$cid = isset($_GET['cid']) ? trim($_GET['cid']) : '';
$overdue_records = [];
$total_fine = 0;
$fine_per_day = 100; // Fine amount per day in Ngultrum

// Payment processing function - FIXED VERSION
function processPayment($conn, $cid, $overdue_records, $file_info)
{
    $response = ['success' => false, 'message' => '', 'payment_ids' => []];

    try {
        // Validate file
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file_info['type'], $allowed_types)) {
            throw new Exception("Invalid file type. Please upload JPG, PNG, or GIF files only.");
        }

        if ($file_info['size'] > $max_size) {
            throw new Exception("File too large. Please upload files under 5MB.");
        }

        // Read file content directly from temporary location
        $image_data = file_get_contents($file_info['tmp_name']);
        if ($image_data === false) {
            throw new Exception("Failed to read uploaded file.");
        }

        // Verify file content is valid
        if (strlen($image_data) < 100) { // Image should be at least 100 bytes
            throw new Exception("Uploaded file appears to be corrupted or too small.");
        }

        // Create upload directory if it doesn't exist
        $upload_dir = 'uploads/payments/';
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                throw new Exception("Failed to create upload directory.");
            }
        }

        // Generate unique filename for backup storage
        $file_extension = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
        $unique_filename = $cid . '_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $unique_filename;

        // Save file to filesystem as backup (optional)
        if (!move_uploaded_file($file_info['tmp_name'], $file_path)) {
            // If file move fails, we still have the image data in memory, continue
            error_log("Warning: Failed to save backup file to filesystem: " . $file_path);
            $file_path = null;
        }

        // Calculate total amount
        $total_amount = array_sum(array_column($overdue_records, 'fine_amount'));

        // Start transaction
        $conn->begin_transaction();

        // Check if payments table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'payments'");
        if ($table_check->num_rows == 0) {
            throw new Exception("Payments table does not exist. Please create the payments table first.");
        }

        // Insert payment records with proper BLOB handling
        $payment_sql = "INSERT INTO payments (reservation_id, name, title, cid, days_overdue, amount, payment_date, payment_screenshot) 
                       VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";

        $payment_stmt = $conn->prepare($payment_sql);

        if (!$payment_stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }

        $payment_ids = [];

        foreach ($overdue_records as $record) {
            // Get customer name from reservation - ensure we have a valid name
            $customer_name = isset($record['name']) && !empty($record['name']) ? trim($record['name']) : 'Customer';

            // Ensure all values are properly formatted
            $reservation_id = (string) $record['reservation_id'];
            $title = isset($record['title']) ? trim($record['title']) : 'Unknown Title';
            $days_overdue = (int) $record['days_overdue'];
            $amount = (float) $record['fine_amount'];

            // Log information for debugging
            error_log("Processing payment for: ResID={$reservation_id}, Name={$customer_name}, Title={$title}, CID={$cid}, Days={$days_overdue}, Amount={$amount}");
            error_log("Image data size: " . strlen($image_data) . " bytes");

            // Bind parameters - FIXED: Use 's' for string instead of 'b' for blob
            $payment_stmt->bind_param(
                "ssssids",
                $reservation_id,    // reservation_id (varchar 50)
                $customer_name,     // name (varchar 255) 
                $title,            // title (varchar 255)
                $cid,              // cid (varchar 50)
                $days_overdue,     // days_overdue (int)
                $amount,           // amount (decimal 10,2)
                $image_data        // payment_screenshot (longblob) - treated as string
            );

            if (!$payment_stmt->execute()) {
                throw new Exception("Failed to insert payment record for reservation {$reservation_id}: " . $payment_stmt->error);
            }

            $payment_id = $conn->insert_id;
            $payment_ids[] = $payment_id;

            // Log successful insertion
            error_log("Payment record inserted successfully with ID: {$payment_id}");
        }
        $payment_stmt->close();

        // Update reservation status to indicate payment is submitted
        $update_sql = "UPDATE reservations SET status = 'due paid',is_viewed = 0 WHERE cid = ? AND (status = 'overdue' OR status = 'due')";
        $update_stmt = $conn->prepare($update_sql);

        if (!$update_stmt) {
            throw new Exception("Database prepare error for update: " . $conn->error);
        }

        $update_stmt->bind_param("s", $cid);

        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update reservation status: " . $update_stmt->error);
        }

        $affected_rows = $update_stmt->affected_rows;
        $update_stmt->close();

        if ($affected_rows == 0) {
            error_log("Warning: No reservations were updated for CID: {$cid}");
        }

        // Commit transaction
        $conn->commit();

        $response['success'] = true;
        $response['message'] = "Payment screenshot uploaded successfully! " . count($payment_ids) . " payment record(s) created. Your payment is under review.";
        $response['payment_ids'] = $payment_ids;
        $response['file_path'] = $file_path;

        // Log successful completion
        error_log("Payment processing completed successfully. Image size: " . strlen($image_data) . " bytes");

    } catch (Exception $e) {
        $conn->rollback();

        // Clean up file if it exists and there was an error
        if (isset($file_path) && $file_path && file_exists($file_path)) {
            unlink($file_path);
            error_log("Cleaned up file due to error: " . $file_path);
        }

        $response['message'] = $e->getMessage();
        error_log("Payment processing error: " . $e->getMessage());
    }

    return $response;
}

// Alternative payment processing function using different BLOB approach
function processPaymentAlternative($conn, $cid, $overdue_records, $file_info)
{
    $response = ['success' => false, 'message' => '', 'payment_ids' => []];

    try {
        // Validate file
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file_info['type'], $allowed_types)) {
            throw new Exception("Invalid file type. Please upload JPG, PNG, or GIF files only.");
        }

        if ($file_info['size'] > $max_size) {
            throw new Exception("File too large. Please upload files under 5MB.");
        }

        // Read file content
        $image_data = file_get_contents($file_info['tmp_name']);
        if ($image_data === false) {
            throw new Exception("Failed to read uploaded file.");
        }

        if (strlen($image_data) < 100) {
            throw new Exception("Uploaded file appears to be corrupted or too small.");
        }

        // Calculate total amount
        $total_amount = array_sum(array_column($overdue_records, 'fine_amount'));

        // Start transaction
        $conn->begin_transaction();

        $payment_ids = [];

        foreach ($overdue_records as $record) {
            $customer_name = isset($record['name']) && !empty($record['name']) ? trim($record['name']) : 'Customer';
            $reservation_id = (string) $record['reservation_id'];
            $title = isset($record['title']) ? trim($record['title']) : 'Unknown Title';
            $days_overdue = (int) $record['days_overdue'];
            $amount = (float) $record['fine_amount'];

            // Use a different approach - direct SQL with proper escaping
            $escaped_image = $conn->real_escape_string($image_data);
            $escaped_reservation_id = $conn->real_escape_string($reservation_id);
            $escaped_customer_name = $conn->real_escape_string($customer_name);
            $escaped_title = $conn->real_escape_string($title);
            $escaped_cid = $conn->real_escape_string($cid);

            $payment_sql = "INSERT INTO payments (reservation_id, name, title, cid, days_overdue, amount, payment_date, payment_screenshot) 
                           VALUES ('$escaped_reservation_id', '$escaped_customer_name', '$escaped_title', '$escaped_cid', $days_overdue, $amount, NOW(), '$escaped_image')";

            if (!$conn->query($payment_sql)) {
                throw new Exception("Failed to insert payment record for reservation {$reservation_id}: " . $conn->error);
            }

            $payment_id = $conn->insert_id;
            $payment_ids[] = $payment_id;

            error_log("Payment record inserted successfully with ID: {$payment_id}, Image size: " . strlen($image_data) . " bytes");
        }

        // Update reservation status
        $update_sql = "UPDATE reservations SET status = 'payment_submitted' WHERE cid = ? AND (status = 'overdue' OR status = 'due')";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("s", $cid);
        $update_stmt->execute();
        $update_stmt->close();

        $conn->commit();

        $response['success'] = true;
        $response['message'] = "Payment screenshot uploaded successfully! " . count($payment_ids) . " payment record(s) created. Your payment is under review.";
        $response['payment_ids'] = $payment_ids;

    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = $e->getMessage();
        error_log("Payment processing error: " . $e->getMessage());
    }

    return $response;
}

// Initialize variables for form handling
$upload_error = '';
$success_message = '';
$payment_submitted = false;

// Fetch overdue records for the CID first
if (!empty($cid)) {
    try {
        // Check if reservations table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'reservations'");
        if ($table_check->num_rows == 0) {
            throw new Exception("Reservations table does not exist.");
        }

        // Updated query to fetch overdue records
        $stmt = $conn->prepare("SELECT reservation_id, name, cid, title, author, reservation_date, status 
                               FROM reservations 
                               WHERE cid = ? AND (status = 'overdue' OR status = 'due')
                               ORDER BY reservation_date ASC");

        if ($stmt === false) {
            throw new Exception("Database prepare error: " . $conn->error);
        }

        $stmt->bind_param("s", $cid);
        if (!$stmt->execute()) {
            throw new Exception("Database execute error: " . $stmt->error);
        }

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            // Calculate days overdue
            $reservation_date = new DateTime($row['reservation_date']);
            $current_date = new DateTime();
            $diff = $current_date->diff($reservation_date);
            $days_since_reservation = $diff->days;

            // Assuming 7 days borrowing period, anything beyond that is overdue
            $days_overdue = max(0, $days_since_reservation - 7);

            $row['days_overdue'] = $days_overdue;
            $row['fine_amount'] = $days_overdue * $fine_per_day;
            $total_fine += $row['fine_amount'];

            // Only add records that actually have overdue days
            if ($days_overdue > 0) {
                $overdue_records[] = $row;
            }
        }
        $stmt->close();
    } catch (Exception $e) {
        $upload_error = "Database error: " . $e->getMessage();
        error_log("Database query error: " . $e->getMessage());
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_payment'])) {
    if (empty($overdue_records)) {
        $upload_error = "No overdue records found to process payment.";
    } elseif (isset($_FILES['payment_screenshot']) && $_FILES['payment_screenshot']['error'] == 0) {
        // Debug file information
        error_log("File upload info: " . print_r($_FILES['payment_screenshot'], true));

        // Additional file validation
        if ($_FILES['payment_screenshot']['size'] == 0) {
            $upload_error = "Uploaded file is empty. Please select a valid image file.";
        } else {
            // Try the primary method first, if it fails, try alternative
            $result = processPayment($conn, $cid, $overdue_records, $_FILES['payment_screenshot']);

            // If primary method fails, try alternative method
            if (!$result['success'] && strpos($result['message'], 'BLOB') !== false) {
                error_log("Primary method failed, trying alternative method");
                $result = processPaymentAlternative($conn, $cid, $overdue_records, $_FILES['payment_screenshot']);
            }

            if ($result['success']) {
                $success_message = $result['message'];
                $payment_submitted = true;
                // Clear overdue_records after successful payment to prevent resubmission
                $overdue_records = [];
                $total_fine = 0;
            } else {
                $upload_error = $result['message'];
            }
        }
    } else {
        // Handle file upload errors
        $file_error_messages = [
            1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            3 => 'The uploaded file was only partially uploaded',
            4 => 'No file was uploaded',
            6 => 'Missing a temporary folder',
            7 => 'Failed to write file to disk',
            8 => 'A PHP extension stopped the file upload'
        ];

        $error_code = isset($_FILES['payment_screenshot']['error']) ? $_FILES['payment_screenshot']['error'] : 4;
        if (isset($file_error_messages[$error_code])) {
            $upload_error = $file_error_messages[$error_code];
        } else {
            $upload_error = "Please select a payment screenshot to upload.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Library Management</title>
   <style>
        /* Color Palette */
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
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, var(--ultra-light-green) 0%, var(--light-green) 100%);
            min-height: 100vh;
            color: var(--text-dark);
            padding-top: 100px;
        }

        .payment-container {
            max-width: 1000px;
            margin: 10px auto;
            background: var(--white);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(46, 106, 51, 0.1);
            border: 1px solid var(--light-green);
            margin-top: 150px;
        }

        h1 {
            color: var(--main-green);
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.2rem;
            font-weight: 600;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--main-green);
            text-decoration: none;
            font-weight: 600;
            padding: 8px 16px;
            border: 2px solid var(--main-green);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background-color: var(--main-green);
            color: var(--white);
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .payment-summary {
            background: linear-gradient(135deg, var(--ultra-light-green), var(--light-green));
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 1px solid var(--light-green);
        }

        .summary-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .cid-display {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--main-green);
        }

        .total-fine {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--error-red);
        }

        .overdue-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: var(--white);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .overdue-table th,
        .overdue-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--light-green);
        }

        .overdue-table th {
            background: var(--main-green);
            color: var(--white);
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
        }

        .overdue-table tr:nth-child(even) {
            background-color: var(--ultra-light-green);
        }

        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .payment-method {
            background: var(--white);
            padding: 25px;
            border-radius: 12px;
            border: 2px solid var(--light-green);
            text-align: center;
        }

        .payment-method h3 {
            color: var(--main-green);
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .qr-code {
            width: 200px;
            height: 200px;
            background: var(--ultra-light-green);
            border: 2px dashed var(--light-green);
            margin: 15px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: var(--text-medium);
            border-radius: 8px;
        }

        .account-details {
            background: var(--ultra-light-green);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .account-details strong {
            color: var(--main-green);
        }

        .upload-form {
            background: linear-gradient(135deg, var(--ultra-light-green), var(--light-green));
            padding: 25px;
            border-radius: 12px;
            border: 1px solid var(--light-green);
        }

        .upload-form h3 {
            color: var(--main-green);
            margin-bottom: 20px;
            text-align: center;
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

        .file-input {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--light-green);
            border-radius: 8px;
            background: var(--white);
            font-size: 14px;
        }

        .file-input:focus {
            outline: none;
            border-color: var(--main-green);
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--main-green), var(--accent-green));
            color: var(--white);
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .submit-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, var(--dark-green), var(--main-green));
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(46, 106, 51, 0.3);
        }

        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .file-requirements {
            font-size: 12px;
            color: var(--text-medium);
            margin-top: 8px;
            font-style: italic;
        }

        .no-records {
            text-align: center;
            padding: 40px;
            color: var(--text-medium);
            font-size: 1.1rem;
        }

        .fine-rate-info {
            background: var(--warning-yellow);
            color: #856404;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: var(--white);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 500px;
            width: 90%;
            position: relative;
            animation: slideUp 0.3s ease;
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: var(--main-green);
            font-size: 1.5rem;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .modal-body {
            margin-bottom: 25px;
            color: var(--text-dark);
            line-height: 1.6;
        }

        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .modal-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .modal-btn-confirm {
            background: linear-gradient(135deg, var(--success-green), #27ae60);
            color: white;
        }

        .modal-btn-confirm:hover {
            background: linear-gradient(135deg, #27ae60, #219a52);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 204, 113, 0.3);
        }

        .modal-btn-cancel {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            color: white;
        }

        .modal-btn-cancel:hover {
            background: linear-gradient(135deg, #7f8c8d, #6c7b7d);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(149, 165, 166, 0.3);
        }

        .modal-btn-ok {
            background: linear-gradient(135deg, var(--main-green), var(--accent-green));
            color: white;
            min-width: 120px;
        }

        .modal-btn-ok:hover {
            background: linear-gradient(135deg, var(--dark-green), var(--main-green));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 106, 51, 0.3);
        }

        .success-icon {
            font-size: 3rem;
            color: var(--success-green);
            margin-bottom: 15px;
        }

        .confirm-icon {
            font-size: 2.5rem;
            color: var(--warning-yellow);
            margin-bottom: 15px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .payment-container {
                margin: 10px;
                padding: 20px;
            }

            .payment-methods {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .summary-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .overdue-table {
                font-size: 12px;
            }

            .overdue-table th,
            .overdue-table td {
                padding: 8px 4px;
            }

            .modal-content {
                padding: 20px;
                width: 95%;
            }

            .modal-buttons {
                flex-direction: column;
                align-items: center;
            }

            .modal-btn {
                width: 100%;
                max-width: 200px;
            }
        }
    </style>
</head>

<body>
    <div class="payment-container">
        <a href="my_borrowing.php" class="back-link">← Back to My Borrowing</a>

        <h1>Payment Portal</h1>

        <div class="fine-rate-info">
            📚 Library Fine Rate: Nu. 100 per day for overdue books
        </div>

        <?php if (!empty($upload_error)): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($upload_error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cid)): ?>
            <div class="no-records">
                <p>No Customer ID provided. Please go back and search for your records first.</p>
            </div>
        <?php elseif (empty($overdue_records) && !$payment_submitted): ?>
            <div class="no-records">
                <p>No overdue records found for Customer ID: <strong><?php echo htmlspecialchars($cid); ?></strong></p>
                <p>You have no outstanding fines to pay.</p>
            </div>
        <?php elseif (!empty($overdue_records)): ?>
            <!-- Payment Summary -->
            <div class="payment-summary">
                <div class="summary-header">
                    <div class="cid-display">Customer ID: <?php echo htmlspecialchars($cid); ?></div>
                    <div class="total-fine">Total Fine: Nu. <?php echo number_format($total_fine, 2); ?></div>
                </div>

                <h3 style="color: #2c5530; margin-bottom: 15px;">Overdue Records</h3>
                <table class="overdue-table">
                    <thead>
                        <tr>
                            <th>Reservation ID</th>
                            <th>Book Title</th>
                            <th>Author</th>
                            <th>Reservation Date</th>
                            <th>Days Overdue</th>
                            <th>Fine Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($overdue_records as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['reservation_id']); ?></td>
                                <td><?php echo htmlspecialchars($record['title']); ?></td>
                                <td><?php echo htmlspecialchars($record['author'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($record['reservation_date']); ?></td>
                                <td><?php echo $record['days_overdue']; ?> days</td>
                                <td>Nu. <?php echo number_format($record['fine_amount'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Payment Methods -->
            <div class="payment-methods">
                <div class="payment-method">
                    <h3>📱 QR Code Payment</h3>
                    <div class="qr-code">
                        <div>
                            <!-- Display QR code image -->
                            <?php if (file_exists('../images/qr_code.png')): ?>
                                <img src="../images/qr_code.png" alt="QR Code for Payment"
                                    style="max-width: 150px; margin-top: 10px;">
                            <?php else: ?>
                                <div style="padding: 20px; background: #f0f0f0; border-radius: 5px;">
                                    QR Code Image Not Available
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p><strong>Amount:</strong> Nu. <?php echo number_format($total_fine, 2); ?></p>
                    <p><small>Scan the QR code with your mobile banking app</small></p>
                </div>


                <div class="payment-method">
                    <h3>🏦 Bank Transfer</h3>
                    <div class="account-details">
                        <p><strong>Bank Name:</strong> Bank of Bhutan</p>
                        <p><strong>Account Name:</strong> Library Management System</p>
                        <p><strong>Account Number:</strong> 200123456789</p>
                        <p><strong>SWIFT Code:</strong> BOBBBTBT</p>
                        <p><strong>Reference:</strong> <?php echo htmlspecialchars($cid); ?></p>
                    </div>
                    <p><strong>Amount to Transfer:</strong> Nu. <?php echo number_format($total_fine, 2); ?></p>
                </div>
            </div>

            <!-- Upload Payment Screenshot -->
            <div class="upload-form">
                <h3>📤 Upload Payment Screenshot</h3>
                <form method="POST" enctype="multipart/form-data" id="paymentForm">
                    <div class="form-group">
                        <label for="payment_screenshot">Payment Screenshot: *</label>
                        <input type="file" id="payment_screenshot" name="payment_screenshot" class="file-input"
                            accept="image/*" required>
                        <div class="file-requirements">
                            * Please upload JPG, PNG, or GIF files only. Maximum file size: 5MB<br>
                            * Make sure the screenshot clearly shows the payment amount and transaction details
                        </div>
                    </div>

                    <button type="button" id="submitBtn" class="submit-btn">
                        📄 Submit Payment Proof
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="confirm-icon">⚠️</div>
                <h3>Confirm Payment Submission</h3>
            </div>
            <div class="modal-body">
                <p><strong>Are you sure you want to submit this payment proof?</strong></p>
                <p>Please ensure that:</p>
                <ul style="text-align: left; margin: 15px 0;">
                    <li>The screenshot clearly shows the payment amount</li>
                    <li>Transaction details are visible</li>
                    <li>The payment amount matches: <strong>Nu. <?php echo number_format($total_fine, 2); ?></strong>
                    </li>
                </ul>
                <p><em>Once submitted, your payment will be reviewed by our staff.</em></p>
            </div>
            <div class="modal-buttons">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closeConfirmationModal()">
                    ❌ Cancel
                </button>
                <button type="button" class="modal-btn modal-btn-confirm" onclick="confirmPaymentSubmission()">
                    ✅ Confirm Submit
                </button>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="success-icon">✅</div>
                <h3>Payment Successfully Submitted!</h3>
            </div>
            <div class="modal-body">
                <p><strong>Your payment proof has been uploaded successfully!</strong></p>
                <p>Your payment is now under review by our library staff.</p>
                <p>You will be notified once the payment verification is complete.</p>
                <div style="background: var(--ultra-light-green); padding: 15px; border-radius: 8px; margin: 15px 0;">
                    <p><strong>Reference Details:</strong></p>
                    <p>Customer ID: <?php echo htmlspecialchars($cid); ?></p>
                    <p>Amount Paid: Nu. <?php echo number_format($total_fine, 2); ?></p>
                    <p>Submission Time: <?php echo date('Y-m-d H:i:s'); ?></p>
                </div>
            </div>
            <div class="modal-buttons">
                <button type="button" class="modal-btn modal-btn-ok" onclick="closeSuccessModal()">
                    👍 OK
                </button>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let paymentForm = null;
        let fileInput = null;
        let submitBtn = null;

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function () {
            paymentForm = document.getElementById('paymentForm');
            fileInput = document.getElementById('payment_screenshot');
            submitBtn = document.getElementById('submitBtn');

            // Initialize submit button state
            if (fileInput && !fileInput.files.length) {
                submitBtn.disabled = true;
            }

            // File input validation
            if (fileInput) {
                fileInput.addEventListener('change', validateFile);
            }

            // Submit button click handler
            if (submitBtn) {
                submitBtn.addEventListener('click', showConfirmationModal);
            }

            // Check if payment was submitted successfully
            <?php if ($payment_submitted): ?>
                showSuccessModal();
            <?php endif; ?>
        });

        // File validation function
        function validateFile(e) {
            const file = e.target.files[0];

            if (file) {
                const fileSize = file.size / 1024 / 1024; // Convert to MB
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];

                if (fileSize > 5) {
                    alert('File size must be less than 5MB');
                    e.target.value = '';
                    submitBtn.disabled = true;
                    return;
                }

                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, or GIF)');
                    e.target.value = '';
                    submitBtn.disabled = true;
                    return;
                }

                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        // Show confirmation modal
        function showConfirmationModal() {
            if (!fileInput.files.length) {
                alert('Please select a payment screenshot first.');
                return;
            }

            const modal = document.getElementById('confirmationModal');
            modal.classList.add('show');

            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        }

        // Close confirmation modal
        function closeConfirmationModal() {
            const modal = document.getElementById('confirmationModal');
            modal.classList.remove('show');

            // Restore body scroll
            document.body.style.overflow = 'auto';
        }

        // Confirm payment submission
        function confirmPaymentSubmission() {
            closeConfirmationModal();

            // Show loading state
            submitBtn.innerHTML = '⏳ Uploading...';
            submitBtn.disabled = true;

            // Create a hidden submit button and click it to submit the form
            const hiddenSubmit = document.createElement('input');
            hiddenSubmit.type = 'submit';
            hiddenSubmit.name = 'submit_payment';
            hiddenSubmit.style.display = 'none';
            paymentForm.appendChild(hiddenSubmit);

            // Submit the form
            hiddenSubmit.click();
        }

        // Show success modal
        function showSuccessModal() {
            const modal = document.getElementById('successModal');
            modal.classList.add('show');

            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        }

        // Close success modal
        function closeSuccessModal() {
            const modal = document.getElementById('successModal');
            modal.classList.remove('show');

            // Restore body scroll
            document.body.style.overflow = 'auto';

            // Optionally redirect to borrowing page
            window.location.href = 'my_borrowing.php';
        }

        // Close modal when clicking outside
        window.addEventListener('click', function (event) {
            const confirmModal = document.getElementById('confirmationModal');
            const successModal = document.getElementById('successModal');

            if (event.target === confirmModal) {
                closeConfirmationModal();
            }

            if (event.target === successModal) {
                closeSuccessModal();
            }
        });

        // Handle escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeConfirmationModal();
                closeSuccessModal();
            }
        });
    </script>
</body>

</html>

<?php
// Close connection at the end
if (isset($conn)) {
    $conn->close();
}
?>
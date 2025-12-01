<?php
// Include DB connection
include('../includes/db.php');

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: fine.php');
    exit;
}

// Validate reservation ID
if (empty($_POST['reservation_id'])) {
    die('Reservation ID missing. <a href="fine.php">Return to fines page</a>');
}

// Get the reservation ID without modifying its format
$resId = trim($_POST['reservation_id']);

// Validate there's actually content in the ID
if (empty($resId)) {
    die('Reservation ID is empty. <a href="fine.php">Return to fines page</a>');
}

// Validate uploaded file
if (!isset($_FILES['payment_screenshot']) || $_FILES['payment_screenshot']['error'] !== UPLOAD_ERR_OK) {
    echo '<h3>Error uploading file. Please try again.</h3>';
    echo '<p><a href="fine.php">Return to fines page</a></p>';
    exit;
}

$file = $_FILES['payment_screenshot'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif'];

if (!in_array($ext, $allowed)) {
    die('Invalid file type. Only JPG, JPEG, PNG, and GIF allowed. <a href="fine.php">Return to fines page</a>');
}

if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
    die('File too large. Maximum size is 5MB. <a href="fine.php">Return to fines page</a>');
}

// Upload directory - use absolute path
$uploadDir = dirname(__FILE__) . '/uploads/payments/';

// Make sure upload directory exists
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        die('Failed to create upload directory. <a href="fine.php">Return to fines page</a>');
    }
}

// Make sure the directory is writable
if (!is_writable($uploadDir)) {
    die('Upload directory is not writable. Please check permissions. <a href="fine.php">Return to fines page</a>');
}

// Generate a secure filename
$safeResId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $resId);
$timestamp = time();
$newName = "payment_{$safeResId}_{$timestamp}.{$ext}";
$dest = $uploadDir . $newName;

// Attempt file move with error checking
if (!move_uploaded_file($file['tmp_name'], $dest)) {
    $error = error_get_last();
    die('Failed to move uploaded file: ' . ($error ? $error['message'] : 'Unknown error') . ' <a href="fine.php">Return to fines page</a>');
}

// Begin DB transaction
$conn->begin_transaction();

try {
    // Get reservation info - using the original reservation ID format
    $stmt = $conn->prepare("SELECT name, cid, title FROM reservations WHERE reservation_id = ?");
    $stmt->bind_param('s', $resId);
    $stmt->execute();
    $res = $stmt->get_result();
    
    // Check if record is found
    if ($res->num_rows == 0) {
        throw new Exception("No records found for reservation_id: " . htmlspecialchars($resId));
    }

    // Fetch reservation details
    $row = $res->fetch_assoc();
    $name = $row['name'];
    $cid = $row['cid'];
    $title = $row['title'];
    $stmt->close();

    // Get due date
    $stmt = $conn->prepare("SELECT return_due_date FROM taken_books WHERE reservation_id = ?");
    $stmt->bind_param('s', $resId);
    $stmt->execute();
    $res = $stmt->get_result();
    
    // Check if due date is found
    if ($res->num_rows == 0) {
        throw new Exception("No due date found for reservation_id: " . htmlspecialchars($resId));
    }

    $due = $res->fetch_assoc();
    $stmt->close();
    
    // Calculate overdue days & fine
    $dueDate = new DateTime($due['return_due_date']);
    $today = new DateTime();
    $days = 0;
    $fine = 0;
    if ($today > $dueDate) {
        $days = $dueDate->diff($today)->days;
        $fine = $days * 100;
    }
    
    // Insert payment record
    $stmt = $conn->prepare("
        INSERT INTO payments (
            reservation_id, name, cid, title,
            days_overdue, amount, payment_date, payment_screenshot
        ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
    ");
    $stmt->bind_param('ssssids', $resId, $name, $cid, $title, $days, $fine, $newName);
    $stmt->execute();
    $stmt->close();
    
    // Update reservation status
    $stmt = $conn->prepare("UPDATE reservations SET status = 'due paid' WHERE reservation_id = ?");
    $stmt->bind_param('s', $resId);
    $stmt->execute();
    $stmt->close();
    
    // Commit changes
    $conn->commit();
    
    // Redirect to success page
    header('Location: fine.php?success=1');
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Delete uploaded file if there was an error in the database operations
    if (file_exists($dest)) {
        unlink($dest);
    }
    
    // Show error and provide a way back
    echo '<h3>Error: ' . $e->getMessage() . '</h3>';
    echo '<p><a href="fine.php">Return to fines page</a></p>';
    exit;
}
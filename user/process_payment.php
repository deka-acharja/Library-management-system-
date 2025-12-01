<?php
// direct_payment.php - standalone solution
// Turn off error reporting
ini_set('display_errors', 0);
error_reporting(0);

include('../includes/db.php');

// Process the payment
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $resId = trim($_GET['id']);
    
    // Skip file upload for now
    $newName = 'manual_payment_' . time() . '.jpg';
    
    // Start transaction
    $conn->begin_transaction();
    try {
        // Fetch reservation details
        $stmt = $conn->prepare("
            SELECT name, cid, title
            FROM reservations
            WHERE reservation_id = ?
        ");
        $stmt->bind_param('s', $resId);
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$row = $res->fetch_assoc()) {
            throw new Exception('Reservation not found.');
        }
        $name = $row['name'];
        $cid = $row['cid'];
        $title = $row['title'];
        $stmt->close();
        
        // Fetch due date
        $stmt = $conn->prepare("
            SELECT return_due_date
            FROM taken_books
            WHERE reservation_id = ?
        ");
        $stmt->bind_param('s', $resId);
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$due = $res->fetch_assoc()) {
            throw new Exception('No due date recorded.');
        }
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
        $stmt->bind_param(
            'ssssids',
            $resId,
            $name,
            $cid,
            $title,
            $days,
            $fine,
            $newName
        );
        $stmt->execute();
        $stmt->close();
        
        // Update reservation status
        $stmt = $conn->prepare("
            UPDATE reservations
            SET status = 'due paid'
            WHERE reservation_id = ?
        ");
        $stmt->bind_param('s', $resId);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        echo "Payment processed successfully for reservation ID: " . htmlspecialchars($resId);
        echo "<br><a href='fine.php?success=1'>Return to Fines Page</a>";
    } catch (Exception $e) {
        $conn->rollback();
        echo 'Error: ' . $e->getMessage();
        echo "<br><a href='fine.php'>Return to Fines Page</a>";
    }
} else {
    echo "No reservation ID provided.";
    echo "<br><a href='fine.php'>Return to Fines Page</a>";
}
$conn->close();
?>
<?php
// Include database connection
include('../includes/db.php');
session_start();

// Check if the form was submitted
if (isset($_POST['book_id']) && isset($_POST['quantity'])) {
    $book_id = intval($_POST['book_id']);
    $quantity = intval($_POST['quantity']);
    
    // Validate inputs
    if ($book_id <= 0) {
        $_SESSION['message'] = "Invalid book ID.";
        $_SESSION['message_type'] = "error";
        header("Location: books.php");
        exit;
    }
    
    if ($quantity < 0) {
        $_SESSION['message'] = "Quantity cannot be negative.";
        $_SESSION['message_type'] = "error";
        header("Location: books.php");
        exit;
    }
    
    // Check if an inventory record already exists for this book
    $check_query = "SELECT book_id FROM inventory WHERE book_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $book_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing record
        $update_query = "UPDATE inventory SET quantity = ? WHERE book_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ii", $quantity, $book_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['message'] = "Book quantity updated successfully.";
        } else {
            $_SESSION['message'] = "Error updating book quantity: " . $conn->error;
            $_SESSION['message_type'] = "error";
        }
        $update_stmt->close();
    } else {
        // Insert new inventory record
        $insert_query = "INSERT INTO inventory (book_id, quantity) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("ii", $book_id, $quantity);
        
        if ($insert_stmt->execute()) {
            $_SESSION['message'] = "Book quantity set successfully.";
        } else {
            $_SESSION['message'] = "Error setting book quantity: " . $conn->error;
            $_SESSION['message_type'] = "error";
        }
        $insert_stmt->close();
    }
    
    $check_stmt->close();
    
    // Also update the availability status in the books table for backward compatibility
    $availability = $quantity > 0 ? "Available" : "Not Available";
    $update_book_query = "UPDATE books SET availability = ? WHERE id = ?";
    $update_book_stmt = $conn->prepare($update_book_query);
    $update_book_stmt->bind_param("si", $availability, $book_id);
    $update_book_stmt->execute();
    $update_book_stmt->close();
    
} else {
    $_SESSION['message'] = "Missing required parameters.";
    $_SESSION['message_type'] = "error";
}

// Redirect back to the books page
header("Location: books.php");
exit;
?>
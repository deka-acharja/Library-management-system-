<?php
// Include database connection
include('../includes/db.php');

// Check if book_id is provided
if (isset($_POST['book_id'])) {
    $book_id = intval($_POST['book_id']);
    
    // Query to get the book's availability
    $query = "SELECT IFNULL(i.quantity, 0) AS quantity FROM inventory i WHERE i.book_id = ?";
    
    // Prepare and execute the statement
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $quantity = $row['quantity'];
    } else {
        $quantity = 0; // Default to 0 if no record found
    }
    
    // Return the quantity as JSON
    echo json_encode(['quantity' => $quantity]);
    
    $stmt->close();
} else {
    echo json_encode(['error' => 'No book ID provided']);
}

$conn->close();
?>

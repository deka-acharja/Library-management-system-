<?php
// Include database connection file
include('../includes/db.php');

// Set content type to JSON
header('Content-Type: application/json');

// Check if username is provided
if (isset($_POST['check_username'])) {
    $username = trim($_POST['check_username']);
    
    // Prepare query to check if username exists in employee_data table
    $query = "SELECT COUNT(*) as count FROM employee_data WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    // Return JSON response
    echo json_encode([
        'exists' => $row['count'] > 0,
        'username' => $username
    ]);
} else {
    // Invalid request
    echo json_encode([
        'error' => 'Invalid request'
    ]);
}

$conn->close();
?>
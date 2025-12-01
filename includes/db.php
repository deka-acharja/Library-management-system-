<?php
// Database connection
$conn = new mysqli('localhost', 'DIT03-11206005480', 'PassWord@5480', 'deka_db');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo " ";
}
?>

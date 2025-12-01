<?php
// Include database connection
include('../includes/db.php');

header('Content-Type: application/json'); // Set the response header to JSON

$response = [
    'books' => [],
    'status' => 'success'
];

$search_query = "";
$status_filter = "all"; // Default status filter
$where_clause = "";

// Check if a search request was made
if (isset($_GET['search'])) {
    $search_query = trim($_GET['search']);
    if (!empty($search_query)) {
        $search_query = $conn->real_escape_string($search_query); // Prevent SQL injection
        $where_clause = "WHERE (b.title LIKE '%$search_query%' 
                         OR b.author LIKE '%$search_query%' 
                         OR b.genre LIKE '%$search_query%' 
                         OR b.pages LIKE '%$search_query%')";
    }
}

// Check if status filter is applied
if (isset($_GET['status']) && $_GET['status'] != 'all') {
    $status_filter = $_GET['status'];
    
    if (!empty($where_clause)) {
        // Add to existing where clause
        if ($status_filter == 'available') {
            $where_clause .= " AND IFNULL(i.quantity, 0) > 0";
        } else if ($status_filter == 'not-available') {
            $where_clause .= " AND IFNULL(i.quantity, 0) = 0";
        }
    } else {
        // Create new where clause
        if ($status_filter == 'available') {
            $where_clause = "WHERE IFNULL(i.quantity, 0) > 0";
        } else if ($status_filter == 'not-available') {
            $where_clause = "WHERE IFNULL(i.quantity, 0) = 0";
        }
    }
}

// Fetch books based on search and status filter
$query = "SELECT b.*, IFNULL(i.quantity, 0) as available_quantity 
          FROM books b 
          LEFT JOIN inventory i ON b.id = i.book_id 
          $where_clause 
          ORDER BY b.id DESC LIMIT 12";
$result = $conn->query($query);

// Error handling if query fails
if ($result === false) {
    $response['status'] = 'error';
    $response['message'] = "Error in SQL query: " . $conn->error;
    echo json_encode($response);
    exit;
}

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $book = [
            'id' => $row['id'],
            'title' => $row['title'],
            'author' => $row['author'],
            'genre' => $row['genre'] ?? 'General',
            'pages' => $row['pages'] ? $row['pages'] : 'N/A',
            'available_quantity' => $row['available_quantity']
        ];
        
        // Handle image display
        if (isset($row['image']) && !empty($row['image'])) {
            // For BLOB data, create a data URI
            $image_data = base64_encode($row['image']);
            $book['image_src'] = 'data:image/jpeg;base64,' . $image_data;
        } else {
            // Fallback to default image
            $book['image_src'] = '../uploads/default.jpg';
        }
        
        $response['books'][] = $book;
    }
} else {
    $response['books'] = [];
}

// Return the JSON response
echo json_encode($response);
exit;
?>
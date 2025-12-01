<?php
// Include your database connection (using MySQLi)
include('../includes/db.php'); // Assuming your db.php includes a mysqli connection

// Pagination
$limit = 20; // Books per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Query to fetch books with optional search
if ($search) {
    $sql = "SELECT * FROM books WHERE title LIKE '%$search%' LIMIT $limit OFFSET $offset";
} else {
    $sql = "SELECT * FROM books LIMIT $limit OFFSET $offset"; // Fetch all books if no search term
}

$result = $conn->query($sql);
$books = $result->fetch_all(MYSQLI_ASSOC); // Fetch all books as an associative array

// Get total number of books for pagination
$total_books_result = $conn->query("SELECT COUNT(*) FROM books WHERE title LIKE '%$search%'");
$total_books = $total_books_result->fetch_row()[0];
$total_pages = ceil($total_books / $limit);
?>

<?php include('../includes/dashboard_header.php'); ?>
<!-- Include CSS file -->
<link rel="stylesheet" href="user.css">

<!-- Search bar -->
<div class="search-bar">
    <input type="text" id="search" placeholder="Search for books..." oninput="liveSearch()">
</div>

<div id="books-grid" class="books-grid">
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $title = $row['title'];
            $author = $row['author'];
            $genre = $row['genre'] ?? 'General'; // Set default if genre is null
            $page_count = $row['pages'] ? $row['pages'] : 'N/A';
            $book_id = $row['id'];
            $quantity = $row['available_quantity']; // Fetch available quantity from inventory table

            // Check if the image path is set, otherwise use a default image
            $image_url = isset($row['image']) && !empty($row['image']) ? $row['image'] : '../uploads/default.jpg';

            echo '<div class="book-card">';
            echo '<span class="book-category">' . htmlspecialchars($genre) . '</span>';

            echo '<div class="book-image-container">';
            echo '<img src="' . htmlspecialchars($image_url) . '" alt="' . htmlspecialchars($title) . '">';
            echo '</div>';

            echo '<div class="book-info">';
            echo '<h3 class="book-title">' . htmlspecialchars($title) . '</h3>';

            echo '<div class="book-meta">';
            echo '<span class="meta-label">Author:</span> ' . htmlspecialchars($author);
            echo '</div>';

            echo '<div class="book-meta">';
            echo '<span class="meta-label">Pages:</span> ' . htmlspecialchars($page_count);
            echo '</div>';

            // Check availability based on quantity only
            if ($quantity > 0) {
                echo '<div class="availability-box available" data-book-id="' . $book_id . '">';
                echo 'Available <span class="availability-count">' . $quantity . '</span>';
                echo '</div>';
            } else {
                echo '<div class="availability-box not-available" data-book-id="' . $book_id . '">';
                echo 'Not Available';
                echo '</div>';
            }

            echo '</div>'; // Close book-info
            echo '</div>'; // Close book-card
        }
    } else {
        echo '<div class="empty-state">';
        echo '<p>No books found.</p>';
        echo '</div>';
    }
    ?>
</div> <!-- End of books-grid -->

<!-- Pagination -->
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>&search=<?= $search ?>" class="prev">Previous</a>
    <?php endif; ?>

    <span class="page-number">Page <?= $page ?> of <?= $total_pages ?></span>

    <?php if ($page < $total_pages): ?>
        <a href="?page=<?= $page + 1 ?>&search=<?= $search ?>" class="next">Next</a>
    <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>

<script>
// Live search functionality
function liveSearch() {
    const searchTerm = document.getElementById('search').value;
    window.location.href = `all_books.php?search=${searchTerm}&page=1`;
}
</script>

</body>
</html>

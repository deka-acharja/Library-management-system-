<?php 
// Include the necessary files for database connection and header
include('../includes/db.php');
include('../includes/dashboard_header.php'); 

// Check if book_id is provided in the URL
if (isset($_GET['book_id'])) {
    $book_id = intval($_GET['book_id']); 
    
    // Query to fetch the book details from the database
    $query = "SELECT b.*, IFNULL(i.quantity, 0) as available_quantity
               FROM books b
               LEFT JOIN inventory i ON b.id = i.book_id
               WHERE b.id = $book_id";
    $result = $conn->query($query);
    
    // Check if book exists
    if ($result->num_rows > 0) {
        $book = $result->fetch_assoc();
        $title = $book['title'];
        $author = $book['author'];
        $genre = $book['genre'] ?? 'General';
        $pages = $book['pages'];
        $publication_details = $book['publication_details'];
        $ISBN = $book['ISBN'];
        $edition = $book['edition'];
        $quantity = $book['available_quantity'];
        
        // Handle image display
        if (isset($book['image']) && !empty($book['image'])) {
            // For BLOB data, create a data URI
            $image_data = base64_encode($book['image']);
            $image_src = 'data:image/jpeg;base64,' . $image_data;
        } else {
            // Fallback to default image
            $image_src = '../uploads/default.jpg';
        }
    } else {
        die("Book not found.");
    }
} else {
    die("Book ID is missing.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Book Details</title>
    <link rel="stylesheet" href="user.css">
    <style>
        /* Main Color Palette */
        :root {
            --main-green: #1d4521;           /* Main green color */
            --dark-green: #153418;           /* Darker shade for hover/focus */
            --light-green: #c9dccb;          /* Light green for backgrounds */
            --accent-green: #2e6a33;         /* Accent green for highlights */
            --ultra-light-green: #eef5ef;    /* Ultra light green for subtle backgrounds */
            --text-dark: #263028;            /* Dark text color */
            --text-medium: #45634a;          /* Medium text color */
            --text-light: #6b8f70;           /* Light text color */
            --white: #ffffff;                /* White */
            --error-red: #d64541;            /* Error red */
            --success-green: #2ecc71;        /* Success green */
            --warning-yellow: #f39c12;       /* Warning yellow */
        }

        .book-details-container {
            display: flex;
            max-width: 1200px;
            margin: 40px auto;
            background-color: var(--white);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            overflow: hidden;
            font-family: 'Times New Roman', Times, serif;
            margin-top: 275px;
        }

        .book-details-image {
            flex: 0 0 40%;
            background-color: var(--ultra-light-green);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px;
        }

        .book-details-image img {
            max-width: 100%;
            height: auto;
            max-height: 500px;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .book-details-image img:hover {
            transform: scale(1.03);
        }

        .book-details-info {
            flex: 0 0 60%;
            padding: 40px;
        }

        .book-details-info h2 {
            margin-bottom: 20px;
            color: var(--text-dark);
            font-size: 28px;
            border-bottom: 2px solid var(--light-green);
            padding-bottom: 15px;
        }

        .book-info-row {
            display: flex;
            margin-bottom: 15px;
        }

        .book-info-label {
            flex: 0 0 80px;
            font-weight: bold;
            color: var(--text-medium);
        }

        .book-info-value {
            flex: 1;
            color: var(--text-dark);
        }

        .availability-box {
            margin-top: 30px;
            padding: 20px;
            background-color: var(--ultra-light-green);
            border-radius: 8px;
            border-left: 4px solid var(--main-green);
        }

        .availability-box p {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .button-container {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        .btn {
            padding: 12px 24px;
            background-color: var(--main-green);
            color: var(--white);
            text-decoration: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn:hover {
            background-color: var(--dark-green);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn.cancel {
            background-color: var(--text-medium);
        }

        .btn.cancel:hover {
            background-color: #3a544c;
        }

        .not-available {
            color: var(--error-red);
            font-weight: bold;
        }

        .available {
            color: var(--success-green);
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .book-details-container {
                flex-direction: column;
                margin: 20px;
            }

            .book-details-image,
            .book-details-info {
                flex: 0 0 100%;
            }
        }
    </style>
</head>

<body>
    <div class="book-details-container">
        <!-- Book Image Section (left side) -->
        <div class="book-details-image">
            <img src="<?php echo htmlspecialchars($image_src); ?>" alt="<?php echo htmlspecialchars($title); ?>">
        </div>

        <!-- Book Information Section (right side) -->
        <div class="book-details-info">
            <h2><?php echo htmlspecialchars($title); ?></h2>
            
            <div class="book-info-row">
                <div class="book-info-label">Author:</div>
                <div class="book-info-value"><?php echo htmlspecialchars($author); ?></div>
            </div>
            
            <div class="book-info-row">
                <div class="book-info-label">Genre:</div>
                <div class="book-info-value"><?php echo htmlspecialchars($genre); ?></div>
            </div>
            
            <div class="book-info-row">
                <div class="book-info-label">Pages:</div>
                <div class="book-info-value"><?php echo htmlspecialchars($pages); ?></div>
            </div>
            <div class="book-info-row">
                <div class="book-info-label">Publication:</div>
                <div class="book-info-value"><?php echo htmlspecialchars($publication_details); ?></div>
            </div>

            <div class="book-info-row">
                <div class="book-info-label">ISBN:</div>
                <div class="book-info-value"><?php echo htmlspecialchars($ISBN); ?></div>
            </div>
            <div class="book-info-row">
                <div class="book-info-label">Edition:</div>
                <div class="book-info-value"><?php echo htmlspecialchars($edition); ?></div>
            </div>

            <div class="availability-box">
                <?php if ($quantity > 0) { ?>
                    <p>Status: <span class="available">Available</span> (<?php echo $quantity; ?> copies)</p>
                    <div class="button-container">
                        <a href="reservation.php?book_id=<?php echo $book_id; ?>" class="btn">Reserve Now</a>
                        <a href="dashboard.php" class="btn cancel">Back</a>
                    </div>
                <?php } else { ?>
                    <p>Status: <span class="not-available">Not Available</span></p>
                    <div class="button-container">
                        <a href="dashboard.php" class="btn cancel">Back to Books</a>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>
</body>

</html>
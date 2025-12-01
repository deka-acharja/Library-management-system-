<?php
ob_start(); // Start output buffering
session_start();
include('../includes/db.php');
include('../includes/dashboard_header.php');

if (!isset($_GET['reservation_id'])) {
    echo "No reservation ID provided.";
    exit;
}

$reservation_id = $_GET['reservation_id'];

// Prepared statement to fetch reservation data
$stmt = $conn->prepare("SELECT r.reservation_id, r.book_id, r.cid, r.reservation_date,
                               b.title AS book_title, r.name AS user_name, r.phone AS user_phone
                        FROM reservations r
                        JOIN books b ON r.book_id = b.id
                        JOIN users u ON r.cid = u.username
                        WHERE r.reservation_id = ?");
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    echo "Reservation not found.";
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cancel'])) {
        header('Location: confirmed_reservations.php');
        exit;
    }

    $taken_date = $_POST['taken_date'];
    $return_date = $_POST['return_date'];

    if (strtotime($taken_date) > strtotime($return_date)) {
        $error = "Return date must be after taken date.";
    } else {
        $stmt1 = $conn->prepare("UPDATE reservations SET status = 'taken', is_viewed = 0 WHERE reservation_id = ?");
        $stmt1->bind_param("s", $reservation_id);

        if ($stmt1->execute()) {
            $stmt2 = $conn->prepare("INSERT INTO taken_books (reservation_id, book_id, cid, taken_date, return_due_date)
                                     VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param("sisss", $reservation_id, $data['book_id'], $data['cid'], $taken_date, $return_date);

            if ($stmt2->execute()) {
                $_SESSION['message'] = "Book is marked as taken.";
                $_SESSION['message_type'] = "success";
                header('Location: confirmed_reservations.php');
                exit;
            } else {
                $error = "Failed to insert into taken_books: " . $stmt2->error;
            }
        } else {
            $error = "Failed to update reservation: " . $stmt1->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Mark Book as Taken</title>
    <link rel="stylesheet" href="employee.css">
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

        body {
            background-color: var(--ultra-light-green);
            font-family: Arial, sans-serif;
            color: var(--text-dark);
        }

        .form-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background: var(--white);
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: var(--main-green);
            margin-bottom: 20px;
            text-align: center;
        }

        label {
            font-weight: bold;
            color: var(--text-medium);
            margin-top: 10px;
        }

        input[type="text"],
        input[type="date"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid var(--light-green);
            border-radius: 4px;
            background-color: var(--ultra-light-green);
        }

        button {
            padding: 10px 16px;
            background: var(--main-green);
            border: none;
            color: var(--white);
            border-radius: 4px;
            margin-top: 15px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: var(--accent-green);
        }

        button[name="cancel"] {
            background: var(--error-red);
            margin-left: 10px;
        }

        button[name="cancel"]:hover {
            background: #a8322e;
        }

        .error-msg {
            color: var(--error-red);
            margin-bottom: 10px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <h2>Mark Book as Taken</h2>

        <?php if (isset($error)) echo "<p class='error-msg'>" . htmlspecialchars($error) . "</p>"; ?>

        <form method="POST">
            <label>Reservation ID:</label>
            <input type="text" value="<?php echo htmlspecialchars($data['reservation_id']); ?>" readonly>

            <label>Book Title:</label>
            <input type="text" value="<?php echo htmlspecialchars($data['book_title']); ?>" readonly>

            <label>User Name:</label>
            <input type="text" value="<?php echo htmlspecialchars($data['user_name']); ?>" readonly>

            <label>Phone Number:</label>
            <input type="text" value="<?php echo htmlspecialchars($data['user_phone']); ?>" readonly>

            <label>Taken Date:</label>
            <input type="date" name="taken_date" required>

            <label>Return Due Date:</label>
            <input type="date" name="return_date" required>

            <button type="submit" name="confirm">Confirm Taken</button>
            <button type="submit" name="cancel">Cancel</button>
        </form>
    </div>

    <?php include('../includes/footer.php'); ?>
</body>

</html>

<?php ob_end_flush(); ?>

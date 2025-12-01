<?php
include('../includes/db.php');

if (isset($_GET['action'], $_GET['id'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);

    // Get the serial_no for inventory updates
    $stmt = $conn->prepare("SELECT book_id FROM reservations WHERE reservation_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($book_id);
    $stmt->fetch();
    $stmt->close();

    if (!$book_id) {
        die("Reservation not found.");
    }

    // Handle each action
    switch ($action) {
        case 'confirm':
            $update = $conn->prepare("UPDATE reservations SET status = 'confirmed' WHERE reservation_id = ?");
            $update->bind_param("i", $id);
            $update->execute();
            break;

        case 'reject':
            $conn->begin_transaction();
            $update = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE reservation_id = ?");
            $update->bind_param("i", $id);
            $update->execute();

            $inv = $conn->prepare("UPDATE inventory SET quantity = quantity + 1 WHERE book_id = ?");
            $inv->bind_param("s", $book_id);
            $inv->execute();
            $conn->commit();
            break;

        case 'terminate':
            $conn->begin_transaction();
            $update = $conn->prepare("UPDATE reservations SET status = 'terminated' WHERE reservation_id = ?");
            $update->bind_param("i", $id);
            $update->execute();

            $inv = $conn->prepare("UPDATE inventory SET quantity = quantity + 1 WHERE book_id = ?");
            $inv->bind_param("s", $book_id);
            $inv->execute();
            $conn->commit();
            break;

        case 'return':
            $update = $conn->prepare("UPDATE reservations SET status = 'returned' WHERE reservation_id = ?");
            $update->bind_param("i", $id);
            $update->execute();

               $inv = $conn->prepare("UPDATE inventory SET quantity = quantity + 1 WHERE book_id = ?");
            $inv->bind_param("s", $book_id);
            $inv->execute();
            $conn->commit();
            break;

        default:
            echo "Invalid action";
            exit;
    }

    header("Location: view_reservations.php?status=$action");
    exit;
} else {
    echo "Invalid request.";
}
?>

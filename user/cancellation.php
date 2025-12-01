<?php
include('../includes/db.php');
include('../includes/dashboard_header.php');

$message = "";
$message_type = "";

// Check if cancellation has been confirmed (via URL param)
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes' && isset($_GET['reservation_id']) && !empty($_GET['reservation_id'])) {
    $reservation_id = $_GET['reservation_id'];

    // First, check if the reservation exists and is in 'reserved' status
    $check_stmt = $conn->prepare("SELECT * FROM reservations WHERE reservation_id = ? AND status = 'reserved'");
    $check_stmt->bind_param("s", $reservation_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Update the status to 'cancelled' and set is_viewed to 0
        $update_stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled', is_viewed = 0 WHERE reservation_id = ?");
        $update_stmt->bind_param("s", $reservation_id);

        if ($update_stmt->execute()) {
            $message = "Reservation has been successfully cancelled!";
            $message_type = "success";
        } else {
            $message = "Error occurred while cancelling the reservation. Please try again.";
            $message_type = "error";
        }
        $update_stmt->close();
    } else {
        $message = "Reservation not found or cannot be cancelled. Only reservations with 'reserved' status can be cancelled.";
        $message_type = "error";
    }
    $check_stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reservation Cancellation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Modal styles (same as your original) */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 100px;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease-in-out;
        }

        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease-in-out;
        }

        .modal-header {
            padding: 20px 30px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }

        .modal-body {
            padding: 30px;
            text-align: center;
        }

        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #eee;
            text-align: center;
        }

        .success-modal .modal-header {
            background-color: #d4edda;
            color: #155724;
        }

        .error-modal .modal-header {
            background-color: #f8d7da;
            color: #721c24;
        }

        .confirm-modal .modal-header {
            background-color: #ffeeba;
            color: #856404;
        }

        .modal-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .success-icon {
            color: #28a745;
        }

        .error-icon {
            color: #dc3545;
        }

        .confirm-icon {
            color: #ffc107;
        }

        .modal-title {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }

        .modal-message {
            font-size: 18px;
            margin: 20px 0;
            line-height: 1.5;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            margin: 0 10px;
        }

        .btn-yes {
            background-color: #28a745;
            color: white;
        }

        .btn-yes:hover {
            background-color: #1e7e34;
        }

        .btn-no {
            background-color: #dc3545;
            color: white;
        }

        .btn-no:hover {
            background-color: #c82333;
        }

        .btn-ok {
            background-color: #007bff;
            color: white;
        }

        .btn-ok:hover {
            background-color: #0056b3;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        body.modal-active {
            overflow: hidden;
        }
    </style>
</head>

<body>

    <?php if (empty($message)): ?>
        <!-- Confirmation Modal -->
        <div id="confirmModal" class="modal confirm-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">Confirm Cancellation</h2>
                </div>
                <div class="modal-body">
                    <div class="modal-icon">
                        <i class="fas fa-question-circle confirm-icon"></i>
                    </div>
                    <div class="modal-message">Are you sure you want to cancel this reservation?</div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-yes" onclick="confirmCancellation()">Yes</button>
                    <button class="btn btn-no" onclick="cancelCancellation()">No</button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Success/Error Modal -->
        <div id="messageModal" class="modal <?php echo $message_type == 'success' ? 'success-modal' : 'error-modal'; ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title"><?php echo $message_type == 'success' ? 'Success!' : 'Error!'; ?></h2>
                </div>
                <div class="modal-body">
                    <div class="modal-icon">
                        <?php if ($message_type == "success"): ?>
                            <i class="fas fa-check-circle success-icon"></i>
                        <?php else: ?>
                            <i class="fas fa-exclamation-triangle error-icon"></i>
                        <?php endif; ?>
                    </div>
                    <div class="modal-message"><?php echo htmlspecialchars($message); ?></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-ok" onclick="redirectAfterMessage()">OK</button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.body.classList.add('modal-active');
            <?php if (empty($message)): ?>
                document.getElementById('confirmModal').style.display = 'block';
            <?php else: ?>
                document.getElementById('messageModal').style.display = 'block';
            <?php endif; ?>
        });

        function confirmCancellation() {
            const reservationId = "<?php echo htmlspecialchars($_GET['reservation_id']); ?>";
            window.location.href = "?confirm=yes&reservation_id=" + reservationId;
        }

        function cancelCancellation() {
            window.location.href = "my_borrowing.php";
        }

        function redirectAfterMessage() {
            window.location.href = "my_borrowing.php";
        }

        // Handle ESC key to close modals
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                <?php if (empty($message)): ?>
                    cancelCancellation();
                <?php else: ?>
                    redirectAfterMessage();
                <?php endif; ?>
            }
        });
    </script>

    <?php include('../includes/footer.php'); ?>

</body>

</html>
<?php
// Include header, footer, and sidebar
include('../includes/dashboard_header.php');
include('../includes/db.php');

// Initialize message variables
$success_message = '';
$error_message = '';

// Validate ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view_user_details.php?msg=no_id");
    exit();
}

$reservation_id = $_GET['id'];

// Use prepared statement to fetch reservation
$stmt = $conn->prepare("SELECT * FROM reservations WHERE id = ?");
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: view_user_details.php?msg=not_found");
    exit();
}

$reservation = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_reservation'])) {
    $cid = $_POST['cid'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $serial_no = $_POST['serial_no'];
    $status = $_POST['status'];

    $update = $conn->prepare("UPDATE reservations SET cid=?, email=?, address=?, phone=?, title=?, author=?, serial_no=?, status=? WHERE id=?");
    $update->bind_param("ssssssssi", $cid, $email, $address, $phone, $title, $author, $serial_no, $status, $reservation_id);

    if ($update->execute()) {
        $success_message = "Reservation updated successfully!";
        // Refresh reservation
        $stmt = $conn->prepare("SELECT * FROM reservations WHERE id = ?");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reservation = $result->fetch_assoc();
    } else {
        $error_message = "Error updating reservation: " . $conn->error;
    }
}

$status_options = [
    'Reserved' => 'Reserved',
    'Confirmed' => 'Confirmed',
    'Approved' => 'Approved',
    'Due' => 'Due',
    'Due Paid' => 'Due Paid',
    'Returned' => 'Returned',
    'Cancelled' => 'Cancelled',
    'Terminated' => 'Terminated',
    'Rejected' => 'Rejected'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Reservation</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
      <style>
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
            --info-blue: #3498db;
            --font-main: 'Poppins', sans-serif;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: var(--font-main);
            background: linear-gradient(135deg, var(--light-green) 0%, var(--ultra-light-green) 100%);
            margin: 0;
            padding: 0;
            color: var(--text-dark);
            min-height: 100vh;
        }

        /* Dashboard Layout */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* Content */
        .content {
            padding: 20px;
            width: 100%;
            max-width: 1200px;
            margin: 20px auto;
            margin-top: 245px;
        }

        .card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(46, 106, 51, 0.1);
        }

        .card-header {
            padding: 25px 30px;
            background: linear-gradient(135deg, var(--main-green) 0%, var(--accent-green) 100%);
            color: var(--white);
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .card-header h3 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            text-align: center;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .card-body {
            padding: 35px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid var(--light-green);
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
            background-color: var(--ultra-light-green);
            box-sizing: border-box;
            font-family: var(--font-main);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-green);
            box-shadow: 0 0 0 4px rgba(74, 153, 80, 0.15);
            background-color: var(--white);
            transform: translateY(-1px);
        }

        .form-control:hover {
            border-color: var(--main-green);
            background-color: var(--white);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 10px;
        }

        .form-full-width {
            grid-column: 1 / -1;
        }

        .btn {
            padding: 15px 30px;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            text-align: center;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
            font-family: var(--font-main);
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--main-green) 0%, var(--accent-green) 100%);
            color: var(--white);
            box-shadow: 0 4px 15px rgba(46, 106, 51, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46, 106, 51, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--text-light) 0%, var(--text-medium) 100%);
            color: var(--white);
            box-shadow: 0 4px 15px rgba(107, 143, 112, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(107, 143, 112, 0.4);
        }

        .form-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid var(--light-green);
        }

        /* Success/Error Messages */
        .alert {
            padding: 20px;
            margin-bottom: 25px;
            border-radius: var(--border-radius);
            position: relative;
            font-weight: 500;
            border-left: 4px solid;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.1) 0%, rgba(46, 204, 113, 0.05) 100%);
            color: var(--success-green);
            border-left-color: var(--success-green);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(214, 69, 65, 0.1) 0%, rgba(214, 69, 65, 0.05) 100%);
            color: var(--error-red);
            border-left-color: var(--error-red);
        }

        .close-alert {
            position: absolute;
            right: 20px;
            top: 20px;
            cursor: pointer;
            color: inherit;
            font-size: 18px;
            font-weight: bold;
        }

        /* Enhanced Status Select */
        select.form-control {
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23263028' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 18px;
            appearance: none;
            cursor: pointer;
        }

        /* Status Color Indicators */
        .status-reserved { border-left-color: var(--info-blue); }
        .status-confirmed { border-left-color: var(--success-green); }
        .status-approved { border-left-color: var(--accent-green); }
        .status-due { border-left-color: var(--error-red); }
        .status-due-paid { border-left-color: var(--text-light); }
        .status-returned { border-left-color: var(--main-green); }
        .status-cancelled { border-left-color: var(--text-medium); }
        .status-terminated { border-left-color: var(--error-red); }
        .status-rejected { border-left-color: var(--error-red); }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: linear-gradient(135deg, var(--white) 0%, var(--ultra-light-green) 100%);
            margin: 10% auto;
            padding: 0;
            border: none;
            width: 450px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 25px;
            background: linear-gradient(135deg, var(--main-green) 0%, var(--accent-green) 100%);
            color: var(--white);
            text-align: center;
        }

        .modal-header h4 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }

        .modal-body {
            padding: 30px 25px;
            text-align: center;
            font-size: 16px;
            color: var(--text-dark);
        }

        .modal-footer {
            display: flex;
            justify-content: center;
            gap: 15px;
            padding: 25px;
            background-color: var(--ultra-light-green);
        }

        .modal-icon {
            font-size: 60px;
            color: var(--success-green);
            margin-bottom: 20px;
            animation: iconPulse 2s infinite;
        }

        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0 0 25px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            backdrop-filter: blur(10px);
        }

        .breadcrumb li {
            padding: 15px 20px;
            position: relative;
        }

        .breadcrumb li:not(:last-child)::after {
            content: '▶';
            margin-left: 15px;
            color: var(--accent-green);
            font-size: 12px;
        }

        .breadcrumb a {
            color: var(--main-green);
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .breadcrumb li:last-child {
            color: var(--text-medium);
            font-weight: 600;
        }

        /* Readonly field styling */
        .form-control[readonly] {
            background-color: var(--light-green);
            color: var(--text-medium);
            cursor: not-allowed;
            border-style: dashed;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .content {
                margin: 20px;
                padding: 15px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .card-body {
                padding: 25px 20px;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                margin-bottom: 10px;
            }

            .modal-content {
                width: 90%;
                margin: 20% auto;
            }
        }

        @media (max-width: 480px) {
            .card-header {
                padding: 20px 15px;
            }

            .card-header h3 {
                font-size: 20px;
            }

            .breadcrumb li {
                padding: 12px 15px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
<section class="content">
    <div class="container-fluid">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" id="success-alert">
                <span class="close-alert" onclick="hideAlert('success-alert')">&times;</span>
                <i class="fas fa-check-circle" style="margin-right: 10px;"></i>
                <?= $success_message ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" id="error-alert">
                <span class="close-alert" onclick="hideAlert('error-alert')">&times;</span>
                <i class="fas fa-exclamation-circle" style="margin-right: 10px;"></i>
                <?= $error_message ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-edit" style="margin-right: 15px;"></i>Edit Reservation</h3>
            </div>
            <div class="card-body">
                <form method="POST" id="editForm">
                    <input type="hidden" name="update_reservation" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cid">CID</label>
                            <input type="text" name="cid" id="cid" class="form-control" value="<?= htmlspecialchars($reservation['cid']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($reservation['email']) ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($reservation['phone']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" name="address" id="address" class="form-control" value="<?= htmlspecialchars($reservation['address']) ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="title">Book Title</label>
                            <input type="text" name="title" id="title" class="form-control" value="<?= htmlspecialchars($reservation['title']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="author">Author</label>
                            <input type="text" name="author" id="author" class="form-control" value="<?= htmlspecialchars($reservation['author']) ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="serial_no">Serial Number</label>
                            <input type="text" name="serial_no" id="serial_no" class="form-control" value="<?= htmlspecialchars($reservation['serial_no']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control" onchange="updateStatusColor(this)">
                                <?php foreach ($status_options as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= ($reservation['status'] === $value) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group form-full-width">
                        <label>Reservation Date</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($reservation['reservation_date']) ?>" readonly>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="button" class="btn btn-primary" onclick="confirmUpdate()">
                            <i class="fas fa-save"></i> Update Reservation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Modals -->
<div id="updateModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4><i class="fas fa-question-circle"></i> Confirm Update</h4>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to update this reservation record?</p>
            <p><strong>This action cannot be undone.</strong></p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="hideModal()">Cancel</button>
            <button class="btn btn-primary" onclick="submitForm()">Yes, Update</button>
        </div>
    </div>
</div>

<div id="successModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Success!</h4>
        </div>
        <div class="modal-body">
            <p>Reservation has been updated successfully.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="redirectToList()">Back to List</button>
            <button class="btn btn-secondary" onclick="hideSuccessModal()">Continue Editing</button>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

<script>
    function updateStatusColor(selectElement) {
        const status = selectElement.value;
        const statusClass = 'status-' + status.toLowerCase().replace(/\s+/g, '-');
        selectElement.className = selectElement.className.replace(/status-\S+/g, '');
        selectElement.classList.add('form-control', statusClass);
    }

    function confirmUpdate() {
        document.getElementById('updateModal').style.display = 'block';
    }

    function hideModal() {
        document.getElementById('updateModal').style.display = 'none';
    }

    function submitForm() {
        hideModal();
        document.getElementById('editForm').submit();
    }

    function redirectToList() {
        window.location.href = 'view_user_details.php';
    }

    function hideSuccessModal() {
        document.getElementById('successModal').style.display = 'none';
    }
</script>

</body>
</html>

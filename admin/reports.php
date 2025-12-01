<?php
include('../includes/db.php');
include('../includes/dashboard_header.php');

// 1️⃣ Setup date filter
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : date('Y');
$startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
$endDate   = date("Y-m-t", strtotime($startDate));

// Helper for issued/overdue with statuses
function fetchBooks($conn, $startDate, $endDate, $statuses = [], $type = 'taken')
{
  if (empty($statuses)) return false;

  $placeholders = implode(',', array_fill(0, count($statuses), '?'));

  $cols = $type === 'reservation'
    ? "reservation_id AS id, name, email, title, author, serial_no AS serial, reservation_date AS date, status, 'reservation' AS source"
    : "b.borrow_id AS id, u.name, u.email, bo.title, bo.author, bo.serialno AS serial, b.taken_date AS date, b.status, 'borrowed' AS source";

  $tableJoin = $type === 'reservation'
    ? "FROM reservations WHERE status IN ($placeholders) AND reservation_date BETWEEN ? AND ?"
    : "FROM borrow_books b JOIN users u ON b.cid = u.username JOIN books bo ON b.book_id = bo.id WHERE b.status IN ($placeholders) AND b.taken_date BETWEEN ? AND ?";

  $sql = "SELECT $cols $tableJoin ORDER BY date DESC";
  $stmt = $conn->prepare($sql);

  if (!$stmt) {
    die("Prepare failed in fetchBooks: (" . $conn->errno . ") " . $conn->error);
  }

  // Build types string for bind_param: one 's' per status + two 's' for startDate, endDate
  $types = str_repeat('s', count($statuses)) . "ss";

  // We need to bind params as references
  $params = array_merge($statuses, [$startDate, $endDate]);

  // Use call_user_func_array with references for bind_param
  $bind_names = [];
  $bind_names[] = &$types;
  foreach ($params as $key => $value) {
    $bind_names[] = &$params[$key];
  }
  call_user_func_array([$stmt, 'bind_param'], $bind_names);

  $stmt->execute();
  return $stmt->get_result();
}

// Fetch reports
$issuedRes  = fetchBooks($conn, $startDate, $endDate, ['taken', 'borrowed'], 'reservation');
$issuedBor  = fetchBooks($conn, $startDate, $endDate, ['taken', 'borrowed'], 'borrowed');
$overdueRes = fetchBooks($conn, $startDate, $endDate, ['due'], 'reservation');
$overdueBor = fetchBooks($conn, $startDate, $endDate, ['due'], 'borrowed');

// Returned books
$sqlReturned = "
    SELECT b.borrow_id AS id, u.name, u.email, bo.title, bo.author, bo.serialno AS serial,
           b.return_due_date AS date, b.status
    FROM borrow_books b
    JOIN users u ON b.cid = u.username
    JOIN books bo ON b.book_id = bo.id
    WHERE b.status = 'returned' AND b.return_due_date BETWEEN ? AND ?
    ORDER BY date DESC
";
$stmtR = $conn->prepare($sqlReturned);
if (!$stmtR) {
  die("Prepare failed for returned books: (" . $conn->errno . ") " . $conn->error);
}
$stmtR->bind_param('ss', $startDate, $endDate);
$stmtR->execute();
$returnedRes = $stmtR->get_result();

// Fines
$fineRows = [];
$totalCollected = 0;

$sql1 = "SELECT name, cid, title, days_overdue, amount, payment_date FROM payments WHERE payment_date BETWEEN ? AND ?";
$stmt1 = $conn->prepare($sql1);
if (!$stmt1) {
  die("Prepare failed for payments: (" . $conn->errno . ") " . $conn->error);
}
$stmt1->bind_param('ss', $startDate, $endDate);
$stmt1->execute();
$r1 = $stmt1->get_result();
while ($row = $r1->fetch_assoc()) {
  $fineRows[] = $row;
  $totalCollected += (float)$row['amount'];
}

$sql2 = "
  SELECT u.name, u.username AS cid, b.title,
         DATEDIFF(pb.payment_date, bb.return_due_date) AS days_overdue,
         pb.fine_amount AS amount, pb.payment_date
  FROM payment_borrowers pb
    JOIN borrow_books bb ON pb.borrow_id = bb.borrow_id
    JOIN users u ON bb.cid = u.username
    JOIN books b ON bb.book_id = b.id
  WHERE pb.payment_date BETWEEN ? AND ?
  ORDER BY pb.payment_date DESC
";
$stmt2 = $conn->prepare($sql2);
if (!$stmt2) {
  die("Prepare failed for payment_borrowers: (" . $conn->errno . ") " . $conn->error);
}
$stmt2->bind_param('ss', $startDate, $endDate);
$stmt2->execute();
$r2 = $stmt2->get_result();
while ($row = $r2->fetch_assoc()) {
  $fineRows[] = $row;
  $totalCollected += (float)$row['amount'];
}

// Users
$sqlUsers = "SELECT id, name, username, email, role, created_at 
             FROM users 
             WHERE role = 'user' AND created_at BETWEEN ? AND ? 
             ORDER BY created_at DESC";

$stmtU = $conn->prepare($sqlUsers);
if (!$stmtU) {
  die("Prepare failed for users: (" . $conn->errno . ") " . $conn->error);
}
$stmtU->bind_param('ss', $startDate, $endDate);
$stmtU->execute();
$usersRes = $stmtU->get_result();

// Get count of new reservations
$new_reservations_query = "SELECT COUNT(*) as count FROM reservations WHERE status IN ('reserved', 'returned', 'cancelled', 'due paid') AND is_viewed = 0";
$new_reservations_result = $conn->query($new_reservations_query);
$new_reservations_count = 0;
if ($new_reservations_result && $row = $new_reservations_result->fetch_assoc()) {
  $new_reservations_count = $row['count'];
}

// Count unread employee registrations
$new_employees_query = "SELECT COUNT(*) as count FROM users WHERE role = 'employee' AND is_viewed = 0";
$new_employees_result = $conn->query($new_employees_query);
$new_employees_count = 0;
if ($new_employees_result && $row = $new_employees_result->fetch_assoc()) {
  $new_employees_count = $row['count'];
}

// Add both counts together for total notifications
$total_notifications_count = $new_reservations_count + $new_employees_count;
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Monthly Library Reports</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
      --font-main: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      --border-radius: 12px;
      --box-shadow: 0 8px 32px rgba(46, 106, 51, 0.1);
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      --navbar-height: 50px;
      --secondary-navbar-height: 50px;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: var(--font-main);
      background: linear-gradient(135deg, var(--ultra-light-green) 0%, var(--light-green) 50%, var(--ultra-light-green) 100%);
      color: var(--text-dark);
      line-height: 1.6;
      min-height: 100vh;
      overflow-x: hidden;
    }

    /* Fixed Top Navbar */
    .navbar-toggle-container {
      position: fixed;
      top: 220px;
      left: 0;
      right: 0;
      z-index: 1000;
      background: linear-gradient(135deg, var(--main-green) 0%, var(--accent-green) 100%);
      box-shadow: 0 4px 20px rgba(46, 106, 51, 0.3);
      padding: 0 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      height: var(--navbar-height);
      backdrop-filter: blur(15px);
    }

    .navbar-brand {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .toggle-btn {
      background: rgba(255, 255, 255, 0.15);
      color: var(--white);
      border: 2px solid rgba(255, 255, 255, 0.25);
      border-radius: 50%;
      width: 45px;
      height: 45px;
      display: flex;
      justify-content: center;
      align-items: center;
      cursor: pointer;
      transition: var(--transition);
      backdrop-filter: blur(10px);
    }

    .toggle-btn:hover {
      background: rgba(255, 255, 255, 0.25);
      border-color: rgba(255, 255, 255, 0.4);
      transform: scale(1.05);
      box-shadow: 0 4px 15px rgba(255, 255, 255, 0.2);
    }

    .toggle-btn i {
      font-size: 16px;
      transition: transform 0.3s ease;
    }

    .toggle-btn.active i {
      transform: rotate(180deg);
    }

    .dashboard-title {
      font-family: var(--font-main);
      font-size: 24px;
      color: var(--white);
      margin: 0;
      font-weight: 700;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
    }

    /* Notification icon styles */
    .navbar-actions {
      display: flex;
      align-items: center;
    }

    .notification-icon {
      position: relative;
      color: var(--white);
      font-size: 20px;
      margin-left: 20px;
      text-decoration: none;
      padding: 12px;
      border-radius: var(--border-radius);
      transition: var(--transition);
      background: rgba(255, 255, 255, 0.1);
    }

    .notification-icon:hover {
      background: rgba(255, 255, 255, 0.2);
      transform: scale(1.1);
      color: var(--white);
    }

    .badge {
      position: absolute;
      top: 0;
      right: 0;
      background: linear-gradient(135deg, var(--error-red) 0%, #c0392b 100%);
      color: var(--white);
      font-size: 12px;
      font-weight: bold;
      width: 22px;
      height: 22px;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      border: 3px solid var(--white);
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0% {
        box-shadow: 0 0 0 0 rgba(214, 69, 65, 0.7);
      }

      70% {
        box-shadow: 0 0 0 10px rgba(214, 69, 65, 0);
      }

      100% {
        box-shadow: 0 0 0 0 rgba(214, 69, 65, 0);
      }
    }

    /* Secondary Navbar */
    .secondary-navbar {
      position: fixed;
      top: 267px;
      left: 0;
      right: 0;
      background: linear-gradient(135deg, var(--dark-green) 0%, var(--main-green) 100%);
      height: 0;
      overflow: hidden;
      transition: height 0.3s ease;
      z-index: 999;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
      font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
    }

    .secondary-navbar.active {
      height: var(--secondary-navbar-height);
    }

    .navbar-links {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100%;
      gap: 8px;
      padding: 0 20px;
      flex-wrap: wrap;
    }

    .navbar-links a {
      color: var(--white);
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      padding: 10px 15px;
      border-radius: var(--border-radius);
      transition: var(--transition);
      display: flex;
      align-items: center;
      white-space: nowrap;
      background: rgba(255, 255, 255, 0.1);
    }

    .navbar-links a i {
      margin-right: 8px;
      font-size: 14px;
    }

    .navbar-links a:hover {
      background: rgba(255, 255, 255, 0.25);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
      color: var(--white);
    }

    /* Main content wrapper */
    .content-wrapper {
      padding: 90px 20px 40px;
      transition: padding-top 0.3s ease;
      min-height: 100vh;
    }

    .content-wrapper.navbar-active {
      padding-top: 150px;
    }

    /* Updated Container Styling */
  .main-container {
    max-width: 1400px;
    margin: 0 auto;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 20px;
    box-shadow: 
      0 10px 30px rgba(46, 106, 51, 0.15),
      0 0 0 1px rgba(46, 106, 51, 0.1);
    padding: 40px;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(46, 106, 51, 0.2);
    position: relative;
    overflow: hidden;
  }

  .main-container::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 8px;
    background: linear-gradient(90deg, 
      #2e6a33 0%, 
      #4a9950 25%, 
      #6b8f70 50%, 
      #4a9950 75%, 
      #2e6a33 100%);
  }

  /* Updated Table Container */
  .table-container {
    margin-bottom: 50px;
    background: var(--white);
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 
      0 5px 15px rgba(46, 106, 51, 0.1),
      inset 0 0 0 1px rgba(46, 106, 51, 0.05);
    transition: all 0.3s ease;
    border: 1px solid rgba(46, 106, 51, 0.1);
  }

  .table-container:hover {
    box-shadow: 
      0 8px 25px rgba(46, 106, 51, 0.15),
      inset 0 0 0 1px rgba(46, 106, 51, 0.1);
    transform: translateY(-2px);
  }

  /* Updated Table Header */
  .table-header {
    background: linear-gradient(135deg, 
      rgba(46, 106, 51, 0.9) 0%, 
      rgba(74, 153, 80, 0.9) 100%);
    color: var(--white);
    padding: 18px 30px;
    margin: 0;
    font-size: 1.4rem;
    font-weight: 600;
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .table-header::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, 
      transparent 0%, 
      rgba(255, 255, 255, 0.6) 50%, 
      transparent 100%);
  }

  /* Updated Filter Form */
  .filter-form {
    background: rgba(255, 255, 255, 0.9);
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 
      0 5px 15px rgba(46, 106, 51, 0.05),
      inset 0 0 0 1px rgba(46, 106, 51, 0.1);
    border: 1px solid rgba(46, 106, 51, 0.1);
    transition: all 0.3s ease;
  }

  .filter-form:hover {
    box-shadow: 
      0 8px 25px rgba(46, 106, 51, 0.1),
      inset 0 0 0 1px rgba(46, 106, 51, 0.15);
  }

  /* Updated Export Buttons */
  .export-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 40px;
    padding: 25px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 15px;
    box-shadow: 
      0 5px 15px rgba(46, 106, 51, 0.05),
      inset 0 0 0 1px rgba(46, 106, 51, 0.1);
    border: 1px solid rgba(46, 106, 51, 0.1);
  }

  /* Updated Summary Card */
  .summary-card {
    background: linear-gradient(135deg, 
      rgba(46, 106, 51, 0.9) 0%, 
      rgba(74, 153, 80, 0.9) 100%);
    color: var(--white);
    padding: 20px;
    border-radius: 12px;
    margin: 0 20px 20px;
    text-align: center;
    box-shadow: 
      0 5px 15px rgba(46, 106, 51, 0.1),
      inset 0 0 0 1px rgba(255, 255, 255, 0.1);
    position: relative;
    overflow: hidden;
  }

  .summary-card::before {
    content: "";
    position: absolute;
    top: -50%;
    left: -50%;
    right: -50%;
    bottom: -50%;
    background: linear-gradient(
      to bottom right,
      rgba(255, 255, 255, 0) 0%,
      rgba(255, 255, 255, 0.1) 50%,
      rgba(255, 255, 255, 0) 100%
    );
    transform: rotate(30deg);
    animation: shimmer 3s infinite linear;
  }

  @keyframes shimmer {
    0% { transform: translateX(-100%) rotate(30deg); }
    100% { transform: translateX(100%) rotate(30deg); }
  }

  /* Updated Page Header */
  .page-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 30px 0;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 15px;
    box-shadow: 
      0 5px 15px rgba(46, 106, 51, 0.05),
      inset 0 0 0 1px rgba(46, 106, 51, 0.1);
    position: relative;
    overflow: hidden;
    border-left: 6px solid var(--main-green);
  }

  .page-header h2 {
    position: relative;
    z-index: 2;
    color: var(--text-dark);
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
  }

  .page-header::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, 
      #2e6a33 0%, 
      #4a9950 25%, 
      #6b8f70 50%, 
      #4a9950 75%, 
      #2e6a33 100%);
  }
  </style>
</head>

<body>
  <!-- Toast Container for Notifications -->
  <div id="toastContainer" class="toast-container"></div>

  <!-- Fixed Top Navigation Bar -->
  <div class="navbar-toggle-container">
    <div class="navbar-brand">
      <button class="toggle-btn" id="navbarToggle" title="Toggle Menu">
        <i class="fas fa-chevron-down"></i>
      </button>
    </div>

    <div class="navbar-actions">
      <a href="notifiactions.php" class="notification-icon" title="Notifications">
        <i class="fas fa-bell"></i>
        <?php if ($total_notifications_count > 0): ?>
          <span class="badge"><?php echo $total_notifications_count; ?></span>
        <?php endif; ?>
      </a>
    </div>
  </div>

  <!-- Secondary Navigation Bar (Initially Hidden) -->
  <div class="secondary-navbar" id="secondaryNavbar">
    <div class="navbar-links">
      <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
      <a href="add_books.php"><i class="fas fa-book"></i> Add Books</a>
      <a href="all_books.php"><i class="fas fa-book-open"></i> Books</a>
      <a href="confirmed_reservation.php"><i class="fas fa-check-circle"></i> Track Reservation</a>
      <a href="reservations_list.php"><i class="fas fa-calendar-check"></i> Reservation</a>
      <a href="view_borrow_details.php"><i class="fas fa-hand-holding"></i> Borrowed Book</a>
      <a href="payment_details.php"><i class="fas fa-money-check-alt"></i> Payment</a>
      <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
      <a href="../login.php"><i class="fas fa-sign-out-alt"></i> LogOut</a>
    </div>
  </div>

  <div class="content-wrapper" id="contentWrapper">
    <div class="main-container">
      <div class="page-header">
        <h2>📊 Monthly Library Reports — <?= date("F Y", strtotime($startDate)) ?></h2>
      </div>

      <form method="GET" class="filter-form">
        <div class="row g-3">
          <div class="col-12 col-md-4">
            <label class="form-label fw-bold text-dark">Select Month</label>
            <select name="month" class="form-select">
              <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>>
                  <?= date("F", mktime(0, 0, 0, $m, 1)) ?>
                </option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-bold text-dark">Select Year</label>
            <select name="year" class="form-select">
              <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="col-12 col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">
              <i class="fas fa-search me-2"></i>Filter Reports
            </button>
          </div>
        </div>
      </form>

      <div class="export-buttons">
        <a href="export_report.php?type=issued&month=<?= $month ?>&year=<?= $year ?>" class="btn btn-success btn-sm">
          <i class="fas fa-file-export me-2"></i>Export Issued
        </a>
        <a href="export_report.php?type=overdue&month=<?= $month ?>&year=<?= $year ?>" class="btn btn-danger btn-sm">
          <i class="fas fa-file-export me-2"></i>Export Overdue
        </a>
        <a href="export_report.php?type=returned&month=<?= $month ?>&year=<?= $year ?>" class="btn btn-info btn-sm">
          <i class="fas fa-file-export me-2"></i>Export Returned
        </a>
        <a href="export_report.php?type=fines&month=<?= $month ?>&year=<?= $year ?>" class="btn btn-primary btn-sm">
          <i class="fas fa-file-export me-2"></i>Export Fines
        </a>
        <a href="export_report.php?type=users&month=<?= $month ?>&year=<?= $year ?>" class="btn btn-warning btn-sm">
          <i class="fas fa-file-export me-2"></i>Export Users
        </a>
      </div>

      <?php
      function renderTable($title, $columns, $rows, $showSummary = false, $summaryText = '')
      {
        echo "<div class='table-container'>";
        echo "<h3 class='table-header'><i class='fas fa-table me-2'></i>$title</h3>";

        if ($showSummary && $summaryText) {
          echo "<div class='summary-card'><h4>$summaryText</h4></div>";
        }

        echo "<table class='table table-striped'><thead><tr>";
        foreach ($columns as $col) echo "<th>$col</th>";
        echo "</tr></thead><tbody>";

        if (empty($rows)) {
          echo "<tr><td colspan='" . count($columns) . "' class='text-center text-danger p-4'>";
          echo "<i class='fas fa-info-circle me-2'></i>No records found for this month";
          echo "</td></tr>";
        } else {
          foreach ($rows as $tr) {
            echo "<tr>";
            foreach ($tr as $val) {
              echo "<td>" . htmlspecialchars($val) . "</td>";
            }
            echo "</tr>";
          }
        }
        echo "</tbody></table></div>";
      }

      // Build arrays for tables
      $issuedRows = [];
      foreach ([$issuedRes, $issuedBor] as $res) {
        if ($res && $res->num_rows > 0) {
          while ($row = $res->fetch_assoc()) {
            $issuedRows[] = [
              $row['id'],
              $row['name'],
              $row['email'],
              $row['title'],
              $row['author'],
              $row['serial'],
              $row['date'],
              $row['status'],
              $row['source']
            ];
          }
        }
      }

      $overdueRows = [];
      foreach ([$overdueRes, $overdueBor] as $res) {
        if ($res && $res->num_rows > 0) {
          while ($row = $res->fetch_assoc()) {
            $overdueRows[] = [
              $row['id'],
              $row['name'],
              $row['email'],
              $row['title'],
              $row['author'],
              $row['serial'],
              $row['date'],
              $row['status'],
              $row['source']
            ];
          }
        }
      }

      $returnedRows = [];
      if ($returnedRes && $returnedRes->num_rows > 0) {
        while ($row = $returnedRes->fetch_assoc()) {
          $returnedRows[] = [
            $row['id'],
            $row['name'],
            $row['email'],
            $row['title'],
            $row['author'],
            $row['serial'],
            $row['date'],
            $row['status']
          ];
        }
      }

      $usersRows = [];
      if ($usersRes && $usersRes->num_rows > 0) {
        while ($row = $usersRes->fetch_assoc()) {
          $usersRows[] = [
            $row['id'],
            $row['name'],
            $row['username'],
            $row['email'],
            $row['role'],
            $row['created_at']
          ];
        }
      }

      // Render tables
      renderTable(
        "📚 Issued Books (Reservations and Borrowed)",
        ['ID', 'Name', 'Email', 'Title', 'Author', 'Serial', 'Date', 'Status', 'Source'],
        $issuedRows
      );

      renderTable(
        "⚠️ Overdue Books (Reservations and Borrowed)",
        ['ID', 'Name', 'Email', 'Title', 'Author', 'Serial', 'Date', 'Status', 'Source'],
        $overdueRows
      );

      renderTable(
        "✅ Returned Books",
        ['ID', 'Name', 'Email', 'Title', 'Author', 'Serial', 'Return Date', 'Status'],
        $returnedRows
      );

      // Fines table
      $fineDisplayRows = [];
      foreach ($fineRows as $fr) {
        $fineDisplayRows[] = [
          $fr['name'] ?? '',
          $fr['cid'] ?? '',
          $fr['title'] ?? '',
          $fr['days_overdue'] ?? '',
          'Nu. ' . number_format($fr['amount'], 2),
          $fr['payment_date'] ?? ''
        ];
      }

      renderTable(
        "💰 Fines Collected",
        ['Name', 'CID', 'Title', 'Days Overdue', 'Amount', 'Payment Date'],
        $fineDisplayRows,
        true,
        "Total Collected: Nu. " . number_format($totalCollected, 2)
      );

      renderTable(
        "👥 New Users Registered",
        ['ID', 'Name', 'Username', 'Email', 'Role', 'Created At'],
        $usersRows
      );
      ?>
    </div>
  </div>

  <?php include('../includes/footer.php'); ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Navbar toggle functionality
    document.getElementById('navbarToggle').addEventListener('click', function() {
      this.classList.toggle('active');

      const secondaryNavbar = document.getElementById('secondaryNavbar');
      secondaryNavbar.classList.toggle('active');

      const contentWrapper = document.getElementById('contentWrapper');
      contentWrapper.classList.toggle('navbar-active');
    });

    // Smooth scroll for better UX
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
          behavior: 'smooth'
        });
      });
    });

    // Add loading states to export buttons
    document.querySelectorAll('.export-buttons a').forEach(button => {
      button.addEventListener('click', function() {
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Exporting...';
        this.classList.add('disabled');

        setTimeout(() => {
          this.innerHTML = originalText;
          this.classList.remove('disabled');
        }, 2000);
      });
    });

    // Add hover effects to table rows
    document.querySelectorAll('.table tbody tr').forEach(row => {
      row.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.02)';
        this.style.zIndex = '10';
      });

      row.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
        this.style.zIndex = '1';
      });
    });
  </script>
</body>

</html>
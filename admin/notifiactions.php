<?php 
// Start the session
session_start();

// Include database connection file
include('../includes/db.php');
include('../includes/dashboard_header.php');

// Now $conn should be defined from the included db.php file
// Check if connection is established
if (!isset($conn) || $conn === null) {
    die("Database connection failed. Please check the db.php file.");
}

// Mark all notifications as read
$mark_read_query = "UPDATE reservations SET is_viewed = 1 
                   WHERE is_viewed = 0 
                   AND status IN ('due', 'rejected', 'confirmed', 'terminated','reserved', 'cancelled', 'returned', 'due paid')";
mysqli_query($conn, $mark_read_query);

// Function to get all notifications, limited to 50 - FIXED VERSION
function getAllNotifications($conn)
{
    // Use DISTINCT and proper subquery to prevent duplicates
    $query = "SELECT DISTINCT
                r.id, 
                r.book_id, 
                r.status, 
                r.reservation_date,
                r.name, 
                r.cid,
                b.title,
                (SELECT t.return_due_date 
                 FROM taken_books t 
                 WHERE t.book_id = r.book_id 
                 AND t.cid = r.cid 
                 AND t.status = 'active'
                 ORDER BY t.taken_date DESC 
                 LIMIT 1) as return_due_date
              FROM 
                reservations r
              LEFT JOIN 
                books b ON r.book_id = b.id
              WHERE
                r.status IN ('due', 'rejected', 'confirmed', 'terminated', 'reserved', 'cancelled', 'returned', 'due paid')
              GROUP BY 
                r.id, r.book_id, r.status, r.reservation_date, r.name, r.cid, b.title
              ORDER BY 
                r.reservation_date DESC
              LIMIT 50";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        // Log error and return empty array
        error_log("Query Error in getAllNotifications: " . mysqli_error($conn));
        return [];
    }

    $notifications = [];
    $seen_records = []; // Track unique records to prevent PHP-level duplicates
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Create unique identifier for each record
        $unique_key = $row['id'] . '_' . $row['cid'] . '_' . $row['status'] . '_' . $row['reservation_date'];
        
        // Skip if we've already processed this exact record
        if (isset($seen_records[$unique_key])) {
            continue;
        }
        
        $seen_records[$unique_key] = true;
        
        // Handle missing title in a simpler way
        if (empty($row['title'])) {
            $row['title'] = 'Unknown Book';
        }
        $notifications[] = $row;
    }

    // Return up to 50 unique notifications
    return $notifications;
}

// Alternative function with even stricter duplicate prevention
function getAllNotificationsAlternative($conn)
{
    // First get unique reservation records
    $query = "SELECT 
                r.id, 
                r.book_id, 
                r.status, 
                r.reservation_date,
                r.name, 
                r.cid,
                b.title
              FROM 
                reservations r
              LEFT JOIN 
                books b ON r.book_id = b.id
              WHERE
                r.status IN ('due', 'rejected', 'confirmed', 'terminated', 'reserved', 'cancelled', 'returned', 'due paid')
              ORDER BY 
                r.reservation_date DESC
              LIMIT 50";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        error_log("Query Error in getAllNotificationsAlternative: " . mysqli_error($conn));
        return [];
    }

    $notifications = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Handle missing title
        if (empty($row['title'])) {
            $row['title'] = 'Unknown Book';
        }
        
        // Get return due date separately if needed for 'due' status
        if ($row['status'] == 'due') {
            $due_query = "SELECT return_due_date 
                         FROM taken_books 
                         WHERE book_id = ? AND cid = ? 
                         AND status = 'active'
                         ORDER BY taken_date DESC 
                         LIMIT 1";
            
            $stmt = mysqli_prepare($conn, $due_query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "is", $row['book_id'], $row['cid']);
                mysqli_stmt_execute($stmt);
                $due_result = mysqli_stmt_get_result($stmt);
                
                if ($due_row = mysqli_fetch_assoc($due_result)) {
                    $row['return_due_date'] = $due_row['return_due_date'];
                }
                mysqli_stmt_close($stmt);
            }
        }
        
        $notifications[] = $row;
    }

    return $notifications;
}

// Function to format notification messages
function formatNotification($notification)
{
    $name = htmlspecialchars($notification['name'] ?? 'User');
    $cid = htmlspecialchars($notification['cid'] ?? '');
    $title = isset($notification['title']) ? htmlspecialchars($notification['title']) : 'Unknown Book';
    $status = $notification['status'] ?? '';
    $time = isset($notification['reservation_date']) ? date('M d, Y h:i A', strtotime($notification['reservation_date'])) : 'Unknown time';

    $message = "";
    $statusClass = "";
    $icon = "";

    if ($status == 'due') {
        $due_date = isset($notification['return_due_date']) ? date('M d, Y', strtotime($notification['return_due_date'])) : 'unknown date';
        $message = "<strong>$name</strong> ($cid): Book \"<strong>$title</strong>\" is overdue since $due_date. Please pay the fine and return the book.";
        $statusClass = "due";
        $icon = "⏰";
    } else if ($status == 'confirmed') {
        $message = "<strong>$name</strong> ($cid): Reservation for book \"<strong>$title</strong>\" has been confirmed";
        $statusClass = "confirmed";
        $icon = "✓";
    } else if ($status == 'terminated') {
        $message = "<strong>$name</strong> ($cid): Reservation for book \"<strong>$title</strong>\" has been terminated";
        $statusClass = "terminated";
        $icon = "❗";
    } else if ($status == 'rejected') {
        $message = "<strong>$name</strong> ($cid): Reservation for book \"<strong>$title</strong>\" has been rejected";
        $statusClass = "rejected";
        $icon = "✕";
    } else if ($status == 'reserved') {
        $message = "<strong>$name</strong> ($cid): Has reserved the book \"<strong>$title</strong>\"";
        $statusClass = "confirmed";
        $icon = "📚";
    } else if ($status == 'cancelled') {
        $message = "<strong>$name</strong> ($cid): Has cancelled reservation for book \"<strong>$title</strong>\"";
        $statusClass = "rejected";
        $icon = "🚫";
    } else if ($status == 'returned') {
        $message = "<strong>$name</strong> ($cid): Has returned the book \"<strong>$title</strong>\"";
        $statusClass = "confirmed";
        $icon = "📖";
    } else if ($status == 'due paid') {
        $message = "<strong>$name</strong> ($cid): Has paid the fine for overdue book \"<strong>$title</strong>\"";
        $statusClass = "confirmed";
        $icon = "💰";
    }

    return [
        'message' => $message,
        'time' => $time,
        'class' => $statusClass,
        'icon' => $icon
    ];
}

// Get notifications for all users, limited to 50
// Use the alternative function for better duplicate prevention
$notifications = getAllNotificationsAlternative($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <title>Library Notifications</title>
    <style>
        :root {
            --main-green: #2e6a33;        /* Main green color */
            --dark-green: #1d4521;        /* Darker shade for hover/focus */
            --light-green: #c9dccb;       /* Light green for backgrounds */
            --accent-green: #4a9950;      /* Accent green for highlights */
            --ultra-light-green: #eef5ef; /* Ultra light green for subtle backgrounds */
            --text-dark: #263028;         /* Dark text color */
            --text-medium: #45634a;       /* Medium text color */
            --text-light: #6b8f70;        /* Light text color */
            --white: #ffffff;             /* White */
            --error-red: #d64541;         /* Error red */
            --success-green: #2ecc71;     /* Success green */
            --warning-yellow: #f39c12;    /* Warning yellow */
            
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 16px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
            line-height: 1.7;
            color: var(--text-dark);
            background-color: var(--ultra-light-green);
            padding-bottom: 20px;
        }

        .container {
            max-width: 1140px;
            margin: 40px auto;
            padding: 0 15px;
        }

        .card {
            background-color: var(--white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            border-top: 4px solid var(--main-green);
        }

        .card-header {
            padding: 20px 25px;
            background-color: var(--white);
            border-bottom: 1px solid var(--light-green);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .card-body {
            padding: 20px 25px;
        }

        .notification-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--main-green);
            margin: 0;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-size: 0.9rem;
            line-height: 1;
        }

        .btn-primary {
            background-color: var(--main-green);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--dark-green);
            box-shadow: var(--shadow-md);
        }
        
        .action-btn {
            padding: 6px 12px;
            margin-top: 8px;
            font-size: 0.8rem;
        }
        
        .btn-pay {
            background-color: var(--accent-green);
            color: var(--white);
        }
        
        .btn-pay:hover {
            background-color: var(--main-green);
        }

        .notification-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .notification-item {
            display: flex;
            padding: 16px;
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            position: relative;
            background-color: var(--white);
        }

        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .notification-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            margin-right: 15px;
            flex-shrink: 0;
            font-size: 1.2rem;
        }

        .notification-content {
            flex-grow: 1;
        }

        .notification-message {
            margin-bottom: 5px;
            line-height: 1.5;
        }

        .notification-message strong {
            color: var(--main-green);
        }

        .notification-time {
            font-size: 0.85rem;
            color: var(--text-light);
        }

        /* Status specific styles */
        .due {
            background-color: #fff8e1;
            border-left: 4px solid var(--warning-yellow);
        }

        .due .notification-icon {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning-yellow);
        }

        .confirmed {
            background-color: #e8f5e9;
            border-left: 4px solid var(--success-green);
        }

        .confirmed .notification-icon {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-green);
        }

        .terminated {
            background-color: #fbe9e7;
            border-left: 4px solid var(--error-red);
        }

        .terminated .notification-icon {
            background-color: rgba(214, 69, 65, 0.1);
            color: var(--error-red);
        }

        .rejected {
            background-color: #f5f5f5;
            border-left: 4px solid var(--text-light);
        }

        .rejected .notification-icon {
            background-color: rgba(107, 143, 112, 0.1);
            color: var(--text-light);
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            color: var(--text-medium);
            font-weight: 500;
            transition: var(--transition);
            margin-right: 20px;
        }

        .back-btn:hover {
            color: var(--main-green);
        }

        .back-btn i {
            margin-right: 8px;
        }

        .filter-controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 6px 12px;
            font-size: 0.8rem;
            border-radius: var(--radius-sm);
            cursor: pointer;
            border: 1px solid var(--light-green);
            background-color: var(--white);
            color: var(--text-medium);
            transition: var(--transition);
        }

        .filter-btn:hover {
            background-color: var(--ultra-light-green);
        }

        .filter-btn.active {
            background-color: var(--main-green);
            color: var(--white);
            border-color: var(--main-green);
        }
        
        .no-notifications {
            text-align: center;
            padding: 30px;
            color: var(--text-light);
            background-color: var(--ultra-light-green);
            border-radius: var(--radius-md);
        }
        
        .no-notifications i {
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: block;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                margin: 15px auto;
            }

            .notification-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .notification-actions {
                width: 100%;
                display: flex;
                justify-content: flex-end;
            }

            .notification-item {
                flex-direction: column;
            }

            .notification-icon {
                margin-bottom: 10px;
                margin-right: 0;
            }

            .filter-controls {
                justify-content: space-between;
            }

            .filter-btn {
                flex: 1;
                text-align: center;
                font-size: 0.75rem;
                padding: 8px 5px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <a href="dashboard.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <h1 class="page-title">All Notifications</h1>
                </div>
                <button class="btn btn-primary" onclick="location.reload();">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            <div class="card-body">   
                <div class="filter-controls">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <button class="filter-btn" data-filter="due">Due</button>
                    <button class="filter-btn" data-filter="confirmed">Confirmed</button>
                    <button class="filter-btn" data-filter="terminated">Terminated</button>
                    <button class="filter-btn" data-filter="rejected">Rejected</button>
                    <button class="filter-btn" data-filter="reserved">Reserved</button>
                    <button class="filter-btn" data-filter="cancelled">Cancelled</button>
                    <button class="filter-btn" data-filter="returned">Returned</button>
                    <button class="filter-btn" data-filter="due paid">Due Paid</button>
                </div>

                <div class="notification-list">
                    <?php if (count($notifications) > 0): ?>
                        <?php foreach ($notifications as $notification): ?>
                            <?php
                            $formattedNotification = formatNotification($notification);
                            if (!empty($formattedNotification['message'])):
                            ?>
                                <div class="notification-item <?php echo $formattedNotification['class']; ?>" data-status="<?php echo $notification['status']; ?>">
                                    <div class="notification-icon">
                                        <?php echo $formattedNotification['icon']; ?>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-message"><?php echo $formattedNotification['message']; ?></div>
                                        <div class="notification-time">
                                            <i class="far fa-clock"></i> <?php echo $formattedNotification['time']; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-notifications">
                            <i class="far fa-bell-slash"></i>
                            <p>There are no notifications at this time</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const notificationItems = document.querySelectorAll('.notification-item');

            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const filter = this.getAttribute('data-filter');

                    // Toggle active class
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');

                    // Filter notification items
                    notificationItems.forEach(item => {
                        if (filter === 'all') {
                            item.style.display = 'flex';
                        } else {
                            const status = item.getAttribute('data-status');
                            item.style.display = status === filter ? 'flex' : 'none';
                        }
                    });
                });
            });
        });
    </script>
    <?php include('../includes/footer.php'); ?>
</body>

</html>
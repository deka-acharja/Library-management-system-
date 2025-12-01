<?php
session_start();
include('../includes/db.php');
include('../includes/dashboard_header.php');

// Check if user is logged in and has appropriate permissions
// You might want to add your authentication check here

$mark_read_query = "UPDATE reservations SET is_viewed = 1 
                   WHERE is_viewed = 0 
                   AND status IN ('reserved', 'cancelled', 'returned', 'due paid')";
mysqli_query($conn, $mark_read_query);

// Function to get all notifications from database
function getNotifications($conn) {
    // Query to get reservation notifications with user details and book titles
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
              JOIN 
                books b ON r.book_id = b.id
              ORDER BY 
                r.reservation_date DESC
              LIMIT 20"; // Limit to most recent 50 notifications
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }
    
    $notifications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }
    
    return $notifications;
}

// Function to format notification message
function formatNotification($notification) {
    $name = htmlspecialchars($notification['name']);
    $cid = htmlspecialchars($notification['cid']);
    $title = htmlspecialchars($notification['title']);
    $status = $notification['status'];
    $time = date('M d, Y h:i A', strtotime($notification['reservation_date']));
    
    $message = "";
    $statusClass = "";
    $filterClass = "";
    
    if ($status == 'reserved') {
        $message = "User <strong>$name</strong> (CID: $cid) reserved the book \"<strong>$title</strong>\"";
        $statusClass = "reservation";
        $filterClass = "reserved";
    } else if ($status == 'cancelled') {
        $message = "User <strong>$name</strong> (CID: $cid) cancelled reservation for the book \"<strong>$title</strong>\"";
        $statusClass = "cancellation";
        $filterClass = "cancelled";
    } else if ($status == 'returned') {
        $message = "User <strong>$name</strong> (CID: $cid) returned the book \"<strong>$title</strong>\"";
        $statusClass = "return";
        $filterClass = "returned";
    }  else if ($status == 'due paid') {
        $message = "User <strong>$name</strong> (CID: $cid) paid the due for the book \"<strong>$title</strong>\"";
        $statusClass = "due_paid";
        $filterClass = "due-paid";
    }
    
    return [
        'message' => $message,
        'time' => $time,
        'class' => $statusClass,
        'filter' => $filterClass
    ];
}

// Get all notifications
$notifications = getNotifications($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="employee.css">
    <title>Library Notifications</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0px;
            background-color: #f0f2f5;
        }
        .container {
            max-width: 1000px;
            margin: 15px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-top: 255px;
        }
        .header-area {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            margin-right: 20px;
            text-decoration: none;
            color: #555;
            font-weight: 500;
            transition: color 0.2s;
        }
        .back-btn:hover {
            color: #2196F3;
        }
        .back-arrow {
            font-size: 24px;
            margin-right: 5px;
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-top: 0;
            margin-bottom: 15px;
            flex-grow: 1;
        }
        .notification {
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            border-left: 5px solid #ccc;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .notification:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.08);
        }
        .notification-time {
            color: #777;
            font-size: 0.8em;
            margin-top: 5px;
        }
        .reservation {
            background-color: #e3f2fd;
            border-left-color: #2196F3;
        }
        .cancellation {
            background-color: #ffebee;
            border-left-color: #f44336;
        }
        .return {
            background-color: #e8f5e9;
            border-left-color: #4CAF50;
        }
        .due_paid {
            background-color: #e8eaf6;
            border-left-color: #3f51b5;
        }
        .no-notifications {
            text-align: center;
            padding: 40px 0;
            color: #757575;
            font-size: 16px;
        }
        .refresh-btn {
            background-color: #2196F3;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        .refresh-btn:hover {
            background-color: #1976D2;
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .filter-controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .filter-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            background-color: #e0e0e0;
            color: #555;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        .filter-btn:hover {
            background-color: #d0d0d0;
        }
        .filter-btn.active {
            background-color: #2196F3;
            color: white;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-area">
            <a href="dashboard.php" class="back-btn">
                <span class="back-arrow">&#8592;</span> 
            </a>
            <h1>Library Notifications</h1>
        </div>
        
        <div class="header-actions">
            <div class="filter-controls">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="reserved">Reserved</button>
                <button class="filter-btn" data-filter="cancelled">Cancelled</button>
                <button class="filter-btn" data-filter="returned">Returned</button>
                <button class="filter-btn" data-filter="due-paid">Due Paid</button>
            </div>
            <button class="refresh-btn" onclick="location.reload();">Refresh</button>
        </div>
        
        <?php if (count($notifications) > 0): ?>
            <div id="notifications-container">
                <?php foreach ($notifications as $notification): ?>
                    <?php 
                        $formattedNotification = formatNotification($notification);
                        if (!empty($formattedNotification['message'])):
                    ?>
                        <div class="notification <?php echo $formattedNotification['class']; ?> filter-item <?php echo $formattedNotification['filter']; ?>">
                            <div><?php echo $formattedNotification['message']; ?></div>
                            <div class="notification-time"><?php echo $formattedNotification['time']; ?></div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <div id="no-results" class="no-notifications hidden">No notifications match the selected filter</div>
        <?php else: ?>
            <div class="no-notifications">No notifications available</div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const notifications = document.querySelectorAll('.filter-item');
            const noResults = document.getElementById('no-results');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    const filterValue = this.getAttribute('data-filter');
                    
                    let visibleCount = 0;
                    
                    // Show/hide notifications based on filter
                    notifications.forEach(notification => {
                        if (filterValue === 'all' || notification.classList.contains(filterValue)) {
                            notification.classList.remove('hidden');
                            visibleCount++;
                        } else {
                            notification.classList.add('hidden');
                        }
                    });
                    
                    // Show "No results" message if no notifications match the filter
                    if (visibleCount === 0) {
                        noResults.classList.remove('hidden');
                    } else {
                        noResults.classList.add('hidden');
                    }
                });
            });
        });
    </script>
    
    <?php include('../includes/footer.php'); ?>
</body>
</html>
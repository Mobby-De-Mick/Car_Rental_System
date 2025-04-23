<?php
session_start();
include "connect.php";
require_once __DIR__ . '/check_admin.php';

// Set default timezone
date_default_timezone_set('UTC');

// Check if renting table exists
$table_check = mysqli_query($connect, "SHOW TABLES LIKE 'renting'");
if (mysqli_num_rows($table_check) == 0) {
    echo "<script>alert('Error: The renting table does not exist. Please create it in the database.');</script>";
} else {
    // Fetch dashboard statistics safely
    $totalCars = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) AS count FROM cars"))['count'] ?? 0;
    $activeBookingsQuery = mysqli_query($connect, "SELECT COUNT(*) AS count FROM renting WHERE status = 'active'");
    $activeBookings = ($activeBookingsQuery) ? mysqli_fetch_assoc($activeBookingsQuery)['count'] : 0;
    $totalRevenueQuery = mysqli_query($connect, "SELECT SUM(total_price) AS total FROM renting WHERE status = 'completed'");
    $totalRevenue = ($totalRevenueQuery) ? mysqli_fetch_assoc($totalRevenueQuery)['total'] : 0;
    
    // Get today's date for reference
    $today = date('Y-m-d');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Your existing CSS styles remain unchanged */
        :root {
            --primary-color: #7b7fed;
            --secondary-color: #5bc0de;
            --danger-color: #d9534f;
            --success-color: #5cb85c;
            --warning-color: #f0ad4e;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background-color: var(--dark-color);
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100%;
        }
        
        .logo {
            color: white;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            padding: 15px;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        nav ul li a {
            display: block;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        nav ul li a:hover {
            background-color: rgba(255,255,255,0.1);
            color: var(--light-color);
        }
        
        nav ul li a.active {
            background-color: var(--primary-color);
        }
        
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 20px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .logout-btn {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.3s;
        }
        
        .logout-btn:hover {
            background-color: #c82333;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-name {
            font-weight: bold;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            margin-top: 0;
            color: var(--dark-color);
            font-size: 18px;
        }
        
        .stat-card p {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0 0;
            color: var(--primary-color);
        }
        
        .recent-bookings {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .recent-bookings h2 {
            margin-top: 0;
            color: var(--dark-color);
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-view {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-view:hover {
            background-color: #46b8da;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-active {
            background-color: #e1f5e1;
            color: var(--success-color);
        }
        
        .status-completed {
            background-color: #e1e5f5;
            color: var(--primary-color);
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: var(--danger-color);
        }
        
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="logo">Admin Panel</div>
            <nav>
                <ul>
                    <li><a href="admin.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="cars.php"><i class="fas fa-car"></i> Manage Cars</a></li>
                    <li><a href="viewuser.php"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="report0.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                </ul>
            </nav>
        </div>

        <div class="main-content">
            <div class="admin-header">
                <div class="admin-info">
                    <i class="fas fa-user-shield"></i>
                    <span class="admin-name">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                </div>
                <a href="admin_logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>

            <div class="stats-container">
                <div class="stat-card">
                    <h3><i class="fas fa-car"></i> Total Cars</h3>
                    <p><?php echo htmlspecialchars($totalCars); ?></p>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-calendar-check"></i> Active Bookings</h3>
                    <p><?php echo htmlspecialchars($activeBookings); ?></p>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-dollar-sign"></i> Total Revenue</h3>
                    <p>$<?php echo number_format((float)$totalRevenue, 2); ?></p>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="recent-bookings">
                <h2><i class="fas fa-history"></i> Recent Bookings</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Car</th>
                            <th>Start Date</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Fetch recent bookings with proper date handling
                        $bookingsQuery = "SELECT 
                                r.Rid AS id, 
                                CONCAT(u.fname, ' ', u.lname) AS user, 
                                cr.model, 
                                r.Sdate AS start_date,
                                r.Nodays,
                                r.Noweeks, 
                                r.status,
                                r.Rtype,
                                r.payment_method,
                                r.actual_return_date,
                                r.created_at
                        FROM renting r
                        JOIN users u ON r.user_id = u.user_id
                        JOIN cars cr ON r.license_no = cr.license_no
                        ORDER BY r.Rid DESC LIMIT 5";

                        $bookingsResult = mysqli_query($connect, $bookingsQuery);
                        
                        if($bookingsResult && mysqli_num_rows($bookingsResult) > 0) {
                            while ($booking = mysqli_fetch_assoc($bookingsResult)) {
                                // Determine status class
                                $statusClass = '';
                                switch ($booking['status']) {
                                    case 'active':
                                        $statusClass = 'status-active';
                                        break;
                                    case 'completed':
                                        $statusClass = 'status-completed';
                                        break;
                                    case 'cancelled':
                                        $statusClass = 'status-cancelled';
                                        break;
                                    default:
                                        $statusClass = '';
                                }
                                
                                // Enhanced date handling with fallbacks
                                $startDate = 'Not set';
                                if (!empty($booking['start_date']) && $booking['start_date'] != '0000-00-00') {
                                    try {
                                        $dateObj = new DateTime($booking['start_date']);
                                        $startDate = $dateObj->format('M j, Y');
                                    } catch (Exception $e) {
                                        // If invalid date but booking is completed, try to estimate from return date
                                        if ($booking['status'] == 'completed' && !empty($booking['actual_return_date'])) {
                                            try {
                                                $returnDate = new DateTime($booking['actual_return_date']);
                                                $days = $booking['Nodays'] ?? 0;
                                                $weeks = $booking['Noweeks'] ?? 0;
                                                $interval = new DateInterval("P{$weeks}W{$days}D");
                                                $startDateObj = $returnDate->sub($interval);
                                                $startDate = $startDateObj->format('M j, Y') . ' (estimated)';
                                            } catch (Exception $e) {
                                                $startDate = 'Invalid date';
                                                error_log("Date estimation error in booking ID: " . $booking['id'] . " - " . $e->getMessage());
                                            }
                                        } else {
                                            // For active bookings, use creation date as fallback
                                            try {
                                                $createdDate = new DateTime($booking['created_at']);
                                                $startDate = $createdDate->format('M j, Y') . ' (created)';
                                            } catch (Exception $e) {
                                                $startDate = 'Invalid date';
                                                error_log("Date parsing error in booking ID: " . $booking['id'] . " - " . $e->getMessage());
                                            }
                                        }
                                    }
                                } elseif ($booking['status'] == 'active') {
                                    // For active bookings with no date, use today's date
                                    $startDate = date('M j, Y') . ' (today)';
                                }
                                
                                // Calculate duration text
                                $durationText = '';
                                if ($booking['Nodays'] > 0) {
                                    $durationText .= $booking['Nodays'] . ' day(s)';
                                }
                                if ($booking['Noweeks'] > 0) {
                                    if (!empty($durationText)) {
                                        $durationText .= ', ';
                                    }
                                    $durationText .= $booking['Noweeks'] . ' week(s)';
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booking['id']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['user']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['model']); ?></td>
                                    <td><?php echo $startDate; ?></td>
                                    <td><?php echo $durationText; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view.php?id=<?php echo $booking['id']; ?>" class="btn btn-view">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo '<tr><td colspan="7">No bookings found</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // You can add interactive functionality here
            console.log('Admin dashboard loaded');
        });
    </script>
</body>
</html>
<?php
session_start();
include "connect.php";

// Debug mode - uncomment to see detailed error information
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Verify database connection
if (!$connect) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: sign_in.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Debug output - uncomment to see current values
// echo "<pre>User ID: $user_id, Booking ID: $booking_id</pre>";

// If no specific booking ID is provided, get the most recent booking
if (!$booking_id) {
    $query = "SELECT Rid FROM renting WHERE user_id = ? ORDER BY Sdate DESC LIMIT 1";
    $stmt = mysqli_prepare($connect, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $booking = mysqli_fetch_assoc($result);
        header("Location: viewBookings.php?id=".$booking['Rid']);
        exit();
    } else {
        die("<div class='error-message'>No bookings found for your account. <a href='book.php'>Make a booking first</a>.</div>");
    }
}

// Fetch booking details with prepared statement for security
$query = "SELECT 
            r.*, 
            c.model, 
            c.year,
            c.ctype AS car_type,
            c.license_no AS car_license,
            rr.daily_rate
          FROM renting r
          JOIN cars c ON r.license_no = c.license_no
          JOIN rental_rates rr ON r.Ctype = rr.Ctype
          WHERE r.Rid = ? AND r.user_id = ?";

$stmt = mysqli_prepare($connect, $query);
mysqli_stmt_bind_param($stmt, "ii", $booking_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    die("<div class='error-message'>Database error: " . mysqli_error($connect) . "</div>");
}

if (mysqli_num_rows($result) == 0) {
    die("<div class='error-message'>Booking #$booking_id not found or you don't have permission to view it.</div>");
}

$booking = mysqli_fetch_assoc($result);

// Calculate end date and status
$end_date = date('Y-m-d', strtotime($booking['Sdate'] . " + {$booking['Nodays']} days"));

if ($booking['status'] == 'completed') {
    $status = 'completed';
    $status_class = 'status-completed';
} elseif ($booking['status'] == 'cancelled') {
    $status = 'cancelled';
    $status_class = 'status-cancelled';
} else {
    $status = strtotime($end_date) < time() ? 'completed' : 'active';
    $status_class = $status == 'completed' ? 'status-completed' : 'status-active';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking #<?php echo $booking_id; ?> - MOBBY CARS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #7b7fed;
            --primary-hover: #6a6ed8;
            --error-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --text-color: #333;
            --light-text: #555;
            --border-color: #ddd;
            --bg-color: #f5f5f5;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        
        .error-message {
            background: var(--error-color);
            color: white;
            padding: 20px;
            border-radius: 5px;
            max-width: 800px;
            margin: 50px auto;
            text-align: center;
        }
        
        .error-message a {
            color: white;
            text-decoration: underline;
        }
        
        .booking-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .booking-header {
            background: var(--primary-color);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .booking-header h1 {
            margin: 0;
            font-size: 24px;
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .status-active {
            background: var(--success-color);
        }
        
        .status-completed {
            background: var(--primary-color);
        }
        
        .status-cancelled {
            background: var(--error-color);
        }
        
        .booking-content {
            padding: 30px;
        }
        
        .detail-section {
            margin-bottom: 30px;
        }
        
        .detail-section h2 {
            color: var(--primary-color);
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
            margin-top: 0;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .detail-item {
            margin-bottom: 10px;
        }
        
        .detail-label {
            font-weight: bold;
            color: var(--light-text);
            display: block;
            margin-bottom: 5px;
        }
        
        .detail-value {
            padding: 8px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
        }
        
        .btn-secondary {
            background: #555;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #444;
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background: #27ae60;
        }
        
        .balance-due {
            color: var(--error-color);
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .booking-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .detail-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                justify-content: center;
            }
        }
        
        @media print {
            .action-buttons {
                display: none;
            }
            
            body {
                background: white;
                padding: 0;
            }
            
            .booking-container {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <div class="booking-container">
        <div class="booking-header">
            <h1>Booking #<?php echo $booking_id; ?></h1>
            <span class="status-badge <?php echo $status_class; ?>">
                <?php echo ucfirst($status); ?>
            </span>
        </div>
        
        <div class="booking-content">
            <div class="detail-section">
                <h2>Booking Information</h2>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Booking Date</span>
                        <div class="detail-value">
                            <?php echo date('F j, Y', strtotime($booking['Sdate'])); ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Status</span>
                        <div class="detail-value">
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo ucfirst($status); ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if($booking['actual_return_date']): ?>
                    <div class="detail-item">
                        <span class="detail-label">Return Date</span>
                        <div class="detail-value">
                            <?php echo date('F j, Y', strtotime($booking['actual_return_date'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="detail-section">
                <h2>Vehicle Details</h2>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Model</span>
                        <div class="detail-value"><?php echo htmlspecialchars($booking['model']); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Year</span>
                        <div class="detail-value"><?php echo htmlspecialchars($booking['year']); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Type</span>
                        <div class="detail-value"><?php echo htmlspecialchars($booking['car_type']); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">License Plate</span>
                        <div class="detail-value"><?php echo htmlspecialchars($booking['car_license']); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Daily Rate</span>
                        <div class="detail-value">$<?php echo number_format($booking['daily_rate'], 2); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h2>Rental Period</h2>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Start Date</span>
                        <div class="detail-value"><?php echo date('F j, Y', strtotime($booking['Sdate'])); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">End Date</span>
                        <div class="detail-value"><?php echo date('F j, Y', strtotime($end_date)); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Duration</span>
                        <div class="detail-value"><?php echo $booking['Nodays'] . ' day(s)'; ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Rental Type</span>
                        <div class="detail-value"><?php echo htmlspecialchars($booking['Rtype']); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h2>Payment Information</h2>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Total Price</span>
                        <div class="detail-value">$<?php echo number_format($booking['total_price'], 2); ?></div>
                    </div>
                    
                    <?php if($booking['additional_charges'] > 0): ?>
                    <div class="detail-item">
                        <span class="detail-label">Additional Charges</span>
                        <div class="detail-value">$<?php echo number_format($booking['additional_charges'], 2); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-item">
                        <span class="detail-label">Payment Status</span>
                        <div class="detail-value"><?php echo ucfirst($booking['payment_status']); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Amount Paid</span>
                        <div class="detail-value">$<?php echo number_format($booking['amount_paid'], 2); ?></div>
                    </div>
                    
                    <?php if($booking['payment_status'] != 'paid' && ($booking['total_price'] + $booking['additional_charges'] - $booking['amount_paid']) > 0): ?>
                    <div class="detail-item">
                        <span class="detail-label">Balance Due</span>
                        <div class="detail-value balance-due">
                            $<?php echo number_format($booking['total_price'] + $booking['additional_charges'] - $booking['amount_paid'], 2); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="javascript:window.print()" class="btn btn-secondary">
                    <i class="fas fa-print"></i> Print Invoice
                </a>
                
                <a href="view.php" class="btn btn-primary">
                    <i class="fas fa-list"></i> View All Bookings
                </a>
                
                <?php if($status == 'active'): ?>
                <a href="return_car.php?rental_id=<?php echo $booking_id; ?>" class="btn btn-success">
                    <i class="fas fa-car"></i> Return Vehicle
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
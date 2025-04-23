<?php
session_start();
include "connect.php";

// Debugging - uncomment these lines to see errors
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: sign_in.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Debug output - shows current values
// echo "<pre>User ID: $user_id, Booking ID: $booking_id</pre>";

// If no booking ID provided, try to get from cid parameter
if (!$booking_id && isset($_GET['cid'])) {
    $user_id_from_url = (int)$_GET['cid'];
    
    // Verify the user in URL matches logged in user
    if ($user_id_from_url != $user_id) {
        die("Access denied. You can only view your own bookings.");
    }
    
    // Get most recent booking for this user
    $query = "SELECT Rid FROM renting WHERE user_id = ? ORDER BY Sdate DESC LIMIT 1";
    $stmt = mysqli_prepare($connect, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $booking = mysqli_fetch_assoc($result);
        header("Location: view.php?id=".$booking['Rid']);
        exit();
    } else {
        die("No bookings found for your account. <a href='book.php'>Make a booking first</a>.");
    }
}

// Fetch booking details with prepared statement
$query = "SELECT 
            r.*, 
            CONCAT(u.fname, ' ', u.lname) AS user_name,
            u.Mobile AS phone, 
            u.dlno AS driver_license,
            u.insno AS insurance_number,
            c.model, 
            c.year,
            c.ctype AS car_type,
            c.license_no AS car_license
         FROM renting r
         JOIN users u ON r.user_id = u.user_id
         JOIN cars c ON r.license_no = c.license_no
         WHERE r.Rid = ? AND r.user_id = ?";

$stmt = mysqli_prepare($connect, $query);
mysqli_stmt_bind_param($stmt, "ii", $booking_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    die("Database error: " . mysqli_error($connect));
}

if (mysqli_num_rows($result) == 0) {
    die("Booking #$booking_id not found or you don't have permission to view it.");
}

$booking = mysqli_fetch_assoc($result);

// Calculate end date
$end_date = date('Y-m-d', strtotime($booking['Sdate'] . " + {$booking['Nodays']} days"));

// Determine status
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
        /* Your existing CSS styles */
        body {
            background: #7b7fed;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            padding: 20px;
        }
        
        .booking-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .booking-header {
            background: #7b7fed;
            color: white;
            padding: 20px;
        }
        
        .detail-section {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .detail-label {
            font-weight: bold;
            width: 200px;
            color: #555;
        }
        
        .detail-value {
            flex: 1;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        
        .status-active {
            background: #4CAF50;
        }
        
        .status-completed {
            background: #7b7fed;
        }
        
        .status-cancelled {
            background: #f44336;
        }
        
        .action-buttons {
            padding: 20px;
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 15px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-weight: bold;
        }
        
        .btn-print {
            background: #4CAF50;
        }
        
        .btn-edit {
            background: #7b7fed;
        }
        
        .btn-cancel {
            background: #f44336;
        }
        
        @media (max-width: 768px) {
            .detail-row {
                flex-direction: column;
            }
            
            .detail-label {
                width: 100%;
                margin-bottom: 5px;
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
        
        <div class="detail-section">
            <h2>Booking Status</h2>
            <div class="detail-row">
                <div class="detail-label">Status:</div>
                <div class="detail-value">
                    <span class="status-badge <?php echo $status_class; ?>">
                        <?php echo ucfirst($status); ?>
                    </span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Booking Date:</div>
                <div class="detail-value"><?php echo date('F j, Y', strtotime($booking['Sdate'])); ?></div>
            </div>
            <?php if($booking['actual_return_date']): ?>
            <div class="detail-row">
                <div class="detail-label">Return Date:</div>
                <div class="detail-value"><?php echo date('F j, Y', strtotime($booking['actual_return_date'])); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="detail-section">
            <h2>User Information</h2>
            <div class="detail-row">
                <div class="detail-label">Name:</div>
                <div class="detail-value"><?php echo htmlspecialchars($booking['user_name']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Phone:</div>
                <div class="detail-value"><?php echo htmlspecialchars($booking['phone']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Driver License:</div>
                <div class="detail-value"><?php echo htmlspecialchars($booking['driver_license']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Insurance Number:</div>
                <div class="detail-value"><?php echo htmlspecialchars($booking['insurance_number']); ?></div>
            </div>
        </div>

        <div class="detail-section">
            <h2>Vehicle Information</h2>
            <div class="detail-row">
                <div class="detail-label">Model:</div>
                <div class="detail-value"><?php echo htmlspecialchars($booking['model']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Year:</div>
                <div class="detail-value"><?php echo htmlspecialchars($booking['year']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Type:</div>
                <div class="detail-value"><?php echo htmlspecialchars($booking['car_type']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">License Plate:</div>
                <div class="detail-value"><?php echo htmlspecialchars($booking['car_license']); ?></div>
            </div>
        </div>

        <div class="detail-section">
            <h2>Rental Details</h2>
            <div class="detail-row">
                <div class="detail-label">Start Date:</div>
                <div class="detail-value"><?php echo date('F j, Y', strtotime($booking['Sdate'])); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">End Date:</div>
                <div class="detail-value"><?php echo date('F j, Y', strtotime($end_date)); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Duration:</div>
                <div class="detail-value">
                    <?php echo $booking['Nodays'] . ' day(s)'; ?>
                    <?php if($booking['Noweeks'] > 0) echo ', ' . $booking['Noweeks'] . ' week(s)'; ?>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Rental Type:</div>
                <div class="detail-value"><?php echo htmlspecialchars($booking['Rtype']); ?></div>
            </div>
        </div>

        <div class="detail-section">
            <h2>Payment Information</h2>
            <div class="detail-row">
                <div class="detail-label">Total Price:</div>
                <div class="detail-value">$<?php echo number_format($booking['total_price'], 2); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Payment Method:</div>
                <div class="detail-value"><?php echo htmlspecialchars($booking['payment_method'] ?? 'Not specified'); ?></div>
            </div>
            <?php if($booking['additional_charges'] > 0): ?>
            <div class="detail-row">
                <div class="detail-label">Additional Charges:</div>
                <div class="detail-value">$<?php echo number_format($booking['additional_charges'], 2); ?></div>
            </div>
            <?php endif; ?>
            <div class="detail-row">
                <div class="detail-label">Payment Status:</div>
                <div class="detail-value"><?php echo ucfirst($booking['payment_status']); ?></div>
            </div>
        </div>

        <div class="action-buttons">
            <a href="javascript:window.print()" class="btn btn-print">
                <i class="fas fa-print"></i> Print Invoice
            </a>
            <?php if($status == 'active'): ?>
            <a href="return_car.php?rental_id=<?php echo $booking_id; ?>" class="btn btn-edit">
                <i class="fas fa-car"></i> Return Vehicle
            </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
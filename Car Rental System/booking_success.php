 
<?php
session_start();
include "connect.php";

// Check if there's a successful booking
if (!isset($_SESSION['booking_success'])) {
    header("Location: book.php");
    exit();
}

// Get booking details from session
$booking = $_SESSION['booking_success'];

// Clear the session data
unset($_SESSION['booking_success']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Booking Confirmation - MOBBY CARS</title>
    <link href="pstyles.css" rel="stylesheet" type="text/css" />
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .confirmation-icon {
            font-size: 80px;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        
        .confirmation-message {
            font-size: 24px;
            margin-bottom: 30px;
            color: #333;
        }
        
        .booking-details {
            background: #7b7fed;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-label {
            font-weight: bold;
            width: 200px;
            color: #555;
        }
        
        .detail-value {
            flex: 1;
        }
        
        .action-buttons {
            margin-top: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            margin: 0 10px;
            background: #7b7fed;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #6a6ed8;
        }
        
        .btn-print {
            background: #555;
        }
    </style>
</head>
<body>
    
    <div class="confirmation-container">
        <div class="confirmation-icon">✓</div>
        <h1>Booking Confirmed!</h1>
        <div class="confirmation-message"><?php echo htmlspecialchars($booking['message']); ?></div>
        
        <div class="booking-details">
            <h3>Booking Details</h3>
            <div class="detail-row">
                <div class="detail-label">Booking ID:</div>
                <div class="detail-value"><?php echo htmlspecialchars($booking['booking_id']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Customer ID:</div>
                <div class="detail-value"><?php echo htmlspecialchars($booking['user_id']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Vehicle:</div>
                <div class="detail-value"><?php echo htmlspecialchars($booking['car_model']); ?> (<?php echo htmlspecialchars($booking['license_no']); ?>)</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Pickup Date:</div>
                <div class="detail-value"><?php echo date('F j, Y', strtotime($booking['start_date'])); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Rental Duration:</div>
                <div class="detail-value"><?php echo htmlspecialchars($booking['duration']); ?></div>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="view.php?cid=<?php echo htmlspecialchars($booking['user_id']); ?>" class="btn">View My Bookings</a>
            <a href="book.php" class="btn">Book Another Vehicle</a>
            <a href="javascript:window.print()" class="btn btn-print">Print Confirmation</a>
        </div>
    </div>
</body>
</html>
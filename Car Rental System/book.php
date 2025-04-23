<?php
include "connect.php"; 
session_start();

// Add security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Set default timezone
date_default_timezone_set('UTC');

// Enhanced session validation
if (!isset($_SESSION['user_id'])) {
    header("location: sign_in.php?error=not_logged_in");
    exit();
}

// Verify user exists
$user_query = "SELECT user_id, fname, lname FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($connect, $user_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);

// Validate user
if (!$user) {
    session_destroy();
    header("location: sign_in.php?error=invalid_user");
    exit();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Car model to image mapping
$car_images = [
    // Compact cars
    'Mazda' => 'mazda.jpg',
    'Honda Civic' => 'honda civic.jpg',
    'Kia Picanto' => 'kia_picanto.jpg',
    'Corolla' => 'toyota_corolla.jpg',
    
    // Medium cars
    'Toyota Harrier' => 'toyota_harrier.jpg',
    'Toyota Camry' => 'toyota_camry.jpg',
    'Subaru Legacy' => 'subaru_legacy.jpg',
    'Nissan Altima' => 'nissan_altima.jpg',
    
    // Large cars
    'Ford Taurus' => 'ford_taurus.jpg',
    'Nissan Maxima' => 'nissan_maxima.jpg',
    
    // SUVs
    'Toyota RAV4' => 'toyota_rav4.jpg',
    'Jeep Grand Cherokee' => 'jeep_grand_cherokee.jpg',
    'Toyota Prado' => 'toyota_prado.jpg',
    'Subaru Outback' => 'subaru_outback.jpg',
    'Lexus Lx' => 'lexus_lx.jpg',
    'Cx-5 Mazda' => 'cx-5 mazda.jpg',
    
    // Vans
    'Toyota Sienna' => 'toyota_sienna.jpg',
    'Mercedes-Benz Sprinter' => 'mercedes_sprinter.jpg',
    'Nissan Quest' => 'nissan_quest.jpg',
    
    // Trucks
    'Ford F-150' => 'ford_f150.jpg',
    'Nissan Titan' => 'nissan_titan.jpg',
    'Toyota Tacoma' => 'toyota_tacoma.jpg'
];

// Initialize variables
$available_result = null;
$selected_type = '';
$check_date = date('Y-m-d');
$check_type = 'Compact';
$errors = [];

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token for all POST requests
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid form submission. Please try again.";
    } else {
        if (isset($_POST["check_availability"])) {
            // Check car availability
            $check_date = mysqli_real_escape_string($connect, $_POST["check_date"]);
            $check_type = mysqli_real_escape_string($connect, $_POST["check_type"]);
           
            $available_query = "SELECT c.license_no, c.model, c.Ctype 
                              FROM cars c
                              WHERE c.Ctype = '$check_type' 
                              AND c.is_available = 'available' 
                              AND c.license_no NOT IN (
                                  SELECT r.license_no 
                                  FROM renting r 
                                  WHERE '$check_date' BETWEEN r.Sdate AND DATE_ADD(r.Sdate, INTERVAL r.Nodays DAY)
                                  AND r.status = 'active'
                              )";
            $available_result = mysqli_query($connect, $available_query);
            
        } elseif (isset($_POST["book"])) {
            // Process booking with enhanced security
            $user_id = $user['user_id'];
            
            // Validate and format the date - UPDATED SECTION
            $Sdate = '';
            if (!empty($_POST["start_date"])) {
                $dateInput = $_POST["start_date"];
                
                // First validate the date format
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateInput)) {
                    // Then create DateTime object
                    try {
                        $dateObj = new DateTime($dateInput);
                        $today = new DateTime();
                        $today->setTime(0, 0, 0);
                        
                        if ($dateObj < $today) {
                            $errors[] = "Start date cannot be in the past.";
                        } else {
                            $Sdate = $dateInput; // Use the validated date directly
                        }
                    } catch (Exception $e) {
                        $errors[] = "Invalid date selected. Please choose a valid date.";
                        error_log("Date parsing error: " . $e->getMessage());
                    }
                } else {
                    $errors[] = "Invalid date format. Please use YYYY-MM-DD format.";
                }
            } else {
                $errors[] = "Start date is required.";
            }
            
            // Rest of booking processing
            $Ctype = mysqli_real_escape_string($connect, $_POST["Ctype"]);
            $Rtype = mysqli_real_escape_string($connect, $_POST["Rtype"]);
            $Nodays = (int)$_POST["Days"];
            $Noweeks = (int)$_POST["Weeks"];
            $license_no = mysqli_real_escape_string($connect, $_POST["license_no"]);
            $payment_method = mysqli_real_escape_string($connect, $_POST["payment_method"]);
            
            // Validate inputs
            if (empty($Sdate)) {
                $errors[] = "Please select a valid start date.";
            }
            if (empty($Ctype) || empty($Rtype) || empty($license_no) || empty($payment_method)) {
                $errors[] = "All fields are required.";
            }
            
            if ($Nodays === 0 && $Noweeks === 0) {
                $errors[] = "Please select rental duration.";
            }
            
            // Only proceed if no errors
            if (empty($errors)) {
                // Start transaction for booking process
                mysqli_begin_transaction($connect);
                
                try {
                    // 1. Verify user exists with lock
                    $user_check = "SELECT user_id FROM users WHERE user_id = ? FOR UPDATE";
                    $stmt = mysqli_prepare($connect, $user_check);
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_store_result($stmt);
                    
                    if (mysqli_stmt_num_rows($stmt) == 0) {
                        throw new Exception("Your account is no longer valid. Please contact support.");
                    }
                    
                    // 2. Verify car availability with lock
                    $car_check = "SELECT Ctype, model FROM cars WHERE license_no = ? AND is_available = 'available' FOR UPDATE";
                    $stmt = mysqli_prepare($connect, $car_check);
                    mysqli_stmt_bind_param($stmt, "s", $license_no);
                    mysqli_stmt_execute($stmt);
                    $car_result = mysqli_stmt_get_result($stmt);
                    
                    if (mysqli_num_rows($car_result) == 0) {
                        throw new Exception("The selected car is no longer available.");
                    }
                    
                    $car_data = mysqli_fetch_assoc($car_result);
                    if ($car_data['Ctype'] != $Ctype) {
                        throw new Exception("Selected car does not match the car type.");
                    }
                    
                    // 3. Check date availability
                    $D2 = date('Y-m-d', strtotime($Sdate . " + $Nodays days"));
                    $check_availability = mysqli_query($connect, "
                        SELECT * FROM renting
                        WHERE license_no = '$license_no' 
                        AND status = 'active'
                        AND (
                            (Sdate <= '$Sdate' AND DATE_ADD(Sdate, INTERVAL Nodays DAY) >= '$Sdate') OR
                            (Sdate <= '$D2' AND DATE_ADD(Sdate, INTERVAL Nodays DAY) >= '$D2')
                        )
                    ");
                    
                    if (mysqli_num_rows($check_availability) > 0) {
                        throw new Exception("Car is not available on the selected dates.");
                    }
                    
                    // 4. Get rates
                    $rate_query = "SELECT daily_rate, weekly_rate FROM rental_rates WHERE Ctype = ?";
                    $stmt = mysqli_prepare($connect, $rate_query);
                    mysqli_stmt_bind_param($stmt, "s", $Ctype);
                    mysqli_stmt_execute($stmt);
                    $rate_result = mysqli_stmt_get_result($stmt);
                    
                    if (mysqli_num_rows($rate_result) != 1) {
                        throw new Exception("Could not determine rental rates for this car type.");
                    }
                    
                    $rate_row = mysqli_fetch_assoc($rate_result);
                    $daily_rate = (float)$rate_row["daily_rate"];
                    $weekly_rate = (float)$rate_row["weekly_rate"];
                    $total_price = ($Nodays * $daily_rate) + ($Noweeks * $weekly_rate);
                    
                    // 5. Create booking using prepared statement
                    $query = "INSERT INTO renting (user_id, license_no, Ctype, Rtype, Sdate, Nodays, Noweeks, payment_method, total_price, status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
                    $stmt = mysqli_prepare($connect, $query);
                    mysqli_stmt_bind_param($stmt, "isssiisds", $user_id, $license_no, $Ctype, $Rtype, $Sdate, $Nodays, $Noweeks, $payment_method, $total_price);
                    
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Error creating booking: " . mysqli_error($connect));
                    }
                    
                    $booking_id = mysqli_insert_id($connect);
                    $car_model = $car_data['model'];
                    
                    // 6. Update car availability
                    $update_query = "UPDATE cars SET is_available = 0 WHERE license_no = ?";
                    $stmt = mysqli_prepare($connect, $update_query);
                    mysqli_stmt_bind_param($stmt, "s", $license_no);
                    
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Error updating car availability: " . mysqli_error($connect));
                    }
                    
                    // Commit transaction
                    mysqli_commit($connect);
                    
                    // Store booking success in session
                    $_SESSION['booking_success'] = [
                        'booking_id' => $booking_id,
                        'user_id' => $user_id,
                        'car_model' => $car_model,
                        'license_no' => $license_no,
                        'start_date' => $Sdate,
                        'duration' => ($Nodays > 0 ? $Nodays . ' day(s)' : '') . 
                                     ($Noweeks > 0 ? ($Nodays > 0 ? ', ' : '') . $Noweeks . ' week(s)' : ''),
                        'total_price' => $total_price,
                        'payment_method' => $payment_method,
                        'message' => 'Your booking was successful!'
                    ];
                    
                    // Redirect to success page
                    header("Location: booking_success.php");
                    exit();
                    
                } catch (Exception $e) {
                    mysqli_rollback($connect);
                    $errors[] = $e->getMessage();
                    error_log("Booking Error [User: $user_id]: " . $e->getMessage());
                }
            }
        }
    }
}

// AJAX handler for vehicle selection
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_vehicles' && isset($_POST['Ctype'])) {
    $selected_type = mysqli_real_escape_string($connect, $_POST['Ctype']);
    $car_query = "SELECT license_no, model FROM cars WHERE is_available = 'available' AND Ctype = '$selected_type'";
    $car_result = mysqli_query($connect, $car_query);
    
    $options = '<option value="">Select Vehicle</option>';
    if (mysqli_num_rows($car_result) > 0) {
        while ($row = mysqli_fetch_assoc($car_result)) {
            $options .= '<option value="'.$row['license_no'].'">'.$row['model'].' ('.$row['license_no'].')</option>';
        }
    } else {
        $options .= '<option value="">No vehicles available for selected type</option>';
    }
    
    echo $options;
    exit();
}

// Get available cars for default selection
$car_query = "SELECT license_no, model FROM cars WHERE is_available = 'available'";
$car_result = mysqli_query($connect, $car_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title>Book a Car - MOBBY CARS</title>
    <link href="pstyles.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Your existing CSS styles remain unchanged */
        :root {
            --primary-color: #7b7fed;
            --primary-hover: #6a6ed8;
            --error-color: #e74c3c;
            --success-color: #2ecc71;
            --text-color: #333;
            --light-text: #555;
            --border-color: #ddd;
            --bg-color: #7b7fed;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--bg-color);
        }
        
        .menu {
            background-color: #333;
            padding: 10px 0;
        }
        
        .menu ul {
            list-style: none;
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        
        .menu li a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .menu li a:hover, #active a {
            background-color: var(--primary-color);
        }
        
        .form-section {
            margin: 20px auto;
            padding: 25px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 1200px;
        }
        
        .form-section h2 {
            margin-top: 0;
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }
        
        .available-cars {
            margin: 30px 0;
        }
        
        .car-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .car-card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .car-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .car-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 10px;
            border: 1px solid var(--border-color);
        }
        
        .car-card h4 {
            margin: 0 0 5px 0;
            color: var(--text-color);
            font-size: 18px;
        }
        
        .car-card p {
            margin: 5px 0;
            color: var(--light-text);
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .form-group input[type="date"],
        .form-group select,
        .form-group input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input[type="date"]:focus,
        .form-group select:focus,
        .form-group input[type="text"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(123, 127, 237, 0.2);
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            text-align: center;
            transition: background-color 0.3s;
            text-decoration: none;
        }
        
        .btn:hover {
            background-color: var(--primary-hover);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
        }
        
        .btn-success {
            background-color: var(--success-color);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }
        
        .alert-danger {
            background-color: rgba(231, 76, 60, 0.1);
            border-left: 4px solid var(--error-color);
            color: var(--error-color);
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            border-left: 4px solid var(--success-color);
            color: var(--success-color);
        }
        
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .user-info {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .user-info h3 {
            margin-top: 0;
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .menu ul {
                flex-direction: column;
                align-items: center;
            }
            
            .form-section {
                padding: 15px;
            }
            
            .car-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<marquee>
    <h3><b style="color: white; font-size:25px;">WELCOME TO MOBBY CARS. RENT YOUR CAR, ENJOY THE RIDE. HURRY HURRY!!!</b></h3>
</marquee>

<div class="menu">
    <ul>
        <li><a href="index.php">Home</a></li>
        <li id="active"><a href="book.php">Book Car</a></li>
        <li><a href="viewBookings.php">My Bookings</a></li>
        <li><a href="profile.php">My Profile</a></li>
    </ul>
</div>

<div class="form-section">
    <div class="user-info">
        <h3>Welcome, <?php echo htmlspecialchars($user['fname'] . ' ' . $user['lname']); ?></h3>
        <p>You're booking as: <?php echo htmlspecialchars($user['fname'] . ' ' . $user['lname']); ?> (User ID: <?php echo htmlspecialchars($user['user_id']); ?>)</p>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <h2>Check Car Availability</h2>
    <form action="" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="grid-container">
            <div class="form-group">
                <label for="check_date">Check Date:</label>
                <input type="date" id="check_date" name="check_date" required value="<?php echo htmlspecialchars($check_date); ?>">
            </div>
            
            <div class="form-group">
                <label for="check_type">Car Type:</label>
                <select id="check_type" name="check_type" required>
                    <option value="Compact" <?php echo $check_type == 'Compact' ? 'selected' : ''; ?>>Compact</option>
                    <option value="Medium" <?php echo $check_type == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="Large" <?php echo $check_type == 'Large' ? 'selected' : ''; ?>>Large</option>
                    <option value="SUV" <?php echo $check_type == 'SUV' ? 'selected' : ''; ?>>SUV</option>
                    <option value="Van" <?php echo $check_type == 'Van' ? 'selected' : ''; ?>>Van</option>
                    <option value="Truck" <?php echo $check_type == 'Truck' ? 'selected' : ''; ?>>Truck</option>
                </select>
            </div>
        </div>
        
        <button type="submit" name="check_availability" class="btn btn-primary">
            <i class="fas fa-search"></i> Check Availability
        </button>
    </form>
    
    <?php if (isset($available_result) && mysqli_num_rows($available_result) > 0): ?>
    <div class="available-cars">
        <h3>Available Cars on <?php echo htmlspecialchars($check_date); ?> (<?php echo htmlspecialchars($check_type); ?>)</h3>
        <div class="car-list">
            <?php while ($car = mysqli_fetch_assoc($available_result)): 
                $image_file = $car_images[$car['model']] ?? 'default_car.jpg';
            ?>
                <div class="car-card">
                    <img src="images/<?php echo htmlspecialchars($image_file); ?>" alt="<?php echo htmlspecialchars($car['model']); ?>">
                    <h4><?php echo htmlspecialchars($car['model']); ?></h4>
                    <p><strong>License:</strong> <?php echo htmlspecialchars($car['license_no']); ?></p>
                    <p><strong>Type:</strong> <?php echo htmlspecialchars($car['Ctype']); ?></p>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php elseif (isset($_POST["check_availability"])): ?>
        <div class="alert alert-danger">
            <p><i class="fas fa-info-circle"></i> No cars available for the selected date and type.</p>
        </div>
    <?php endif; ?>
</div>

<div class="form-section">
    <h2>Make a Reservation</h2>
    <form action="" method="post" id="bookingForm">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>">
        <div class="grid-container">
            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" required 
                       min="<?php echo date('Y-m-d'); ?>" 
                       value="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <label for="Ctype">Car Type:</label>
                <select id="carType" name="Ctype" required>
                    <option value="Compact">Compact</option>
                    <option value="Medium">Medium</option>
                    <option value="Large">Large</option>
                    <option value="SUV">SUV</option>
                    <option value="Van">Van</option>
                    <option value="Truck">Truck</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="Rtype">Rent Type:</label>
                <select id="Rtype" name="Rtype" required>
                    <option value="Daily">Daily</option>
                    <option value="Weekly">Weekly</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="Days">No. of Days:</label>
                <select id="Days" name="Days" required>
                    <?php for ($i = 0; $i <= 7; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo (isset($_POST['Days'])) && $_POST['Days'] == $i ? 'selected' : ''; ?>>
                            <?php echo $i; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="Weeks">No. of Weeks:</label>
                <select id="Weeks" name="Weeks" required>
                    <?php for ($i = 0; $i <= 3; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo (isset($_POST['Weeks'])) && $_POST['Weeks'] == $i ? 'selected' : ''; ?>>
                            <?php echo $i; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="vehicleSelect">Select Vehicle:</label>
                <select id="vehicleSelect" name="license_no" required>
                    <option value="">Select Vehicle</option>
                    <?php if ($car_result && mysqli_num_rows($car_result) > 0): 
                        mysqli_data_seek($car_result, 0);
                        while ($row = mysqli_fetch_assoc($car_result)): ?>
                            <option value="<?php echo htmlspecialchars($row['license_no']); ?>"
                                <?php echo (isset($_POST['license_no'])) && $_POST['license_no'] == $row['license_no'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($row['model'] . " (" . $row['license_no'] . ")"); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <option value="">No vehicles currently available</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="payment_method">Payment Method:</label>
                <select id="payment_method" name="payment_method" required>
                    <option value="">Select Payment Method</option>
                    <option value="M-Pesa" <?php echo (isset($_POST['payment_method'])) && $_POST['payment_method'] == 'M-Pesa' ? 'selected' : ''; ?>>M-Pesa</option>
                    <option value="PayPal" <?php echo (isset($_POST['payment_method'])) && $_POST['payment_method'] == 'PayPal' ? 'selected' : ''; ?>>PayPal</option>
                    <option value="Credit Card" <?php echo (isset($_POST['payment_method'])) && $_POST['payment_method'] == 'Credit Card' ? 'selected' : ''; ?>>Credit Card</option>
                    <option value="Cash" <?php echo (isset($_POST['payment_method'])) && $_POST['payment_method'] == 'Cash' ? 'selected' : ''; ?>>Cash</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Total Cost:</label>
                <input type="text" id="totalCost" readonly style="font-weight:bold; color:green; background-color:#f5f5f5; padding:10px;">
                <input type="hidden" name="total_price" id="totalPriceHidden">
            </div>
        </div>
        
        <button type="submit" name="book" class="btn btn-success">
            <i class="fas fa-check"></i> Book Now
        </button>
    </form>
</div>

<script>
$(document).ready(function() {
    // Update vehicle options when car type changes
    $('#carType').change(function() {
        var selectedType = $(this).val();
        
        $.ajax({
            url: '?ajax=get_vehicles',
            type: 'POST',
            data: { 
                Ctype: selectedType,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#vehicleSelect').html(response);
                updateTotalCost(); 
            },
            error: function() {
                alert('Error loading vehicles');
                $('#vehicleSelect').html('<option value="">Error loading vehicles</option>');
            }
        });
    });

    // Calculate and update total cost
    function updateTotalCost() {
        const carType = $('[name="Ctype"]').val();
        const rentType = $('[name="Rtype"]').val();
        const days = parseInt($('[name="Days"]').val());
        const weeks = parseInt($('[name="Weeks"]').val());

        if (days === 0 && weeks === 0) {
            $('#totalCost').val("Select rental period");
            $('#totalCost').css("color", "red");
            return;
        }

        $.ajax({
            url: 'get_rates.php',
            type: 'POST',
            data: { 
                Ctype: carType,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function(data) {
                if (data.daily_rate && data.weekly_rate) {
                    const dailyRate = parseFloat(data.daily_rate);
                    const weeklyRate = parseFloat(data.weekly_rate);
                    const totalCost = (days * dailyRate) + (weeks * weeklyRate);
                    
                    $('#totalCost').val("$" + totalCost.toFixed(2));
                    $('#totalCost').css("color", "green");
                    $('#totalPriceHidden').val(totalCost.toFixed(2));
                } else {
                    $('#totalCost').val("Could not calculate cost");
                    $('#totalCost').css("color", "red");
                }
            },
            error: function() {
                $('#totalCost').val("Error calculating cost");
                $('#totalCost').css("color", "red");
            }
        });
    }

    // Update total cost when relevant fields change
    $('[name="Ctype"], [name="Rtype"], [name="Days"], [name="Weeks"]').change(updateTotalCost);
    
    // Initialize total cost calculation
    updateTotalCost();
    
    // Set minimum date for start date (today)
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('start_date').min = today;
    document.getElementById('check_date').min = today;
    
    // Form validation
    $('#bookingForm').submit(function(e) {
        const dateInput = $('#start_date').val();
        if (!dateInput) {
            alert('Please select a start date');
            e.preventDefault();
            return false;
        }
        
        const selectedDate = new Date(dateInput);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate < today) {
            alert('Start date cannot be in the past');
            e.preventDefault();
            return false;
        }
        
        if ($('#vehicleSelect').val() === '') {
            alert('Please select a vehicle');
            e.preventDefault();
            return false;
        }
        
        if ($('#payment_method').val() === '') {
            alert('Please select a payment method');
            e.preventDefault();
            return false;
        }
        
        const days = parseInt($('[name="Days"]').val());
        const weeks = parseInt($('[name="Weeks"]').val());
        
        if (days === 0 && weeks === 0) {
            alert('Please select rental duration (days or weeks)');
            e.preventDefault();
            return false;
        }
        
        return true;
    });
    
    // Preserve form values on error
    <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["book"]) && !empty($errors)): ?>
        // Set the previously selected values
        $('#carType').val('<?php echo isset($_POST["Ctype"]) ? $_POST["Ctype"] : ""; ?>');
        $('#Rtype').val('<?php echo isset($_POST["Rtype"]) ? $_POST["Rtype"] : ""; ?>');
        $('#Days').val('<?php echo isset($_POST["Days"]) ? $_POST["Days"] : ""; ?>');
        $('#Weeks').val('<?php echo isset($_POST["Weeks"]) ? $_POST["Weeks"] : ""; ?>');
        $('#payment_method').val('<?php echo isset($_POST["payment_method"]) ? $_POST["payment_method"] : ""; ?>');
        
        // Trigger change to update vehicle list and total cost
        $('#carType').trigger('change');
        updateTotalCost();
    <?php endif; ?>
});
</script>

</body>
</html>
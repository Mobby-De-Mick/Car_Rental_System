<?php
session_start();
require_once "connect.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: sign_in.php");
    exit();
}

// Get user details
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $connect->prepare($query);

if (!$stmt) {
    die("Error preparing statement: " . $connect->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("User not found in database");
}

$user = $result->fetch_assoc();

// Fetch user's bookings - UPDATED QUERY
$bookings_query = "SELECT r.*, cr.model AS car_name, cr.ctype AS car_type, 
                   DATEDIFF(r.Sdate + INTERVAL r.Nodays DAY, r.Sdate) AS duration_days
                   FROM renting r
                   JOIN cars cr ON r.license_no = cr.license_no
                   WHERE r.user_id = ?
                   ORDER BY r.Sdate DESC";
$bookings_stmt = $connect->prepare($bookings_query);

if (!$bookings_stmt) {
    die("Error preparing bookings statement: " . $connect->error);
}

$bookings_stmt->bind_param("i", $user_id);
$bookings_stmt->execute();
$bookings = $bookings_stmt->get_result();

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $mobile = $_POST['mobile'];
    $dlno = $_POST['dlno'];
    $insno = $_POST['insno'];
    
    $update_query = "UPDATE users SET mobile = ?, dlno = ?, insno = ? WHERE user_id = ?";
    $update_stmt = $connect->prepare($update_query);
    $update_stmt->bind_param("sssi", $mobile, $dlno, $insno, $user_id);
    
    if ($update_stmt->execute()) {
        $success = "Profile updated successfully!";
        // Refresh user data
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    } else {
        $error = "Failed to update profile: " . $connect->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Profile - MOBBY CARS</title>
    <link href="styles.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #7b7fed;
            --primary-light: #a5a7f0;
            --secondary-color: #5bc0de;
            --danger-color: #d9534f;
            --text-dark: #333;
            --text-light: #666;
            --bg-light: #f9f9f9;
            --border-color: #ddd;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-light);
            margin: 0;
            padding: 0;
            color: var(--text-dark);
        }
        
        .profile-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: var(--primary-light);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 15px;
        }
        
        .profile-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .detail-card {
            background-color: var(--bg-light);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .detail-card h3 {
            margin-top: 0;
            color: var(--primary-color);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 12px;
        }
        
        .detail-label {
            font-weight: 600;
            width: 150px;
            color: var(--text-light);
        }
        
        .detail-value {
            flex: 1;
        }
        
        .bookings-section {
            margin-top: 40px;
        }
        
        .section-title {
            color: var(--primary-color);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .bookings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .booking-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: transform 0.3s;
        }
        
        .booking-card:hover {
            transform: translateY(-5px);
        }
        
        .booking-image {
            height: 160px;
            background-color: #eee;
            background-size: cover;
            background-position: center;
        }
        
        .booking-details {
            padding: 15px;
        }
        
        .booking-title {
            font-weight: 600;
            margin: 0 0 5px;
            color: var(--text-dark);
        }
        
        .booking-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
            color: var(--text-light);
        }
        
        .booking-price {
            font-weight: bold;
            color: var(--primary-color);
            font-size: 18px;
        }
        
        .booking-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-active {
            background-color: #e1f5e1;
            color: #4CAF50;
        }
        
        .status-completed {
            background-color: #e1e5f5;
            color: var(--primary-color);
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: 2px solid var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #6a6ed8;
            border-color: #6a6ed8;
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
            border: 2px solid var(--secondary-color);
        }
        
        .btn-secondary:hover {
            background-color: #46b8da;
            border-color: #46b8da;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
            border: 2px solid var(--danger-color);
        }
        
        .btn-danger:hover {
            background-color: #c9302c;
            border-color: #c9302c;
        }
        
        .no-bookings {
            text-align: center;
            padding: 40px;
            color: var(--text-light);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 16px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            margin: 0;
            color: var(--primary-color);
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-light);
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        
        .alert-danger {
            background-color: #f2dede;
            color: #a94442;
        }
        
        @media (max-width: 768px) {
            .profile-details {
                grid-template-columns: 1fr;
            }
            
            .bookings-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php @include "header.php"; ?>
    
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['fname'], 0, 1)) . strtoupper(substr($user['lname'], 0, 1)); ?>
            </div>
            <h2><?php echo htmlspecialchars($user['fname'] . ' ' . $user['lname']); ?></h2>
            <p>Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-details">
            <div class="detail-card">
                <h3><i class="fas fa-user"></i> Personal Information</h3>
                <div class="detail-row">
                    <div class="detail-label">First Name:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['fname']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Last Name:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['lname']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Age:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['age']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Email:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
            </div>
            
            <div class="detail-card">
                <h3><i class="fas fa-id-card"></i> Contact & Legal</h3>
                <div class="detail-row">
                    <div class="detail-label">Phone Number:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['mobile']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Driver License:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['dlno']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Insurance Number:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['insno']); ?></div>
                </div>
            </div>
        </div>
        
        
        <div class="bookings-section">
            <h3 class="section-title"><i class="fas fa-calendar-alt"></i> My Bookings</h3>
            
            <?php if ($bookings->num_rows > 0): ?>
                <div class="bookings-grid">
                    <?php while($booking = $bookings->fetch_assoc()): 
                        $end_date = date('Y-m-d', strtotime($booking['Sdate'] . " + {$booking['Nodays']} days"));
                        $status_class = strtotime($end_date) < time() ? 'status-completed' : 'status-active';
                        $status = strtotime($end_date) < time() ? 'Completed' : 'Active';
                        $car_image = !empty($booking['image_path']) ? $booking['image_path'] : 'images/default-car.jpg';
                    ?>
                        <div class="booking-card">
                            <div class="booking-image" style="background-image: url('<?php echo htmlspecialchars($car_image); ?>')"></div>
                            <div class="booking-details">
                                <h4 class="booking-title"><?php echo htmlspecialchars($booking['car_name']); ?></h4>
                                <div class="booking-meta">
                                    <span><?php echo htmlspecialchars($booking['car_type']); ?></span>
                                    <span><?php echo $booking['duration_days']; ?> days</span>
                                </div>
                                <div class="booking-meta">
                                    <span><?php echo date('M j, Y', strtotime($booking['Sdate'])); ?></span>
                                    <span>to <?php echo date('M j, Y', strtotime($end_date)); ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div class="booking-price">$<?php echo number_format($booking['total_price'], 2); ?></div>
                                    <span class="booking-status <?php echo $status_class; ?>">
                                        <?php echo $status; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-bookings">
                    <i class="fas fa-calendar-times" style="font-size: 50px; color: var(--primary-light); margin-bottom: 15px;"></i>
                    <h3>No Bookings Yet</h3>
                    <p>You haven't made any bookings with us yet.</p>
                    <a href="book.php" class="btn btn-primary">
                        <i class="fas fa-car"></i> Book a Car Now
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="action-buttons">
            <button id="editProfileBtn" class="btn btn-secondary">
                <i class="fas fa-edit"></i> Edit Profile
            </button>
            <a href="change_password.php" class="btn btn-secondary">
                <i class="fas fa-key"></i> Change Password
            </a>
            <a href="logout.php" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Profile</h3>
                <button class="close-btn">&times;</button>
            </div>
            <form method="POST" action="profile.php">
                <div class="form-group">
                    <label for="mobile">Phone Number</label>
                    <input type="tel" id="mobile" name="mobile" value="<?php echo htmlspecialchars($user['mobile']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="dlno">Driver License Number</label>
                    <input type="text" id="dlno" name="dlno" value="<?php echo htmlspecialchars($user['dlno']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="insno">Insurance Number</label>
                    <input type="text" id="insno" name="insno" value="<?php echo htmlspecialchars($user['insno']); ?>" required>
                </div>
                
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-danger close-btn">Cancel</button>
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php @include "footer.php"; ?>
    
    <script>
        // Modal functionality
        const modal = document.getElementById('editProfileModal');
        const openBtn = document.getElementById('editProfileBtn');
        const closeBtns = document.querySelectorAll('.close-btn');
        
        openBtn.addEventListener('click', () => {
            modal.style.display = 'flex';
        });
        
        closeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    </script>
</body>
</html>
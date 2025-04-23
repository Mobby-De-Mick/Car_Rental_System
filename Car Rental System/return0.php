<?php
session_start();
include "connect.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('location: sign_in.php');
    exit();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$error = '';
$success = '';
$rental_info = [];
$charge_details = [];
$active_rentals = [];

// Get active rentals for the logged-in user
$user_id = $_SESSION['user_id'];
$active_rentals_query = "SELECT r.Rid, r.license_no, c.model, r.Sdate, r.Nodays, r.Ctype
                        FROM renting r
                        JOIN cars c ON r.license_no = c.license_no
                        WHERE r.status = 'active' AND r.user_id = ?";
$stmt = mysqli_prepare($connect, $active_rentals_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$active_rentals_result = mysqli_stmt_get_result($stmt);
$active_rentals = mysqli_fetch_all($active_rentals_result, MYSQLI_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_return'])) {
    // Validate CSRF token first
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission. Please try again.";
    } else {
        $rental_id = filter_input(INPUT_POST, 'rental_id', FILTER_VALIDATE_INT);
        $return_date = $_POST['return_date'] ?? date('Y-m-d');
        $fuel_level = $_POST['fuel_level'] ?? 'full';
        $damage_report = trim($_POST['damage_report'] ?? '');
        $damage_severity = $_POST['damage_severity'] ?? 'none';
        $payment_amount = filter_input(INPUT_POST, 'payment_amount', FILTER_VALIDATE_FLOAT);
        $payment_method = $_POST['payment_method'] ?? 'cash';

        try {
            // Validate inputs
            if (!$rental_id) throw new Exception("Invalid rental ID");
            if (strtotime($return_date) === false) throw new Exception("Invalid return date");
            if (!in_array($fuel_level, ['full', '3/4', '1/2', '1/4', 'empty'])) {
                throw new Exception("Invalid fuel level selection");
            }
            if (!in_array($damage_severity, ['none', 'minor', 'moderate', 'major', 'severe'])) {
                throw new Exception("Invalid damage severity selection");
            }
            if ($payment_amount === false || $payment_amount < 0) {
                throw new Exception("Invalid payment amount");
            }

            // Begin transaction
            mysqli_begin_transaction($connect);

            // 1. Verify rental belongs to current user
            $verify_query = "SELECT r.*, c.model, rr.daily_rate 
                           FROM renting r
                           JOIN cars c ON r.license_no = c.license_no
                           JOIN rental_rates rr ON r.Ctype = rr.Ctype
                           WHERE r.Rid = ? AND r.user_id = ? AND r.status = 'active'";
            $stmt = mysqli_prepare($connect, $verify_query);
            mysqli_stmt_bind_param($stmt, "ii", $rental_id, $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $rental_info = mysqli_fetch_assoc($result);

            if (!$rental_info) throw new Exception("Invalid rental ID or rental does not belong to you");

            // Calculate charges
            $charge_details = calculate_charges($rental_info, $return_date, $fuel_level, $damage_severity);
            $total_additional_charges = $charge_details['total'];

            // Determine payment status
            $payment_status = ($payment_amount >= $total_additional_charges) ? 'paid' : ($payment_amount > 0 ? 'partial' : 'pending');

            // 2. Update rental record
            $update_rental = "UPDATE renting SET 
                            status = 'completed',
                            additional_charges = ?,
                            damage_report = ?,
                            actual_return_date = ?,
                            fuel_level_on_return = ?,
                            damage_severity = ?,
                            return_processed_date = NOW(),
                            payment_status = ?,
                            amount_paid = ?
                            WHERE Rid = ?";
            
            $stmt = mysqli_prepare($connect, $update_rental);
            mysqli_stmt_bind_param($stmt, "dsssssdi", 
                $total_additional_charges,
                $damage_report,
                $return_date,
                $fuel_level,
                $damage_severity,
                $payment_status,
                $payment_amount,
                $rental_id
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to update rental record");
            }

            // 3. Record payment if any
            if ($payment_amount > 0) {
                $payment_query = "INSERT INTO payment (Rid, amount, payment_method, payment_date, notes)
                                VALUES (?, ?, ?, NOW(), ?)";
                $stmt = mysqli_prepare($connect, $payment_query);
                $notes = "Payment for return charges";
                mysqli_stmt_bind_param($stmt, "idss", $rental_id, $payment_amount, $payment_method, $notes);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to record payment");
                }
            }

            // 4. Update car availability
            $update_car = "UPDATE cars SET is_available = 'available' WHERE license_no = ?";
            $stmt = mysqli_prepare($connect, $update_car);
            mysqli_stmt_bind_param($stmt, "s", $rental_info['license_no']);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to update car availability");
            }

            // 5. Add to service queue if needed
            if (in_array($damage_severity, ['major', 'severe'])) {
                $service_query = "INSERT INTO service_queue (license_no, service_type, requested_date, notes) 
                                VALUES (?, 'damage repair', NOW(), ?)";
                $stmt = mysqli_prepare($connect, $service_query);
                mysqli_stmt_bind_param($stmt, "ss", $rental_info['license_no'], $damage_report);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to add car to service queue");
                }
            }

            // Commit transaction
            mysqli_commit($connect);
            
            // Generate success message
            $success = generate_success_message($rental_info, $charge_details, $payment_amount, $return_date);

            // Regenerate CSRF token after successful form submission
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            // Clear form values
            unset($_POST);

        } catch (Exception $e) {
            mysqli_rollback($connect);
            $error = "Error processing return: " . $e->getMessage();
            error_log("Return Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }
}

// Charge calculation function
function calculate_charges($rental_info, $return_date, $fuel_level, $damage_severity) {
    $charges = [
        'late_fee' => 0,
        'fuel_fee' => 0,
        'damage_fee' => 0,
        'total' => 0,
        'late_days' => 0
    ];

    // Calculate late return fee (1.5x daily rate per day)
    $scheduled_return = date('Y-m-d', strtotime($rental_info['Sdate'] . " + {$rental_info['Nodays']} days"));
    $late_days = max(0, (strtotime($return_date) - strtotime($scheduled_return)) / (60 * 60 * 24));

    if ($late_days > 0) {
        $charges['late_fee'] = $late_days * ($rental_info['daily_rate'] * 1.5);
        $charges['late_days'] = $late_days;
    }

    // Calculate fuel fee
    $fuel_prices = [
        'full' => 0,
        '3/4' => 25,
        '1/2' => 50,
        '1/4' => 75,
        'empty' => 100
    ];
    $charges['fuel_fee'] = $fuel_prices[$fuel_level];

    // Calculate damage fees
    $damage_prices = [
        'none' => 0,
        'minor' => 50,
        'moderate' => 200,
        'major' => 500,
        'severe' => 1000
    ];
    $charges['damage_fee'] = $damage_prices[$damage_severity];

    // Calculate total
    $charges['total'] = $charges['late_fee'] + $charges['fuel_fee'] + $charges['damage_fee'];

    return $charges;
}

// Generate success message
function generate_success_message($rental_info, $charge_details, $payment_amount, $return_date) {
    $message = "<h3>Return Processed Successfully</h3>";
    $message .= "<div class='receipt'>";
    $message .= "<p><strong>Vehicle:</strong> {$rental_info['model']} ({$rental_info['license_no']})</p>";
    $message .= "<p><strong>Return Date:</strong> " . date('F j, Y', strtotime($return_date)) . "</p>";
    
    $scheduled_return = date('F j, Y', strtotime($rental_info['Sdate'] . " + {$rental_info['Nodays']} days"));
    $message .= "<p><strong>Scheduled Return:</strong> $scheduled_return</p>";
    
    if ($charge_details['late_days'] > 0) {
        $message .= "<p><strong>Days Late:</strong> " . number_format($charge_details['late_days'], 1) . "</p>";
    }
    
    $message .= "<h4>Charges</h4>";
    $message .= "<table class='charges-table'>";
    $message .= "<tr><td>Late Fee:</td><td>$" . number_format($charge_details['late_fee'], 2) . "</td></tr>";
    $message .= "<tr><td>Fuel Charge:</td><td>$" . number_format($charge_details['fuel_fee'], 2) . "</td></tr>";
    $message .= "<tr><td>Damage Fee:</td><td>$" . number_format($charge_details['damage_fee'], 2) . "</td></tr>";
    $message .= "<tr class='total-row'><td><strong>Total Charges:</strong></td><td><strong>$" . number_format($charge_details['total'], 2) . "</strong></td></tr>";
    $message .= "<tr><td>Amount Paid:</td><td>$" . number_format($payment_amount, 2) . "</td></tr>";
    
    if ($payment_amount < $charge_details['total']) {
        $balance = $charge_details['total'] - $payment_amount;
        $message .= "<tr class='balance-row'><td><strong>Balance Due:</strong></td><td><strong>$" . number_format($balance, 2) . "</strong></td></tr>";
    }
    
    $message .= "</table>";
    $message .= "<p class='thank-you'>Thank you for choosing MOBBY CARS!</p>";
    $message .= "</div>";
    
    return $message;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Vehicle - MOBBY CARS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Your existing CSS styles remain unchanged */
        :root {
            --primary-color: #7b7fed;
            --primary-hover: #6a6ed8;
            --error-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --text-color: #333;
            --light-text: #555;
            --border-color: #ddd;
            --bg-color:  #7b7fed;
        }
        
        
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--bg-color);
        }
        
        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        h1, h2, h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-error {
            background-color: rgba(231, 76, 60, 0.1);
            border-left: 4px solid var(--error-color);
            color: var(--error-color);
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            border-left: 4px solid var(--success-color);
        }
        
        .alert-success h3 {
            margin-top: 0;
        }
        
        .receipt {
            background-color: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
        }
        
        .charges-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .charges-table td {
            padding: 8px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .charges-table tr:last-child td {
            border-bottom: none;
        }
        
        .total-row td {
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .balance-row td {
            color: var(--error-color);
            font-weight: bold;
        }
        
        .thank-you {
            text-align: center;
            font-style: italic;
            margin-top: 20px;
            color: var(--light-text);
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        select, input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        select:focus, input:focus, textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(123, 127, 237, 0.2);
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s;
            text-align: center;
        }
        
        .btn:hover {
            background-color: var(--primary-hover);
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .rental-card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: var(--bg-color);
        }
        
        .rental-card h4 {
            margin-top: 0;
            color: var(--primary-color);
        }
        
        .rental-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .detail-item {
            margin-bottom: 5px;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--light-text);
        }
        
        .no-rentals {
            text-align: center;
            padding: 30px;
            color: var(--light-text);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
                margin: 10px;
            }
            
            .rental-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-car"></i> Return Rental Vehicle</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if (empty($active_rentals)): ?>
            <div class="no-rentals">
                <i class="fas fa-car fa-3x" style="color: var(--primary-color); margin-bottom: 15px;"></i>
                <h3>No Active Rentals Found</h3>
                <p>You don't have any vehicles currently rented.</p>
                <a href="book.php" class="btn">Rent a Vehicle</a>
            </div>
        <?php else: ?>
            <form method="post" id="returnForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                
                <div class="form-section">
                    <h2><i class="fas fa-clipboard-list"></i> Select Rental</h2>
                    <div class="form-group">
                        <label for="rental_id">Your Active Rentals:</label>
                        <select name="rental_id" id="rental_id" required>
                            <option value="">-- Select a rental --</option>
                            <?php foreach ($active_rentals as $rental): ?>
                                <option value="<?= htmlspecialchars($rental['Rid']) ?>" <?= isset($_POST['rental_id']) && $_POST['rental_id'] == $rental['Rid'] ? 'selected' : '' ?>>
                                    #<?= htmlspecialchars($rental['Rid']) ?> - <?= htmlspecialchars($rental['model']) ?> (<?= htmlspecialchars($rental['license_no']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php foreach ($active_rentals as $rental): ?>
                        <div class="rental-card" id="rental-<?= htmlspecialchars($rental['Rid']) ?>" style="<?= isset($_POST['rental_id']) && $_POST['rental_id'] == $rental['Rid'] ? '' : 'display: none;' ?>">
                            <h4><?= htmlspecialchars($rental['model']) ?> (<?= htmlspecialchars($rental['license_no']) ?>)</h4>
                            <div class="rental-details">
                                <div class="detail-item">
                                    <span class="detail-label">Pickup Date:</span>
                                    <span><?= date('M j, Y', strtotime($rental['Sdate'])) ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Scheduled Return:</span>
                                    <span><?= date('M j, Y', strtotime($rental['Sdate'] . " + {$rental['Nodays']} days")) ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Rental Days:</span>
                                    <span><?= htmlspecialchars($rental['Nodays']) ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Vehicle Type:</span>
                                    <span><?= htmlspecialchars($rental['Ctype']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Rest of your form remains unchanged -->
                <div class="form-section">
                    <h2><i class="fas fa-calendar-check"></i> Return Details</h2>
                    <div class="form-group">
                        <label for="return_date">Return Date:</label>
                        <input type="date" name="return_date" id="return_date" 
                               value="<?= isset($_POST['return_date']) ? htmlspecialchars($_POST['return_date']) : date('Y-m-d') ?>" 
                               min="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="fuel_level">Fuel Level on Return:</label>
                        <select name="fuel_level" id="fuel_level" required>
                            <option value="full" <?= isset($_POST['fuel_level']) && $_POST['fuel_level'] == 'full' ? 'selected' : '' ?>>Full (no charge)</option>
                            <option value="3/4" <?= isset($_POST['fuel_level']) && $_POST['fuel_level'] == '3/4' ? 'selected' : '' ?>>3/4 Tank ($25 charge)</option>
                            <option value="1/2" <?= isset($_POST['fuel_level']) && $_POST['fuel_level'] == '1/2' ? 'selected' : '' ?>>1/2 Tank ($50 charge)</option>
                            <option value="1/4" <?= isset($_POST['fuel_level']) && $_POST['fuel_level'] == '1/4' ? 'selected' : '' ?>>1/4 Tank ($75 charge)</option>
                            <option value="empty" <?= isset($_POST['fuel_level']) && $_POST['fuel_level'] == 'empty' ? 'selected' : '' ?>>Empty ($100 charge)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2><i class="fas fa-car-crash"></i> Vehicle Condition</h2>
                    <div class="form-group">
                        <label for="damage_severity">Damage Severity:</label>
                        <select name="damage_severity" id="damage_severity" required>
                            <option value="none" <?= isset($_POST['damage_severity']) && $_POST['damage_severity'] == 'none' ? 'selected' : '' ?>>None (no charge)</option>
                            <option value="minor" <?= isset($_POST['damage_severity']) && $_POST['damage_severity'] == 'minor' ? 'selected' : '' ?>>Minor ($50 charge)</option>
                            <option value="moderate" <?= isset($_POST['damage_severity']) && $_POST['damage_severity'] == 'moderate' ? 'selected' : '' ?>>Moderate ($200 charge)</option>
                            <option value="major" <?= isset($_POST['damage_severity']) && $_POST['damage_severity'] == 'major' ? 'selected' : '' ?>>Major ($500 charge)</option>
                            <option value="severe" <?= isset($_POST['damage_severity']) && $_POST['damage_severity'] == 'severe' ? 'selected' : '' ?>>Severe ($1000 charge)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="damage_report">Damage Description (if any):</label>
                        <textarea name="damage_report" id="damage_report" placeholder="Please describe any damage to the vehicle"><?= isset($_POST['damage_report']) ? htmlspecialchars($_POST['damage_report']) : '' ?></textarea>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2><i class="fas fa-credit-card"></i> Payment Information</h2>
                    <div class="form-group">
                        <label for="payment_method">Payment Method:</label>
                        <select name="payment_method" id="payment_method" required>
                            <option value="cash" <?= isset($_POST['payment_method']) && $_POST['payment_method'] == 'cash' ? 'selected' : '' ?>>Cash</option>
                            <option value="credit" <?= isset($_POST['payment_method']) && $_POST['payment_method'] == 'credit' ? 'selected' : '' ?>>Credit Card</option>
                            <option value="debit" <?= isset($_POST['payment_method']) && $_POST['payment_method'] == 'debit' ? 'selected' : '' ?>>Debit Card</option>
                            <option value="mpesa" <?= isset($_POST['payment_method']) && $_POST['payment_method'] == 'mpesa' ? 'selected' : '' ?>>M-Pesa</option>
                            <option value="bank_transfer" <?= isset($_POST['payment_method']) && $_POST['payment_method'] == 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_amount">Payment Amount ($):</label>
                        <input type="number" name="payment_amount" id="payment_amount" 
                            min="0" step="0.01" 
                            value="0.00" required readonly>
                        <small class="charge-breakdown" id="chargeBreakdown"></small>
                    </div>
                </div>
                
                <button type="submit" name="process_return" class="btn btn-block">
                    <i class="fas fa-check-circle"></i> Process Return
                </button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // Show/hide rental cards based on selection
        document.getElementById('rental_id').addEventListener('change', function() {
            const rentalId = this.value;
            document.querySelectorAll('.rental-card').forEach(card => {
                card.style.display = 'none';
            });
            
            if (rentalId) {
                document.getElementById('rental-' + rentalId).style.display = 'block';
            }
        });
        
        // Initialize to show selected rental if form was submitted with errors
        <?php if (isset($_POST['rental_id'])): ?>
            document.getElementById('rental-<?= htmlspecialchars($_POST['rental_id']) ?>').style.display = 'block';
        <?php endif; ?>
        
        // Set minimum date for return date (today)
        document.getElementById('return_date').min = new Date().toISOString().split('T')[0];

        // Calculate charges based on selections
        function calculateCharges() {
            // Get selected values
            const fuelLevel = document.getElementById('fuel_level').value;
            const damageSeverity = document.getElementById('damage_severity').value;
            
            // Define charge tables (same as PHP)
            const fuelPrices = {
                'full': 0,
                '3/4': 25,
                '1/2': 50,
                '1/4': 75,
                'empty': 100
            };
            
            const damagePrices = {
                'none': 0,
                'minor': 50,
                'moderate': 200,
                'major': 500,
                'severe': 1000
            };
            
            // Calculate totals
            const fuelCharge = fuelPrices[fuelLevel];
            const damageCharge = damagePrices[damageSeverity];
            const total = fuelCharge + damageCharge;
            
            // Update payment amount field
            document.getElementById('payment_amount').value = total.toFixed(2);
            
            // Display breakdown (optional)
            const breakdown = `Fuel Charge: $${fuelCharge.toFixed(2)} + Damage Fee: $${damageCharge.toFixed(2)} = Total: $${total.toFixed(2)}`;
            document.getElementById('chargeBreakdown').textContent = breakdown;
        }

        // Add event listeners to trigger calculation
        document.getElementById('fuel_level').addEventListener('change', calculateCharges);
        document.getElementById('damage_severity').addEventListener('change', calculateCharges);

        // Calculate initial charges when page loads
        window.addEventListener('load', calculateCharges);
    </script>
</body>
</html>
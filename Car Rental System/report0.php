<?php
session_start();
include "connect.php";
require_once __DIR__ . '/check_admin.php';

// Validate date range
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$report_type = $_GET['report_type'] ?? 'rentals';
$vehicle_type = $_GET['vehicle_type'] ?? '';

// Ensure end date is not before start date
if (strtotime($end_date) < strtotime($start_date)) {
    $_SESSION['error'] = "End date cannot be before start date";
    header("Location: report.php");
    exit();
}

// Base query conditions - modified to handle invalid dates (0000-00-00)
$conditions = ["(r.Sdate BETWEEN '$start_date' AND '$end_date' OR (r.Sdate = '0000-00-00' AND DATE(r.created_at) BETWEEN '$start_date' AND '$end_date'))"];
if(!empty($vehicle_type)) {
    $conditions[] = "r.Ctype = '$vehicle_type'";
}
$where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Initialize report data
$report_data = [];
$has_data = false;
$chart_labels = [];
$chart_values = [];

// Fetch data based on report type
switch($report_type) {
    case 'rentals':
        $query = "SELECT 
            r.Rid, 
            r.user_id, 
            r.license_no, 
            r.Ctype, 
            r.Rtype, 
            IF(r.Sdate = '0000-00-00', DATE(r.created_at), r.Sdate) AS Sdate,
            r.Nodays, 
            r.Noweeks, 
            r.total_price, 
            IFNULL(r.payment_method, 'Not specified') AS payment_method, 
            r.status,
            CONCAT(IFNULL(u.fname, ''), ' ', IFNULL(u.lname, '')) AS customer_name, 
            IFNULL(cr.model, 'Unknown Model') AS model,
            r.license_no AS car_license,
            IFNULL(rr.daily_rate, 0) AS daily_rate,
            IFNULL(rr.weekly_rate, 0) AS weekly_rate
        FROM renting r
        LEFT JOIN users u ON r.user_id = u.user_id
        LEFT JOIN cars cr ON r.license_no = cr.license_no
        LEFT JOIN rental_rates rr ON r.Ctype = rr.Ctype
        $where_clause
        ORDER BY IF(r.Sdate = '0000-00-00', r.created_at, r.Sdate) DESC";
        $result = mysqli_query($connect, $query);
        if($result && mysqli_num_rows($result) > 0) {
            $report_data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            $has_data = true;
        }
        break;
        
    case 'revenue':
        $query = "SELECT 
            r.Ctype, 
            COUNT(*) AS rental_count, 
            SUM(r.total_price) AS total_revenue,
            AVG(r.Nodays) AS avg_duration,
            IFNULL(rr.daily_rate, 0) AS daily_rate, 
            IFNULL(rr.weekly_rate, 0) AS weekly_rate,
            AVG(r.total_price/r.Nodays) AS avg_daily_rate_charged
        FROM renting r
        LEFT JOIN rental_rates rr ON r.Ctype = rr.Ctype
        $where_clause
        GROUP BY r.Ctype";
        $result = mysqli_query($connect, $query);
        if($result && mysqli_num_rows($result) > 0) {
            $report_data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            $has_data = true;
            
            // Prepare chart data
            $chart_query = "SELECT 
                IF(r.Sdate = '0000-00-00', DATE(r.created_at), DATE(r.Sdate)) AS day, 
                SUM(r.total_price) AS daily_revenue
            FROM renting r
            $where_clause
            GROUP BY day
            ORDER BY day";
            $chart_result = mysqli_query($connect, $chart_query);
            if($chart_result) {
                $chart_data = mysqli_fetch_all($chart_result, MYSQLI_ASSOC);
                $chart_labels = array_column($chart_data, 'day');
                $chart_values = array_column($chart_data, 'daily_revenue');
            }
        }
        break;
        
    case 'vehicle_utilization':
        $query = "SELECT 
            IFNULL(cr.model, 'Unknown Model') AS model, 
            r.license_no, 
            r.Ctype,
            SUM(r.Nodays) AS rental_days,
            DATEDIFF('$end_date', '$start_date') + 1 AS period_days,
            (SUM(r.Nodays) / (DATEDIFF('$end_date', '$start_date') + 1)) * 100 AS utilization_rate,
            SUM(r.total_price) AS revenue,
            IFNULL(rr.daily_rate, 0) AS daily_rate, 
            IFNULL(rr.weekly_rate, 0) AS weekly_rate
        FROM renting r
        LEFT JOIN cars cr ON r.license_no = cr.license_no
        LEFT JOIN rental_rates rr ON r.Ctype = rr.Ctype
        $where_clause
        GROUP BY r.license_no, r.Ctype";
        $result = mysqli_query($connect, $query);
        if($result && mysqli_num_rows($result) > 0) {
            $report_data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            $has_data = true;
        }
        break;
}

// Calculate metrics - updated to handle invalid dates
$metrics_query = "SELECT 
    SUM(r.total_price) AS total_revenue,
    SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) AS completed_rentals,
    SUM(CASE WHEN r.status = 'active' THEN 1 ELSE 0 END) AS active_rentals,
    SUM(CASE WHEN r.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_rentals
FROM renting r
$where_clause";
$metrics_result = mysqli_query($connect, $metrics_query);
$metrics = $metrics_result ? mysqli_fetch_assoc($metrics_result) : [];

$total_revenue = $metrics['total_revenue'] ?? 0;
$completed_rentals = $metrics['completed_rentals'] ?? 0;
$active_rentals = $metrics['active_rentals'] ?? 0;
$cancelled_rentals = $metrics['cancelled_rentals'] ?? 0;

// Calculate utilization rate - updated to handle invalid dates
$utilization_query = "SELECT 
    (SUM(r.Nodays) / (COUNT(DISTINCT r.license_no) * (DATEDIFF('$end_date', '$start_date') + 1))) * 100 AS utilization_rate
FROM renting r
$where_clause";
$utilization_result = mysqli_query($connect, $utilization_query);
$utilization = $utilization_result ? mysqli_fetch_assoc($utilization_result) : [];
$utilization_rate = round($utilization['utilization_rate'] ?? 0, 2);

// Handle Excel export request
if(isset($_GET['export']) && $_GET['export'] == 'excel' && $has_data) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="rental_report_'.date('Y-m-d').'.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add report header with filter info
    fputcsv($output, ['MOBBY CARS - Rental Report']);
    fputcsv($output, ['Report Type:', ucfirst($report_type) . ' Report']);
    fputcsv($output, ['Date Range:', $start_date . ' to ' . $end_date]);
    if(!empty($vehicle_type)) {
        fputcsv($output, ['Vehicle Type:', $vehicle_type]);
    }
    fputcsv($output, ['Generated On:', date('Y-m-d H:i:s')]);
    fputcsv($output, []); // Empty row
    
    switch($report_type) {
        case 'rentals':
            fputcsv($output, ['Booking ID', 'Customer', 'Vehicle', 'License Plate', 'Type', 'Start Date', 'Days', 'Total Price', 'Status']);
            foreach($report_data as $row) {
                fputcsv($output, [
                    $row['Rid'],
                    $row['customer_name'],
                    $row['model'],
                    $row['car_license'],
                    $row['Ctype'],
                    $row['Sdate'],
                    $row['Nodays'],
                    number_format($row['total_price'], 2),
                    ucfirst($row['status'])
                ]);
            }
            break;
            
        case 'revenue':
            fputcsv($output, ['Vehicle Type', 'Rental Count', 'Total Revenue', 'Avg. Duration (days)', 'Avg. Daily Rate']);
            foreach($report_data as $row) {
                fputcsv($output, [
                    $row['Ctype'],
                    $row['rental_count'],
                    number_format($row['total_revenue'], 2),
                    round($row['avg_duration'], 1),
                    number_format($row['avg_daily_rate_charged'], 2)
                ]);
            }
            break;
            
        case 'vehicle_utilization':
            fputcsv($output, ['Vehicle', 'License Plate', 'Type', 'Rental Days', 'Utilization Rate', 'Revenue Generated']);
            foreach($report_data as $row) {
                fputcsv($output, [
                    $row['model'],
                    $row['license_no'],
                    $row['Ctype'],
                    $row['rental_days'],
                    round($row['utilization_rate'], 1) . '%',
                    number_format($row['revenue'], 2)
                ]);
            }
            break;
    }
    
    fclose($output);
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Rental Reports - MOBBY CARS</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
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
            line-height: 1.6;
            color: #333;
            background-color: #7b7fed;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 25px;
        }
        
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e1e5eb;
        }
        
        .report-header h1 {
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 28px;
        }
        
        .report-header p {
            color: #7f8c8d;
            margin-top: 0;
        }
        
        .report-filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 30px;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .filter-group label {
            min-width: 120px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .filter-group input[type="date"],
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            margin-right: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            margin-right: 10px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-generate {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-generate:hover {
            background-color: #6a6ed8;
        }
        
        .btn-export {
            background-color: #6a6ed8;
            color: white;
        }
        
        .btn-export:hover {
            background-color:rgb(152, 155, 225);
        }
        
        .btn-export:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        
        .metrics-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .metric-card {
            background: white;
            border-radius: 6px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-top: 4px solid var(--primary-color);
            text-align: center;
        }
        
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .report-table th {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 15px;
            text-align: left;
        }
        
        .report-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e1e5eb;
        }
        
        .report-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-completed {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .progress-bar {
            width: 100%;
            background-color: #e9ecef;
            border-radius: 4px;
            height: 20px;
            position: relative;
        }
        
        .progress-bar div {
            height: 100%;
            border-radius: 4px;
            background-color: #4CAF50;
        }
        
        .progress-bar span {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 30px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .filter-group {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-group label {
                margin-bottom: 5px;
            }
            
            .metrics-dashboard {
                grid-template-columns: 1fr;
            }
            
            .report-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="report-header">
            <h1>MOBBY CARS Rental Reports</h1>
            <p>Comprehensive analysis of rental business performance</p>
        </div>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert">
                <?= htmlspecialchars($_SESSION['error']); ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="report-filters">
            <form method="get" action="report.php">
                <div class="filter-group">
                    <label>Date Range:</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
                    <span>to</span>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required>
                </div>
                
                <div class="filter-group">
                    <label>Report Type:</label>
                    <select name="report_type">
                        <option value="rentals" <?= $report_type == 'rentals' ? 'selected' : '' ?>>Rental Transactions</option>
                        <option value="revenue" <?= $report_type == 'revenue' ? 'selected' : '' ?>>Revenue Summary</option>
                        <option value="vehicle_utilization" <?= $report_type == 'vehicle_utilization' ? 'selected' : '' ?>>Vehicle Utilization</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Vehicle Type:</label>
                    <select name="vehicle_type">
                        <option value="">All Types</option>
                        <option value="Compact" <?= $vehicle_type == 'Compact' ? 'selected' : '' ?>>Compact</option>
                        <option value="Medium" <?= $vehicle_type == 'Medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="Large" <?= $vehicle_type == 'Large' ? 'selected' : '' ?>>Large</option>
                        <option value="SUV" <?= $vehicle_type == 'SUV' ? 'selected' : '' ?>>SUV</option>
                        <option value="Van" <?= $vehicle_type == 'Van' ? 'selected' : '' ?>>Van</option>
                        <option value="Truck" <?= $vehicle_type == 'Truck' ? 'selected' : '' ?>>Truck</option>
                    </select>
                </div>
                
               <!-- <button type="submit" class="btn btn-generate">Generate Report</button> -->
                <?php if($has_data): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'excel'])) ?>" class="btn btn-export">Export to Excel</a>
                <?php else: ?>
                    <button type="button" class="btn btn-export" disabled>Generate Report</button>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="metrics-dashboard">
            <div class="metric-card">
                <h3>Total Revenue</h3>
                <p>$<?= number_format($total_revenue, 2) ?></p>
                <small><?= date('M j, Y', strtotime($start_date)) ?> - <?= date('M j, Y', strtotime($end_date)) ?></small>
            </div>
            
            <div class="metric-card">
                <h3>Completed Rentals</h3>
                <p><?= $completed_rentals ?></p>
                <small><?= date('M j, Y', strtotime($start_date)) ?> - <?= date('M j, Y', strtotime($end_date)) ?></small>
            </div>
            
            <div class="metric-card">
                <h3>Active Rentals</h3>
                <p><?= $active_rentals ?></p>
                <small>Currently Ongoing</small>
            </div>
            
            <div class="metric-card">
                <h3>Vehicle Utilization</h3>
                <p><?= $utilization_rate ?>%</p>
                <small><?= date('M j, Y', strtotime($start_date)) ?> - <?= date('M j, Y', strtotime($end_date)) ?></small>
            </div>
        </div>
        
        <?php if($report_type == 'revenue' && !empty($chart_labels)): ?>
            <div class="chart-container">
                <canvas id="revenueChart"></canvas>
            </div>
        <?php endif; ?>
        
        <?php if($has_data): ?>
            <?php if($report_type == 'rentals'): ?>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Customer</th>
                            <th>Vehicle</th>
                            <th>Type</th>
                            <th>Rental Period</th>
                            <th>Total Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($report_data as $rental): ?>
                        <tr>
                            <td><?= htmlspecialchars($rental['Rid']) ?></td>
                            <td><?= htmlspecialchars($rental['customer_name']) ?></td>
                            <td><?= htmlspecialchars($rental['model']) ?></td>
                            <td><?= htmlspecialchars($rental['Ctype']) ?></td>
                            <td>
                                <?= date('M j', strtotime($rental['Sdate'])) ?> - 
                                <?= date('M j', strtotime($rental['Sdate'] . " + {$rental['Nodays']} days")) ?>
                                (<?= $rental['Nodays'] ?> days)
                            </td>
                            <td>$<?= number_format($rental['total_price'], 2) ?></td>
                            <td>
                                <span class="status-badge status-<?= $rental['status'] ?>">
                                    <?= ucfirst($rental['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            
            <?php elseif($report_type == 'revenue'): ?>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Vehicle Type</th>
                            <th>Rental Count</th>
                            <th>Total Revenue</th>
                            <th>Avg. Duration</th>
                            <th>Avg. Daily Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($report_data as $type): ?>
                        <tr>
                            <td><?= htmlspecialchars($type['Ctype']) ?></td>
                            <td><?= number_format($type['rental_count']) ?></td>
                            <td>$<?= number_format($type['total_revenue'], 2) ?></td>
                            <td><?= round($type['avg_duration'], 1) ?> days</td>
                            <td>$<?= number_format($type['avg_daily_rate_charged'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            
            <?php elseif($report_type == 'vehicle_utilization'): ?>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>License Plate</th>
                            <th>Type</th>
                            <th>Rental Days</th>
                            <th>Utilization Rate</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($report_data as $vehicle): ?>
                        <tr>
                            <td><?= htmlspecialchars($vehicle['model']) ?></td>
                            <td><?= htmlspecialchars($vehicle['license_no']) ?></td>
                            <td><?= htmlspecialchars($vehicle['Ctype']) ?></td>
                            <td><?= $vehicle['rental_days'] ?></td>
                            <td>
                                <div class="progress-bar">
                                    <div style="width: <?= min(100, $vehicle['utilization_rate']) ?>%"></div>
                                    <span><?= round($vehicle['utilization_rate'], 1) ?>%</span>
                                </div>
                            </td>
                            <td>$<?= number_format($vehicle['revenue'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-data">
                <h3>No data found for the selected filters</h3>
                <p>Try adjusting your date range or other filter criteria</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
    <?php if($report_type == 'revenue' && !empty($chart_labels)): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Daily Revenue ($)',
                    data: <?= json_encode($chart_values) ?>,
                    backgroundColor: '#7b7fed',
                    borderColor: '#6a6ed8',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Daily Revenue Breakdown'
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Amount ($)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                }
            }
        });
    });
    <?php endif; ?>
    </script>
</body>


</html>
<?php
include "connect.php";
require_once __DIR__ . '/check_admin.php';

// Handle car deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_car"])) {
    $license_no = mysqli_real_escape_string($connect, $_POST["license_no"]);
    
    // Check for active rentals
    $check_rental = "SELECT * FROM rental 
                    WHERE license_no = '$license_no' 
                    AND status = 'active'";
    $rental_result = mysqli_query($connect, $check_rental);
    
    if (mysqli_num_rows($rental_result) > 0) {
        echo "<script>
                alert('Cannot delete car. It is currently rented out.');
                document.getElementById('license_no').focus();
              </script>";
    } else {
        // Check for future bookings
        $check_future = "SELECT * FROM rental 
                        WHERE license_no = '$license_no' 
                        AND Sdate > CURDATE()";
        $future_result = mysqli_query($connect, $check_future);
        
        if (mysqli_num_rows($future_result) > 0) {
            echo "<script>
                    alert('Cannot delete car. It has future bookings.');
                    document.getElementById('license_no').focus();
                  </script>";
        } else {
            $delete_query = "DELETE FROM cars WHERE license_no = '$license_no'";
            $delete_result = mysqli_query($connect, $delete_query);
            
            if ($delete_result) {
                echo "<script>
                        alert('Car deleted successfully!');
                        document.getElementById('deleteForm').reset();
                      </script>";
            } else {
                echo "<script>
                        alert('Error deleting car: " . addslashes(mysqli_error($connect)) . "');
                        document.getElementById('license_no').focus();
                      </script>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Management | Mobby Cars</title>
    <link href="pstyles.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary-color: #7b7fed;;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #7b7fed;;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        
        marquee {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 0;
            margin-bottom: 20px;
        }
        
        .logoBackground {
            background-color: var(--primary-color);
            padding: 15px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h6 {
            color: white;
            font-size: 24px;
            margin: 0;
        }
        
        .container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            gap: 30px;
        }
        
        .leftContent, .rightContent {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 25px;
            flex: 1;
            min-width: 300px;
            margin-bottom: 30px;
        }
        
        h1 {
            color: var(--primary-color);
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 10px;
            margin-top: 0;
            font-size: 22px;
        }
        
        form {
            margin-top: 20px;
        }
        
        input[type="text"], select {
            width: 100%;
            padding: 10px;
            margin: 8px 0 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        
        input[type="submit"] {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            cursor: pointer;
            border-radius: 4px;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        input[type="submit"]:hover {
            background-color: #2980b9;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-group {
            flex: 1;
        }
        
        .search-box {
            margin-bottom: 30px;
        }
        
        .search-box input {
            width: 70%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
        }
        
        .search-box button {
            padding: 10px 15px;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .leftContent, .rightContent {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <marquee>
        <h3><b style="color: white; font-size:25px;">WELCOME TO MOBBY CARS. RENT YOUR CAR, ENJOY THE RIDE. HURRY HURRY!!!</b></h3>
    </marquee>
    <div class="logoBackground">
        <div class="logo"><h6><i class="fas fa-car"></i> CAR MANAGEMENT <i class="fas fa-tools"></i></h6></div>
    </div>
    
    <div class="container">
        <div class="leftContent">
            <div class="search-box">
                <input type="text" id="carSearch" placeholder="Search cars by license number..." onkeyup="searchCars()">
                <button onclick="searchCars()"><i class="fas fa-search"></i></button>
            </div>
            
            <form action="registercar.php" method="post" id="addCarForm">
                <h1><i class="fas fa-car-alt"></i> ADD NEW CAR</h1>
                <div class="form-row">
                    <div class="form-group">
                        <label for="lno">License No:</label>
                        <input type="text" name="lno" id="lno" required />
                    </div>
                    <div class="form-group">
                        <label for="model">Model:</label>
                        <input type="text" name="model" id="model" required />
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="year">Year:</label>
                        <input type="text" name="year" id="year" required />
                    </div>
                    <div class="form-group">
                        <label for="Cartype">Car Type:</label>
                        <select name="Cartype" id="Cartype" required>
                            <option value="">Select Type</option>
                            <option value="Compact">Compact</option>
                            <option value="Medium">Medium</option>
                            <option value="Large">Large</option>
                            <option value="SUV">SUV</option>
                            <option value="Van">Van</option>
                            <option value="Truck">Truck</option>
                        </select>
                    </div>
                </div>
                
                <input type="submit" name="submit" value="Register Car">
            </form>
            
            <br><br>
            
            <form method="post" id="deleteForm">
                <h1><i class="fas fa-trash-alt"></i> DELETE CAR</h1>
                <div class="form-group">
                    <label for="license_no">License No:</label>
                    <input type="text" name="license_no" id="license_no" required />
                </div>
                <input type="submit" name="delete_car" value="Delete Car" onclick="return confirmDelete()">
            </form>
        </div>
        
        <div class="rightContent">
            <h1><i class="fas fa-eye"></i> VIEW CARS & RATES</h1>
            <form action="viewcar.php" method="post" id="viewForm">
                <div class="form-group">
                    <label for="Ctype">Select Car Type to View:</label>
                    <select name="Ctype" id="Ctype">
                        <option value="">All Types</option>
                        <option value="Compact">Compact</option>
                        <option value="Medium">Medium</option>
                        <option value="Large">Large</option>
                        <option value="SUV">SUV</option>
                        <option value="Van">Van</option>
                        <option value="Truck">Truck</option>
                    </select>
                </div>
                <input type="submit" name="submit1" value="View Cars">
            </form>

            <h1><i class="fas fa-dollar-sign"></i> UPDATE RENTAL RATES</h1>
            <form action="update.php" method="post" id="updateForm">
                <div class="form-group">
                    <label for="updateCtype">Select Car Type to Update:</label>
                    <select name="Ctype" id="updateCtype" required>
                        <option value="">Select Type</option>
                        <option value="Compact">Compact</option>
                        <option value="Medium">Medium</option>
                        <option value="Large">Large</option>
                        <option value="SUV">SUV</option>
                        <option value="Van">Van</option>
                        <option value="Truck">Truck</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="udrate">Daily Rate ($):</label>
                        <input type="number" step="0.01" name="udrate" id="udrate" required />
                    </div>
                    <div class="form-group">
                        <label for="uwrate">Weekly Rate ($):</label>
                        <input type="number" step="0.01" name="uwrate" id="uwrate" required />
                    </div>
                </div>
                
                <input type="submit" name="submit1" value="Update Rates">
            </form>
        </div>
    </div>

    <script>
    // Confirm before deleting a car
    function confirmDelete() {
        const licenseNo = document.getElementById('license_no').value;
        if (!licenseNo) {
            alert('Please enter a license number');
            return false;
        }
        return confirm(`Are you sure you want to delete car with license ${licenseNo}? This action cannot be undone.`);
    }
    
    // Search cars function (would need backend support)
    function searchCars() {
        const searchTerm = document.getElementById('carSearch').value.trim();
        if (searchTerm.length > 2) {
            // Here you would typically make an AJAX call to search for cars
            console.log(`Searching for cars with license: ${searchTerm}`);
            // Example AJAX call:
            /*
            $.ajax({
                url: 'search_cars.php',
                method: 'POST',
                data: { search: searchTerm },
                success: function(response) {
                    // Handle the response (update a results div, etc.)
                }
            });
            */
        }
    }
    
    // Form validation for add car form
    document.getElementById('addCarForm').addEventListener('submit', function(e) {
        const year = document.getElementById('year').value;
        if (year.length !== 4 || isNaN(year)) {
            alert('Please enter a valid 4-digit year');
            e.preventDefault();
            document.getElementById('year').focus();
        }
    });
    
    // Form validation for update rates form
    document.getElementById('updateForm').addEventListener('submit', function(e) {
        const dailyRate = document.getElementById('udrate').value;
        const weeklyRate = document.getElementById('uwrate').value;
        
        if (parseFloat(dailyRate) <= 0 || parseFloat(weeklyRate) <= 0) {
            alert('Rates must be positive numbers');
            e.preventDefault();
        }
        
        if (parseFloat(weeklyRate) <= parseFloat(dailyRate) * 5) {
            if (!confirm('Weekly rate seems low compared to daily rate. Continue?')) {
                e.preventDefault();
            }
        }
    });
    
    // Auto-focus license field on page load
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('lno').focus();
    });
    </script>
</body>
</html>
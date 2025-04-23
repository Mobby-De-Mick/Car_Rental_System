<?php
// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "car_rental";


$connect = mysqli_connect($servername, $username, $password, $dbname);

if (mysqli_connect_errno()) {
    die("Failed connecting to MySQL database. Invalid credentials: " . mysqli_connect_error() . " (" . mysqli_connect_errno() . ")");
}

$Cid = isset($_GET["cbid"]) ? mysqli_real_escape_string($connect, $_GET["cbid"]) : null; // Use $_GET instead of $_POST
$Sdate = isset($_GET["start_date"]) ? mysqli_real_escape_string($connect, $_GET["start_date"]) : null; // Use $_GET
$Ctype = isset($_GET["Ctype"]) ? mysqli_real_escape_string($connect, $_GET["Ctype"]) : null; // Use $_GET
$Rtype = isset($_GET["Rtype"]) ? mysqli_real_escape_string($connect, $_GET["Rtype"]) : null; // Use $_GET
$Nodays = isset($_GET["Days"]) ? (int)$_GET["Days"] : 0; // Use $_GET
$Noweeks = isset($_GET["Weeks"]) ? (int)$_GET["Weeks"] : 0; // Use $_GET
$Vehicle_id = isset($_GET["Vehicleid"]) ? mysqli_real_escape_string($connect, $_GET["Vehicleid"]) : null; // Use $_GET

// Calculate end date based on rental duration
if ($Noweeks >= 1) {
    $num = $Noweeks * 7;
    $D2 = date('Y-m-d', strtotime($Sdate . " + $num days"));
} else {
    $D2 = date('Y-m-d', strtotime($Sdate . " + $Nodays days"));
}

// Check if the cars table exists
$check_cars_table = mysqli_query($connect, "SHOW TABLES LIKE 'cars'");
if (mysqli_num_rows($check_cars_table) == 0) {
    die("The 'cars' table does not exist in the database. Please create it.");
}

// Check if the rental table exists
$check_rental_table = mysqli_query($connect, "SHOW TABLES LIKE 'rental'");
if (mysqli_num_rows($check_rental_table) == 0) {
    die("The 'rental' table does not exist in the database. Please create it.");
}

// Check if the customer exists
$check_customer = mysqli_query($connect, "SELECT * FROM customer WHERE Cid = '$Cid'");
if (mysqli_num_rows($check_customer) == 0) {
    die("<h3>Error: Invalid Customer ID. The customer does not exist.</h3>");
}

// Check if the vehicle exists
$check_vehicle = mysqli_query($connect, "SELECT * FROM cars WHERE license_no = '$Vehicle_id'");
if (mysqli_num_rows($check_vehicle) == 0) {
    die("<h3>Error: Invalid Vehicle ID. The vehicle does not exist.</h3>");
}

// Check vehicle availability
$res = "
    SELECT license_no 
    FROM cars 
    WHERE ctype = '$Ctype' 
    AND license_no NOT IN (
        SELECT Vehicle_id 
        FROM rental 
        WHERE Ctype = '$Ctype' 
        AND (
            (Sdate <= '$Sdate' AND DATE_ADD(Sdate, INTERVAL Nodays DAY) >= '$Sdate') OR
            (Sdate <= '$D2' AND DATE_ADD(Sdate, INTERVAL Nodays DAY) >= '$D2')
        )
    )
";
$result = mysqli_query($connect, $res);

if (!$result) {
    die("Error fetching data: " . mysqli_error($connect));
}

// Display availability results
echo "<!DOCTYPE html>
<html>
<head>
    <title>Car Availability</title>
    <style>
        body {
            background-color: #7b7fed; /* Set background color */
            font-family: Arial, sans-serif;
            color: #fff; /* Set text color to white for better contrast */
        }
        table {
            width: 80%;
            border-collapse: collapse;
            margin: 20px auto;
            background-color: #fff; /* Set table background to white */
            color: #000; /* Set table text color to black */
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2; /* Light gray for table headers */
        }
        h1, h2, h3 {
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Car Availability</h1>";

if (mysqli_num_rows($result) > 0) {
    echo "<br><h2>Congrats! Vehicle is available</h2><br>";
    echo "<h3>List of Available Vehicles</h3><br>";
    echo "<table border='1'>
            <tr>
                <th>License No</th>
                <th>Model</th>
                <th>Year</th>
                <th>Car Type</th>
            </tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        // Fetch additional details for each car
        $license_no = htmlspecialchars($row["license_no"]);
        $car_details = mysqli_query($connect, "SELECT model, year, ctype FROM cars WHERE license_no = '$license_no'");
        if ($car_details && $car_row = mysqli_fetch_assoc($car_details)) {
            echo "<tr>
                    <td>" . $license_no . "</td>
                    <td>" . htmlspecialchars($car_row["model"]) . "</td>
                    <td>" . htmlspecialchars($car_row["year"]) . "</td>
                    <td>" . htmlspecialchars($car_row["ctype"]) . "</td>
                  </tr>";
        }
    }
    echo "</table>";
} else {
    echo "<h3>No cars available for the selected criteria.</h3>";
}

// Insert rental record if a vehicle is selected
if ($Vehicle_id) 
{
    // Check if the customer exists
    $check_customer = mysqli_query($connect, "SELECT * FROM customer WHERE Cid = '$Cid'");
    if (mysqli_num_rows($check_customer) == 0) {
        die("<h3>Error: Invalid Customer ID. The customer does not exist.</h3>");
    }

    // Check if the vehicle exists
    $check_vehicle = mysqli_query($connect, "SELECT * FROM cars WHERE license_no = '$Vehicle_id'");
    if (mysqli_num_rows($check_vehicle) == 0) {
        die("<h3>Error: Invalid Vehicle ID. The vehicle does not exist.</h3>");
    }

    // Insert the rental record
    $res = "
        INSERT INTO rental (Cid, Vehicle_id, Ctype, Rtype, Sdate, Nodays, Noweeks) 
        VALUES ('$Cid', '$Vehicle_id', '$Ctype', '$Rtype', '$Sdate', '$Nodays', '$Noweeks')
    ";
    $result = mysqli_query($connect, $res);

    if ($result) {
        echo "<h3>Rental has been added successfully.</h3>";
    } else {
        echo "<h3>Error adding rental: " . mysqli_error($connect) . "</h3>";
    }
}

// Display active and scheduled rentals
$res2 = "SELECT Rid, Cid, Vehicle_id, Ctype, Rtype, Sdate, Nodays, Noweeks FROM rental";
$result2 = mysqli_query($connect, $res2);

if (!$result2) {
    die("Error fetching data: " . mysqli_error($connect));
}

echo "<h1>Active & Scheduled Rentals</h1><br><br>
      <center>
      <table border='1'>
        <tr>
            <th>RID</th>
            <th>Customer ID</th>
            <th>Vehicle ID</th>
            <th>Car Type</th>
            <th>Rent Type</th>
            <th>Start Date</th>
            <th>No of Days</th>
            <th>No of Weeks</th>
        </tr>";

if (mysqli_num_rows($result2) > 0) {
    while ($row2 = mysqli_fetch_assoc($result2)) {
        echo "<tr>
                <td>" . htmlspecialchars($row2["Rid"]) . "</td>
                <td>" . htmlspecialchars($row2["Cid"]) . "</td>
                <td>" . htmlspecialchars($row2["Vehicle_id"]) . "</td>
                <td>" . htmlspecialchars($row2["Ctype"]) . "</td>
                <td>" . htmlspecialchars($row2["Rtype"]) . "</td>
                <td>" . htmlspecialchars($row2["Sdate"]) . "</td>
                <td>" . htmlspecialchars($row2["Nodays"]) . "</td>
                <td>" . htmlspecialchars($row2["Noweeks"]) . "</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='8'>No active or scheduled rentals found.</td></tr>";
}

echo "</table></center></body></html>";

// Close the database connection
mysqli_close($connect);
?>
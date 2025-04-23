<?php
// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "car_rental";

// Create connection
$connect = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$connect) {
    die("<h3>Error: Unable to connect to database. " . mysqli_connect_error() . "</h3>");
}

// Sanitize and validate user input
$License_no = isset($_POST["lno"]) ? trim($_POST["lno"]) : null;
$Model = isset($_POST["model"]) ? trim($_POST["model"]) : null;
$Year = isset($_POST["year"]) ? trim($_POST["year"]) : null;
$Ctype = isset($_POST["Cartype"]) ? trim($_POST["Cartype"]) : null;

// Validate input
if (empty($License_no) || empty($Model) || empty($Year) || empty($Ctype)) {
    die("<h3>Error: All fields are required.</h3>");
}

if (!is_numeric($Year) || $Year < 1900 || $Year > date("Y")) {
    die("<h3>Error: Invalid year entered.</h3>");
}

// Check for duplicate License_no
$query = "SELECT license_no FROM cars WHERE license_no = ?";
$stmt = mysqli_prepare($connect, $query);
mysqli_stmt_bind_param($stmt, "s", $License_no);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    die("<h3>Error: A car with the license number '$License_no' already exists.</h3>");
}
mysqli_stmt_close($stmt);

// Insert car into the database using prepared statement
$query = "INSERT INTO cars (license_no, model, year, ctype) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($connect, $query);
mysqli_stmt_bind_param($stmt, "ssis", $License_no, $Model, $Year, $Ctype);

if (mysqli_stmt_execute($stmt)) {
    echo "<h3>New car has been successfully added!</h3>";
} else {
    echo "<h3>Error adding car: " . mysqli_error($connect) . "</h3>";
}

mysqli_stmt_close($stmt);
mysqli_close($connect);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register Car</title>
    <style>
        body {
            background-color: #7b7fed;
            font-family: Arial, sans-serif;
            color: #fff;
            padding: 20px;
        }
        h3 {
            color: #fff;
        }
    </style>
</head>
<body>
    <!-- Output messages will be displayed here -->
</body>
</html>

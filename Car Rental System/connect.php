<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "car_rental";

// Create connection with error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $connect = new mysqli($servername, $username, $password, $dbname);
    
    // Set charset to ensure proper encoding
    $connect->set_charset("utf8mb4");
    
} catch (mysqli_sql_exception $e) {
    // Log the error and display a user-friendly message
    error_log("Database connection failed: " . $e->getMessage());
    die("We're experiencing technical difficulties. Please try again later.");
}
?>
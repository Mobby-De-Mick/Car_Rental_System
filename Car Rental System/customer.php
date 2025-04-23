<?php
session_start();
include "connect.php";

// Redirect to signup if not logged in
if (!isset($_SESSION['logged_in'])) {
    header("Location: signup.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Car Rental - Customers</title>
    <link href="pstyles.css" rel="stylesheet" type="text/css" />
</head>
<body>
    <marquee><h3><b style="color: white; font-size:25px;">WELCOME TO MOBBY CARS. RENT YOUR CAR, ENJOY THE RIDE. HURRY HURRY!!!</b></h3></marquee>
    <div class="logoBackground">
        <div class="logo"><h6>CUSTOMERS<h6></div>
    </div>
    
    <div class="menu">
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="admin.php">Admin</a></li>
            <li id="active"><a href="">Customers</a></li>
            <li><a href="book.php">Book Car</a></li>
            <li><a href="return0.php">Return Car</a></li>
        </ul>
    </div>
    
    <div class="rightContent">
        <b><form action="viewcustomer.php" method="post">
        <h1> VIEW CUSTOMERS </h1>
        <input class="view" type="submit" value="View"/>
        </form></b>	
    </div>
</body>
</html>
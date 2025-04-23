<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<body>
<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "car_rental";

$connect = mysqli_connect($servername, $username, $password, $dbname);

if (mysqli_connect_errno()) {
    die("Failed connecting to MySQL database. Invalid credentials: " . mysqli_connect_error() . " (" . mysqli_connect_errno() . ")");
}

$Drate = mysqli_real_escape_string($connect, $_POST["udrate"]);
$Wrate = mysqli_real_escape_string($connect, $_POST["uwrate"]);
$Ctype = mysqli_real_escape_string($connect, $_POST["Ctype"]);

if (isset($_POST["udrate"]) && isset($_POST["uwrate"]) && isset($_POST["Ctype"]) && !empty($Drate) && !empty($Wrate) && !empty($Ctype)) {
    $res = "UPDATE rental_rates SET Drate = ?, Wrate = ? WHERE Ctype = ?";
    $stmt = mysqli_prepare($connect, $res);

    if ($stmt) {
        // Bind parameters to the prepared statement
        mysqli_stmt_bind_param($stmt, "dds", $Drate, $Wrate, $Ctype);

        // Execute the statement
        if (mysqli_stmt_execute($stmt)) {
            echo "<h1><center>" . htmlspecialchars($Ctype) . " Rates updated successfully!</center></h1>";
        } else {
            echo "<h1><center>Error updating rates: " . mysqli_error($connect) . "</center></h1>";
        }

        // Close the statement
        mysqli_stmt_close($stmt);
    } else {
        echo "<h1><center>Error preparing statement: " . mysqli_error($connect) . "</center></h1>";
    }
} else {
    echo "<h1><center>Invalid input. Please fill all fields.</center></h1>";
}

mysqli_close($connect);
?>
</body>
</html>
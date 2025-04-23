<?php
// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "car_rental";

// Create connection
$connect = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (mysqli_connect_errno()) {
    die("Failed connecting to MySQL database: " . mysqli_connect_error());
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Secure user input and check if values exist
    $fname = isset($_POST["fname"]) ? mysqli_real_escape_string($connect, trim($_POST["fname"])) : "";
    $lname = isset($_POST["lname"]) ? mysqli_real_escape_string($connect, trim($_POST["lname"])) : "";
    $Age = isset($_POST["Age"]) ? (int) $_POST["Age"] : 0;  // Ensure AGE is an integer
    $Mobile = isset($_POST["Mobile"]) ? mysqli_real_escape_string($connect, trim($_POST["Mobile"])) : "";
    $dlno = isset($_POST["dlno"]) ? mysqli_real_escape_string($connect, trim($_POST["dlno"])) : "";
    $insno = isset($_POST["insno"]) ? mysqli_real_escape_string($connect, trim($_POST["insno"])) : "";

    // Validate input
    if (empty($fname) || empty($lname) || $Age < 18 || empty($Mobile) || empty($dlno) || empty($insno)) {
        die("<h3>Error: All fields are required, and age must be 18 or above.</h3>");
    }
    
    if (!ctype_digit($Mobile) || strlen($Mobile) != 10) {
        die("<h3>Error: Invalid mobile number. It must be 10 digits.</h3>");
    }

    // Insert query using prepared statements
    $query = "INSERT INTO customer (fname, lname, Age, Mobile, dlno, Insno) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($connect, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssisss", $fname, $lname, $Age, $Mobile, $dlno, $insno);
        if (mysqli_stmt_execute($stmt)) {
            echo "<h3>New customer has been successfully added</h3><br><br>";

            // Retrieve Customer ID
            $query1 = "SELECT Cid FROM customer WHERE dlno = ?";
            $stmt2 = mysqli_prepare($connect, $query1);

            if ($stmt2) {
                mysqli_stmt_bind_param($stmt2, "s", $dlno);
                mysqli_stmt_execute($stmt2);
                mysqli_stmt_bind_result($stmt2, $Cid);
                mysqli_stmt_fetch($stmt2);

                if ($Cid) {
                    echo "<h3>Customer ID is: " . htmlspecialchars($Cid) . "</h3>";
                } else {
                    echo "<h3>Error: Unable to retrieve Customer ID.</h3>";
                }
                mysqli_stmt_close($stmt2);
            }
        } else {
            echo "<h3>Error: " . mysqli_stmt_error($stmt) . "</h3>";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "<h3>Database error: Unable to prepare statement.</h3>";
    }
}

// Close the database connection
mysqli_close($connect);
?>
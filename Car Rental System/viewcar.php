<?php
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
$Ctype = isset($_POST["Ctype"]) ? trim($_POST["Ctype"]) : null;

if (empty($Ctype)) {
    die("<h3>Error: Car type is required.</h3>");
}

// Fetch cars of the selected type using prepared statement
$query = "
    SELECT c.license_no, c.model, c.year, r.daily_rate, r.weekly_rate 
    FROM cars AS c 
    JOIN rental_rates AS r ON c.ctype = r.Ctype 
    WHERE c.ctype = ?
";

$stmt = mysqli_prepare($connect, $query);
mysqli_stmt_bind_param($stmt, "s", $Ctype);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    die("<h3>Error fetching data: " . mysqli_error($connect) . "</h3>");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Cars</title>
    <style>
        body {
            background-color: #7b7fed;
            font-family: Arial, sans-serif;
            color: #fff;
            padding: 20px;
        }

        table {
            width: 80%;
            border-collapse: collapse;
            margin: 20px auto;
            background-color: #fff;
            color: #000;
        }

        table, th, td {
            border: 1px solid black;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        h1 {
            text-align: center;
        }
    </style>
</head>
<body>
    <h1><?php echo htmlspecialchars($Ctype); ?> Cars</h1>
    <br><br>
    <center>
        <table border='1'>
            <tr>
                <th>License No</th>
                <th>Model</th>
                <th>Year</th>
                <th>Daily Rate</th>
                <th>Weekly Rate</th>
            </tr>
            <?php
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row["license_no"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["model"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["year"]) . "</td>";
                    echo "<td>$" . htmlspecialchars($row["daily_rate"]) . "</td>";
                    echo "<td>$" . htmlspecialchars($row["weekly_rate"]) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5'>No cars found for the selected type.</td></tr>";
            }
            ?>
        </table>
    </center>
</body>
</html>

<?php
// Close the statement and database connection
mysqli_stmt_close($stmt);
mysqli_close($connect);
?>

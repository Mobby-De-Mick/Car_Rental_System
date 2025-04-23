<?php
include "connect.php";

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["Ctype"])) {
    $Ctype = mysqli_real_escape_string($connect, $_POST["Ctype"]);
    
    $rate_query = "SELECT daily_rate, weekly_rate FROM rental_rates WHERE Ctype = '$Ctype'";
    $rate_result = mysqli_query($connect, $rate_query);
    
    if (mysqli_num_rows($rate_result) > 0) {
        $rate_row = mysqli_fetch_assoc($rate_result);
        echo json_encode([
            'daily_rate' => $rate_row['daily_rate'],
            'weekly_rate' => $rate_row['weekly_rate']
        ]);
    } else {
        echo json_encode(['error' => 'Rate not found']);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>
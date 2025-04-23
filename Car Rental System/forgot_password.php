<?php
include "connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];

    
    $stmt = $connect->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close(); // 
    if ($user) {
        $link = "http://localhost/Project1/reset_password.php";

        /
        $stmt1 = $connect->prepare("UPDATE users SET verification_code=? WHERE email=?");
        $stmt1->bind_param("ss", $link, $email);
        $stmt1->execute();
        $stmt1->close(); // 

        echo "A password code has been sent to your email.";
    } else {
        echo "No account found with this email.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
</head>
<body style="background:#7b7fed;">
    <form method="POST">
        <label for="email">Enter your email:</label>
        <input type="email" name="email" required>
        <button type="submit" style="cursor:pointer;">Submit</button>
    </form>
</body>
</html>

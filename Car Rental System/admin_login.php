<?php
session_start();

if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header("Location: admin.php");
    exit();
}

$admin_username = "Admin";
$admin_password = "Admin123";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

   
    if ($username === $admin_username && $password === $admin_password) {
        $_SESSION['user_role'] = 'admin';
        $_SESSION['username'] = $username;

        header("Location: admin.php");
        exit();
    } else {
        
        echo "<script>alert('Invalid Admin Credentials!'); window.location='admin_login.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        body {
          font-family: Arial, sans-serif;
          background-color: #7b7fed;
          display: flex;
          justify-content: center;
          align-items: center;
          height: 100vh;
          margin: 0;
        }
        .login-container {
          background-color: #fff;
          padding: 20px;
          border-radius: 8px;
          box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
          width: 300px;
          text-align: center;
        }

        h2 {
        margin-bottom: 20px;
        color: #333;
      }

      .form-group {
        margin-bottom: 15px;
        text-align: left;
      }

      label {
        display: block;
        margin-bottom: 5px;
        color: #555;
      }

      input {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
      }

      .login-btn {
        width: 100%;
        padding: 10px;
        background-color:  #7b7fed;
        color: #fff;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
      }

      .login-btn:hover {
        background-color:rgb(145, 148, 231);
      }

      p {
        margin-top: 15px;
        color: #555;
      }

      a {
        color:  #7b7fed;
        text-decoration: none;
      }

      a:hover {
        text-decoration: underline;
      }

    
    </style>
</head>
<body>
  <div class="login-container">
    <h2>Admin Login</h2>
    <form id="login-form" action="admin_login.php" method="POST">
      <div class="form-group">
        <label for="username">Username: </label>
        <input type="text" id="username" name="username" placeholder="Enter admin username" required>
      </div>
      <br>
      <div class="form-group">
        <label for="password">Password: </label>
        <input type="password" id="password" name="password" placeholder="Enter admin password" required>
      </div>
      <br>
      <button type="submit" class="login-btn">Login</button>
    </form>
    <p><a href="sign_in.php">User Login</a></p>
  </div>
</body>
</html>
<?php
session_start();
include "connect.php";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Handle User Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sign_in'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = "Email and password are required!";
    } else {
        // Check database for user with customer details
        $stmt = $connect->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['logged_in'] = true;
                $_SESSION['email'] = $user['email'];
                $_SESSION['fname'] = $user['fname'];
                $_SESSION['lname'] = $user['lname'];                
                $_SESSION['mobile'] = $user['mobile'];
                $_SESSION['user_role'] = $user['role'] ?? 'user'; // Default to 'customer' if role not set
                
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                // Update last login time
                $update = $connect->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?");
                $update->bind_param("i", $user['user_id']);
                $update->execute();
                
                // Redirect based on role
                if ($_SESSION['user_role'] === 'admin') {
                    header("Location: admin.php");
                } else {
                    header("Location: book.php");
                }
                exit();
            }
        }
        
        // If we get here, login failed
        $error = "Invalid email or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Car Rental System - Login</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #7b7fed;
      --primary-hover: #6a6ed8;
      --error-color: #e74c3c;
      --success-color: #2ecc71;
      --text-color: #333;
      --light-text: #555;
      --border-color: #ddd;
      --bg-color: #f9f9f9;
    }
    
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: var(--bg-color);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background-image: linear-gradient(135deg, #7b7fed 0%, #5d5fdf 100%);
    }
    
    .login-container {
      background-color: #fff;
      padding: 2.5rem;
      border-radius: 10px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 400px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    
    .login-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: var(--primary-color);
    }
    
    h2 {
      margin-bottom: 1.5rem;
      color: var(--text-color);
      font-weight: 600;
    }
    
    .logo {
      margin-bottom: 1.5rem;
    }
    
    .logo img {
      max-width: 120px;
    }
    
    .form-group {
      margin-bottom: 1.2rem;
      text-align: left;
      position: relative;
    }
    
    label {
      display: block;
      margin-bottom: 0.5rem;
      color: var(--light-text);
      font-size: 0.9rem;
      font-weight: 500;
    }
    
    input {
      width: 100%;
      padding: 0.8rem 1rem;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      font-size: 0.95rem;
      transition: border-color 0.3s;
    }
    
    input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 2px rgba(123, 127, 237, 0.2);
    }
    
    .password-container {
      position: relative;
    }
    
    .password-toggle {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: var(--light-text);
    }
    
    .login-btn {
      width: 100%;
      padding: 0.9rem;
      background-color: var(--primary-color);
      color: #fff;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 1rem;
      font-weight: 500;
      transition: background-color 0.3s;
      margin-top: 0.5rem;
    }
    
    .login-btn:hover {
      background-color: var(--primary-hover);
    }
    
    .links {
      margin-top: 1.5rem;
      display: flex;
      flex-direction: column;
      gap: 0.8rem;
    }
    
    .links a {
      color: var(--primary-color);
      text-decoration: none;
      font-size: 0.9rem;
      transition: color 0.2s;
    }
    
    .links a:hover {
      text-decoration: underline;
      color: var(--primary-hover);
    }
    
    .separator {
      display: flex;
      align-items: center;
      margin: 1.2rem 0;
      color: var(--light-text);
      font-size: 0.8rem;
    }
    
    .separator::before,
    .separator::after {
      content: "";
      flex: 1;
      border-bottom: 1px solid var(--border-color);
      margin: 0 0.5rem;
    }
    
    .error-message {
      color: var(--error-color);
      background-color: rgba(231, 76, 60, 0.1);
      padding: 0.8rem;
      border-radius: 6px;
      margin-bottom: 1.2rem;
      font-size: 0.9rem;
      text-align: left;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .error-message i {
      font-size: 1.1rem;
    }
    
    @media (max-width: 480px) {
      .login-container {
        padding: 1.5rem;
        margin: 0 1rem;
      }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="logo">
      <h2 style="color: var(--primary-color);">MOBBY CARS</h2>
    </div>
    
    <?php if (isset($error)): ?>
      <div class="error-message">
        <i class="fas fa-exclamation-circle"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
      </div>
    <?php endif; ?>
    
    <form id="login-form" action="sign_in.php" method="POST">
      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" placeholder="Enter your email" required
               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
      </div>
      
      <div class="form-group">
        <label for="password">Password</label>
        <div class="password-container">
          <input type="password" id="password" name="password" placeholder="Enter your password" required>
          <i class="fas fa-eye password-toggle" id="togglePassword"></i>
        </div>
      </div>
      
      <button type="submit" name="sign_in" class="login-btn">Sign In</button>
    </form>
    
    <div class="links">
      <a href="forgot_password.php">Forgot password?</a>
      <div class="separator">OR</div>
      <a href="signup.php">Create new account</a>
      <a href="admin.php">Admin Login</a>
    </div>
  </div>

  <script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
      const passwordInput = document.getElementById('password');
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
      this.classList.toggle('fa-eye');
      this.classList.toggle('fa-eye-slash');
    });

    // Form validation
    document.getElementById('login-form').addEventListener('submit', function(e) {
      const email = document.getElementById('email').value.trim();
      const password = document.getElementById('password').value.trim();
      
      if (!email || !password) {
        e.preventDefault();
        alert('Please fill in all fields');
      }
    });

    // Focus on email field when page loads
    window.onload = function() {
      document.getElementById('email').focus();
    };
  </script>
</body>
</html>
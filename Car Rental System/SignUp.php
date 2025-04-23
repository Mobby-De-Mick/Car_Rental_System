<?php
include "connect.php";

// Initialize error array
$errors = [];

// Process form when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['SignUp'])) {
    // Get form data
    $email = mysqli_real_escape_string($connect, $_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $firstName = mysqli_real_escape_string($connect, $_POST['firstName']);
    $lastName = mysqli_real_escape_string($connect, $_POST['lastName']);
    $age = intval($_POST['age']);
    $mobile = mysqli_real_escape_string($connect, $_POST['mobile']);
    $dlno = mysqli_real_escape_string($connect, $_POST['dlno']);
    $insno = mysqli_real_escape_string($connect, $_POST['insno']);

    // Validate inputs
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }

    if (empty($firstName) || empty($lastName)) {
        $errors[] = "First name and last name are required";
    }

    if ($age < 18) {
        $errors[] = "You must be at least 18 years old";
    }

    if (empty($mobile)) {
        $errors[] = "Mobile number is required";
    }

    if (empty($dlno)) {
        $errors[] = "Driving license number is required";
    }

    // Check if email already exists
    $checkEmail = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($connect, $checkEmail);
    if (mysqli_num_rows($result) > 0) {
        $errors[] = "Email already exists";
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user into database
        $query = "INSERT INTO users (email, password, fname, lname, age, mobile, dlno, insno, created_at) 
                  VALUES ('$email', '$hashedPassword', '$firstName', '$lastName', $age, '$mobile', '$dlno', '$insno', NOW())";
        
        if (mysqli_query($connect, $query)) {
            // Registration successful
            header("Location: sign_in.php?success=Registration successful. Please log in.");
            exit();
        } else {
            $errors[] = "Database error: " . mysqli_error($connect);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Car Rental System - Sign Up</title>
  <style>
    /* Your existing CSS styles */
    body {
      font-family: Arial, sans-serif;
      background-color: #7b7fed;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      margin: 0;
    }

    .signup-container {
      background-color: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      width: 350px;
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

    .signup-btn {
      width: 100%;
      padding: 10px;
      background-color: #7b7fed;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
      margin-top: 10px;
    }

    .signup-btn:hover {
      background-color: rgba(129, 146, 231, 0.91);
    }

    p {
      margin-top: 15px;
      color: #555;
    }

    a {
      color: #007bff;
      text-decoration: none;
    }

    a:hover {
      text-decoration: underline;
    }
    
    .password-hint {
      font-size: 12px;
      color: #666;
      margin-top: 5px;
    }
    
    .error {
      color: red;
      font-size: 12px;
      margin-top: 5px;
    }
    
    .error-message {
      color: red;
      margin-bottom: 15px;
      text-align: left;
    }
    
    .section-title {
      font-weight: bold;
      margin: 20px 0 10px;
      color: #444;
      border-bottom: 1px solid #eee;
      padding-bottom: 5px;
    }
  </style>
</head>
<body>
  <div class="signup-container">
    <h2>Sign Up</h2>
    
    <?php if(!empty($errors)): ?>
      <div class="error-message">
        <?php foreach($errors as $error): ?>
          <div><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    
    <form id="signup-form" method="POST" onsubmit="return validateForm()">
      <div class="section-title">Account Information</div>
      
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Enter your email" required>
      </div>
      
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password (min 8 characters)" required>
        <div class="password-hint">Password must be at least 8 characters long.</div>
        <div id="password-error" class="error"></div>
      </div>
      
      <div class="form-group">
        <label for="confirmPassword">Confirm Password</label>
        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm your password" required>
        <div id="confirm-error" class="error"></div>
      </div>
      
      <div class="section-title">Personal Information</div>
      
      <div class="form-group">
        <label for="firstName">First Name</label>
        <input type="text" id="firstName" name="firstName" placeholder="Enter your first name" required>
      </div>
      
      <div class="form-group">
        <label for="lastName">Last Name</label>
        <input type="text" id="lastName" name="lastName" placeholder="Enter your last name" required>
      </div>
      
      <div class="form-group">
        <label for="age">Age</label>
        <input type="number" id="age" name="age" placeholder="Enter your age" min="18" required>
      </div>
      
      <div class="form-group">
        <label for="mobile">Mobile Number</label>
        <input type="tel" id="mobile" name="mobile" placeholder="Enter your mobile number" required>
      </div>
      
      <div class="section-title">Driving Information</div>
      
      <div class="form-group">
        <label for="dlno">Driving License Number</label>
        <input type="text" id="dlno" name="dlno" placeholder="Enter your driving license number" required>
      </div>
      
      <div class="form-group">
        <label for="insno">Insurance Number</label>
        <input type="text" id="insno" name="insno" placeholder="Enter your insurance number" required>
      </div>
      
      <button type="submit" name="SignUp" class="signup-btn">Sign Up</button>
    </form>
    <p>Already have an account? <a href="sign_in.php">Log In</a></p>
  </div>

  <script>
    function validateForm() {
      let isValid = true;
      
      // Validate password
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirmPassword').value;
      const passwordError = document.getElementById('password-error');
      const confirmError = document.getElementById('confirm-error');
      
      // Reset errors
      passwordError.textContent = '';
      confirmError.textContent = '';
      
      // Password length requirement
      const minLength = 8;
      
      // Validate password length
      if (password.length < minLength) {
        passwordError.textContent = 'Password must be at least 8 characters long.';
        isValid = false;
      }
      
      // Check if passwords match
      if (password !== confirmPassword) {
        confirmError.textContent = 'Passwords do not match.';
        isValid = false;
      }
      
      // Validate age
      const age = document.getElementById('age').value;
      if (age < 18) {
        alert('You must be at least 18 years old to register.');
        isValid = false;
      }
      
      return isValid;
    }
    
    // Add real-time password validation
    document.getElementById('password').addEventListener('input', function() {
      const password = this.value;
      const passwordError = document.getElementById('password-error');
      
      if (password.length > 0 && password.length < 8) {
        passwordError.textContent = 'Password must be at least 8 characters long.';
      } else {
        passwordError.textContent = '';
      }
    });
    
    // Add real-time password confirmation check
    document.getElementById('confirmPassword').addEventListener('input', function() {
      const password = document.getElementById('password').value;
      const confirmPassword = this.value;
      const confirmError = document.getElementById('confirm-error');
      
      if (confirmPassword.length > 0 && password !== confirmPassword) {
        confirmError.textContent = 'Passwords do not match.';
      } else {
        confirmError.textContent = '';
      }
    });
  </script>
</body>
</html>
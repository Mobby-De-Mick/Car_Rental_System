<?php
// Start session at the very beginning
session_start();

include "connect.php";

// Collect all form data
$fname = $_POST['fname'] ?? '';
$lname = $_POST['lname'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$age = $_POST['age'] ?? '';
$mobile = $_POST['mobile'] ?? '';
$dlno = $_POST['dlno'] ?? '';
$insno = $_POST['insno'] ?? '';

// Validate data
$errors = [];

// Check required fields
if (empty($fname)) $errors[] = "First name is required";
if (empty($lname)) $errors[] = "Last name is required";
if (empty($email)) $errors[] = "Email is required";
if (empty($password)) $errors[] = "Password is required";
if (empty($age)) $errors[] = "Age is required";
if (empty($mobile)) $errors[] = "Mobile number is required";
if (empty($dlno)) $errors[] = "Driving license number is required";
if (empty($insno)) $errors[] = "Insurance number is required";

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format";
}

// Validate mobile number (10 digits)
if (!preg_match('/^\d{10}$/', $mobile)) {
    $errors[] = "Mobile number must be 10 digits";
}

// Check if email already exists
$stmt = $connect->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $errors[] = "Email already exists";
}

// Check if mobile already exists
$stmt = $connect->prepare("SELECT user_id FROM users WHERE mobile = ?");
$stmt->bind_param("s", $mobile);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $errors[] = "Mobile number already registered";
}

// Validate password length
if (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters long";
}

// Validate age
if ($age < 18) {
    $errors[] = "You must be at least 18 years old to register";
}

// If there are errors, redirect back to signup page
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old_input'] = $_POST;
    header("Location: sign_up.php");
    exit();
}

// Start transaction
$connect->begin_transaction();

try {
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert into users table - CORRECTED PARAMETER TYPES
    $stmt = $connect->prepare("INSERT INTO users (email, password, fname, lname, age, mobile, dlno, insno) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    // Changed from "ssssisii" to "ssssisss" since dlno and insno are VARCHAR
    $stmt->bind_param("ssssisss", $email, $hashedPassword, $fname, $lname, $age, $mobile, $dlno, $insno);
    $stmt->execute();
    
    $user_id = $connect->insert_id;
    
    // Commit transaction
    $connect->commit();
    
    // Set session data
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user_id;
    $_SESSION['email'] = $email;
    $_SESSION['fname'] = $fname;
    $_SESSION['lname'] = $lname;
    
    // Redirect to booking page
    header("Location: book.php");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction if something went wrong
    $connect->rollback();
    
    // Log the error for debugging
    error_log("Registration error: " . $e->getMessage());
    
    $_SESSION['errors'] = ["Registration failed. Please try again."];
    $_SESSION['old_input'] = $_POST;
    header("Location: signUp.php");
    exit();
}
?>
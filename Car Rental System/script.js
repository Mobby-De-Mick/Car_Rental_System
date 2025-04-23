// script.js
function validateForm(event) {
    event.preventDefault(); // Prevent form submission
  
    // Get form values
    const fullname = document.getElementById('fullname').value;
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    const driverLicense = document.getElementById('driver-license').value;
  
    // Simple validation
    if (password !== confirmPassword) {
      alert("Passwords do not match!");
      return;
    }
  
    if (!validateEmail(email)) {
      alert("Please enter a valid email address.");
      return;
    }
  
    if (!validatePhone(phone)) {
      alert("Please enter a valid phone number.");
      return;
    }
  
    // If all validations pass, submit the form (or send data to the server)
    alert("Sign up successful!");
    // You can send the data to your backend here using fetch or XMLHttpRequest
  }
  
  function validateEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
  }
  
  function validatePhone(phone) {
    const regex = /^\d{10}$/; // Simple 10-digit phone number validation
    return regex.test(phone);
  }
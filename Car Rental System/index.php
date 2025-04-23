<?php
session_start(); 
include "connect.php"; 
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <title>Car Rental - Mobby Cars</title>
    <link href="styles.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        /* New styles for top-right auth buttons */
        .top-auth-buttons {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 15px;
            z-index: 1000;
        }
        
        .auth-btn {
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .signup-btn {
            background-color: #7b7fed;
            color: white;
            border: 2px solid #7b7fed;
        }
        
        .signup-btn:hover {
            background-color: rgba(129, 146, 231, 0.91);
            border-color: rgba(129, 146, 231, 0.91);
        }
        
        .signin-btn {
            background-color: #7b7fed;
            color: white;
            border: 2px solid #7b7fed;
        }
        
        .signin-btn:hover {
            background-color: rgba(167, 179, 240, 0.91);
            border-color: rgba(129, 146, 231, 0.91);
        }
        
        .profile-btn {
            background-color:  #7b7fed;
            border: 2px solid  #7b7fed;
        }
        
        .profile-btn:hover {
            background-color:  #7b7fed;
            border-color:  #7b7fed;
        }
        
        .logout-btn {
            background-color: #d9534f;
            color: white;
            border: 2px solid #d9534f;
        }
        
        .logout-btn:hover {
            background-color:rgb(212, 136, 133);
            border-color: #c9302c;
        }
        
        /* Adjust header to accommodate auth buttons */
        #header {
            position: relative;
        }
        
        /* Main content styling */
        #main-content {
            text-align: center;
            padding: 40px 20px;
            margin-top: 20px;
        }
        
        /* Remove the centered auth container */
        .auth-container {
            display: none;
        }
        
        /* Profile dropdown styles */
        .profile-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .dropdown-content a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 13px;
        }
        
        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }
        
        .profile-dropdown:hover .dropdown-content {
            display: block;
        }
        
      


         /* Contact Us Section Styles */
         .contact-section {
            background-color: #f8f9fa;
            padding: 30px 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .contact-section h2 {
            color: #7b7fed;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .contact-info {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .contact-method {
            flex: 1;
            min-width: 250px;
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 3px rgba(0,0,0,0.1);
        }
        
        .contact-method i {
            font-size: 24px;
            color: #7b7fed;
            margin-bottom: 10px;
        }
        
        .contact-method h3 {
            margin: 10px 0;
            color: #333;
        }
        
        .contact-method p {
            color: #666;
            margin: 5px 0;
        }


/* About Us Section Styles */
.about-section {
	background-color: #fff;
	padding: 40px 20px;
	margin: 20px 0;
	border-radius: 8px;
	box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.about-section h2 {
	color: #7b7fed;
	margin-bottom: 20px;
	text-align: center;
}

.about-content {
	display: flex;
	flex-wrap: wrap;
	gap: 30px;
	align-items: center;
	justify-content: center;
}

.about-text {
	flex: 1;
	min-width: 300px;
	max-width: 600px;
}

.about-text p {
	color: #555;
	line-height: 1.6;
	margin-bottom: 15px;
}

.about-image {
	flex: 1;
	min-width: 300px;
	max-width: 500px;
	text-align: center;
}

.about-image img {
	max-width: 100%;
	border-radius: 8px;
	box-shadow: 0 3px 10px rgba(0,0,0,0.2);
}

.features {
	display: flex;
	flex-wrap: wrap;
	justify-content: center;
	gap: 20px;
	margin-top: 30px;
}

.feature {
	flex: 1;
	min-width: 200px;
	max-width: 250px;
	text-align: center;
	padding: 20px;
	background: #f8f9fa;
	border-radius: 8px;
	transition: transform 0.3s ease;
}

.feature:hover {
	transform: translateY(-5px);
	box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.feature i {
	font-size: 30px;
	color: #7b7fed;
	margin-bottom: 15px;
}

.feature h3 {
	color: #333;
	margin-bottom: 10px;
}

.support-section {
            background-color: #f8f9fa;
            padding: 40px 20px;
            margin: 30px 0;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .support-section h2 {
            color: #7b7fed;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .support-options {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }
        
        .support-card {
            flex: 1;
            min-width: 280px;
            max-width: 350px;
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            text-align: center;
        }
        
        .support-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .support-icon {
            font-size: 40px;
            color: #7b7fed;
            margin-bottom: 15px;
        }
        
        .support-card h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .support-card p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .support-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #7b7fed;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .support-btn:hover {
            background-color: #6a6ed8;
            color: white;
        }
        
        /* Emergency Banner */
        .emergency-banner {
            background-color:rgb(240, 52, 52);
            color: white;
            padding: 15px;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 5px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 68, 68, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(255, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 68, 68, 0); }
        }
        
        .emergency-banner a {
            color: white;
            text-decoration: underline;
            font-weight: bold;
        }
        
        /* FAQ Section */
        .faq-section {
            background-color: white;
            padding: 40px 20px;
            margin: 30px 0;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .faq-section h2 {
            color: #7b7fed;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .faq-item {
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        
        .faq-question {
            font-weight: bold;
            color: #333;
            cursor: pointer;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .faq-question:hover {
            background-color: #e9ecef;
        }
        
        .faq-answer {
            padding: 15px;
            color: #555;
            line-height: 1.6;
            display: none;
        }
        
        /* Live Chat Button */
        .chat-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: #7b7fed;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            text-align: center;
            line-height: 60px;
            font-size: 24px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            cursor: pointer;
            z-index: 999;
            transition: all 0.3s;
        }
        
        .chat-btn:hover {
            transform: scale(1.1);
            background-color: #6a6ed8;
        }
        
        /* Chat Modal */
        .chat-modal {
            display: none;
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 350px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            z-index: 1000;
            overflow: hidden;
        }
        
        .chat-header {
            background-color: #7b7fed;
            color: white;
            padding: 15px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .close-chat {
            cursor: pointer;
            font-size: 20px;
        }
        
        .chat-body {
            padding: 15px;
            height: 300px;
            overflow-y: auto;
        }
        
        .chat-message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
            max-width: 80%;
        }
        
        .user-message {
            background-color: #e9ecef;
            margin-left: auto;
        }
        
        .agent-message {
            background-color: #7b7fed;
            color: white;
            margin-right: auto;
        }
        
        .chat-input {
            display: flex;
            padding: 10px;
            border-top: 1px solid #eee;
        }
        
        .chat-input input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-right: 10px;
        }
        
        .chat-input button {
            background-color: #7b7fed;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    
    <marquee>
        <h3>
            <b style="color: white; font-size:25px;">
                WELCOME TO MOBBY CARS. RENT YOUR CAR, ENJOY THE RIDE. HURRY HURRY!!!
            </b>
        </h3>
    </marquee>
    <br><br>
    
    <div class="top-auth-buttons">
        <?php if (isset($_SESSION['email'])): ?>
            
            <div class="profile-dropdown">
                <a href="profile.php" class="auth-btn profile-btn">
                    <i class="fas fa-user"></i> Profile
                </a>
                <div class="dropdown-content">
                    <a href="profile.php"><i class="fas fa-user-circle"></i> My Account</a>
                    <a href="book.php"><i class="fas fa-car"></i> My Bookings</a>
                    <a href="signout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        <?php else: ?>
           
            <a href="SignUp.php" class="auth-btn signup-btn">
                <i class="fas fa-user-plus"></i> Sign Up
            </a>
            <a href="sign_in.php" class="auth-btn signin-btn">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </a>
        <?php endif; ?>
    </div>

   
    <div id="main-content">
        <h1>Welcome to Mobby Cars</h1>
        <p>Your trusted partner for car rentals. Explore our wide range of vehicles and enjoy a seamless booking experience.</p>
        
        <?php if (isset($_SESSION['email'])): ?>
            <p>Rent Car as Early as Possible <a href="book.php">book a car</a> now!</p>
        <?php else: ?>
            <p><a href="sign_in.php">Sign in</a> to book your dream car or <a href="SignUp.php">create an account</a> if you're new.</p>
        <?php endif; ?>
    </div>
  

    <div id="container">
        <!-- Header Section -->
        <div id="header">
            <!-- Navigation Menu -->
            <div class="menu">
                <ul>
                    <li id="active"><a href="">Home</a></li>
                    <li><a href="admin.php">Admin</a></li>
                    <li><a href="book.php">Book Car</a></li>
                    <li><a href="return0.php">Return Car</a></li>
                </ul>
            </div>
        </div>

                <!-- Emergency Support Section -->
                <div class="emergency-banner">
            <h3><i class="fas fa-exclamation-triangle"></i> EMERGENCY ROADSIDE ASSISTANCE</h3>
            <p>If you're stranded or need immediate help, call our 24/7 support line: <a href="tel:+254701573217">+254 701 573 217</a></p>
        </div>

        <!-- Support Options Section -->
        <div class="support-section">
            <h2>We're Here to Help</h2>
            <div class="support-options">
                <div class="support-card">
                    <div class="support-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <h3>24/7 Phone Support</h3>
                    <p>Our customer service team is available call for assistance. Call on tel:+254701573217 </p>
                    <a href="tel:+254701573217" class="support-btn">Call Now</a>
                </div>
                
                
                <div class="support-card">
                    <div class="support-icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <h3>Find Nearest Location</h3>
                    <p>Need to return your car at a different location? Find our nearest rental office.</p>
                    <a href="locations.php" class="support-btn">View Locations</a>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="faq-section">
            <h2>Frequently Asked Questions</h2>
            
            <div class="faq-item">
                <div class="faq-question">
                    What should I do if my rental car breaks down?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>If your rental car breaks down, please follow these steps:</p>
                    <ol>
                        <li>Turn on your hazard lights and move to a safe location if possible</li>
                        <li>Call our 24/7 emergency line at +254 700 123 456</li>
                        <li>Provide your location and rental agreement number</li>
                        <li>Our team will dispatch assistance or arrange for a replacement vehicle</li>
                    </ol>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    What's included in your roadside assistance?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Our roadside assistance covers:</p>
                    <ul>
                        <li>Jump starts for dead batteries</li>
                        <li>Lockout service (if keys are locked inside)</li>
                        <li>Flat tire changes (spare must be usable)</li>
                        <li>Emergency fuel delivery (you pay for fuel)</li>
                        <li>Towing to nearest repair facility if needed</li>
                    </ul>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    How can I extend my rental period?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>To extend your rental:</p>
                    <ol>
                        <li>Call our customer service at least 24 hours before your scheduled return</li>
                        <li>Check vehicle availability for the extended period</li>
                        <li>We'll adjust your rental agreement and payment</li>
                        <li>You'll receive confirmation via email/SMS</li>
                    </ol>
                    <p>Note: Extensions are subject to vehicle availability.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    What payment methods do you accept?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>We accept all major payment methods:</p>
                    <ul>
                        <li>Credit/Debit Cards (Visa, MasterCard, American Express)</li>
                        <li>M-Pesa Mobile Payments</li>
                        <li>Bank Transfers</li>
                        <li>Cash (at select locations only)</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="chat-btn" id="chatBtn">
        <i class="fas fa-comments"></i>
    </div>

    <!-- Chat Modal -->
    <div class="chat-modal" id="chatModal">
        <div class="chat-header">
            <span>Mobby Cars Support</span>
            <span class="close-chat" id="closeChat">&times;</span>
        </div>
        <div class="chat-body" id="chatBody">
            <div class="chat-message agent-message">
                Hello! How can we help you today?
            </div>
        </div>
        <div class="chat-input">
            <input type="text" id="chatInput" placeholder="Type your message...">
            <button id="sendMessage">Send</button>
        </div>
    </div>

    <script>
        // FAQ Toggle Functionality
        document.querySelectorAll('.faq-question').forEach(question => 
        {
            question.addEventListener('click', () => {
                const answer = question.nextElementSibling;
                const icon = question.querySelector('i');
                
                if (answer.style.display === 'block') {
                    answer.style.display = 'none';
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                } else {
                    answer.style.display = 'block';
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                }
            });
        });

        // Chat Functionality
        const chatBtn = document.getElementById('chatBtn');
        const chatModal = document.getElementById('chatModal');
        const closeChat = document.getElementById('closeChat');
        const chatBody = document.getElementById('chatBody');
        const chatInput = document.getElementById('chatInput');
        const sendMessage = document.getElementById('sendMessage');
        
        chatBtn.addEventListener('click', () => {
            chatModal.style.display = chatModal.style.display === 'block' ? 'none' : 'block';
        });
        
        closeChat.addEventListener('click', () => {
            chatModal.style.display = 'none';
        });
        
        sendMessage.addEventListener('click', sendChatMessage);
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendChatMessage();
        });
        
        function sendChatMessage() {
            const message = chatInput.value.trim();
            if (message) {
                // Add user message
                const userMsg = document.createElement('div');
                userMsg.className = 'chat-message user-message';
                userMsg.textContent = message;
                chatBody.appendChild(userMsg);
                
                // Clear input
                chatInput.value = '';
                
                // Scroll to bottom
                chatBody.scrollTop = chatBody.scrollHeight;
                
                // Simulate agent response after delay
                setTimeout(() => {
                    const agentMsg = document.createElement('div');
                    agentMsg.className = 'chat-message agent-message';
                    agentMsg.textContent = getAgentResponse(message);
                    chatBody.appendChild(agentMsg);
                    chatBody.scrollTop = chatBody.scrollHeight;
                }, 1000);
            }
        }
        
        function getAgentResponse(message) {
            const lowerMsg = message.toLowerCase();
            
            if (lowerMsg.includes('break') || lowerMsg.includes('strand')) {
                return "For roadside assistance, please call our 24/7 emergency line at +254 701 573 217. We'll dispatch help immediately.";
            } else if (lowerMsg.includes('extend') || lowerMsg.includes('longer')) {
                return "To extend your rental, please call our customer service at +254 113 262 087 with your rental agreement number ready.";
            } else if (lowerMsg.includes('pay') || lowerMsg.includes('payment')) {
                return "We accept M-Pesa, credit cards, and bank transfers. You can make payments through our app or at any of our locations.";
            } else if (lowerMsg.includes('location') || lowerMsg.includes('office')) {
                return "We have locations in Nairobi, Mombasa, Kisumu, and Eldoret. Visit our Locations page for addresses and maps.";
            } else {
                return "Thank you for your message. For immediate assistance, please call our 24/7 support line at +254 701 573 217.";
            }
        }
    </script>

    <!-- About Us Section -->
    <div class="about-section">
            <h2>About Mobby Cars</h2>
            <div class="about-content">
                <div class="about-text">
                    <p>Founded in 2010, Mobby Cars has grown to become one of the most trusted car rental services in the region. We started with just 5 vehicles and a passion for providing excellent service, and today we boast a fleet of over 200 vehicles ranging from economy cars to luxury vehicles.</p>
                    <p>Our mission is to make car rental simple, convenient, and affordable for everyone. Whether you need a car for business, vacation, or just everyday use, we've got you covered with our wide selection of well-maintained vehicles.</p>
                    <p>At Mobby Cars, we pride ourselves on our customer-first approach. Our team is available 24/7 to ensure you have the best rental experience possible.</p>
                </div>
                <div class="about-image">
                    <img src="images/subaru_legacy.jpg" alt="Mobby Cars Office">
                </div>
            </div>
            <div class="features">
                <div class="feature">
                    <i class="fas fa-car-alt"></i>
                    <h3>Wide Selection</h3>
                    <p>Choose from 200+ vehicles of all classes</p>
                </div>
                <div class="feature">
                    <i class="fas fa-medal"></i>
                    <h3>Quality Service</h3>
                    <p>Award-winning customer service</p>
                </div>
                <div class="feature">
                    <i class="fas fa-map-marker-alt"></i>
                    <h3>Multiple Locations</h3>
                    <p>Convenient pickup locations nationwide</p>
                </div>
                <div class="feature">
                    <i class="fas fa-hand-holding-usd"></i>
                    <h3>Best Prices</h3>
                    <p>Competitive rates with no hidden fees</p>
                </div>
            </div>
        </div>
      




        <br><br> <br><br>
        <div class="contact-section">
            <h2>Contact Us</h2>
            <div class="contact-info">
                <div class="contact-method">
                    <i class="fas fa-phone-alt"></i>
                    <h3>Phone</h3>
                    <p>+254 (113) 262-087</p>
                    <p>+254 (701) 573-217</p>
                </div>
                <div class="contact-method">
                    <i class="fas fa-envelope"></i>
                    <h3>Email</h3>
                    <p>info@mobbycars.com</p>
                    <p>support@mobbycars.com</p>
                </div>
                <div class="contact-method">
                    <i class="fas fa-map-marker-alt"></i>
                    <h3>Address</h3>
                    <p>123 Kibabii Street</p>
                    <p>Bungoma, Bg 50100</p>
                </div>
                <div class="contact-method">
                    <i class="fas fa-clock"></i>
                    <h3>Working Hours</h3>
                    <p>Monday - Friday: 9AM - 6PM</p>
                    <p>Saturday: 10AM - 4PM</p>
                </div>
            </div>
        </div>
      
    </div>
</body>
</html>
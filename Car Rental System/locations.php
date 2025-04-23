<?php
session_start();
include "connect.php";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mobby Cars - Locations</title>
    <link href="styles.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        /* Inherit existing styles */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
        }
        
        /* Locations Page Specific Styles */
        .locations-header {
            background-color: #7b7fed;
            color: white;
            padding: 60px 20px;
            text-align: center;
            margin-bottom: 40px;
        }
        
        .locations-header h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .locations-header p {
            font-size: 1.1rem;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .location-filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin: 0 auto 30px;
            max-width: 1200px;
        }
        
        .filter-group {
            margin-bottom: 15px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .filter-group select, 
        .filter-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .locations-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .location-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .location-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .location-image {
            height: 200px;
            overflow: hidden;
        }
        
        .location-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .location-card:hover .location-image img {
            transform: scale(1.05);
        }
        
        .location-details {
            padding: 20px;
        }
        
        .location-details h3 {
            margin-top: 0;
            color: #7b7fed;
            font-size: 1.3rem;
        }
        
        .location-info {
            margin-bottom: 15px;
        }
        
        .location-info p {
            margin: 5px 0;
            display: flex;
            align-items: flex-start;
        }
        
        .location-info i {
            margin-right: 10px;
            color: #7b7fed;
            min-width: 16px;
        }
        
        .location-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .action-btn {
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
        }
        
        .direction-btn {
            background-color: #7b7fed;
            color: white;
        }
        
        .direction-btn:hover {
            background-color: #6a6ed8;
        }
        
        .book-btn {
            background-color: #4CAF50;
            color: white;
        }
        
        .book-btn:hover {
            background-color: #45a049;
        }
        
        .action-btn i {
            margin-right: 5px;
        }
        
        .map-container {
            height: 300px;
            margin-top: 15px;
            border-radius: 5px;
            overflow: hidden;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .locations-container {
                grid-template-columns: 1fr;
            }
            
            .locations-header {
                padding: 40px 15px;
            }
        }
    </style>
</head>
<body>
    
    <!-- Include your existing header/navigation -->
    <?php include('header.php'); ?>
    
    <div class="locations-header">
        <h1>Our Rental Locations</h1>
        <p>Find the nearest Mobby Cars rental office for pickup, drop-off, or assistance. All locations offer full service and support.</p>
    </div>
    
    <div class="location-filters">
        <div class="filter-group">
            <label for="city-filter">Filter by City:</label>
            <select id="city-filter">
                <option value="all">All Cities</option>
                <option value="nairobi">Nairobi</option>
                <option value="mombasa">Mombasa</option>
                <option value="kisumu">Kisumu</option>
                <option value="nakuru">Nakuru</option>
                <option value="eldoret">Eldoret</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="service-filter">Services Available:</label>
            <select id="service-filter">
                <option value="all">All Services</option>
                <option value="pickup">Airport Pickup</option>
                <option value="24hour">24-Hour Service</option>
                <option value="premium">Premium Vehicles</option>
                <option value="commercial">Commercial Rentals</option>
            </select>
        </div>
    </div>
    
    <div class="locations-container">
        <!-- Location 1 -->
        <div class="location-card" data-city="nairobi" data-services="pickup,24hour,premium">
            <div class="location-image">
                <img src="images/nairobi.jpg" alt="Nairobi Downtown Location">
            </div>
            <div class="location-details">
                <h3>Nairobi Downtown</h3>
                <div class="location-info">
                    <p><i class="fas fa-map-marker-alt"></i> 123 Kenyatta Avenue, Nairobi</p>
                    <p><i class="fas fa-phone-alt"></i> +254 700 111 222</p>
                    <p><i class="fas fa-envelope"></i> nairobi@mobbycars.com</p>
                    <p><i class="fas fa-clock"></i> Mon-Sun: 6:00 AM - 10:00 PM</p>
                    <p><i class="fas fa-star"></i> Airport pickup available</p>
                </div>
                <div class="map-container">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3988.808530227407!2d36.82115931475397!3d-1.286359835980925!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x182f10d664f5b5c9%3A0x1df5e5e5c5e5e5e5!2sKenyatta%20Avenue%2C%20Nairobi!5e0!3m2!1sen!2ske!4v1620000000000!5m2!1sen!2ske" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
                <div class="location-actions">
                    <a href="https://maps.google.com?q=123+Kenyatta+Avenue,+Nairobi" class="action-btn direction-btn" target="_blank">
                        <i class="fas fa-directions"></i> Directions
                    </a>
                    <a href="book.php?location=nairobi_downtown" class="action-btn book-btn">
                        <i class="fas fa-car"></i> Book Now
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Location 2 -->
        <div class="location-card" data-city="mombasa" data-services="pickup,24hour">
            <div class="location-image">
                <img src="images/mombasa.jpg" alt="Mombasa Beach Location">
            </div>
            <div class="location-details">
                <h3>Mombasa Beach Branch</h3>
                <div class="location-info">
                    <p><i class="fas fa-map-marker-alt"></i> 456 Moi Avenue, Mombasa</p>
                    <p><i class="fas fa-phone-alt"></i> +254 700 333 444</p>
                    <p><i class="fas fa-envelope"></i> mombasa@mobbycars.com</p>
                    <p><i class="fas fa-clock"></i> Mon-Sun: 7:00 AM - 9:00 PM</p>
                    <p><i class="fas fa-umbrella-beach"></i> Beach vehicles available</p>
                </div>
                <div class="map-container">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3979.808530227407!2d39.66415931475397!3d-4.056359835980925!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x184012e6f5b5c9%3A0x1df5e5e5c5e5e5e5!2sMoi%20Avenue%2C%20Mombasa!5e0!3m2!1sen!2ske!4v1620000000000!5m2!1sen!2ske" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
                <div class="location-actions">
                    <a href="https://maps.google.com?q=456+Moi+Avenue,+Mombasa" class="action-btn direction-btn" target="_blank">
                        <i class="fas fa-directions"></i> Directions
                    </a>
                    <a href="book.php?location=mombasa_beach" class="action-btn book-btn">
                        <i class="fas fa-car"></i> Book Now
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Location 3 -->
        <div class="location-card" data-city="kisumu" data-services="24hour,commercial">
            <div class="location-image">
                <img src="images/kisumu.jpg" alt="Kisumu Lakeside Location">
            </div>
            <div class="location-details">
                <h3>Kisumu Lakeside</h3>
                <div class="location-info">
                    <p><i class="fas fa-map-marker-alt"></i> 789 Oginga Odinga Road, Kisumu</p>
                    <p><i class="fas fa-phone-alt"></i> +254 700 555 666</p>
                    <p><i class="fas fa-envelope"></i> kisumu@mobbycars.com</p>
                    <p><i class="fas fa-clock"></i> 24/7 Operation</p>
                    <p><i class="fas fa-truck"></i> Commercial vehicles available</p>
                </div>
                <div class="map-container">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.808530227407!2d34.76115931475397!3d-0.086359835980925!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x182aa6f5b5c9%3A0x1df5e5e5c5e5e5e5!2sOginga%20Odinga%20Road%2C%20Kisumu!5e0!3m2!1sen!2ske!4v1620000000000!5m2!1sen!2ske" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
                <div class="location-actions">
                    <a href="https://maps.google.com?q=789+Oginga+Odinga+Road,+Kisumu" class="action-btn direction-btn" target="_blank">
                        <i class="fas fa-directions"></i> Directions
                    </a>
                    <a href="book.php?location=kisumu_lakeside" class="action-btn book-btn">
                        <i class="fas fa-car"></i> Book Now
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Location 4 -->
        <div class="location-card" data-city="nakuru" data-services="premium">
            <div class="location-image">
                <img src="images/nakuru.jpg" alt="Nakuru Town Location">
            </div>
            <div class="location-details">
                <h3>Nakuru Town</h3>
                <div class="location-info">
                    <p><i class="fas fa-map-marker-alt"></i> 321 Kenyatta Avenue, Nakuru</p>
                    <p><i class="fas fa-phone-alt"></i> +254 700 777 888</p>
                    <p><i class="fas fa-envelope"></i> nakuru@mobbycars.com</p>
                    <p><i class="fas fa-clock"></i> Mon-Fri: 8:00 AM - 7:00 PM, Sat: 9:00 AM - 4:00 PM</p>
                    <p><i class="fas fa-gem"></i> Luxury vehicles available</p>
                </div>
                <div class="map-container">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.808530227407!2d36.07115931475397!3d-0.286359835980925!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x18298d6f5b5c9%3A0x1df5e5e5c5e5e5e5!2sKenyatta%20Avenue%2C%20Nakuru!5e0!3m2!1sen!2ske!4v1620000000000!5m2!1sen!2ske" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
                <div class="location-actions">
                    <a href="https://maps.google.com?q=321+Kenyatta+Avenue,+Nakuru" class="action-btn direction-btn" target="_blank">
                        <i class="fas fa-directions"></i> Directions
                    </a>
                    <a href="book.php?location=nakuru_town" class="action-btn book-btn">
                        <i class="fas fa-car"></i> Book Now
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Location 5 -->
        <div class="location-card" data-city="eldoret" data-services="24hour,commercial">
            <div class="location-image">
                <img src="images/eldoret.jpg" alt="Eldoret Town Location">
            </div>
            <div class="location-details">
                <h3>Eldoret Central</h3>
                <div class="location-info">
                    <p><i class="fas fa-map-marker-alt"></i> 654 Uganda Road, Eldoret</p>
                    <p><i class="fas fa-phone-alt"></i> +254 700 999 000</p>
                    <p><i class="fas fa-envelope"></i> eldoret@mobbycars.com</p>
                    <p><i class="fas fa-clock"></i> 24/7 Operation</p>
                    <p><i class="fas fa-truck"></i> Commercial vehicles available</p>
                </div>
                <div class="map-container">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.808530227407!2d35.27115931475397!3d0.513640164019075!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x178101a6f5b5c9%3A0x1df5e5e5c5e5e5e5!2sUganda%20Road%2C%20Eldoret!5e0!3m2!1sen!2ske!4v1620000000000!5m2!1sen!2ske" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
                <div class="location-actions">
                    <a href="https://maps.google.com?q=654+Uganda+Road,+Eldoret" class="action-btn direction-btn" target="_blank">
                        <i class="fas fa-directions"></i> Directions
                    </a>
                    <a href="book.php?location=eldoret_central" class="action-btn book-btn">
                        <i class="fas fa-car"></i> Book Now
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Location filtering functionality
        document.getElementById('city-filter').addEventListener('change', filterLocations);
        document.getElementById('service-filter').addEventListener('change', filterLocations);
        
        function filterLocations() {
            const cityFilter = document.getElementById('city-filter').value;
            const serviceFilter = document.getElementById('service-filter').value;
            const locations = document.querySelectorAll('.location-card');
            
            locations.forEach(location => {
                const city = location.getAttribute('data-city');
                const services = location.getAttribute('data-services').split(',');
                
                const cityMatch = cityFilter === 'all' || city === cityFilter;
                const serviceMatch = serviceFilter === 'all' || services.includes(serviceFilter);
                
                if (cityMatch && serviceMatch) {
                    location.style.display = 'block';
                } else {
                    location.style.display = 'none';
                }
            });
        }
        
       
        window.onload = function() {
            filterLocations();
        };
    </script>
    
     
</body>
</html>
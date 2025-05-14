<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "hms_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management System</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            line-height: 1.6; 
            display: flex; 
            flex-direction: column; 
            min-height: 100vh; 
        }
        header { 
            background-color: #3498db; 
            color: #fff; 
            padding: 1em 0; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            padding: 10px 20px; 
        }
        .logo-container { 
            display: flex; 
            align-items: center; 
            gap: 15px; 
        }
        .logo { 
            width: 100px; 
            height: auto; 
        }
        .health-logo { 
            width: 100px; 
            height: auto; 
        }
        nav ul { 
            list-style-type: none; 
            margin: 0; 
            padding: 0; 
            background-color: #f4f4f4; 
            overflow: hidden; 
            display: flex; 
        }
        nav li { 
            float: left; 
        }
        nav li a { 
            display: block; 
            color: #333; 
            text-align: center; 
            padding: 14px 16px; 
            text-decoration: none; 
        }
        nav li a:hover { 
            background-color: #ddd; 
        }
        nav li a.active { 
            background-color: #ddd; 
            font-weight: bold; 
        }
        main { 
            padding: 20px; 
            flex: 1; 
            padding-bottom: 60px; 
        }
        footer { 
            background-color: #333; 
            color: #fff; 
            text-align: center; 
            padding: 1em 0; 
            width: 100%; 
        }

        /* Dropdown Styles */
        .dropdown { 
            position: relative; 
            display: inline-block; 
        }
        .dropbtn { 
            background-color: #2c3e50; 
            color: white; 
            padding: 10px 15px; 
            font-size: 16px; 
            border: none; 
            cursor: pointer; 
        }
        .dropdown-content { 
            display: none; 
            position: absolute; 
            right: 0; 
            background-color: #f9f9f9; 
            min-width: 180px; 
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2); 
            z-index: 1; 
        }
        .dropdown-content a { 
            color: black; 
            padding: 12px 16px; 
            display: block; 
            text-decoration: none; 
        }
        .dropdown-content a:hover { 
            background-color: #ddd; 
        }
        .dropdown:hover .dropdown-content { 
            display: block; 
        }
        .signup-link { 
            padding: 12px 16px; 
            display: block; 
            color: #3498db; 
            text-align: center; 
            text-decoration: none; 
            font-weight: bold; 
        }
        .signup-link:hover { 
            text-decoration: underline; 
        }
        .right-menu { 
            display: flex; 
            align-items: center; 
        }

        /* Services Section */
        .services { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 20px; 
            justify-content: center; 
            margin-top: 20px; 
        }
        .service-card { 
            background-color: #f9f9f9; 
            border-radius: 8px; 
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); 
            width: 250px; 
            text-align: center; 
            padding: 15px; 
            transition: transform 0.2s; 
        }
        .service-card:hover { 
            transform: scale(1.05); 
        }
        .service-card img { 
            width: 100%; 
            height: 150px; 
            object-fit: cover; 
            border-radius: 8px; 
        }
        .service-card h3 { 
            margin: 10px 0; 
            color: #3498db; 
        }
        .service-card p { 
            font-size: 14px; 
            color: #666; 
        }
        .service-card a { 
            display: inline-block; 
            margin-top: 10px; 
            padding: 8px 16px; 
            background-color: #3498db; 
            color: #fff; 
            text-decoration: none; 
            border-radius: 5px; 
        }
        .service-card a:hover { 
            background-color: #2980b9; 
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <a href="index.php">
                <img src="images/new_logo.jpeg" alt="Hospital Logo" class="logo">
            </a>
            <a href="index.php">
             <img src="images/health.jpeg" alt="NSW Health" class="health-logo">
         </a>

        </div>
        <nav>
            <ul>
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="register.php">Register Patient</a></li>
                <li><a href="appointment.php">Book Appointment</a></li>
                <li><a href="bills.php">View Bills</a></li>
                <li><a href="contact_us.php">Contact Us</a></li>
            </ul>
        </nav>
        <div class="right-menu">
            <div class="dropdown">
                <button class="dropbtn">Login</button>
                <div class="dropdown-content">
                    <a href="admin_login.php">Login as Admin</a>
                    <a href="employee_login.php">Login as Employee</a>
                    <a href="user_login.php">Login as User</a>
                    <hr>
                    <a href="signup.php" class="signup-link">Don't have an account? Sign up here</a>
                </div>
            </div>
        </div>
    </header>
    
    <main>
        <section>
            <h2>Welcome to HMS</h2>
            <p>This web-based system is designed to streamline hospital operations, from patient registration to appointment scheduling and billing.</p>
            <?php
            if ($conn->ping()) {
                echo "";
            } else {
                echo "<p>Database Status: Connection failed.</p>";
            }
            $conn->close();
            ?>
        </section>

        <section>
            <h2>Services We Offer</h2>
            <div class="services">
                <!-- Maternity Ward -->
                <div class="service-card">
                    <img src="images/maternity.jpeg" alt="Maternity Ward">
                    <h3>Maternity Ward</h3>
                    <p>Comprehensive care for expecting mothers, ensuring a safe and comfortable delivery experience.</p>
                    <a href="maternity.php">Learn More</a>
                </div>

                <!-- Emergency Department -->
                <div class="service-card">
                    <img src="images/emergency.jpeg" alt="Emergency Department">
                    <h3>Emergency Department</h3>
                    <p>24/7 emergency services with a dedicated team to handle critical cases promptly.</p>
                    <a href="emergency.php">Learn More</a>
                </div>

                <!-- Childcare Department -->
                <div class="service-card">
                    <img src="images/childcare.jpeg" alt="Childcare Department">
                    <h3>Childcare Department</h3>
                    <p>Specialized pediatric care for children, from newborns to adolescents.</p>
                    <a href="childcare.php">Learn More</a>
                </div>

                <!-- News and Media -->
                <div class="service-card">
                    <img src="images/news.jpeg" alt="News and Media">
                    <h3>News and Media</h3>
                    <p>Stay updated with the latest hospital news, events, and health tips.</p>
                    <a href="news.php">Learn More</a>
                </div>
            </div>
        </section>
    </main>
    
    <footer>
        <p>© 2025 Hospital Management System</p>
    </footer>
</body>
</html>
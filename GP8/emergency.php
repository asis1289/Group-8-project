<?php
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
    <title>Emergency Department</title>
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
            flex: 1; 
            padding: 20px; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            padding-bottom: 60px; 
        }
        .container { 
            width: 100%; 
            max-width: 600px; 
            background-color: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        footer { 
            background-color: #333; 
            color: #fff; 
            text-align: center; 
            padding: 1em 0; 
            width: 100%; 
        }
        h2 {
            margin: 10px 0;
            color: #3498db;
        }
        p {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #666;
            margin-bottom: 1rem;
        }
        .contact-details {
            font-size: 1rem;
            color: #666;
        }
        .contact-details strong {
            color: #3498db;
        }
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
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="register.php">Register Patient</a></li>
                <li><a href="appointment.php">Book Appointment</a></li>
                <li><a href="bills.php">View Bills</a></li>
                <li><a href="emergency.php" class="active">Emergency Department</a></li>
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
        <div class="container">
            <h2>Emergency Department</h2>
            <p>
                Our Emergency Department is dedicated to providing immediate, life-saving care for patients facing critical medical situations. Staffed 24/7 by highly trained specialists and equipped with advanced diagnostic and treatment technologies, we handle a wide range of emergencies with efficiency and compassion, ensuring rapid response and optimal patient outcomes.
            </p>
            <div class="contact-details">
                <p><strong>In case of an emergency:</strong></p>
                <p>Call the Australian Emergency Number: <strong>000</strong> (Triple Zero) for immediate assistance from ambulance, police, or fire services.</p>
                <p>For non-urgent inquiries, contact our Emergency Department at: <strong>(02) 1234 5678</strong></p>
            </div>
        </div>
    </main>
    
    <footer>
        <p>© 2025 Hospital Management System</p>
    </footer>
</body>
</html>
<?php $conn->close(); ?>
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
    <title>Childcare Department</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            line-height: 1.6; 
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; 
            background-color: #f0f4f8;
            color: #333;
        }
        header { 
            background: linear-gradient(90deg, #2c3e50, #3498db); 
            color: #fff; 
            padding: 1em 0; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            padding: 10px 20px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
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
            background-color: #34495e; 
            overflow: hidden; 
            display: flex; 
        }
        nav li { 
            float: left; 
        }
        nav li a { 
            display: block; 
            color: white; 
            text-align: center; 
            padding: 14px 16px; 
            text-decoration: none; 
            font-weight: 500;
            transition: background-color 0.3s;
        }
        nav li a:hover { 
            background-color: #2980b9; 
        }
        nav li a.active { 
            background-color: #2980b9; 
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
            background-color: #2c3e50; 
            color: #fff; 
            text-align: center; 
            padding: 1em 0; 
            position: relative; 
            width: 100%; 
        }
        h2 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        p {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #555;
            margin-bottom: 1rem;
        }
        .treatments {
            font-size: 1rem;
            color: #333;
        }
        .treatments ul {
            list-style-type: disc;
            padding-left: 20px;
        }
        .treatments li {
            margin-bottom: 0.5rem;
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
                <li><a href="childcare.php" class="active">Childcare Department</a></li>
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
            <h2>Childcare Department</h2>
            <p>
                Our Childcare Department is committed to the health and well-being of children from infancy through adolescence. With a team of dedicated pediatric specialists, we provide comprehensive care, including routine check-ups, vaccinations, and treatment for common childhood illnesses, in a welcoming and child-friendly environment designed to support young patients and their families.
            </p>
            <div class="treatments">
                <p><strong>Common Treatments at Our Clinics:</strong></p>
                <ul>
                    <li><strong>Vaccinations:</strong> Administering essential immunizations (e.g., measles, polio, flu) to protect children from preventable diseases.</li>
                    <li><strong>Ear Infection Treatment:</strong> Diagnosing and treating otitis media with antibiotics or ear drops to relieve pain and prevent complications.</li>
                    <li><strong>Strep Throat Management:</strong> Testing and treating streptococcal infections with antibiotics to reduce symptoms and prevent spread.</li>
                    <li><strong>Asthma Care:</strong> Providing inhalers, medications, and management plans to help children breathe easier and control asthma symptoms.</li>
                </ul>
            </div>
        </div>
    </main>
    
    <footer>
        <p>© 2025 Hospital Management System</p>
    </footer>
</body>
</html>
<?php $conn->close(); ?>
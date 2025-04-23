<?php
$conn = new mysqli("localhost", "root", "", "hms_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $phoneno = $_POST["phoneno"];
    $enquiry = $_POST["enquiry"];

    if (empty($name) || empty($email) || empty($phoneno) || empty($enquiry)) {
        $message = "All fields are required!";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format!";
        $message_type = "error";
    } elseif (!preg_match("/^[0-9]{10}$/", $phoneno)) {
        $message = "Phone number must be exactly 10 digits!";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO enquiries (name, email, phoneno, enquiry) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $phoneno, $enquiry);
        if ($stmt->execute()) {
            $message = "Enquiry submitted successfully! We’ll get back to you soon.";
            $message_type = "success";
        } else {
            $message = "Error submitting enquiry: " . $conn->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Hospital Management System</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            line-height: 1.6; 
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; 
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
            padding-bottom: 60px; 
        }
        footer { 
            background-color: #333; 
            color: #fff; 
            text-align: center; 
            padding: 1em 0; 
            width: 100%; 
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

        /* Form Styles */
        .container { 
            background-color: #f9f9f9; 
            border-radius: 8px; 
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); 
            padding: 20px; 
            max-width: 600px; 
            width: 100%; 
            margin: 0 auto; 
        }
        h2 { 
            color: #3498db; 
            text-align: center; 
            margin-bottom: 20px; 
            font-size: 1.8rem; 
        }
        form label { 
            display: block; 
            margin: 10px 0 5px; 
            font-weight: bold; 
            color: #333; 
        }
        input, textarea { 
            width: 100%; 
            padding: 8px; 
            margin-bottom: 15px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            font-size: 14px; 
        }
        textarea { 
            height: 100px; 
            resize: vertical; 
        }
        button { 
            width: 100%; 
            background-color: #3498db; 
            color: #fff; 
            padding: 10px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 16px; 
        }
        button:hover { 
            background-color: #2980b9; 
        }
        .message-box { 
            position: fixed; 
            top: 20px; 
            left: 50%; 
            transform: translateX(-50%); 
            padding: 15px 25px; 
            border-radius: 5px; 
            color: #fff; 
            font-size: 16px; 
            text-align: center; 
            z-index: 1000; 
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.2); 
        }
        .message-success { 
            background-color: #27ae60; 
        }
        .message-error { 
            background-color: #c0392b; 
        }

        /* Map Styles */
        .map-container { 
            margin-top: 2rem; 
            text-align: center; 
        }
        .map-container h3 { 
            color: #3498db; 
            margin-bottom: 1rem; 
            font-size: 1.5rem; 
        }
        .map-toggle-btn { 
            background-color: #2c3e50; 
            color: #fff; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            margin-bottom: 1rem; 
        }
        .map-toggle-btn:hover { 
            background-color: #34495e; 
        }
        .map-content { 
            display: none; 
        }
        .map-content.active { 
            display: block; 
        }
        iframe { 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        .map-link { 
            color: #3498db; 
            text-decoration: none; 
            font-weight: bold; 
        }
        .map-link:hover { 
            text-decoration: underline; 
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const messageBox = document.querySelector(".message-box");
            if (messageBox) {
                setTimeout(() => {
                    messageBox.style.transition = "opacity 0.5s ease-out";
                    messageBox.style.opacity = "0";
                    setTimeout(() => messageBox.remove(), 500);
                }, 3000);
            }

            const toggleBtn = document.getElementById("map-toggle-btn");
            const mapContent = document.getElementById("map-content");
            toggleBtn.addEventListener("click", function() {
                mapContent.classList.toggle("active");
                toggleBtn.textContent = mapContent.classList.contains("active") ? "Hide Map" : "Show Map";
            });
        });
    </script>
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
                <li><a href="contact_us.php" class="active">Contact Us</a></li>
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
            <h2>Contact Us</h2>
            <form method="POST" action="contact_us.php">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
                
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
                
                <label for="phoneno">Phone Number:</label>
                <input type="text" id="phoneno" name="phoneno" required maxlength="10">
                
                <label for="enquiry">Enquiry About:</label>
                <textarea id="enquiry" name="enquiry" required></textarea>
                
                <button type="submit">Submit Enquiry</button>
            </form>
            <?php if (!empty($message)) { ?>
                <div class="message-box <?php echo $message_type === 'success' ? 'message-success' : 'message-error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>

            <div class="map-container">
                <h3>Our Location - Lidcombe, NSW</h3>
                <button id="map-toggle-btn" class="map-toggle-btn">Show Map</button>
                <div id="map-content" class="map-content">
                    <iframe 
                        width="100%" 
                        height="300" 
                        frameborder="0" 
                        style="border:0" 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3312.396392668103!2d151.0436113152098!3d-33.86614498065834!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x6b12bc8e6c5f6d8d%3A0x5017d681632b9a0!2sLidcombe%20NSW%202145%2C%20Australia!5e0!3m2!1sen!2sus!4v1634567890123" 
                        allowfullscreen>
                    </iframe>
                    <p>View on Google Maps: <a href="https://www.google.com/maps/place/Lidcombe+NSW+Australia" target="_blank" class="map-link">Lidcombe, NSW</a></p>
                </div>
            </div>
        </div>
    </main>
    
    <footer>
        <p>© 2025 Hospital Management System</p>
    </footer>
</body>
</html>
<?php $conn->close(); ?>
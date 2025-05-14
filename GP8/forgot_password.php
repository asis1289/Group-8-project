<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "hms_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
$message = "";
$message_type = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phoneno = $_POST["phoneno"];

    // Check if account exists
    $stmt = $conn->prepare("SELECT p.pid FROM patient p JOIN users u ON p.pid = u.pid WHERE p.phoneno = ?");
    $stmt->bind_param("s", $phoneno);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $message = "Account found!";
        $message_type = "success";
        $_SESSION['reset_phoneno'] = $phoneno; // Store phoneno for reset_password.php
    } else {
        $message = "No account found with this phone number.";
        $message_type = "error";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Hospital Management System</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Arial', sans-serif; 
            background: linear-gradient(135deg, #e0f7fa, #b2ebf2); 
            color: #333; 
            line-height: 1.6; 
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; 
        }
        header { 
            background: linear-gradient(90deg, #1a237e, #3f51b5); 
            color: #fff; 
            padding: 1rem; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); 
        }
        .logo-container { 
            display: flex; 
            align-items: center; 
            gap: 15px; 
        }
        .logo, .health-logo { 
            width: 100px; 
            height: auto; 
            transition: transform 0.3s; 
        }
        .logo:hover, .health-logo:hover { 
            transform: scale(1.05); 
        }
        nav ul { 
            list-style: none; 
            display: flex; 
            background: #283593; 
            border-radius: 5px; 
        }
        nav li a { 
            color: #fff; 
            padding: 14px 20px; 
            text-decoration: none; 
            font-weight: 600; 
            transition: background 0.3s, color 0.3s; 
        }
        nav li a:hover { 
            background: #5c6bc0; 
        }
        nav li a.active { 
            background: #5c6bc0; 
            border-radius: 5px; 
        }
        main { 
            flex: 1; 
            padding: 40px 20px; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
        }
        .forgot-password-container { 
            background: #fff; 
            padding: 2rem; 
            border-radius: 12px; 
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15); 
            max-width: 450px; 
            width: 100%; 
            animation: fadeIn 0.5s ease-in; 
        }
        @keyframes fadeIn { 
            from { opacity: 0; transform: translateY(20px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        h2 { 
            color: #1a237e; 
            text-align: center; 
            margin-bottom: 1.5rem; 
            font-size: 1.8rem; 
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1); 
        }
        form label { 
            display: block; 
            margin: 10px 0 5px; 
            font-weight: 600; 
            color: #1a237e; 
        }
        input { 
            width: 100%; 
            padding: 10px; 
            margin-bottom: 15px; 
            border: 2px solid #ddd; 
            border-radius: 6px; 
            font-size: 1rem; 
            transition: border-color 0.3s; 
        }
        input:focus { 
            border-color: #3f51b5; 
            outline: none; 
        }
        button { 
            width: 100%; 
            background: linear-gradient(90deg, #3f51b5, #5c6bc0); 
            color: #fff; 
            padding: 12px; 
            border: none; 
            border-radius: 6px; 
            font-size: 1.1rem; 
            cursor: pointer; 
            transition: background 0.3s, transform 0.2s; 
        }
        button:hover { 
            background: linear-gradient(90deg, #5c6bc0, #3f51b5); 
            transform: translateY(-2px); 
        }
        .message { 
            text-align: center; 
            margin: 15px 0; 
            font-size: 1rem; 
            padding: 10px; 
            border-radius: 5px; 
            animation: slideIn 0.5s ease-out; 
        }
        .message.success { 
            background: #d4edda; 
            color: #155724; 
        }
        .message.error { 
            background: #f8d7da; 
            color: #721c24; 
        }
        @keyframes slideIn { 
            from { opacity: 0; transform: translateY(-20px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        .right-menu { 
            display: flex; 
            align-items: center; 
        }
        .dropdown { 
            position: relative; 
        }
        .dropbtn { 
            background: #283593; 
            color: #fff; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            transition: background 0.3s; 
        }
        .dropbtn:hover { 
            background: #5c6bc0; 
        }
        .dropdown-content { 
            display: none; 
            position: absolute; 
            right: 0; 
            background: #fff; 
            min-width: 200px; 
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2); 
            border-radius: 5px; 
            z-index: 1; 
        }
        .dropdown-content a { 
            color: #333; 
            padding: 12px 16px; 
            display: block; 
            text-decoration: none; 
            transition: background 0.3s; 
        }
        .dropdown-content a:hover { 
            background: #e8eaf6; 
        }
        .dropdown:hover .dropdown-content { 
            display: block; 
        }
        .signup-link { 
            color: #3f51b5; 
            font-weight: 600; 
            text-align: center; 
            padding: 12px; 
        }
        .signup-link:hover { 
            text-decoration: underline; 
        }
        footer { 
            background: #1a237e; 
            color: #fff; 
            text-align: center; 
            padding: 1rem; 
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.2); 
        }
        .popup { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0, 0, 0, 0.5); 
            z-index: 1000; 
            justify-content: center; 
            align-items: center; 
        }
        .popup-content { 
            background: #fff; 
            padding: 20px; 
            border-radius: 12px; 
            width: 90%; 
            max-width: 450px; 
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15); 
            position: relative; 
            animation: fadeIn 0.3s ease-in; 
        }
        .close-btn { 
            position: absolute; 
            top: 10px; 
            right: 10px; 
            font-size: 18px; 
            color: #721c24; 
            cursor: pointer; 
            border: none; 
            background: none; 
            padding: 5px 10px; 
            display: flex; 
            align-items: center; 
            gap: 5px; 
            transition: color 0.3s ease, transform 0.2s; 
        }
        .close-btn:hover { 
            color: #9b1c2c; 
            transform: scale(1.1); 
        }
        .close-btn span.cross { 
            font-size: 24px; 
            line-height: 1; 
        }
    </style>
    <script>
        function showPopup() {
            // Hide the success message
            const messageDiv = document.querySelector('.message.success');
            if (messageDiv) {
                messageDiv.style.display = 'none';
            }
            // Show the popup
            document.getElementById('resetPopup').style.display = 'flex';
        }
        function closePopup() {
            document.getElementById('resetPopup').style.display = 'none';
        }
        <?php if ($message_type === 'success') { ?>
            document.addEventListener("DOMContentLoaded", function() {
                setTimeout(showPopup, 2500); // Delay popup and message removal by 2.5 seconds
            });
        <?php } ?>
    </script>
</head>
<body>
    <header>
        <div class="logo-container">
            <a href="index.php"><img src="images/new_logo.jpeg" alt="Hospital Logo" class="logo"></a>
            <a href="index.php"><img src="images/health.jpeg" alt="NSW Health" class="health-logo"></a>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
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
                    <a href="user_login.php" class="active">Login as User</a>
                    <hr>
                    <a href="signup.php" class="signup-link">Don't have an account? Sign up here</a>
                </div>
            </div>
        </div>
    </header>
    
    <main>
        <div class="forgot-password-container">
            <h2>Forgot Password</h2>
            <form method="POST" action="forgot_password.php">
                <label for="phoneno">Phone Number</label>
                <input type="text" id="phoneno" name="phoneno" placeholder="Enter your phone number" required>
                <button type="submit">Check Account</button>
            </form>
            <?php if (!empty($message)) { ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>
        </div>
    </main>
    
    <!-- Popup for Reset Password -->
    <div id="resetPopup" class="popup">
        <div class="popup-content">
            <button class="close-btn" onclick="closePopup()">
                <span class="cross">×</span> Cancel
            </button>
            <iframe src="reset_password.php" style="width: 100%; height: 400px; border: none;"></iframe>
        </div>
    </div>
    
    <footer>
        <p>© 2025 Hospital Management System. All rights reserved.</p>
    </footer>
</body>
</html>
<?php $conn->close(); ?>
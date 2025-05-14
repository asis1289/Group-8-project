<?php
session_start();
$conn = new mysqli("localhost", "root", "", "hms_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = ""; // Initialize message variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Check if username is unique
    $check_username = $conn->prepare("SELECT username FROM users WHERE username = ?");
    $check_username->bind_param("s", $username);
    $check_username->execute();
    $username_result = $check_username->get_result();

    if ($username_result->num_rows > 0) {
        $message = "Error: Username already taken.";
    } else {
        // Match name with patient table to fetch pid
        $check_patient = $conn->prepare("SELECT pid FROM patient WHERE name = ?");
        $check_patient->bind_param("s", $name);
        $check_patient->execute();
        $patient_result = $check_patient->get_result();

        if ($patient_result->num_rows > 0) {
            $patient = $patient_result->fetch_assoc();
            $pid = $patient["pid"];

            // Check if pid is already linked to another user
            $check_pid = $conn->prepare("SELECT pid FROM users WHERE pid = ?");
            $check_pid->bind_param("i", $pid);
            $check_pid->execute();
            $pid_result = $check_pid->get_result();

            if ($pid_result->num_rows == 0) {
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (pid, username, password) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $pid, $username, $password);
                if ($stmt->execute()) {
                    $message = "Signup successful! Redirecting to login...";
                    header("Refresh: 2; url=user_login.php"); // Redirect after 2 seconds
                } else {
                    $message = "Error: " . $conn->error;
                }
                $stmt->close();
            } else {
                $message = "Error: This patient is already registered with a username.";
            }
            $check_pid->close();
        } else {
            $message = "Error: Patient name not found. Please contact an admin to register.";
        }
        $check_patient->close();
    }
    $check_username->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Signup</title>
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
        .signup-container { 
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
        .login-link { 
            text-align: center; 
            margin-top: 15px; 
        }
        .login-link a { 
            color: #3f51b5; 
            font-weight: 600; 
            text-decoration: none; 
            transition: color 0.3s; 
        }
        .login-link a:hover { 
            color: #5c6bc0; 
            text-decoration: underline; 
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
    </style>
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
                    <a href="user_login.php">Login as User</a>
                    <hr>
                    <a href="signup.php" class="signup-link">Don't have an account? Sign up here</a>
                </div>
            </div>
        </div>
    </header>
    
    <main>
        <div class="signup-container">
            <h2>Patient Signup</h2>
            <form method="POST">
                <label for="name">Your Full Name (as registered)</label>
                <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                <label for="username">Choose Username</label>
                <input type="text" id="username" name="username" placeholder="Enter a unique username" required>
                <label for="password">Choose Password</label>
                <input type="password" id="password" name="password" placeholder="Enter a strong password" required>
                <button type="submit">Sign Up Now</button>
            </form>
            <?php if (!empty($message)) { ?>
                <div class="message <?php echo strpos($message, 'Error') === false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>
            <div class="login-link">
                <p>Already have an account? <a href="user_login.php">Login here</a></p>
            </div>
        </div>
    </main>
    
    <footer>
        <p>© 2025 Hospital Management System. All rights reserved.</p>
    </footer>
</body>
</html>
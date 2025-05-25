<?php
$conn = new mysqli("localhost", "root", "", "hms_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $age = $_POST["age"];
    $dob = $_POST["dob"];
    $gender = $_POST["gender"];
    $phoneno = $_POST["phoneno"];

    if (!preg_match('/^[0-9]{10}$/', $phoneno)) {
        $message = "Error: Phone number must be exactly 10 digits and contain only numbers.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("SELECT pid FROM patient WHERE name = ? AND dob = ?");
        $stmt->bind_param("ss", $name, $dob);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = "Patient already exists. Please login or book an appointment.";
            $message_type = "error";
            $stmt->close();
        } else {
            $stmt->close();

            if (empty($age) || preg_match('/^0(\s|$)/', $age)) {
                $message = "Error: Age cannot be zero or invalid. Please enter a valid Date of Birth.";
                $message_type = "error";
            } else {
                $stmt = $conn->prepare("INSERT INTO patient (name, age, dob, gender, phoneno) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $name, $age, $dob, $gender, $phoneno);
                if ($stmt->execute()) {
                    $message = "Patient registered successfully! Patient ID: " . $conn->insert_id;
                    $message_type = "success";
                } else {
                    $message = "Error registering patient: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Patient - Hospital Management System</title>
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
            max-width: 450px; 
            width: 100%; 
            margin: 0 auto; 
        }
        h2 { 
            color: #3498db; 
            text-align: center; 
            margin-bottom: 20px; 
        }
        form label { 
            display: block; 
            margin: 10px 0 5px; 
            font-weight: bold; 
            color: #333; 
        }
        input, select { 
            width: 100%; 
            padding: 8px; 
            margin-bottom: 15px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            font-size: 14px; 
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
        .error-message { 
            color: #c0392b; 
            font-size: 14px; 
            margin-top: -10px; 
            margin-bottom: 10px; 
            display: none; 
        }

        /* Floating Guide Box */
        .guide-box { 
            position: fixed; 
            bottom: 70px; /* Adjusted to stay above footer */
            right: 20px; 
            background-color: #f9f9f9; 
            border-radius: 8px; 
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2); 
            padding: 20px; 
            max-width: 300px; 
            width: 100%; 
            z-index: 1000; 
            border-left: 5px solid #3498db; 
            opacity: 0.95; /* Slight transparency for subtlety */
            transition: opacity 0.3s ease; 
        }
        .guide-box:hover { 
            opacity: 1; /* Full opacity on hover for better visibility */
        }
        .guide-box h3 { 
            color: #3498db; 
            margin-bottom: 15px; 
            font-size: 18px; 
        }
        .guide-box p { 
            color: #666; 
            font-size: 14px; 
            line-height: 1.4; 
        }
        .guide-box a { 
            color: #3498db; 
            text-decoration: none; 
            font-weight: bold; 
        }
        .guide-box a:hover { 
            text-decoration: underline; 
        }
    </style>
    <script>
        function calculateAge() {
            const dobInput = document.getElementById("dob").value;
            if (dobInput) {
                const today = new Date();
                const birthDate = new Date(dobInput);

                if (birthDate >= today) {
                    alert("Date of Birth cannot be today or in the future!");
                    document.getElementById("dob").value = "";
                    document.getElementById("age").value = "";
                    return;
                }

                const diffMs = today - birthDate;
                const days = Math.floor(diffMs / (1000 * 60 * 60 * 24));

                if (days < 1) {
                    alert("Patient must be at least 1 day old!");
                    document.getElementById("dob").value = "";
                    document.getElementById("age").value = "";
                    return;
                }

                const years = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                let ageYears = years;
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    ageYears--;
                }

                if (ageYears >= 1) {
                    document.getElementById("age").value = ageYears;
                } else {
                    const months = Math.floor(days / 30);
                    const remainingDays = days % 30;

                    if (months > 0) {
                        document.getElementById("age").value = 
                            months + " month" + (months > 1 ? "s" : "") + 
                            (remainingDays > 0 ? " " + remainingDays + " day" + (remainingDays > 1 ? "s" : "") : "");
                    } else {
                        document.getElementById("age").value = days + " day" + (days > 1 ? "s" : "");
                    }
                }
            }
        }

        function validatePhoneNumber() {
            const phoneInput = document.getElementById("phoneno");
            const errorMessage = document.getElementById("phone-error");
            const phoneValue = phoneInput.value;
            const digitsOnly = phoneValue.replace(/\D/g, '');
            
            phoneInput.value = digitsOnly;
            if (digitsOnly.length > 10) {
                phoneInput.value = digitsOnly.substring(0, 10);
                errorMessage.textContent = "Phone number cannot exceed 10 digits";
                errorMessage.style.display = "block";
                return false;
            }
            if (phoneValue !== digitsOnly) {
                errorMessage.textContent = "Phone number can only contain numbers";
                errorMessage.style.display = "block";
                return false;
            }
            if (digitsOnly.length !== 10) {
                errorMessage.textContent = "Phone number must be exactly 10 digits";
                errorMessage.style.display = "block";
                return false;
            }
            errorMessage.style.display = "none";
            return true;
        }

        document.addEventListener("DOMContentLoaded", function() {
            const messageBox = document.querySelector(".message-box");
            if (messageBox) {
                setTimeout(() => {
                    messageBox.style.opacity = "0";
                    setTimeout(() => messageBox.remove(), 500);
                }, 3000);
            }
            
            const phoneInput = document.getElementById("phoneno");
            if (phoneInput) {
                phoneInput.addEventListener("input", validatePhoneNumber);
                phoneInput.addEventListener("blur", validatePhoneNumber);
            }
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
                <li><a href="register.php" class="active">Register Patient</a></li>
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
        <div class="container">
            <h2>Register Patient</h2>
            <form method="POST" action="register.php" onsubmit="return validatePhoneNumber()">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required placeholder="Enter your full name">
                <label for="dob">Date of Birth:</label>
                <input type="date" id="dob" name="dob" required onchange="calculateAge()">
                <label for="age">Age:</label>
                <input type="text" id="age" name="age" readonly>
                <label for="gender">Gender:</label>
                <select id="gender" name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
                <label for="phoneno">Phone Number:</label>
                <input type="text" id="phoneno" name="phoneno" required maxlength="10" pattern="[0-9]{10}" title="Please enter exactly 10 digits">
                <div id="phone-error" class="error-message"></div>
                <button type="submit">Register Now</button>
            </form>
            <?php if (!empty($message)) { ?>
                <div class="message-box <?php echo $message_type === 'success' ? 'message-success' : 'message-error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php } ?>
        </div>

        <!-- Floating Guide Box -->
        <div class="guide-box">
            <h3>Next Steps</h3>
            <p><strong>Important:</strong> Users must register as a patient by filling out the form. Then you can book your appointment. You'll need to signup and log in for more services: <a href="signup.php"> Signup Here </a></p>
        </div>
    </main>
    
    <footer>
        <p>© 2025 Hospital Management System</p>
    </footer>
</body>
</html>
<?php $conn->close(); ?>
<?php
$conn = new mysqli("localhost", "root", "", "hms_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set timezone to AEDT
date_default_timezone_set("Australia/Sydney");

// Function to generate unique 4-digit bill number
function generateBillNo($conn) {
    do {
        $bill_no = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $check = $conn->query("SELECT bill_no FROM bill WHERE bill_no = '$bill_no'");
    } while ($check->num_rows > 0);
    return $bill_no;
}

// Fetch doctors with dept
$doctors = $conn->query("SELECT doctorid, doctorname, dept FROM doctor");

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST["full_name"];
    $doctorid = $_POST["doctorid"];
    $appt_date = $_POST["appt_date"];
    $reason = $_POST["reason"];
    $status = "Scheduled";

    // Validate patient by full name
    $stmt = $conn->prepare("SELECT pid FROM Patient WHERE name = ?");
    $stmt->bind_param("s", $full_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $patient = $result->fetch_assoc();
        $pid = $patient['pid'];

        // Check if appointment date is in the past
        $current_date = new DateTime();
        $appointment_date = new DateTime($appt_date);
        if ($appointment_date < $current_date) {
            $message = "Error: Cannot book an appointment in the past!";
            $message_type = "error";
        } else {
            // Start transaction
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO Appointment (pid, doctorid, appt_date, reason, status) 
                                      VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $pid, $doctorid, $appt_date, $reason, $status);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error booking appointment: " . $conn->error);
                }

                $appt_id = $conn->insert_id;
                $bill_no = generateBillNo($conn);

                $bill_stmt = $conn->prepare("INSERT INTO bill (bill_no, appt_id) VALUES (?, ?)");
                $bill_stmt->bind_param("si", $bill_no, $appt_id);
                
                if (!$bill_stmt->execute()) {
                    throw new Exception("Error creating bill: " . $conn->error);
                }

                $conn->commit();
                $message = "Appointment booked successfully! Appointment ID: $appt_id, Bill No: $bill_no";
                $message_type = "success";
                $stmt->close();
                $bill_stmt->close();
            } catch (Exception $e) {
                $conn->rollback();
                $message = $e->getMessage();
                $message_type = "error";
            }
        }
    } else {
        $message = "Error: Patient not found. Please register with this name first.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Hospital Management System</title>
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
        input, select, textarea { 
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

        /* Floating Guide Box */
        .guide-box { 
            position: fixed; 
            bottom: 70px; 
            right: 20px; 
            background-color: #f9f9f9; 
            border-radius: 8px; 
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2); 
            padding: 20px; 
            max-width: 300px; 
            width: 100%; 
            z-index: 1000; 
            border-left: 5px solid #3498db; 
            opacity: 0.95; 
            transition: opacity 0.3s ease; 
        }
        .guide-box:hover { 
            opacity: 1; 
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
            margin-bottom: 10px; 
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
        document.addEventListener("DOMContentLoaded", function () {
            const apptDateInput = document.getElementById("appt_date");
            const now = new Date();
            const tzOffset = 11 * 60; // AEDT offset in minutes (UTC+11)
            const localISOTime = new Date(now.getTime() + tzOffset * 60000).toISOString().slice(0, 16);
            apptDateInput.setAttribute("min", localISOTime);

            apptDateInput.addEventListener("change", function () {
                const selectedDate = new Date(apptDateInput.value);
                const currentDate = new Date(now.getTime() + tzOffset * 60000);
                if (selectedDate < currentDate) {
                    alert("Error: You cannot select a past date/time for the appointment!");
                    apptDateInput.value = localISOTime;
                }
            });

            const messageBox = document.querySelector(".message-box");
            if (messageBox) {
                setTimeout(() => {
                    messageBox.style.transition = "opacity 0.5s ease-out";
                    messageBox.style.opacity = "0";
                    setTimeout(() => messageBox.remove(), 500);
                }, 3000);
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
                <li><a href="register.php">Register Patient</a></li>
                <li><a href="appointment.php" class="active">Book Appointment</a></li>
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
            <h2>Book Appointment</h2>
            <form method="POST" action="appointment.php">
                <label for="full_name">Your Full Name (as registered):</label>
                <input type="text" id="full_name" name="full_name" required placeholder="Enter your full name">
                <label for="doctorid">Doctor:</label>
                <select id="doctorid" name="doctorid" required>
                    <option value="">Select Doctor</option>
                    <?php
                    $doctors->data_seek(0);
                    while ($row = $doctors->fetch_assoc()) {
                        echo "<option value='{$row['doctorid']}'>{$row['doctorname']} - {$row['dept']}</option>";
                    }
                    ?>
                </select>
                <label for="appt_date">Appointment Date:</label>
                <input type="datetime-local" id="appt_date" name="appt_date" required>
                <label for="reason">Reason to Visit:</label>
                <textarea id="reason" name="reason" required placeholder="Describe your reason for visit"></textarea>
                <button type="submit">Book Now</button>
            </form>
            <?php if (!empty($message)) { ?>
                <div class="message-box <?php echo $message_type === 'success' ? 'message-success' : 'message-error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>
        </div>

        <!-- Floating Guide Box -->
        <div class="guide-box">
            <h3>Next Step</h3>
            <p><strong>Important:</strong> User can view their details by creating an account using the name used to register and book an appointment. For further use of hospital services, please signup now.</p>
            <a href="signup.php">Sign Up Here</a>
        </div>
    </main>
    
    <footer>
        <p>© 2025 Hospital Management System</p>
    </footer>
</body>
</html>
<?php $conn->close(); ?>
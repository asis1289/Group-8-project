<?php
$conn = new mysqli("localhost", "root", "", "hms_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = ""; 
$message_type = ""; 
$bill_data = null; 

require 'lib/fpdf.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["search"])) {
    $bill_no = !empty($_POST["bill_no"]) ? $_POST["bill_no"] : null; 
    $dob = $_POST["dob"];
    $name = $_POST["name"];
    $bill_date = $_POST["bill_date"]; 

    $dob_date = new DateTime($dob);
    $today = new DateTime();
    if ($dob_date > $today) {
        $message = "Date of Birth cannot be in the future.";
        $message_type = "error";
    } else {
        $age_interval = $today->diff($dob_date);
        $age_years = $age_interval->y;
        $age_months = $age_interval->m + ($age_interval->y * 12) + ($age_interval->d > 0 ? 1 : 0); 

        $query = "
            SELECT b.bill_no, b.appt_id, b.doctor_charge, b.total_bill, b.bill_date, 
                   p.name, p.dob, p.gender, p.phoneno, 
                   d.doctorname, d.dept, 
                   a.appt_date, a.reason
            FROM bill b
            JOIN Appointment a ON b.appt_id = a.appt_id
            JOIN Patient p ON a.pid = p.pid
            JOIN Doctor d ON a.doctorid = d.doctorid
            WHERE p.name = ? AND p.dob = ? AND b.bill_date = ?
        ";
        if ($bill_no) {
            $query .= " AND b.bill_no = ?";
        }

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error); 
        }

        if ($bill_no) {
            $stmt->bind_param("sssi", $name, $dob, $bill_date, $bill_no);
        } else {
            $stmt->bind_param("sss", $name, $dob, $bill_date);
        }

        if (!$stmt->execute()) {
            die("Execute failed: " . $stmt->error); 
        }

        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $bill_data = $result->fetch_assoc();
            $bill_data['age_years'] = $age_years;
            $bill_data['age_months'] = $age_months; 
        } else {
            $message = "No bill found with the provided details.";
            $message_type = "error";
        }
        $stmt->close();
    }

    if ($bill_data && isset($_POST["download_pdf"])) {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->Image('images/new_logo.jpeg', 10, 10, 40);
        $pdf->Image('images/health.jpeg', 160, 10, 40);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Ln(35);
        $pdf->Cell(0, 10, 'Hospital Bill', 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 8, "Bill No: {$bill_data['bill_no']} | Appt ID: {$bill_data['appt_id']} | Date: {$bill_data['bill_date']}", 0, 1);
        $pdf->Ln(3);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'Patient:', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, "{$bill_data['name']} | DOB: {$bill_data['dob']} | {$bill_data['gender']} | Phone: {$bill_data['phoneno']}", 0, 1);
        $pdf->Ln(3);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'Doctor:', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, "{$bill_data['doctorname']} | Dept: {$bill_data['dept']}", 0, 1);
        $pdf->Ln(3);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'Appointment:', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, "Date: {$bill_data['appt_date']} | Reason: {$bill_data['reason']}", 0, 1);
        $pdf->Ln(3);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'Payment:', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, "Doctor Charge: $" . number_format($bill_data['doctor_charge'], 2), 0, 1);
        $pdf->Cell(0, 6, "Total (incl. 10% GST): $" . number_format($bill_data['total_bill'], 2), 0, 1);

        ob_clean();
        $pdf->Output('D', "bill_{$bill_data['bill_no']}.pdf");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Bills - Hospital Management System</title>
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

        /* Popup Styles */
        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            z-index: 2000;
            max-width: 700px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        .popup .logo-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .popup .logo-header img {
            width: 80px;
            height: auto;
        }
        .popup h1 {
            text-align: center;
            color: #3498db;
            font-size: 24px;
            margin-bottom: 15px;
        }
        .popup .bill-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }
        .popup .bill-item {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .popup .bill-item h2 {
            color: #3498db;
            font-size: 16px;
            margin-bottom: 8px;
        }
        .popup .bill-item p {
            margin: 4px 0;
            font-size: 14px;
        }
        .popup .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            font-size: 18px;
            line-height: 30px;
            text-align: center;
        }
        .popup .close-btn:hover {
            background: #c0392b;
        }
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1500;
        }
        .popup .actions {
            text-align: center;
            margin-top: 20px;
        }
        .popup .actions button {
            padding: 8px 16px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
        }
        .popup .actions button:hover {
            background-color: #2980b9;
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

            <?php if ($bill_data) { ?>
                document.getElementById("popup").style.display = "block";
                document.getElementById("overlay").style.display = "block";
            <?php } ?>
        });

        function closePopup() {
            document.getElementById("popup").style.display = "none";
            document.getElementById("overlay").style.display = "none";
        }
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
                <li><a href="bills.php" class="active">View Bills</a></li>
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
            <h2>View Bills</h2>
            <form method="POST" action="bills.php">
                <label for="bill_no">Bill Number (Optional):</label>
                <input type="number" id="bill_no" name="bill_no">
                <label for="name">Patient Name:</label>
                <input type="text" id="name" name="name" required>
                <label for="dob">Date of Birth:</label>
                <input type="date" id="dob" name="dob" required>
                <label for="bill_date">Bill Date:</label>
                <input type="date" id="bill_date" name="bill_date" required>
                <button type="submit" name="search">Search Bill</button>
            </form>

            <?php if (!empty($message)) { ?>
                <div class="message-box <?php echo $message_type === 'success' ? 'message-success' : 'message-error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>
        </div>

        <!-- Floating Guide Box -->
        <div class="guide-box">
            <h3>Next Steps</h3>
            <p><strong>Important:</strong> Register an account to manage your bills and appointments more easily.</p>
            <a href="signup.php">Sign Up Here</a>
        </div>
    </main>
    
    <!-- Popup for bill details -->
    <?php if ($bill_data) { ?>
    <div id="overlay" class="overlay"></div>
    <div id="popup" class="popup">
        <button class="close-btn" onclick="closePopup()">X</button>
        <div class="logo-header">
            <img src="images/new_logo.jpeg" alt="Hospital Logo">
            <img src="images/health.jpeg" alt="NSW Health">
        </div>
        <h1>Bill #<?php echo htmlspecialchars($bill_data["bill_no"]); ?></h1>

        <div class="bill-grid">
            <div class="bill-item">
                <h2>Patient</h2>
                <p><?php echo htmlspecialchars($bill_data["name"]); ?></p>
                <p>DOB: <?php echo htmlspecialchars($bill_data["dob"]); ?></p>
                <p>Age: <?php 
                    if ($bill_data['age_years'] < 1) {
                        echo htmlspecialchars($bill_data['age_months']) . " month" . ($bill_data['age_months'] != 1 ? "s" : "");
                    } else {
                        echo htmlspecialchars($bill_data['age_years']) . " year" . ($bill_data['age_years'] != 1 ? "s" : "");
                    }
                ?></p>
                <p><?php echo htmlspecialchars($bill_data["gender"]); ?></p>
                <p>Phone: <?php echo htmlspecialchars($bill_data["phoneno"]); ?></p>
            </div>
            <div class="bill-item">
                <h2>Doctor</h2>
                <p><?php echo htmlspecialchars($bill_data["doctorname"]); ?></p>
                <p>Dept: <?php echo htmlspecialchars($bill_data["dept"]); ?></p>
            </div>
            <div class="bill-item">
                <h2>Appointment</h2>
                <p>ID: <?php echo htmlspecialchars($bill_data["appt_id"]); ?></p>
                <p>Date: <?php echo htmlspecialchars($bill_data["appt_date"]); ?></p>
                <p>Reason: <?php echo htmlspecialchars($bill_data["reason"]); ?></p>
            </div>
            <div class="bill-item">
                <h2>Bill</h2>
                <p>Date: <?php echo htmlspecialchars($bill_data["bill_date"]); ?></p>
                <p>Charge: $<?php echo number_format($bill_data["doctor_charge"], 2); ?></p>
                <p>Total: $<?php echo number_format($bill_data["total_bill"], 2); ?></p>
            </div>
        </div>

        <div class="actions">
            <form method="POST" action="bills.php">
                <input type="hidden" name="search" value="1">
                <input type="hidden" name="bill_no" value="<?php echo htmlspecialchars($bill_data['bill_no']); ?>">
                <input type="hidden" name="name" value="<?php echo htmlspecialchars($bill_data['name']); ?>">
                <input type="hidden" name="dob" value="<?php echo htmlspecialchars($bill_data['dob']); ?>">
                <input type="hidden" name="bill_date" value="<?php echo htmlspecialchars($bill_data['bill_date']); ?>">
                <button type="submit" name="download_pdf">Download PDF</button>
            </form>
        </div>
    </div>
    <?php } ?>
    
    <footer>
        <p>© 2025 Hospital Management System</p>
    </footer>
</body>
</html>
<?php $conn->close(); ?>
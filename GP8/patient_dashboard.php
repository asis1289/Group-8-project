<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: user_login.php");
    exit();
}

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

// Get patient details
$username = $_SESSION["user"];
$stmt = $conn->prepare("SELECT p.pid, p.name, p.age, p.dob, p.gender, p.phoneno, u.username, u.password 
                        FROM patient p 
                        JOIN users u ON p.pid = u.pid 
                        WHERE u.username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$patient_result = $stmt->get_result();
$patient = $patient_result->fetch_assoc();
$stmt->close();

// Handle reschedule request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reschedule"])) {
    $appt_id = $_POST["appt_id"];
    $new_date = $_POST["new_date"];

    $current_date = new DateTime();
    $appointment_date = new DateTime($new_date);
    if ($appointment_date < $current_date) {
        $message = "Cannot reschedule to a past date!";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("UPDATE appointment SET appt_date = ?, status = 'Rescheduled' WHERE appt_id = ? AND pid = ?");
        $stmt->bind_param("sii", $new_date, $appt_id, $patient["pid"]);
        if ($stmt->execute()) {
            $message = "Appointment rescheduled successfully!";
            $message_type = "success";
        } else {
            $message = "Error rescheduling: " . $conn->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}

// Handle cancel request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["cancel"])) {
    $appt_id = $_POST["appt_id"];

    $stmt = $conn->prepare("UPDATE appointment SET status = 'Cancelled' WHERE appt_id = ? AND pid = ?");
    $stmt->bind_param("ii", $appt_id, $patient["pid"]);
    if ($stmt->execute()) {
        $message = "Appointment cancelled successfully!";
        $message_type = "success";
    } else {
        $message = "Error cancelling appointment: " . $conn->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Handle book appointment from popup
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["book_appointment"])) {
    $doctorid = $_POST["doctorid"];
    $appt_date = $_POST["appt_date"];
    $reason = $_POST["reason"];
    $status = "Scheduled";

    $current_date = new DateTime();
    $appointment_date = new DateTime($appt_date);
    if ($appointment_date < $current_date) {
        $message = "Cannot book an appointment in the past!";
        $message_type = "error";
    } else {
        // Start transaction
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO appointment (pid, doctorid, appt_date, reason, status) 
                                    VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $patient["pid"], $doctorid, $appt_date, $reason, $status);
            
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
}

// Handle edit personal details
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["edit_details"])) {
    $phoneno = $_POST["phoneno"];
    $new_username = $_POST["username"];
    $gender = $_POST["gender"];
    $password = $_POST["password"];

    // Update patient table
    $stmt = $conn->prepare("UPDATE patient SET phoneno = ?, gender = ? WHERE pid = ?");
    $stmt->bind_param("ssi", $phoneno, $gender, $patient["pid"]);
    $patient_success = $stmt->execute();
    $stmt->close();

    // Update users table
    $stmt = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE pid = ?");
    $stmt->bind_param("ssi", $new_username, $password, $patient["pid"]);
    $user_success = $stmt->execute();
    $stmt->close();

    if ($patient_success && $user_success) {
        $message = "Changes made successfully!";
        $message_type = "success";
        $_SESSION["user"] = $new_username;
        $stmt = $conn->prepare("SELECT p.pid, p.name, p.age, p.dob, p.gender, p.phoneno, u.username, u.password 
                                FROM patient p 
                                JOIN users u ON p.pid = u.pid 
                                WHERE u.username = ?");
        $stmt->bind_param("s", $new_username);
        $stmt->execute();
        $patient_result = $stmt->get_result();
        $patient = $patient_result->fetch_assoc();
        $stmt->close();
    } else {
        $message = "Error updating details: " . $conn->error;
        $message_type = "error";
    }
}

// Get upcoming appointments
$pid = $patient["pid"];
$stmt = $conn->prepare("SELECT a.appt_id, d.doctorname, d.dept, a.appt_date, a.reason, a.status 
                        FROM appointment a 
                        JOIN doctor d ON a.doctorid = d.doctorid 
                        WHERE a.pid = ? AND a.appt_date >= NOW() AND a.status NOT IN ('Completed', 'Cancelled')");
$stmt->bind_param("i", $pid);
$stmt->execute();
$upcoming_result = $stmt->get_result();

// Get appointment history
$stmt = $conn->prepare("SELECT a.appt_id, d.doctorname, d.dept, a.appt_date, a.reason, a.status, b.bill_no 
                        FROM appointment a 
                        JOIN doctor d ON a.doctorid = d.doctorid 
                        LEFT JOIN bill b ON a.appt_id = b.appt_id 
                        WHERE a.pid = ? AND a.status IN ('Completed', 'Cancelled')");
$stmt->bind_param("i", $pid);
$stmt->execute();
$history_result = $stmt->get_result();

// Fetch doctors for popup
$doctors = $conn->query("SELECT doctorid, doctorname, dept FROM doctor");

// Set minimum date
$current_min_date = date('Y-m-d\TH:i');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - Hospital Management System</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background-color: #fff; /* White, matching index.php */
            color: #333; 
            line-height: 1.6; 
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; 
        }
        header { 
            background: #3498db; /* Sky blue, matching index.php */
            color: #fff; 
            padding: 1rem 2rem; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); 
        }
        .logo-container { 
            display: flex; 
            align-items: center; 
            gap: 15px; 
        }
        .logo, .health-logo { 
            width: 90px; 
            height: auto; 
            transition: transform 0.3s ease; 
        }
        .logo:hover, .health-logo:hover { 
            transform: scale(1.05); 
        }
        .page-message { 
            font-size: 1.4rem; 
            font-weight: 500; 
            letter-spacing: 0.5px; 
        }
        .welcome-text { 
            font-size: 1.2rem; 
            font-weight: 500; 
        }
        .logout-btn { 
            background: #e74c3c; 
            color: #fff; 
            padding: 8px 16px; 
            border-radius: 6px; 
            text-decoration: none; 
            font-size: 0.9rem; 
            font-weight: 500; 
            transition: background 0.3s ease, transform 0.2s ease; 
        }
        .logout-btn:hover { 
            background: #c0392b; 
            transform: translateY(-2px); 
        }
        main { 
            flex: 1; 
            padding: 30px 20px; 
        }
        .dashboard { 
            max-width: 1200px; 
            margin: 0 auto; 
        }
        .section { 
            background: #f9f9f9; /* Off-white, like index.php containers */
            padding: 20px; 
            border-radius: 10px; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); 
            margin-bottom: 20px; 
            animation: fadeIn 0.5s ease-in; 
        }
        @keyframes fadeIn { 
            from { opacity: 0; transform: translateY(10px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        h2 { 
            color: #3498db; /* Sky blue */
            font-size: 1.8rem; 
            margin-bottom: 15px; 
            text-align: center; 
            font-weight: 600; 
        }
        h3 { 
            color: #3498db; 
            font-size: 1.5rem; 
            margin-bottom: 15px; 
            text-align: center; 
            font-weight: 500; 
        }
        .patient-details { position: relative; }
        .patient-details p { 
            margin: 10px 0; 
            font-size: 0.95rem; 
            display: flex; 
            justify-content: space-between; 
            max-width: 400px; 
            margin-left: auto; 
            margin-right: auto; 
        }
        .patient-details p strong { color: #333; }
        .edit-btn { 
            position: absolute; 
            top: 20px; 
            right: 20px; 
            background: #3498db; 
            color: #fff; 
            padding: 8px 16px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 0.9rem; 
            transition: background 0.3s ease, transform 0.2s ease; 
        }
        .edit-btn:hover { 
            background: #2980b9; 
            transform: translateY(-2px); 
        }
        .message-box { 
            position: fixed; 
            top: 20px; 
            left: 50%; 
            transform: translateX(-50%); 
            padding: 1rem 2rem; 
            border-radius: 8px; 
            color: #fff; 
            font-size: 1rem; 
            text-align: center; 
            z-index: 1000; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); 
            animation: slideIn 0.5s ease-out; 
        }
        @keyframes slideIn { 
            from { opacity: 0; top: 0; } 
            to { opacity: 1; top: 20px; } 
        }
        .message-success { background: #4caf50; }
        .message-error { background: #e74c3c; }
        table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0; 
            margin-top: 15px; 
            font-size: 0.95rem; 
        }
        th, td { 
            padding: 12px 15px; 
            text-align: left; 
            border-bottom: 1px solid #e0e0e0; 
        }
        th { 
            background: #3498db; 
            color: #fff; 
            font-weight: 600; 
        }
        td { background: #fff; }
        tr:hover td { background: #e6f3fa; /* Light blue hover */ }
        .book-btn { 
            background: #3498db; 
            color: #fff; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 0.95rem; 
            display: block; 
            margin: 15px auto; 
            font-weight: 500; 
            transition: background 0.3s ease, transform 0.2s ease; 
        }
        .book-btn:hover { 
            background: #2980b9; 
            transform: translateY(-2px); 
        }
        .reschedule-btn { 
            background: #4caf50; 
            color: #fff; 
            padding: 6px 12px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 0.85rem; 
            margin-right: 5px; 
            transition: background 0.3s ease, transform 0.2s ease; 
        }
        .reschedule-btn:hover { 
            background: #388e3c; 
            transform: translateY(-2px); 
        }
        .cancel-btn { 
            background: #e74c3c; 
            color: #fff; 
            padding: 6px 12px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 0.85rem; 
            transition: background 0.3s ease, transform 0.2s ease; 
        }
        .cancel-btn:hover { 
            background: #c0392b; 
            transform: translateY(-2px); 
        }
        .view-pay-btn { 
            background: #4caf50; 
            color: #fff; 
            padding: 6px 12px; 
            border: none; 
            border-radius: 5px; 
            text-decoration: none; 
            font-size: 0.85rem; 
            transition: background 0.3s ease, transform 0.2s ease; 
        }
        .view-pay-btn:hover { 
            background: #388e3c; 
            transform: translateY(-2px); 
        }
        .reschedule-form { 
            display: none; 
            margin-top: 10px; 
            background: #f9f9f9; 
            padding: 10px; 
            border-radius: 5px; 
        }
        .reschedule-form input[type="datetime-local"] { 
            width: 70%; 
            padding: 8px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            margin-bottom: 10px; 
            font-size: 0.9rem; 
        }
        .reschedule-form button { 
            background: #4caf50; 
            color: #fff; 
            padding: 6px 12px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            margin-left: 10px; 
            font-size: 0.85rem; 
            transition: background 0.3s ease, transform 0.2s ease; 
        }
        .reschedule-form button:hover { 
            background: #388e3c; 
            transform: translateY(-2px); 
        }
        .no-data, .no-bill { 
            text-align: center; 
            color: #e74c3c; 
            padding: 15px; 
            font-size: 0.95rem; 
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
            border-radius: 10px; 
            width: 90%; 
            max-width: 450px; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); 
            position: relative; 
            animation: fadeIn 0.3s ease-in; 
        }
        .popup-content h3 { 
            color: #3498db; 
            text-align: center; 
            margin-bottom: 15px; 
        }
        .close-btn { 
            position: absolute; 
            top: 10px; 
            right: 10px; 
            font-size: 24px; 
            color: #333; 
            cursor: pointer; 
            border: none; 
            background: none; 
            transition: color 0.3s ease; 
        }
        .close-btn:hover { color: #e74c3c; }
        .popup-form label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: 500; 
            color: #333; 
        }
        .popup-form select, 
        .popup-form input[type="datetime-local"], 
        .popup-form input[type="text"], 
        .popup-form input[type="password"], 
        .popup-form textarea { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            margin-bottom: 15px; 
            font-size: 0.95rem; 
            transition: border-color 0.3s ease; 
        }
        .popup-form select:focus, 
        .popup-form input:focus, 
        .popup-form textarea:focus { 
            border-color: #3498db; 
            outline: none; 
        }
        .popup-form textarea { height: 80px; resize: vertical; }
        .popup-form button { 
            background: #3498db; 
            color: #fff; 
            padding: 12px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            width: 100%; 
            font-size: 0.95rem; 
            font-weight: 500; 
            transition: background 0.3s ease, transform 0.2s ease; 
        }
        .popup-form button:hover { 
            background: #2980b9; 
            transform: translateY(-2px); 
        }
        footer { 
            background-color: #333; 
            color: #fff; 
            text-align: center; 
            padding: 1em 0; 
            width: 100%; 
        }
        @media (max-width: 768px) { 
            .page-message { font-size: 1.2rem; } 
            .welcome-text { font-size: 1rem; } 
            .section { padding: 15px; } 
            h2 { font-size: 1.6rem; } 
            h3 { font-size: 1.3rem; } 
            table { font-size: 0.9rem; } 
            th, td { padding: 10px; } 
            .reschedule-form input[type="datetime-local"] { width: 60%; } 
        }
        @media (max-width: 600px) { 
            header { flex-direction: column; gap: 1rem; padding: 1rem; } 
            .logo-container { margin-bottom: 0.5rem; } 
            .page-message { font-size: 1.1rem; } 
            .welcome-text { font-size: 0.9rem; } 
            .logout-btn { padding: 6px 12px; font-size: 0.85rem; } 
            h2 { font-size: 1.4rem; } 
            h3 { font-size: 1.2rem; } 
            table { display: block; overflow-x: auto; white-space: nowrap; } 
            .popup-content { padding: 15px; max-width: 95%; } 
        }
    </style>
    <script>
        function showRescheduleForm(apptId) {
            document.querySelectorAll('.reschedule-form').forEach(form => form.style.display = 'none');
            document.getElementById('reschedule-' + apptId).style.display = 'block';
        }
        function showPopup(id) {
            document.getElementById(id).style.display = 'flex';
            if (id === 'editPopup') {
                document.getElementById('editForm').reset();
            }
        }
        function closePopup(id) {
            document.getElementById(id).style.display = 'none';
            if (id === 'editPopup') {
                document.getElementById('editForm').reset();
            }
        }
        document.addEventListener("DOMContentLoaded", function() {
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
            <a href="index.php"><img src="images/new_logo.jpeg" alt="Hospital Logo" class="logo"></a>
            <a href="index.php"><img src="images/health.jpeg" alt="NSW Health" class="health-logo"></a>
        </div>
        <span class="page-message">Patient Dashboard - Hospital Management System</span>
        <span class="welcome-text"><?php echo htmlspecialchars($patient["name"]); ?></span>
        <a href="logout.php" class="logout-btn">Logout</a>
    </header>

    <main>
        <div class="dashboard">
            <div class="section patient-details">
                <h2>Your Details</h2>
                <button class="edit-btn" onclick="showPopup('editPopup')">Edit Details</button>
                <p><strong>Patient ID:</strong> <?php echo htmlspecialchars($patient["pid"]); ?></p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($patient["name"]); ?></p>
                <p><strong>Age:</strong> <?php echo htmlspecialchars($patient["age"]); ?></p>
                <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($patient["dob"]); ?></p>
                <p><strong>Gender:</strong> <?php echo htmlspecialchars($patient["gender"]); ?></p>
                <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($patient["phoneno"]); ?></p>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($patient["username"]); ?></p>
            </div>

            <div class="section">
                <h2>Upcoming Appointments</h2>
                <button class="book-btn" onclick="showPopup('appointmentPopup')">Book Appointment</button>
                <?php if ($upcoming_result->num_rows > 0) { ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Appointment ID</th>
                                <th>Doctor Name</th>
                                <th>Department</th>
                                <th>Date & Time</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($appointment = $upcoming_result->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($appointment["appt_id"]); ?></td>
                                    <td><?php echo htmlspecialchars($appointment["doctorname"]); ?></td>
                                    <td><?php echo htmlspecialchars($appointment["dept"]); ?></td>
                                    <td><?php echo date("d/m/Y H:i", strtotime($appointment["appt_date"])); ?></td>
                                    <td><?php echo htmlspecialchars($appointment["reason"]); ?></td>
                                    <td><?php echo htmlspecialchars($appointment["status"]); ?></td>
                                    <td>
                                        <button class="reschedule-btn" onclick="showRescheduleForm(<?php echo $appointment['appt_id']; ?>)">Reschedule</button>
                                        <form style="display:inline;" method="POST">
                                            <input type="hidden" name="appt_id" value="<?php echo $appointment['appt_id']; ?>">
                                            <button type="submit" name="cancel" class="cancel-btn">Cancel</button>
                                        </form>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="7">
                                        <form id="reschedule-<?php echo $appointment['appt_id']; ?>" class="reschedule-form" method="POST">
                                            <input type="hidden" name="appt_id" value="<?php echo $appointment['appt_id']; ?>">
                                            <input type="datetime-local" name="new_date" min="<?php echo $current_min_date; ?>" required>
                                            <button type="submit" name="reschedule">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p class="no-data">No upcoming appointments found.</p>
                <?php } ?>
            </div>

            <div class="section">
                <h2>Appointment History</h2>
                <?php if ($history_result->num_rows > 0) { ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Appointment ID</th>
                                <th>Doctor Name</th>
                                <th>Department</th>
                                <th>Date & Time</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Bill</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($appointment = $history_result->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($appointment["appt_id"]); ?></td>
                                    <td><?php echo htmlspecialchars($appointment["doctorname"]); ?></td>
                                    <td><?php echo htmlspecialchars($appointment["dept"]); ?></td>
                                    <td><?php echo date("d/m/Y H:i", strtotime($appointment["appt_date"])); ?></td>
                                    <td><?php echo htmlspecialchars($appointment["reason"]); ?></td>
                                    <td><?php echo htmlspecialchars($appointment["status"]); ?></td>
                                    <td>
                                        <?php if ($appointment['bill_no'] && $appointment['status'] == 'Completed') { ?>
                                            <a href="view_bill.php?bill_no=<?php echo htmlspecialchars($appointment['bill_no']); ?>" class="view-pay-btn">View & Pay Bill</a>
                                        <?php } else { ?>
                                            <span class="no-bill">
                                                <?php echo ($appointment['status'] == 'Completed' ? 'Bill Missing (Contact Admin)' : 'No Bill'); ?>
                                            </span>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p class="no-data">No completed or cancelled appointments in history.</p>
                <?php } ?>
            </div>
        </div>
    </main>

    <?php if (isset($message)) { ?>
        <div class="message-box <?php echo $message_type === 'success' ? 'message-success' : 'message-error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php } ?>

    <!-- Popup for Booking Appointment -->
    <div id="appointmentPopup" class="popup">
        <div class="popup-content">
            <button class="close-btn" onclick="closePopup('appointmentPopup')">×</button>
            <h3>Book Appointment</h3>
            <form class="popup-form" method="POST">
                <label for="doctorid">Doctor:</label>
                <select id="doctorid" name="doctorid" required>
                    <option value="">Select Doctor</option>
                    <?php
                    while ($row = $doctors->fetch_assoc()) {
                        echo "<option value='{$row['doctorid']}'>{$row['doctorname']} - {$row['dept']}</option>";
                    }
                    $doctors->data_seek(0);
                    ?>
                </select>
                <label for="appt_date">Appointment Date:</label>
                <input type="datetime-local" id="appt_date" name="appt_date" min="<?php echo $current_min_date; ?>" required>
                <label for="reason">Reason for Visit:</label>
                <textarea id="reason" name="reason" required placeholder="Please describe the reason for your visit"></textarea>
                <button type="submit" name="book_appointment">Book</button>
            </form>
        </div>
    </div>

    <!-- Popup for Editing Personal Details -->
    <div id="editPopup" class="popup">
        <div class="popup-content">
            <button class="close-btn" onclick="closePopup('editPopup')">×</button>
            <h3>Edit Personal Details</h3>
            <form id="editForm" class="popup-form" method="POST" autocomplete="off">
                <label for="phoneno">Phone Number:</label>
                <input type="text" id="phoneno" name="phoneno" value="<?php echo htmlspecialchars($patient['phoneno']); ?>" required>
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($patient['username']); ?>" required>
                <label for="gender">Gender:</label>
                <select id="gender" name="gender" required>
                    <option value="Male" <?php echo $patient['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo $patient['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo $patient['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" value="<?php echo htmlspecialchars($patient['password']); ?>" required>
                <button type="submit" name="edit_details">Save Changes</button>
            </form>
        </div>
    </div>

    <footer>
        <p>© 2025 Hospital Management System. All rights reserved.</p>
    </footer>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
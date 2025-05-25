<?php
session_start();

// Check if employee is logged in
if (!isset($_SESSION["employee"])) {
    header("Location: employee_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "hms_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set timezone to Australian Eastern Daylight Time (AEDT)
date_default_timezone_set("Australia/Sydney");

// Get logged-in employee's doctorid
$username = $_SESSION["employee"];
$stmt = $conn->prepare("SELECT e.doctorid, e.emp_name, d.dept 
                        FROM employees e 
                        JOIN doctor d ON e.doctorid = d.doctorid 
                        WHERE e.username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $_SESSION["message"] = "Employee not found or not a doctor!";
    $_SESSION["message_type"] = "error";
    header("Location: employee_login.php");
    exit();
}
$employee = $result->fetch_assoc();
$doctorid = $employee["doctorid"];
$emp_name = $employee["emp_name"];
$dept = $employee["dept"];
$stmt->close();

// Function to generate unique 4-digit bill number
function generateBillNo($conn) {
    do {
        $bill_no = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare("SELECT bill_no FROM bill WHERE bill_no = ?");
        $stmt->bind_param("s", $bill_no);
        $stmt->execute();
        $check = $stmt->get_result();
    } while ($check->num_rows > 0);
    $stmt->close();
    return $bill_no;
}

// Function to update bill when status changes to Completed
function updateBillOnComplete($conn, $appt_id) {
    $doctor_charge = 70.00; // Fixed doctor charge
    $gst = $doctor_charge * 0.10; // 10% GST
    $total_bill = $doctor_charge + $gst; // Total including GST
    $bill_date = date('Y-m-d'); // Current date in AEDT

    $stmt = $conn->prepare("UPDATE bill 
                           SET doctor_charge = ?, 
                               total_bill = ?, 
                               bill_date = ? 
                           WHERE appt_id = ?");
    $stmt->bind_param("ddsi", $doctor_charge, $total_bill, $bill_date, $appt_id);
    
    return $stmt->execute();
}

// Handle form submission (Add or Edit appointment)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pid = $_POST["pid"];
    $doctorid_form = $_POST["doctorid"];
    $appt_date = $_POST["appt_date"];
    $reason = $_POST["reason"];
    $status = $_POST["status"];

    // Server-side check for past date
    $current_date = new DateTime();
    $appointment_date = new DateTime($appt_date);
    if ($appointment_date < $current_date) {
        $_SESSION["message"] = "Cannot schedule or update an appointment in the past!";
        $_SESSION["message_type"] = "error";
        header("Location: schedule_appointments.php");
        exit();
    }

    // Ensure the appointment is for the logged-in employee
    if ($doctorid_form != $doctorid) {
        $_SESSION["message"] = "You can only schedule appointments for yourself!";
        $_SESSION["message_type"] = "error";
        header("Location: schedule_appointments.php");
        exit();
    }

    if (isset($_POST["appt_id"]) && !empty($_POST["appt_id"])) {
        // Update existing appointment
        $appt_id = $_POST["appt_id"];
        
        // Check original appointment details
        $stmt = $conn->prepare("SELECT pid, doctorid, appt_date, reason, status 
                                FROM appointment 
                                WHERE appt_id = ? AND doctorid = ?");
        $stmt->bind_param("ii", $appt_id, $doctorid);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $_SESSION["message"] = "Appointment not found or you lack permission!";
            $_SESSION["message_type"] = "error";
            header("Location: schedule_appointments.php");
            exit();
        }
        $original = $result->fetch_assoc();
        $stmt->close();

        // If any field changed and status isn't manually set to Completed/Cancelled, set to Rescheduled
        if (($pid != $original['pid'] || $doctorid_form != $original['doctorid'] || $appt_date != $original['appt_date'] || $reason != $original['reason']) 
            && $status != "Completed" && $status != "Cancelled") {
            $status = "Rescheduled";
        }

        $stmt = $conn->prepare("UPDATE appointment 
                               SET pid = ?, doctorid = ?, appt_date = ?, reason = ?, status = ? 
                               WHERE appt_id = ? AND doctorid = ?");
        $stmt->bind_param("sisssii", $pid, $doctorid_form, $appt_date, $reason, $status, $appt_id, $doctorid);
        
        if ($stmt->execute()) {
            // If status changed to Completed, update the bill
            if ($status == "Completed" && $original['status'] != "Completed") {
                if (updateBillOnComplete($conn, $appt_id)) {
                    $_SESSION["message"] = "Appointment completed and bill updated successfully!";
                } else {
                    $_SESSION["message"] = "Appointment updated but failed to update bill: " . $conn->error;
                    $_SESSION["message_type"] = "error";
                }
            } else {
                $_SESSION["message"] = "Changes made successfully!";
            }
            $_SESSION["message_type"] = "success";
        } else {
            $_SESSION["message"] = "Error updating appointment: " . $conn->error;
            $_SESSION["message_type"] = "error";
        }
        $stmt->close();
    } else {
        // Insert new appointment (defaults to Scheduled unless specified)
        if (empty($status)) {
            $status = "Scheduled"; // Default for new appointments
        }
        
        // Start transaction for appointment and bill insertion
        $conn->begin_transaction();
        try {
            // Insert appointment
            $stmt = $conn->prepare("INSERT INTO appointment (pid, doctorid, appt_date, reason, status) 
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sisss", $pid, $doctorid_form, $appt_date, $reason, $status);
            if (!$stmt->execute()) {
                throw new Exception("Error scheduling appointment: " . $conn->error);
            }

            $appt_id = $conn->insert_id;
            $bill_no = generateBillNo($conn);

            // Insert initial bill record
            $bill_stmt = $conn->prepare("INSERT INTO bill (bill_no, appt_id) VALUES (?, ?)");
            $bill_stmt->bind_param("si", $bill_no, $appt_id);
            if (!$bill_stmt->execute()) {
                throw new Exception("Error creating bill: " . $conn->error);
            }

            $conn->commit();
            $_SESSION["message"] = "Appointment scheduled successfully! Appointment ID: $appt_id, Bill No: $bill_no";
            $_SESSION["message_type"] = "success";
            $stmt->close();
            $bill_stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION["message"] = $e->getMessage();
            $_SESSION["message_type"] = "error";
        }
    }
    header("Location: schedule_appointments.php");
    exit();
}

// Handle edit request (populate form)
$edit_appointment = null;
if (isset($_GET["edit"])) {
    $appt_id = $_GET["edit"];
    $stmt = $conn->prepare("SELECT * FROM appointment WHERE appt_id = ? AND doctorid = ?");
    $stmt->bind_param("ii", $appt_id, $doctorid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_appointment = $result->fetch_assoc();
    } else {
        $_SESSION["message"] = "Appointment not found or you lack permission!";
        $_SESSION["message_type"] = "error";
        header("Location: schedule_appointments.php");
        exit();
    }
    $stmt->close();
}

// Fetch patients who have appointments with the logged-in employee
$stmt = $conn->prepare("SELECT DISTINCT p.pid, p.name 
                        FROM patient p 
                        JOIN appointment a ON p.pid = a.pid 
                        WHERE a.doctorid = ? 
                        ORDER BY p.name");
$stmt->bind_param("i", $doctorid);
$stmt->execute();
$patients = $stmt->get_result();
$stmt->close();

// Fetch logged-in employee's doctor details
$stmt = $conn->prepare("SELECT e.doctorid, e.emp_name, d.dept 
                        FROM employees e 
                        JOIN doctor d ON e.doctorid = d.doctorid 
                        WHERE e.doctorid = ?");
$stmt->bind_param("i", $doctorid);
$stmt->execute();
$doctors = $stmt->get_result();
$stmt->close();

// Fetch appointments for the logged-in employee
$stmt = $conn->prepare("SELECT a.appt_id, p.name AS patient_name, p.dob, a.doctorid, e.emp_name AS doctorname, d.dept, a.appt_date, a.reason, a.status 
                        FROM appointment a 
                        JOIN patient p ON a.pid = p.pid 
                        JOIN employees e ON a.doctorid = e.doctorid 
                        JOIN doctor d ON a.doctorid = d.doctorid 
                        WHERE a.status NOT IN ('Completed', 'Cancelled') AND a.doctorid = ?");
$stmt->bind_param("i", $doctorid);
$stmt->execute();
$result = $stmt->get_result();

// Check for session message
if (isset($_SESSION["message"])) {
    $message = $_SESSION["message"];
    $message_type = $_SESSION["message_type"];
    unset($_SESSION["message"]);
    unset($_SESSION["message_type"]);
}

// Set minimum date/time for datetime-local input
$current_min_date = date('Y-m-d\TH:i');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Appointments - HMS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f0f4f8;
            color: #333;
            line-height: 1.6;
        }
        header {
            background: linear-gradient(90deg, #2c3e50, #3498db);
            color: white;
            padding: 1.5rem 0;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo { width: 10vw; max-width: 120px; height: auto; margin-right: 1.5rem; }
        nav {
            background-color: #34495e;
            padding: 1rem 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        nav ul {
            list-style-type: none;
            text-align: center;
        }
        nav li { display: inline-block; margin: 0 1rem; }
        nav li a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 0.75rem 1rem;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        nav li a:hover { background-color: #2980b9; }
        main {
            padding: 2rem;
            min-height: calc(100vh - 12rem);
            max-width: 90vw;
            margin: 0 auto;
            position: relative;
        }
        h2 { color: #2c3e50; margin-bottom: 1.5rem; font-size: 1.8rem; }
        form {
            background-color: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 100%;
            width: 500px;
            margin: 0 auto 2rem;
        }
        label { display: block; margin: 0.75rem 0 0.5rem; font-weight: 500; }
        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #3498db;
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
            font-size: 1rem;
        }
        button:hover { background-color: #2980b9; }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
            font-size: 1rem;
        }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #3498db; color: white; }
        tr:hover { background-color: #f5f7fa; }
        .btn-return {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.75rem 1.5rem;
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
            font-size: 1rem;
        }
        .btn-return:hover { background-color: #c0392b; }
        .btn-edit {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            color: white;
            background-color: #27ae60;
            display: inline-block;
            margin: 0 0.5rem;
            transition: background-color 0.3s;
        }
        .btn-edit:hover { background-color: #219653; }
        .message-box {
            position: fixed;
            top: 20%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 1.5rem;
            border-radius: 8px;
            color: white;
            font-size: 1.2rem;
            text-align: center;
            z-index: 1000;
            opacity: 0.9;
            max-width: 80%;
        }
        .message-success { background-color: #27ae60; }
        .message-error { background-color: #e74c3c; }
        .no-data {
            text-align: center;
            color: #e74c3c;
            padding: 1rem;
            font-size: 1rem;
        }
        footer {
            background-color: #2c3e50;
            color: white;
            text-align: center;
            padding: 1rem 0;
            width: 100%;
        }
        @media (max-width: 768px) {
            nav li { display: block; margin: 0.5rem 0; }
            .logo { width: 15vw; }
            main { padding: 1rem; max-width: 95vw; }
            h2 { font-size: 1.5rem; }
            form { width: 100%; padding: 1rem; }
            table { font-size: 0.9rem; }
            th, td { padding: 0.75rem; }
            .btn-edit { padding: 0.4rem 0.8rem; font-size: 0.9rem; }
            .message-box { font-size: 1rem; padding: 1rem; }
        }
        @media (max-width: 480px) {
            header { flex-direction: column; padding: 1rem 0; }
            .logo { margin: 0 0 1rem 0; }
            h2 { font-size: 1.2rem; }
            form { padding: 0.75rem; }
            table { font-size: 0.8rem; }
            th, td { padding: 0.5rem; }
            .btn-edit { padding: 0.3rem 0.6rem; font-size: 0.8rem; }
            .message-box { font-size: 0.9rem; padding: 0.75rem; }
        }
    </style>
</head>
<body>
    <header>
        <img src="images/new_logo.jpeg" alt="Hospital Logo" class="logo">
        <h1>Hospital Management System</h1>
    </header>
    <nav>
        <ul>
            <li><a href="employee_dashboard.php">Dashboard</a></li>
            <li><a href="view_patients.php">View Patients</a></li>
            <li><a href="schedule_appointments.php">Schedule Appointments</a></li>
            <li><a href="update_records.php">Update Records</a></li>
        </ul>
    </nav>
    <main>
        <section>
            <h2>Schedule Appointments</h2>
            <form method="POST" action="schedule_appointments.php">
                <?php if ($edit_appointment) { ?>
                    <input type="hidden" name="appt_id" value="<?php echo htmlspecialchars($edit_appointment['appt_id']); ?>">
                <?php } ?>
                <label for="pid">Patient Name:</label>
                <select id="pid" name="pid" required>
                    <option value="">Select Patient</option>
                    <?php
                    if ($patients->num_rows > 0) {
                        while ($row = $patients->fetch_assoc()) {
                            $selected = ($edit_appointment && $row['pid'] == $edit_appointment['pid']) ? 'selected' : '';
                            echo "<option value='{$row['pid']}' $selected>" . htmlspecialchars($row['name']) . "</option>";
                        }
                        $patients->data_seek(0);
                    } else {
                        echo "<option value='' disabled>No patients assigned to you</option>";
                    }
                    ?>
                </select>
                <label for="doctorid">Doctor Name:</label>
                <select id="doctorid" name="doctorid" required>
                    <option value="<?php echo $doctorid; ?>" selected><?php echo htmlspecialchars($emp_name) . ' - ' . htmlspecialchars($dept); ?></option>
                </select>
                <label for="appt_date">Appointment Date:</label>
                <input type="datetime-local" id="appt_date" name="appt_date" required 
                       min="<?php echo htmlspecialchars($current_min_date); ?>" 
                       value="<?php echo $edit_appointment ? date('Y-m-d\TH:i', strtotime($edit_appointment['appt_date'])) : ''; ?>">
                <label for="reason">Reason:</label>
                <textarea id="reason" name="reason" required placeholder="Enter reason for appointment"><?php echo $edit_appointment ? htmlspecialchars($edit_appointment['reason']) : ''; ?></textarea>
                <label for="status">Status:</label>
                <select id="status" name="status" required>
                    <option value="Scheduled" <?php echo $edit_appointment && $edit_appointment['status'] == 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="Rescheduled" <?php echo $edit_appointment && $edit_appointment['status'] == 'Rescheduled' ? 'selected' : ''; ?>>Rescheduled</option>
                    <option value="Completed" <?php echo $edit_appointment && $edit_appointment['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="Cancelled" <?php echo $edit_appointment && $edit_appointment['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <button type="submit"><?php echo $edit_appointment ? 'Update Appointment' : 'Schedule Appointment'; ?></button>
            </form>

            <?php if (isset($message)) { ?>
                <div class="message-box <?php echo $message_type === 'success' ? 'message-success' : 'message-error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php } ?>

            <?php
            if ($result->num_rows > 0) {
                echo "<table><tr><th>Patient Name</th><th>Date of Birth</th><th>Doctor Name</th><th>Department</th><th>Appointment Date</th><th>Reason</th><th>Status</th><th>Actions</th></tr>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>" . htmlspecialchars($row['patient_name']) . "</td>
                        <td>" . htmlspecialchars($row['dob']) . "</td>
                        <td>" . htmlspecialchars($row['doctorname']) . "</td>
                        <td>" . htmlspecialchars($row['dept']) . "</td>
                        <td>" . date("d/m/Y H:i", strtotime($row['appt_date'])) . "</td>
                        <td>" . htmlspecialchars($row['reason']) . "</td>
                        <td>" . htmlspecialchars($row['status']) . "</td>
                        <td><a href='schedule_appointments.php?edit={$row['appt_id']}' class='btn-edit'>Edit</a></td>
                    </tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='no-data'>No active appointments scheduled for you.</p>";
            }
            $stmt->close();
            ?>
            <a href="employee_dashboard.php" class="btn-return">Return to Dashboard</a>
        </section>
    </main>
    <footer>
        <p>© 2025 Hospital Management System</p>
    </footer>

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
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>
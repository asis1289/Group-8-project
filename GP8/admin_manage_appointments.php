<?php
session_start(); // Start session for message handling
if (!isset($_SESSION["admin"])) {
    header("Location: admin_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "hms_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set timezone to Australian Eastern Daylight Time (AEDT)
date_default_timezone_set("Australia/Sydney");

// Function to generate unique 4-digit bill number
function generateBillNo($conn) {
    do {
        $bill_no = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $check = $conn->query("SELECT bill_no FROM bill WHERE bill_no = '$bill_no'");
    } while ($check->num_rows > 0);
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
    $doctorid = $_POST["doctorid"];
    $appt_date = $_POST["appt_date"];
    $reason = $_POST["reason"];
    $status = $_POST["status"];

    // Server-side check for past date (fallback)
    $current_date = new DateTime(); // Current date/time in AEDT
    $appointment_date = new DateTime($appt_date); // Submitted appointment date/time
    if ($appointment_date < $current_date) {
        $_SESSION["message"] = "Cannot schedule or update an appointment in the past!";
        $_SESSION["message_type"] = "error";
        header("Location: admin_manage_appointments.php");
        exit();
    }

    if (isset($_POST["appt_id"]) && !empty($_POST["appt_id"])) {
        // Update existing appointment
        $appt_id = $_POST["appt_id"];
        
        // Check original appointment details
        $original_sql = "SELECT pid, doctorid, appt_date, reason, status FROM Appointment WHERE appt_id='$appt_id'";
        $original_result = $conn->query($original_sql);
        $original = $original_result->fetch_assoc();

        // If any field changed and status isn't manually set to Completed/Cancelled, set to Rescheduled
        if (($pid != $original['pid'] || $doctorid != $original['doctorid'] || $appt_date != $original['appt_date'] || $reason != $original['reason']) 
            && $status != "Completed" && $status != "Cancelled") {
            $status = "Rescheduled";
        }

        $stmt = $conn->prepare("UPDATE Appointment 
                              SET pid = ?, doctorid = ?, appt_date = ?, reason = ?, status = ? 
                              WHERE appt_id = ?");
        $stmt->bind_param("sssssi", $pid, $doctorid, $appt_date, $reason, $status, $appt_id);
        
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
            $stmt = $conn->prepare("INSERT INTO Appointment (pid, doctorid, appt_date, reason, status) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $pid, $doctorid, $appt_date, $reason, $status);
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
    header("Location: admin_manage_appointments.php"); // Redirect to self
    exit();
}

// Handle edit request (populate form)
$edit_appointment = null;
if (isset($_GET["edit"])) {
    $appt_id = $_GET["edit"];
    $sql = "SELECT * FROM Appointment WHERE appt_id='$appt_id'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $edit_appointment = $result->fetch_assoc();
    }
}

// Fetch patients and doctors for dropdowns
$patients = $conn->query("SELECT pid, name FROM Patient");
$doctors = $conn->query("SELECT doctorid, doctorname, dept FROM Doctor");

// Fetch appointments with doctor name and dept, excluding Completed and Cancelled
$sql = "SELECT a.appt_id, p.name AS patient_name, p.dob, a.doctorid, d.doctorname, d.dept, a.appt_date, a.reason, a.status 
        FROM Appointment a 
        JOIN Patient p ON a.pid = p.pid 
        JOIN Doctor d ON a.doctorid = d.doctorid 
        WHERE a.status NOT IN ('Completed', 'Cancelled')";
$result = $conn->query($sql);

// Check for session message
if (isset($_SESSION["message"])) {
    $message = $_SESSION["message"];
    $message_type = $_SESSION["message_type"];
    unset($_SESSION["message"]);
    unset($_SESSION["message_type"]);
}

// Set minimum date/time for the datetime-local input (current AEDT time)
$current_min_date = date('Y-m-d\TH:i'); // Format: "2025-03-31T14:30"
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments - Hospital Management System</title>
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
        .page-message { 
            font-size: 1.5rem; 
            font-weight: 600; 
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2); 
        }
        main { 
            flex: 1; 
            padding: 40px 20px; 
            display: flex; 
            justify-content: center; 
        }
        .container { 
            background: #fff; 
            padding: 2rem; 
            border-radius: 12px; 
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15); 
            max-width: 1000px; 
            width: 100%; 
            animation: fadeIn 0.5s ease-in; 
        }
        @keyframes fadeIn { 
            from { opacity: 0; transform: translateY(20px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        h2, h3 { 
            color: #1a237e; 
            text-align: center; 
            margin-bottom: 1.5rem; 
            font-size: 1.8rem; 
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1); 
        }
        h3 { font-size: 1.5rem; }
        .message-box { 
            position: fixed; 
            top: 20px; 
            left: 50%; 
            transform: translateX(-50%); 
            padding: 1.5rem 2rem; 
            border-radius: 10px; 
            color: #fff; 
            font-size: 1.2rem; 
            text-align: center; 
            z-index: 1000; 
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3); 
            animation: slideIn 0.5s ease-out; 
        }
        @keyframes slideIn { 
            from { opacity: 0; top: 0; } 
            to { opacity: 0.95; top: 20px; } 
        }
        .message-success { background: #2ecc71; }
        .message-error { background: #e74c3c; }
        form { 
            margin: 20px 0; 
            background: #f9f9f9; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); 
        }
        label { 
            font-weight: 600; 
            color: #1a237e; 
            margin-bottom: 5px; 
            display: block; 
        }
        input, select, textarea { 
            width: 100%; 
            padding: 10px; 
            margin-bottom: 15px; 
            border: 2px solid #ddd; 
            border-radius: 6px; 
            font-size: 1rem; 
            transition: border-color 0.3s; 
        }
        input:focus, select:focus, textarea:focus { 
            border-color: #3f51b5; 
            outline: none; 
        }
        textarea { resize: vertical; }
        button { 
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
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
        }
        th, td { 
            padding: 12px; 
            text-align: left; 
            border-bottom: 1px solid #ddd; 
        }
        th { 
            background: #3f51b5; 
            color: #fff; 
            font-weight: 600; 
        }
        tr:hover { 
            background: #f5f5f5; 
        }
        .btn-edit { 
            padding: 6px 12px; 
            background: #2ecc71; 
            color: #fff; 
            text-decoration: none; 
            border-radius: 5px; 
            transition: background 0.3s, transform 0.2s; 
        }
        .btn-edit:hover { 
            background: #27ae60; 
            transform: translateY(-2px); 
        }
        .no-data { 
            text-align: center; 
            color: #721c24; 
            padding: 20px; 
        }
        .back-link { 
            display: block; 
            text-align: center; 
            margin: 20px 0; 
            color: #ffffff; 
            background: linear-gradient(90deg, #e74c3c, #c0392b); 
            padding: 10px 20px; 
            border-radius: 6px; 
            font-weight: 600; 
            text-decoration: none; 
            transition: background 0.3s, transform 0.2s; 
            max-width: 200px; 
            margin-left: auto; 
            margin-right: auto; 
        }
        .back-link:hover { 
            background: linear-gradient(90deg, #c0392b, #e74c3c); 
            transform: translateY(-2px); 
            color: #ffffff; 
        }
        footer { 
            background: #1a237e; 
            color: #fff; 
            text-align: center; 
            padding: 1rem; 
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.2); 
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
        });
    </script>
</head>
<body>
    <header>
        <div class="logo-container">
            <a href="index.php"><img src="images/new_logo.jpeg" alt="Hospital Logo" class="logo"></a>
            <a href="index.php"><img src="images/health.jpeg" alt="NSW Health" class="health-logo"></a>
        </div>
        <span class="page-message">Manage Appointments - Hospital Management System</span>
    </header>

    <main>
        <div class="container">
            <h2>Manage Appointments</h2>

            <h3><?php echo $edit_appointment ? 'Edit Appointment' : 'Schedule New Appointment'; ?></h3>
            <form method="POST" action="admin_manage_appointments.php">
                <?php if ($edit_appointment) { ?>
                    <input type="hidden" name="appt_id" value="<?php echo $edit_appointment['appt_id']; ?>">
                <?php } ?>
                <label for="pid">Patient Name:</label>
                <select id="pid" name="pid" required>
                    <option value="">Select Patient</option>
                    <?php
                    while ($row = $patients->fetch_assoc()) {
                        $selected = ($edit_appointment && $row['pid'] == $edit_appointment['pid']) ? 'selected' : '';
                        echo "<option value='{$row['pid']}' $selected>{$row['name']}</option>";
                    }
                    $patients->data_seek(0);
                    ?>
                </select>
                <label for="doctorid">Doctor Name:</label>
                <select id="doctorid" name="doctorid" required>
                    <option value="">Select Doctor</option>
                    <?php
                    while ($row = $doctors->fetch_assoc()) {
                        $selected = ($edit_appointment && $row['doctorid'] == $edit_appointment['doctorid']) ? 'selected' : '';
                        echo "<option value='{$row['doctorid']}' $selected>{$row['doctorname']} - {$row['dept']}</option>";
                    }
                    $doctors->data_seek(0);
                    ?>
                </select>
                <label for="appt_date">Appointment Date:</label>
                <input type="datetime-local" id="appt_date" name="appt_date" required 
                       min="<?php echo $current_min_date; ?>" 
                       value="<?php echo $edit_appointment ? date('Y-m-d\TH:i', strtotime($edit_appointment['appt_date'])) : ''; ?>">
                <label for="reason">Reason:</label>
                <textarea id="reason" name="reason" required placeholder="Enter reason for appointment"><?php echo $edit_appointment ? $edit_appointment['reason'] : ''; ?></textarea>
                <label for="status">Status:</label>
                <select id="status" name="status" required>
                    <option value="Scheduled" <?php echo $edit_appointment && $edit_appointment['status'] == 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="Rescheduled" <?php echo $edit_appointment && $edit_appointment['status'] == 'Rescheduled' ? 'selected' : ''; ?>>Rescheduled</option>
                    <option value="Completed" <?php echo $edit_appointment && $edit_appointment['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="Cancelled" <?php echo $edit_appointment && $edit_appointment['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <button type="submit"><?php echo $edit_appointment ? 'Update Appointment' : 'Schedule Appointment'; ?></button>
            </form>

            <h3>Current Appointments</h3>
            <?php if ($result->num_rows > 0) { ?>
                <table>
                    <tr>
                        <th>Patient Name</th>
                        <th>Date of Birth</th>
                        <th>Doctor Name</th>
                        <th>Department</th>
                        <th>Appointment Date</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['dob']); ?></td>
                            <td><?php echo htmlspecialchars($row['doctorname']); ?></td>
                            <td><?php echo htmlspecialchars($row['dept']); ?></td>
                            <td><?php echo date("d/m/Y H:i", strtotime($row['appt_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['reason']); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td>
                                <a href="admin_manage_appointments.php?edit=<?php echo $row['appt_id']; ?>" class="btn-edit">Edit</a>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } else { ?>
                <p class="no-data">No active appointments scheduled.</p>
            <?php } ?>

            <a href="admin_dashboard.php" class="back-link">Back to Dashboard</a>
        </div>
    </main>

    <?php if (isset($message)) { ?>
        <div class="message-box <?php echo $message_type === 'success' ? 'message-success' : 'message-error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php } ?>

    <footer>
        <p>© 2025 Hospital Management System. All rights reserved.</p>
    </footer>
</body>
</html>
<?php $conn->close(); ?>
<?php
session_start(); // Start session to preserve messages across redirects

$conn = new mysqli("localhost", "root", "", "hms_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set timezone to Australian Eastern Daylight Time (AEDT)
date_default_timezone_set("Australia/Sydney");

// Handle form submission (Add or Edit)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pid = $_POST["pid"];
    $history = $_POST["history"];
    $medication = $_POST["medication"];
    $blood_pressure = $_POST["blood_pressure"];
    $weight = $_POST["weight"];
    $record_date = date("Y-m-d H:i:s");

    if (isset($_POST["record_id"]) && !empty($_POST["record_id"])) {
        // Update existing record
        $record_id = $_POST["record_id"];
        $sql = "UPDATE Patient_Records SET pid='$pid', history='$history', medication='$medication', 
                blood_pressure='$blood_pressure', weight='$weight', record_date='$record_date' 
                WHERE record_id='$record_id'";
        if ($conn->query($sql) === TRUE) {
            $message = "Record updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating record: " . $conn->error;
            $message_type = "error";
        }
    } else {
        // Insert new record
        $sql = "INSERT INTO Patient_Records (pid, history, medication, blood_pressure, weight, record_date) 
                VALUES ('$pid', '$history', '$medication', '$blood_pressure', '$weight', '$record_date')";
        if ($conn->query($sql) === TRUE) {
            $message = "Record added successfully!";
            $message_type = "success";
        } else {
            $message = "Error adding record: " . $conn->error;
            $message_type = "error";
        }
    }
}

// Handle delete request
if (isset($_GET["delete"])) {
    $record_id = $_GET["delete"];
    $sql = "DELETE FROM Patient_Records WHERE record_id='$record_id'";
    if ($conn->query($sql) === TRUE) {
        $_SESSION["message"] = "Record deleted successfully!";
        $_SESSION["message_type"] = "success";
    } else {
        $_SESSION["message"] = "Error deleting record: " . $conn->error;
        $_SESSION["message_type"] = "error";
    }
    // Redirect to clear GET parameters
    header("Location: update_records.php");
    exit();
}

// Handle edit request (populate form)
$edit_record = null;
if (isset($_GET["edit"])) {
    $record_id = $_GET["edit"];
    $sql = "SELECT * FROM Patient_Records WHERE record_id='$record_id'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $edit_record = $result->fetch_assoc();
    }
}

// Check for session message (e.g., from delete)
if (isset($_SESSION["message"])) {
    $message = $_SESSION["message"];
    $message_type = $_SESSION["message_type"];
    unset($_SESSION["message"]); // Clear after displaying
    unset($_SESSION["message_type"]);
}

$patients = $conn->query("SELECT pid, name FROM Patient");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Records - HMS</title>
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
        h2, h3 { color: #2c3e50; margin-bottom: 1.5rem; font-size: 1.8rem; }
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
        .btn-edit, .btn-delete {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            color: white;
            display: inline-block;
            margin: 0 0.5rem;
            transition: background-color 0.3s;
        }
        .btn-edit { background-color: #27ae60; } /* Green */
        .btn-edit:hover { background-color: #219653; }
        .btn-delete { background-color: #8b4513; } /* Brown-red */
        .btn-delete:hover { background-color: #5c2d0c; }
        footer {
            background-color: #2c3e50;
            color: white;
            text-align: center;
            padding: 1rem 0;
            width: 100%;
        }
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
        @media (max-width: 768px) {
            nav li { display: block; margin: 0.5rem 0; }
            .logo { width: 15vw; }
            main { padding: 1rem; max-width: 95vw; }
            h2, h3 { font-size: 1.5rem; }
            form { width: 100%; padding: 1rem; }
            table { font-size: 0.9rem; }
            th, td { padding: 0.75rem; }
            .btn-edit, .btn-delete { padding: 0.4rem 0.8rem; font-size: 0.9rem; }
            .message-box { font-size: 1rem; padding: 1rem; }
        }
        @media (max-width: 480px) {
            header { flex-direction: column; padding: 1rem 0; }
            .logo { margin: 0 0 1rem 0; }
            h2, h3 { font-size: 1.2rem; }
            form { padding: 0.75rem; }
            table { font-size: 0.8rem; }
            th, td { padding: 0.5rem; }
            .btn-edit, .btn-delete { padding: 0.3rem 0.6rem; font-size: 0.8rem; }
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
            <h2>Update Patient Records</h2>
            <form method="POST" action="update_records.php">
                <?php if ($edit_record) { ?>
                    <input type="hidden" name="record_id" value="<?php echo $edit_record['record_id']; ?>">
                <?php } ?>
                <label for="pid">Patient Name:</label>
                <select id="pid" name="pid" required>
                    <option value="">Select Patient</option>
                    <?php
                    while ($row = $patients->fetch_assoc()) {
                        $selected = ($edit_record && $row['pid'] == $edit_record['pid']) ? 'selected' : '';
                        echo "<option value='{$row['pid']}' $selected>{$row['name']}</option>";
                    }
                    $patients->data_seek(0); // Reset pointer for reuse
                    ?>
                </select>
                <label for="history">Medical History:</label>
                <textarea id="history" name="history" required placeholder="Enter patient history"><?php echo $edit_record ? $edit_record['history'] : ''; ?></textarea>
                <label for="medication">Medication:</label>
                <input type="text" id="medication" name="medication" required placeholder="e.g., Paracetamol" value="<?php echo $edit_record ? $edit_record['medication'] : ''; ?>">
                <label for="blood_pressure">Blood Pressure (e.g., 120/80):</label>
                <input type="text" id="blood_pressure" name="blood_pressure" required placeholder="e.g., 120/80" value="<?php echo $edit_record ? $edit_record['blood_pressure'] : ''; ?>">
                <label for="weight">Weight (kg):</label>
                <input type="number" step="0.01" id="weight" name="weight" required placeholder="e.g., 70.5" value="<?php echo $edit_record ? $edit_record['weight'] : ''; ?>">
                <button type="submit"><?php echo $edit_record ? 'Save Changes' : 'Update Record'; ?></button>
            </form>

            <?php if (isset($message)) { ?>
                <div class="message-box <?php echo $message_type === 'success' ? 'message-success' : 'message-error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>

            <h3>Patient Records</h3>
            <?php
            $records_sql = "SELECT pr.record_id, p.name, pr.history, pr.medication, pr.blood_pressure, pr.weight, pr.record_date 
                            FROM Patient_Records pr 
                            JOIN Patient p ON pr.pid = p.pid";
            $records_result = $conn->query($records_sql);
            if ($records_result->num_rows > 0) {
                echo "<table><tr><th>Patient Name</th><th>History</th><th>Medication</th><th>Blood Pressure</th><th>Weight</th><th>Date</th><th>Actions</th></tr>";
                while ($row = $records_result->fetch_assoc()) {
                    echo "<tr>
                        <td>{$row['name']}</td>
                        <td>{$row['history']}</td>
                        <td>{$row['medication']}</td>
                        <td>{$row['blood_pressure']}</td>
                        <td>{$row['weight']}</td>
                        <td>{$row['record_date']}</td>
                        <td>
                            <a href='update_records.php?edit={$row['record_id']}' class='btn-edit'>Edit</a>
                            <a href='update_records.php?delete={$row['record_id']}' class='btn-delete' onclick='return confirm(\"Are you sure you want to delete this record?\");'>Delete</a>
                        </td>
                    </tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No records found.</p>";
            }
            $conn->close();
            ?>
            <a href="employee_dashboard.php" class="btn-return">Return to Dashboard</a>
        </section>
    </main>
    <footer>
        <p>© 2025 Hospital Management System</p>
    </footer>

    <script>
        // Auto-hide message after 3 seconds
        document.addEventListener("DOMContentLoaded", function() {
            const messageBox = document.querySelector(".message-box");
            if (messageBox) {
                setTimeout(() => {
                    messageBox.style.transition = "opacity 0.5s ease-out";
                    messageBox.style.opacity = "0";
                    setTimeout(() => messageBox.remove(), 500); // Remove after fade-out
                }, 3000); // 3 seconds
            }
        });
    </script>
</body>
</html>
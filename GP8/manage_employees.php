<?php
session_start();
if (!isset($_SESSION["admin"])) {
    header("Location: admin_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "hms_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$message_type = "";

// Add employee
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_employee"])) {
    $doctorid = $_POST["doctorid"];
    $username = $_POST["username"];
    $password = $_POST["password"];
    $emp_name = $_POST["emp_name"];
    $dept = $_POST["dept"];

    // Check if doctorid or username already exists in employees
    $check = $conn->prepare("SELECT doctorid, username FROM employees WHERE doctorid = ? OR username = ?");
    $check->bind_param("ss", $doctorid, $username);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows == 0) {
        // Insert or update doctor table if this employee is a doctor
        $check_doctor = $conn->prepare("SELECT doctorid FROM doctor WHERE doctorid = ?");
        $check_doctor->bind_param("s", $doctorid);
        $check_doctor->execute();
        $doctor_result = $check_doctor->get_result();

        if ($doctor_result->num_rows == 0) {
            // Insert new doctor record
            $stmt_doctor = $conn->prepare("INSERT INTO doctor (doctorid, doctorname, dept) VALUES (?, ?, ?)");
            $stmt_doctor->bind_param("sss", $doctorid, $emp_name, $dept);
            $stmt_doctor->execute();
            $stmt_doctor->close();
        } else {
            // Update existing doctor record to sync doctorname and dept
            $stmt_doctor = $conn->prepare("UPDATE doctor SET doctorname = ?, dept = ? WHERE doctorid = ?");
            $stmt_doctor->bind_param("sss", $emp_name, $dept, $doctorid);
            $stmt_doctor->execute();
            $stmt_doctor->close();
        }
        $check_doctor->close();

        // Insert into employees table
        $stmt = $conn->prepare("INSERT INTO employees (doctorid, username, password, emp_name) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $doctorid, $username, $password, $emp_name);
        if ($stmt->execute()) {
            $message = "Employee added successfully!";
            $message_type = "success";
        } else {
            $message = "Error adding employee: " . $conn->error;
            $message_type = "error";
        }
        $stmt->close();
    } else {
        $message = "Error: Doctor ID or username already taken.";
        $message_type = "error";
    }
    $check->close();
}

// Delete employee
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_employee"])) {
    $id = $_POST["id"];
    $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Employee deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting employee: " . $conn->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Change password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_password"])) {
    $id = $_POST["id"];
    $new_password = $_POST["new_password"];
    $stmt = $conn->prepare("UPDATE employees SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $new_password, $id);
    if ($stmt->execute()) {
        $message = "Password changed successfully!";
        $message_type = "success";
    } else {
        $message = "Error changing password: " . $conn->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Fetch employees
$employees = $conn->query("SELECT e.id, e.doctorid, e.username, e.emp_name, d.doctorname, d.dept 
                           FROM employees e 
                           LEFT JOIN doctor d ON e.doctorid = d.doctorid");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - Hospital Management System</title>
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
        main { 
            flex: 1; 
            padding: 30px 20px; 
            display: flex; 
            justify-content: center; 
        }
        .container { 
            background: #f9f9f9; /* Off-white, like index.php containers */
            padding: 2rem; 
            border-radius: 10px; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); 
            max-width: 900px; 
            width: 100%; 
            animation: fadeIn 0.5s ease-in; 
        }
        @keyframes fadeIn { 
            from { opacity: 0; transform: translateY(10px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        h2 { 
            color: #3498db; /* Sky blue */
            text-align: center; 
            margin-bottom: 1.5rem; 
            font-size: 1.8rem; 
            font-weight: 600; 
        }
        .toggle-btn { 
            background: #3498db; 
            color: #fff; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 6px; 
            font-size: 1rem; 
            cursor: pointer; 
            margin: 10px 0; 
            width: 100%; 
            max-width: 300px; 
            display: block; 
            text-align: center; 
            transition: background 0.3s ease, transform 0.2s ease; 
        }
        .toggle-btn:hover { 
            background: #2980b9; 
            transform: translateY(-2px); 
        }
        .form-section, .employees-section { 
            display: none; 
            margin-top: 1rem; 
            padding: 1.5rem; 
            background: #fff; 
            border-radius: 8px; 
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); 
            animation: slideDown 0.3s ease-out; 
        }
        .form-section.active, .employees-section.active { 
            display: block; 
        }
        @keyframes slideDown { 
            from { opacity: 0; transform: translateY(-10px); } 
            to { opacity: 1; transform: translateY(0); } 
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
        form { 
            display: flex; 
            flex-direction: column; 
            gap: 10px; 
        }
        label { 
            font-weight: 500; 
            color: #333; 
        }
        input { 
            width: 100%; 
            padding: 10px; 
            margin-bottom: 15px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            font-size: 0.95rem; 
            transition: border-color 0.3s ease; 
        }
        input:focus { 
            border-color: #3498db; 
            outline: none; 
        }
        button { 
            background: #3498db; 
            color: #fff; 
            padding: 10px; 
            border: none; 
            border-radius: 5px; 
            font-size: 0.95rem; 
            cursor: pointer; 
            transition: background 0.3s ease, transform 0.2s ease; 
        }
        button:hover { 
            background: #2980b9; 
            transform: translateY(-2px); 
        }
        table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0; 
            margin-top: 1rem; 
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
        td { 
            background: #fff; 
        }
        tr:hover td { 
            background: #e6f3fa; /* Light blue hover */
        }
        .delete-btn { 
            background: #e74c3c; 
            padding: 6px 12px; 
            margin-right: 10px; 
        }
        .change-btn { 
            background: #4caf50; 
            padding: 6px 12px; 
        }
        .change-form { 
            display: none; 
            margin-top: 10px; 
            padding: 10px; 
            background: #f9f9f9; 
            border-radius: 5px; 
        }
        .no-data { 
            text-align: center; 
            color: #e74c3c; 
            padding: 1rem; 
            font-size: 1rem; 
        }
        .back-link { 
            display: block; 
            text-align: center; 
            margin: 1.5rem 0; 
            color: #3498db; 
            font-weight: 500; 
            text-decoration: none; 
            transition: color 0.3s ease; 
        }
        .back-link:hover { 
            color: #2980b9; 
            text-decoration: underline; 
        }
        footer { 
            background: #f5f5f5; /* Light gray */
            color: #555; 
            text-align: center; 
            padding: 1rem; 
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.05); 
        }
        @media (max-width: 768px) {
            .container { 
                padding: 1.5rem; 
            }
            .toggle-btn { 
                max-width: 100%; 
            }
            table { 
                font-size: 0.9rem; 
            }
            th, td { 
                padding: 10px; 
            }
        }
        @media (max-width: 600px) {
            header { 
                flex-direction: column; 
                gap: 1rem; 
            }
            .page-message { 
                font-size: 1.2rem; 
            }
            table { 
                display: block; 
                overflow-x: auto; 
                white-space: nowrap; 
            }
        }
    </style>
    <script>
        function toggleSection(sectionId, buttonId) {
            const section = document.getElementById(sectionId);
            const button = document.getElementById(buttonId);
            const isActive = section.classList.toggle('active');
            button.textContent = isActive ? `Hide ${sectionId === 'add-employee-section' ? 'Add Employee Form' : 'Current Employees'}` : `Show ${sectionId === 'add-employee-section' ? 'Add Employee Form' : 'Current Employees'}`;
            
            // Hide other section if open
            const otherSectionId = sectionId === 'add-employee-section' ? 'employees-section' : 'add-employee-section';
            const otherButtonId = sectionId === 'add-employee-section' ? 'toggle-employees-btn' : 'toggle-add-employee-btn';
            const otherSection = document.getElementById(otherSectionId);
            const otherButton = document.getElementById(otherButtonId);
            if (otherSection.classList.contains('active')) {
                otherSection.classList.remove('active');
                otherButton.textContent = `Show ${otherSectionId === 'add-employee-section' ? 'Add Employee Form' : 'Current Employees'}`;
            }
        }

        function showChangeForm(id) {
            document.querySelectorAll('.change-form').forEach(form => form.style.display = 'none');
            document.getElementById('change-' + id).style.display = 'block';
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
        <span class="page-message">Manage Employees - Hospital Management System</span>
    </header>

    <main>
        <div class="container">
            <h2>Manage Employees</h2>
            <a href="admin_dashboard.php" class="back-link">Back to Dashboard</a>

            <button id="toggle-add-employee-btn" class="toggle-btn" onclick="toggleSection('add-employee-section', 'toggle-add-employee-btn')"> Add New Employee </button>
            <div id="add-employee-section" class="form-section">
                <form method="POST">
                    <label for="doctorid">Doctor ID</label>
                    <input type="text" id="doctorid" name="doctorid" placeholder="e.g., D001" required>
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Choose a username" required>
                    <label for="password">Password</label>
                    <input type="text" id="password" name="password" placeholder="Choose a password" required>
                    <label for="emp_name">Employee Name</label>
                    <input type="text" id="emp_name" name="emp_name" placeholder="Enter employee's full name" required>
                    <label for="dept">Department</label>
                    <input type="text" id="dept" name="dept" placeholder="e.g., Cardiology" required>
                    <button type="submit" name="add_employee">Add Employee</button>
                </form>
            </div>

            <button id="toggle-employees-btn" class="toggle-btn" onclick="toggleSection('employees-section', 'toggle-employees-btn')">Show Current Employees</button>
            <div id="employees-section" class="employees-section">
                <?php if ($employees->num_rows > 0) { ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Doctor ID</th>
                                <th>Username</th>
                                <th>Employee Name</th>
                                <th>Department</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($emp = $employees->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($emp["id"]); ?></td>
                                    <td><?php echo htmlspecialchars($emp["doctorid"]); ?></td>
                                    <td><?php echo htmlspecialchars($emp["username"]); ?></td>
                                    <td><?php echo htmlspecialchars($emp["emp_name"]); ?></td>
                                    <td><?php echo htmlspecialchars($emp["dept"] ?: 'N/A'); ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="id" value="<?php echo $emp['id']; ?>">
                                            <button type="submit" name="delete_employee" class="delete-btn">Delete</button>
                                        </form>
                                        <button onclick="showChangeForm(<?php echo $emp['id']; ?>)" class="change-btn">Change Password</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="6">
                                        <form id="change-<?php echo $emp['id']; ?>" class="change-form" method="POST">
                                            <input type="hidden" name="id" value="<?php echo $emp['id']; ?>">
                                            <input type="text" name="new_password" placeholder="New Password" required>
                                            <button type="submit" name="change_password">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p class="no-data">No employees found.</p>
                <?php } ?>
            </div>
        </div>
    </main>

    <?php if (!empty($message)) { ?>
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
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

// Add user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_user"])) {
    $name = $_POST["name"];
    $username = $_POST["username"];
    $password = $_POST["password"];

    $check_patient = $conn->prepare("SELECT pid FROM patient WHERE name = ?");
    $check_patient->bind_param("s", $name);
    $check_patient->execute();
    $patient_result = $check_patient->get_result();

    if ($patient_result->num_rows > 0) {
        $patient = $patient_result->fetch_assoc();
        $pid = $patient["pid"];

        $check = $conn->prepare("SELECT pid, username FROM users WHERE pid = ? OR username = ?");
        $check->bind_param("is", $pid, $username);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO users (pid, username, password) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $pid, $username, $password);
            if ($stmt->execute()) {
                $message = "User added successfully!";
                $message_type = "success";
            } else {
                $message = "Error adding user: " . $conn->error;
                $message_type = "error";
            }
            $stmt->close();
        } else {
            $message = "Error: Patient ID or username already taken.";
            $message_type = "error";
        }
        $check->close();
    } else {
        $message = "Error: Patient name not found.";
        $message_type = "error";
    }
    $check_patient->close();
}

// Delete user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_user"])) {
    $id = $_POST["id"];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "User deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting user: " . $conn->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Change password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_password"])) {
    $id = $_POST["id"];
    $new_password = $_POST["new_password"];
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
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

// Fetch users
$users = $conn->query("SELECT u.id, u.pid, u.username, p.name 
                       FROM users u 
                       JOIN patient p ON u.pid = p.pid");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Hospital Management System</title>
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
        .form-section, .users-section { 
            display: none; 
            margin-top: 1rem; 
            padding: 1.5rem; 
            background: #fff; 
            border-radius: 8px; 
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); 
            animation: slideDown 0.3s ease-out; 
        }
        .form-section.active, .users-section.active { 
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
            button.textContent = isActive ? `Hide ${sectionId === 'add-user-section' ? 'Add User Form' : 'Current Users'}` : `Show ${sectionId === 'add-user-section' ? 'Add User Form' : 'Current Users'}`;
            
            // Hide other section if open
            const otherSectionId = sectionId === 'add-user-section' ? 'users-section' : 'add-user-section';
            const otherButtonId = sectionId === 'add-user-section' ? 'toggle-users-btn' : 'toggle-add-user-btn';
            const otherSection = document.getElementById(otherSectionId);
            const otherButton = document.getElementById(otherButtonId);
            if (otherSection.classList.contains('active')) {
                otherSection.classList.remove('active');
                otherButton.textContent = `Show ${otherSectionId === 'add-user-section' ? 'Add User Form' : 'Current Users'}`;
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
        <span class="page-message">Manage Users - Hospital Management System</span>
    </header>

    <main>
        <div class="container">
            <h2>Manage Users</h2>
            <a href="admin_dashboard.php" class="back-link">Back to Dashboard</a>

            <button id="toggle-add-user-btn" class="toggle-btn" onclick="toggleSection('add-user-section', 'toggle-add-user-btn')">Add New User</button>
            <div id="add-user-section" class="form-section">
                <form method="POST">
                    <label for="name">Patient Name (as registered)</label>
                    <input type="text" id="name" name="name" placeholder="Patient Name (as registered)" required>
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Choose a username" required>
                    <label for="password">Password</label>
                    <input type="text" id="password" name="password" placeholder="Choose a password" required>
                    <button type="submit" name="add_user">Add User</button>
                </form>
            </div>

            <button id="toggle-users-btn" class="toggle-btn" onclick="toggleSection('users-section', 'toggle-users-btn')">Show Current Users</button>
            <div id="users-section" class="users-section">
                <?php if ($users->num_rows > 0) { ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Patient ID</th>
                                <th>Patient Name</th>
                                <th>Username</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $users->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user["id"]); ?></td>
                                    <td><?php echo htmlspecialchars($user["pid"]); ?></td>
                                    <td><?php echo htmlspecialchars($user["name"]); ?></td>
                                    <td><?php echo htmlspecialchars($user["username"]); ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="delete-btn">Delete</button>
                                        </form>
                                        <button onclick="showChangeForm(<?php echo $user['id']; ?>)" class="change-btn">Change Password</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="5">
                                        <form id="change-<?php echo $user['id']; ?>" class="change-form" method="POST">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <input type="text" name="new_password" placeholder="New Password" required>
                                            <button type="submit" name="change_password">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p class="no-data">No users found.</p>
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
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

$unread_query = "SELECT COUNT(*) as unread_count FROM enquiries WHERE status = 'unread'";
$unread_result = $conn->query($unread_query);
$unread_count = $unread_result->fetch_assoc()['unread_count'];

$sql = "SELECT a.appt_id, p.name AS patient_name, p.dob, a.doctorid, d.doctorname, a.appt_date, a.reason, a.status 
        FROM Appointment a 
        JOIN Patient p ON a.pid = p.pid 
        JOIN Doctor d ON a.doctorid = d.doctorid 
        WHERE a.status NOT IN ('Completed', 'Cancelled') 
        ORDER BY a.appt_date DESC LIMIT 5";
$appointments_result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Hospital Management System</title>
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
        .welcome-message { 
            font-size: 1.4rem; 
            font-weight: 500; 
            letter-spacing: 0.5px; 
        }
        .logout-btn { 
            background: #e74c3c; /* Red for contrast */
            color: #fff; 
            padding: 8px 16px; 
            border: none; 
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
            display: flex; 
            justify-content: center; 
        }
        .dashboard-container { 
            background: #f9f9f9; /* Off-white, like index.php containers */
            padding: 2rem; 
            border-radius: 10px; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); 
            max-width: 1100px; 
            width: 100%; 
            animation: fadeIn 0.5s ease-in; 
        }
        @keyframes fadeIn { 
            from { opacity: 0; transform: translateY(10px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        h3 { 
            color: #3498db; /* Sky blue, matching index.php */
            text-align: center; 
            margin-bottom: 1.5rem; 
            font-size: 1.7rem; 
            font-weight: 600; 
        }
        ul { 
            list-style: none; 
            padding: 0; 
            max-width: 350px; 
            margin: 0 auto 2rem; 
            display: grid; 
            gap: 10px; 
        }
        ul li { 
            position: relative; 
            padding: 14px 20px; 
            background: #fff; 
            border-radius: 8px; 
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05); 
            transition: transform 0.2s ease, box-shadow 0.2s ease; 
        }
        ul li:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); 
        }
        ul li a { 
            text-decoration: none; 
            color: #3498db; 
            font-weight: 500; 
            font-size: 1rem; 
            display: block; 
        }
        ul li a:hover { 
            color: #2980b9; /* Darker sky blue for hover */
        }
        .notification-circle { 
            position: absolute; 
            top: 8px; 
            right: 10px; 
            background: #e74c3c; 
            color: #fff; 
            width: 22px; 
            height: 22px; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 0.8rem; 
            font-weight: 600; 
            display: <?php echo $unread_count > 0 ? 'flex' : 'none'; ?>; 
        }
        .popup { 
            position: fixed; 
            top: 20px; 
            left: 50%; 
            transform: translateX(-50%); 
            background: #3498db; /* Sky blue for consistency */
            color: #fff; 
            padding: 1rem 2rem; 
            border-radius: 8px; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); 
            z-index: 1000; 
            font-size: 1rem; 
            animation: slideIn 0.5s ease-out; 
        }
        @keyframes slideIn { 
            from { opacity: 0; top: 0; } 
            to { opacity: 1; top: 20px; } 
        }
        .appointments-section { 
            margin-top: 2rem; 
            background: #fff; 
            padding: 1.5rem; 
            border-radius: 8px; 
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); 
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
            background: #3498db; /* Sky blue, matching index.php */
            color: #fff; 
            font-weight: 600; 
            font-size: 0.95rem; 
        }
        td { 
            background: #fff; 
        }
        tr:hover td { 
            background: #e6f3fa; /* Light blue tint for hover */
        }
        .btn-edit { 
            padding: 6px 12px; 
            background: #3498db; 
            color: #fff; 
            text-decoration: none; 
            border-radius: 5px; 
            font-size: 0.85rem; 
            transition: background 0.3s ease, transform 0.2s ease; 
        }
        .btn-edit:hover { 
            background: #2980b9; 
            transform: translateY(-1px); 
        }
        .btn-schedule { 
            display: inline-block; 
            margin-top: 1rem; 
            padding: 10px 20px; 
            background: #3498db; 
            color: #fff; 
            text-decoration: none; 
            border-radius: 6px; 
            font-size: 0.95rem; 
            font-weight: 500; 
            transition: background 0.3s ease, transform 0.2s ease; 
        }
        .btn-schedule:hover { 
            background: #2980b9; 
            transform: translateY(-2px); 
        }
        .no-data { 
            text-align: center; 
            color: #e74c3c; 
            padding: 1.5rem; 
            font-size: 1rem; 
        }
        footer { 
            background: #f5f5f5; /* Light gray, avoiding dark colors */
            color: #555; 
            text-align: center; 
            padding: 1rem; 
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.05); 
        }
        @media (max-width: 768px) {
            .dashboard-container { 
                padding: 1.5rem; 
            }
            ul { 
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
            .welcome-message { 
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
        document.addEventListener("DOMContentLoaded", function() {
            <?php if ($unread_count > 0) { ?>
                const popup = document.createElement("div");
                popup.className = "popup";
                popup.textContent = "You have a new Enquiry!";
                document.body.appendChild(popup);
                popup.style.display = "block";
                setTimeout(() => {
                    popup.style.transition = "opacity 0.5s ease-out";
                    popup.style.opacity = "0";
                    setTimeout(() => popup.remove(), 500);
                }, 3000);
            <?php } ?>
        });
    </script>
</head>
<body>
    <header>
        <div class="logo-container">
            <a href="index.php"><img src="images/new_logo.jpeg" alt="Hospital Logo" class="logo"></a>
            <a href="index.php"><img src="images/health.jpeg" alt="NSW Health" class="health-logo"></a>
        </div>
        <span class="welcome-message">Welcome, Admin</span>
        <a href="logout.php" class="logout-btn">Logout</a>
    </header>

    <main>
        <div class="dashboard-container">
            <h3>Admin Dashboard</h3>
            <ul>
                <li><a href="manage_users.php">Manage Users</a></li>
                <li><a href="manage_employees.php">Manage Employees</a></li>
                <li><a href="view_reports.php">View Reports</a></li>
                <li>
                    <a href="view_enquiries.php">View Enquiries</a>
                    <span class="notification-circle"><?php echo $unread_count; ?></span>
                </li>
            </ul>

            <div class="appointments-section">
                <h3>View/Manage Appointments</h3>
                <?php if ($appointments_result->num_rows > 0) { ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Patient Name</th>
                                <th>Date of Birth</th>
                                <th>Doctor Name</th>
                                <th>Appointment Date</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $appointments_result->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['dob']); ?></td>
                                    <td><?php echo htmlspecialchars($row['doctorname']); ?></td>
                                    <td><?php echo date("d/m/Y H:i", strtotime($row['appt_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                                    <td>
                                        <a href="admin_manage_appointments.php?edit=<?php echo $row['appt_id']; ?>" class="btn-edit">Edit</a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p class="no-data">No active appointments scheduled.</p>
                <?php } ?>
                <a href="admin_manage_appointments.php" class="btn-schedule">Schedule New Appointment</a>
            </div>
        </div>
    </main>

    <footer>
        <p>© 2025 Hospital Management System. All rights reserved.</p>
    </footer>
</body>
</html>
<?php $conn->close(); ?>
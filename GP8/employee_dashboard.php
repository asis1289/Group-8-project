<?php
session_start();
if (!isset($_SESSION["employee"])) {
    header("Location: employee_login.php");
    exit();
}

// Database connection (using mysqli to match your setup)
$conn = new mysqli("localhost", "root", "", "hms_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch emp_name from employees table based on username
$username = $_SESSION['employee']; // This stores the username (e.g., "emp1")
$query = $conn->prepare("SELECT emp_name FROM employees WHERE username = ?");
$query->bind_param("s", $username);
$query->execute();
$result = $query->get_result();
$employee = $result->fetch_assoc();

// Set employee name, fallback to "Employee" if not found
$employeeName = $employee ? htmlspecialchars($employee['emp_name']) : 'Employee';

$query->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Hospital Management System</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Arial', sans-serif; 
            background: linear-gradient(135deg, #d4efdf, #a9e0c5); /* Light green gradient */
            color: #333; 
            line-height: 1.6; 
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; 
        }
        header { 
            background: #3498db; 
            color: #fff; 
            padding: 10px 20px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
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
        .nav-links { 
            display: flex; 
            gap: 10px; 
        }
        .nav-links a { 
            color: #fff; 
            text-decoration: none; 
            padding: 8px 12px; 
            font-size: 16px; 
            border-radius: 4px; 
            transition: background 0.3s ease; 
        }
        .nav-links a:hover { 
            background: rgba(255, 255, 255, 0.1); 
        }
        .nav-links a.active { 
            background: rgba(255, 255, 255, 0.2); 
            font-weight: bold; 
        }
        .right-menu { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        .welcome-message { 
            font-size: 1.4rem; 
            font-weight: 500; 
            letter-spacing: 0.5px; 
        }
        .logout-btn { 
            background: #e74c3c; 
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
            padding: 40px 20px; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
        }
        .dashboard-container { 
            background: #fff; 
            padding: 2rem; 
            border-radius: 12px; 
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15); 
            max-width: 600px; 
            width: 100%; 
            animation: fadeIn 0.5s ease-in; 
            text-align: center; 
        }
        @keyframes fadeIn { 
            from { opacity: 0; transform: translateY(20px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        h3 { 
            color: #27ae60; 
            margin-bottom: 2rem; 
            font-size: 1.8rem; 
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1); 
        }
        ul { 
            list-style: none; 
            padding: 0; 
        }
        ul li { 
            margin: 15px 0; 
        }
        ul li a { 
            display: block; 
            background: linear-gradient(90deg, #2ecc71, #27ae60); /* Green gradient for buttons */
            color: #fff; 
            padding: 12px 20px; 
            border-radius: 8px; 
            text-decoration: none; 
            font-size: 1.1rem; 
            font-weight: 600; 
            transition: background 0.3s, transform 0.2s, box-shadow 0.3s; 
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); 
        }
        ul li a:hover { 
            background: linear-gradient(90deg, #27ae60, #2ecc71); 
            transform: translateY(-3px); 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); 
        }
        footer { 
            background: #27ae60; /* Matching green footer */
            color: #fff; 
            text-align: center; 
            padding: 1rem; 
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.2); 
        }
        @media (max-width: 768px) { 
            .dashboard-container { padding: 1.5rem; } 
            h3 { font-size: 1.5rem; } 
            ul li a { padding: 10px 15px; font-size: 1rem; } 
        }
        @media (max-width: 600px) { 
            header { 
                flex-direction: column; 
                gap: 1rem; 
            }
            .nav-links { 
                flex-direction: column; 
                align-items: center; 
                gap: 5px; 
            }
            .right-menu { 
                flex-direction: column; 
                gap: 5px; 
            }
            .welcome-message { 
                font-size: 1.2rem; 
            }
            .logout-btn { 
                padding: 6px 12px; 
                font-size: 0.9rem; 
            }
            .dashboard-container { padding: 1rem; } 
            h3 { font-size: 1.2rem; } 
            ul li a { padding: 8px 12px; font-size: 0.9rem; } 
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <a href="index.php"><img src="images/new_logo.jpeg" alt="Hospital Logo" class="logo"></a>
            <a href="index.php"><img src="images/health.jpeg" alt="NSW Health" class="health-logo"></a>
        </div>
        <div class="nav-links">
            <a href="index.php" class="active">Home</a>
            <a href="about.php">About</a>
            <a href="register.php">Register Patient</a>
        </div>
        <div class="right-menu">
            <span class="welcome-message">Welcome, <?php echo $employeeName; ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <main>
        <div class="dashboard-container">
            <h3>Employee Dashboard</h3>
            <ul>
                <li><a href="view_patients.php">View Patients</a></li>
                <li><a href="schedule_appointments.php">Schedule Appointments</a></li>
                <li><a href="update_records.php">Update Records</a></li>
            </ul>
        </div>
    </main>

    <footer>
        <p>© 2025 Hospital Management System. All rights reserved.</p>
    </footer>
</body>
</html>
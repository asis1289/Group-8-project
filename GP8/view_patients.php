<?php
$conn = new mysqli("localhost", "root", "", "hms_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT pid, name, age, dob, gender, phoneno FROM Patient";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Patients - HMS</title>
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
        }
        h2 { color: #2c3e50; margin-bottom: 1.5rem; font-size: 1.8rem; }
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
            table { font-size: 0.9rem; }
            th, td { padding: 0.75rem; }
        }
        @media (max-width: 480px) {
            header { flex-direction: column; padding: 1rem 0; }
            .logo { margin: 0 0 1rem 0; }
            h2 { font-size: 1.2rem; }
            table { font-size: 0.8rem; }
            th, td { padding: 0.5rem; }
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
            <h2>View Patients</h2>
            <?php
            if ($result->num_rows > 0) {
                echo "<table><tr><th>Patient ID</th><th>Name</th><th>Age</th><th>Date of Birth</th><th>Gender</th><th>Phone Number</th></tr>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr><td>{$row['pid']}</td><td>{$row['name']}</td><td>{$row['age']}</td><td>{$row['dob']}</td><td>{$row['gender']}</td><td>{$row['phoneno']}</td></tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No patients registered.</p>";
            }
            $conn->close();
            ?>
            <a href="employee_dashboard.php" class="btn-return">Return to Dashboard</a>
        </section>
    </main>
    <footer>
        <p>© 2025 Hospital Management System</p>
    </footer>
</body>
</html>
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

// Set timezone to AEDT
date_default_timezone_set("Australia/Sydney");

// 1. Employee Performance (Appointments per Doctor)
$doctor_performance_sql = "
    SELECT d.doctorname, COUNT(a.appt_id) as appt_count
    FROM Doctor d
    LEFT JOIN Appointment a ON d.doctorid = a.doctorid
    GROUP BY d.doctorid, d.doctorname
    ORDER BY appt_count DESC";
$doctor_result = $conn->query($doctor_performance_sql);
$doctor_names = [];
$doctor_appt_counts = [];
while ($row = $doctor_result->fetch_assoc()) {
    $doctor_names[] = $row['doctorname'];
    $doctor_appt_counts[] = $row['appt_count'];
}

// 2. Patient Volume (Monthly Patient Count for 2025)
$patient_volume_sql = "
    SELECT MONTH(a.appt_date) as month, COUNT(DISTINCT a.pid) as patient_count
    FROM Appointment a
    WHERE YEAR(a.appt_date) = 2025
    GROUP BY MONTH(a.appt_date)";
$patient_result = $conn->query($patient_volume_sql);
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$patient_counts = array_fill(0, 12, 0); // Initialize with 0 for all months
while ($row = $patient_result->fetch_assoc()) {
    $patient_counts[$row['month'] - 1] = $row['patient_count']; // Month is 1-based, array is 0-based
}

// 3. Appointment Status Distribution
$status_sql = "
    SELECT status, COUNT(appt_id) as status_count
    FROM Appointment
    GROUP BY status";
$status_result = $conn->query($status_sql);
$status_labels = [];
$status_counts = [];
while ($row = $status_result->fetch_assoc()) {
    $status_labels[] = $row['status'];
    $status_counts[] = $row['status_count'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Reports - Hospital Management System</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .welcome-message { 
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
        .reports-container { 
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
        h2 { 
            color: #1a237e; 
            text-align: center; 
            margin-bottom: 2rem; 
            font-size: 2rem; 
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1); 
        }
        h3 { 
            color: #3f51b5; 
            margin-bottom: 1rem; 
            font-size: 1.5rem; 
            text-align: center; 
        }
        .chart-container { 
            background: #f9f9f9; 
            padding: 1.5rem; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); 
            margin-bottom: 2rem; 
            transition: transform 0.3s, box-shadow 0.3s; 
        }
        .chart-container:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15); 
        }
        canvas { 
            width: 100% !important; 
            height: auto !important; 
        }
        .btn-return { 
            display: block; 
            margin: 1.5rem auto 0; 
            padding: 12px 20px; 
            background: linear-gradient(90deg, #e74c3c, #c0392b); 
            color: #fff; 
            text-decoration: none; 
            border-radius: 6px; 
            font-size: 1.1rem; 
            text-align: center; 
            transition: background 0.3s, transform 0.2s; 
        }
        .btn-return:hover { 
            background: linear-gradient(90deg, #c0392b, #e74c3c); 
            transform: translateY(-2px); 
        }
        footer { 
            background: #1a237e; 
            color: #fff; 
            text-align: center; 
            padding: 1rem; 
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.2); 
        }
        @media (max-width: 768px) { 
            .welcome-message { font-size: 1.2rem; } 
            h2 { font-size: 1.5rem; } 
            h3 { font-size: 1.2rem; } 
            .chart-container { padding: 1rem; } 
            .btn-return { padding: 10px 15px; font-size: 1rem; } 
        }
        @media (max-width: 480px) { 
            header { flex-direction: column; padding: 1rem 0; } 
            .logo-container { margin-bottom: 1rem; } 
            .welcome-message { font-size: 1rem; } 
            h2 { font-size: 1.2rem; } 
            h3 { font-size: 1rem; } 
            .chart-container { padding: 0.75rem; } 
            .btn-return { padding: 8px 12px; font-size: 0.9rem; } 
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <a href="index.php"><img src="images/new_logo.jpeg" alt="Hospital Logo" class="logo"></a>
            <a href="index.php"><img src="images/health.jpeg" alt="NSW Health" class="health-logo"></a>
        </div>
        <span class="welcome-message">Hospital Management System - Admin Reports</span>
    </header>

    <main>
        <div class="reports-container">
            <h2>Performance Reports</h2>

            <!-- Employee Performance Bar Chart -->
            <div class="chart-container">
                <h3>Employee Performance (Appointments per Doctor)</h3>
                <canvas id="doctorPerformanceChart"></canvas>
            </div>

            <!-- Patient Volume Line Chart -->
            <div class="chart-container">
                <h3>Patient Volume (Monthly 2025)</h3>
                <canvas id="patientVolumeChart"></canvas>
            </div>

            <!-- Appointment Status Pie Chart -->
            <div class="chart-container">
                <h3>Appointment Status Distribution</h3>
                <canvas id="statusPieChart"></canvas>
            </div>

            <a href="admin_dashboard.php" class="btn-return">Return to Dashboard</a>
        </div>
    </main>

    <footer>
        <p>© 2025 Hospital Management System. All rights reserved.</p>
    </footer>

    <script>
        // Doctor Performance Bar Chart
        const doctorCtx = document.getElementById('doctorPerformanceChart').getContext('2d');
        new Chart(doctorCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($doctor_names); ?>,
                datasets: [{
                    label: 'Appointments Handled',
                    data: <?php echo json_encode($doctor_appt_counts); ?>,
                    backgroundColor: 'rgba(52, 152, 219, 0.7)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Number of Appointments' } },
                    x: { title: { display: true, text: 'Doctors' } }
                },
                plugins: { legend: { display: false } }
            }
        });

        // Patient Volume Line Chart
        const patientCtx = document.getElementById('patientVolumeChart').getContext('2d');
        new Chart(patientCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Unique Patients',
                    data: <?php echo json_encode($patient_counts); ?>,
                    backgroundColor: 'rgba(39, 174, 96, 0.2)',
                    borderColor: 'rgba(39, 174, 96, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Number of Patients' } },
                    x: { title: { display: true, text: 'Month (2025)' } }
                }
            }
        });

        // Appointment Status Pie Chart
        const statusCtx = document.getElementById('statusPieChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($status_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($status_counts); ?>,
                    backgroundColor: [
                        'rgba(52, 152, 219, 0.7)',  // Scheduled
                        'rgba(241, 196, 15, 0.7)',  // Rescheduled
                        'rgba(39, 174, 96, 0.7)',   // Completed
                        'rgba(231, 76, 60, 0.7)'    // Cancelled
                    ],
                    borderColor: [
                        'rgba(52, 152, 219, 1)',
                        'rgba(241, 196, 15, 1)',
                        'rgba(39, 174, 96, 1)',
                        'rgba(231, 76, 60, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'right' },
                    title: { display: true, text: 'Appointment Status Breakdown' }
                }
            }
        });
    </script>
</body>
</html>
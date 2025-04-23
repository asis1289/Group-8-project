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

// Mark all enquiries as read when viewed
$conn->query("UPDATE enquiries SET status = 'read' WHERE status = 'unread'");

$result = $conn->query("SELECT * FROM enquiries ORDER BY submitted_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Enquiries - Hospital Management System</title>
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
        h2 { 
            color: #1a237e; 
            text-align: center; 
            margin-bottom: 1.5rem; 
            font-size: 1.8rem; 
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1); 
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
</head>
<body>
    <header>
        <div class="logo-container">
            <a href="index.php"><img src="images/new_logo.jpeg" alt="Hospital Logo" class="logo"></a>
            <a href="index.php"><img src="images/health.jpeg" alt="NSW Health" class="health-logo"></a>
        </div>
        <span class="page-message">View Enquiries - Hospital Management System</span>
    </header>

    <main>
        <div class="container">
            <h2>Enquiries</h2>
            <?php if ($result->num_rows > 0) { ?>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Enquiry</th>
                        <th>Submitted At</th>
                    </tr>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['phoneno']); ?></td>
                            <td><?php echo htmlspecialchars($row['enquiry']); ?></td>
                            <td><?php echo date("d/m/Y H:i", strtotime($row['submitted_at'])); ?></td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } else { ?>
                <p class="no-data">No enquiries found.</p>
            <?php } ?>
            <a href="admin_dashboard.php" class="back-link">Back to Dashboard</a>
        </div>
    </main>

    <footer>
        <p>© 2025 Hospital Management System. All rights reserved.</p>
    </footer>
</body>
</html>
<?php $conn->close(); ?>
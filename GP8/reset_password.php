<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "hms_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
$message = "";
$message_type = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];
    $phoneno = isset($_SESSION['reset_phoneno']) ? $_SESSION['reset_phoneno'] : '';

    if (empty($phoneno)) {
        $message = "Session expired. Please try again.";
        $message_type = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = "Confirm password does not match.";
        $message_type = "error";
    } else {
        // Update password in users table
        $stmt = $conn->prepare("UPDATE users u 
                                JOIN patient p ON u.pid = p.pid 
                                SET u.password = ? 
                                WHERE p.phoneno = ?");
        $stmt->bind_param("ss", $new_password, $phoneno);
        if ($stmt->execute()) {
            $message = "Password reset successfully!";
            $message_type = "success";
            unset($_SESSION['reset_phoneno']); // Clear session
        } else {
            $message = "Error updating password: " . $conn->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Arial', sans-serif; 
            color: #333; 
            line-height: 1.6; 
        }
        h2 { 
            color: #1a237e; 
            text-align: center; 
            margin-bottom: 1.5rem; 
            font-size: 1.8rem; 
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1); 
        }
        form label { 
            display: block; 
            margin: 10px 0 5px; 
            font-weight: 600; 
            color: #1a237e; 
        }
        input { 
            width: 100%; 
            padding: 10px; 
            margin-bottom: 15px; 
            border: 2px solid #ddd; 
            border-radius: 6px; 
            font-size: 1rem; 
            transition: border-color 0.3s; 
        }
        input:focus { 
            border-color: #3f51b5; 
            outline: none; 
        }
        button { 
            width: 100%; 
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
        .message { 
            text-align: center; 
            margin: 15px 0; 
            font-size: 1rem; 
            padding: 10px; 
            border-radius: 5px; 
            animation: slideIn 0.5s ease-out; 
        }
        .message.success { 
            background: #d4edda; 
            color: #155724; 
        }
        .message.error { 
            background: #f8d7da; 
            color: #721c24; 
        }
        .message.success a { 
            display: block; 
            margin-top: 10px; 
            color: #3f51b5; 
            font-weight: 600; 
            text-decoration: none; 
            transition: color 0.3s, text-decoration 0.3s; 
        }
        .message.success a:hover { 
            color: #5c6bc0; 
            text-decoration: underline; 
        }
        @keyframes slideIn { 
            from { opacity: 0; transform: translateY(-20px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
    </style>
</head>
<body>
    <h2>Reset Password</h2>
    <form method="POST" action="reset_password.php">
        <label for="new_password">New Password</label>
        <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
        <label for="confirm_password">Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
        <button type="submit">Reset</button>
    </form>
    <?php if (!empty($message)) { ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo $message; ?>
            <?php if ($message_type === 'success') { ?>
                <a href="user_login.php" target="_parent">Go to Login</a>
            <?php } ?>
        </div>
    <?php } ?>
</body>
</html>
<?php $conn->close(); ?>
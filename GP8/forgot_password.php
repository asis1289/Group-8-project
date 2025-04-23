<?php
session_start();
$conn = new mysqli("localhost", "root", "", "hms_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phoneno = $_POST["phoneno"];

    // Check if phone number exists
    $stmt = $conn->prepare("SELECT p.pid, p.phoneno, u.id 
                            FROM patient p 
                            JOIN users u ON p.pid = u.pid 
                            WHERE p.phoneno = ?");
    $stmt->bind_param("s", $phoneno);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $token = bin2hex(random_bytes(16)); // Simple token for reset link
        $reset_link = "http://localhost/hms/reset_password.php?token=$token&id=" . $user["id"];

        // Simulate sending link (in reality, use an SMS API)
        $message = "Password reset link: $reset_link (Simulated - normally sent to $phoneno)";
        
        // Store token in database (temporary table for simplicity)
        $conn->query("CREATE TABLE IF NOT EXISTS reset_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(32) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )");
        $stmt = $conn->prepare("INSERT INTO reset_tokens (user_id, token) VALUES (?, ?)");
        $stmt->bind_param("is", $user["id"], $token);
        $stmt->execute();
    } else {
        $message = "Error: Phone number not found.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 400px; margin: 0 auto; }
        h2 { text-align: center; color: #3498db; }
        input { width: 100%; padding: 8px; margin: 10px 0; }
        button { background-color: #3498db; color: #fff; padding: 10px; border: none; width: 100%; cursor: pointer; }
        p { text-align: center; color: green; }
        .error { color: red; }
        a { color: #3498db; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>
        <form method="POST">
            <input type="text" name="phoneno" placeholder="Enter your registered phone number" required>
            <button type="submit">Request Reset Link</button>
        </form>
        <?php if (isset($message)) echo "<p class='" . (strpos($message, 'Error') !== false ? 'error' : '') . "'>$message</p>"; ?>
        <p><a href="user_login.php">Back to Login</a></p>
    </div>
</body>
</html>
<?php $conn->close(); ?>
<?php
session_start();
$conn = new mysqli("localhost", "root", "", "hms_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET["token"]) || !isset($_GET["id"])) {
    die("Invalid reset link.");
}

$token = $_GET["token"];
$id = $_GET["id"];

$stmt = $conn->prepare("SELECT user_id FROM reset_tokens WHERE token = ? AND user_id = ? AND created_at > NOW() - INTERVAL 1 HOUR");
$stmt->bind_param("si", $token, $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Invalid or expired reset link.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST["new_password"];
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $new_password, $id);
    if ($stmt->execute()) {
        $conn->query("DELETE FROM reset_tokens WHERE token = '$token'"); // Remove used token
        $message = "Password reset successfully! <a href='user_login.php'>Login here</a>";
    } else {
        $message = "Error: " . $conn->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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
        <h2>Reset Password</h2>
        <form method="POST">
            <input type="text" name="new_password" placeholder="Enter new password" required>
            <button type="submit">Reset Password</button>
        </form>
        <?php if (isset($message)) echo "<p class='" . (strpos($message, 'Error') !== false ? 'error' : '') . "'>$message</p>"; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>
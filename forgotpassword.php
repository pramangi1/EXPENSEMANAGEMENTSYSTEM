<?php
session_start();
include 'db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $newPassword = $_POST['new_password'];
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Check if email exists
    $stmt = $conn->prepare("SELECT u_id FROM users WHERE u_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Update password
        $update = $conn->prepare("UPDATE users SET password_hash = ? WHERE u_email = ?");
        $update->bind_param("ss", $hashedPassword, $email);
        if ($update->execute()) {
            $message = "Password successfully updated. <a href='login.php'>Login here</a>";
        } else {
            $message = "Error updating password.";
        }
    } else {
        $message = "No account found with that email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="css/forgot.css">
</head>
<body>

    <div class="forgot-container">
        <h2>Reset Password</h2>
        <!-- Display message if exists -->
        <?php if ($message) { ?>
            <p class="message"><?php echo $message; ?></p>
        <?php } ?>
        
        <form method="POST" action="">
            <div class="input-group">
                <label for="email">Your Email</label>
                <input type="email" name="email" id="email" placeholder="Enter your email" required>
            </div>

            <div class="input-group">
                <label for="new_password">New Password</label>
                <input type="password" name="new_password" id="new_password" placeholder="Enter your new password" required>
            </div>
            
            <button type="submit">Update Password</button>
        </form>
    </div>

</body>
</html>

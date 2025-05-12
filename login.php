<?php
// Start the session
session_start();

// Include database connection
include 'db.php'; 

// Connect to the database
$conn = mysqli_connect($servername, $user, $pass, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userEmail = trim($_POST['email']);
    $userPassword = $_POST['password'];

    // Validate input
    if (empty($userEmail) || empty($userPassword)) {
        $loginError = "Email and password are required.";
    } else {
        // Prepare SQL statement to fetch user
        $sql = "SELECT * FROM users WHERE u_email = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("SQL Prepare Error: " . $conn->error);
        }

        // Bind and execute
        $stmt->bind_param("s", $userEmail);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($userPassword, $user['password_hash'])) {
                // Store session
                $_SESSION['u_id'] = $user['u_id'];  
                $_SESSION['u_name'] = $user['u_name']; 
                $_SESSION['u_email'] = $user['u_email']; 

                // ✅ Check if user has any budget head amounts via join
                $checkBH = "
                    SELECT COUNT(*) as count 
                    FROM budget_head_amount bha
                    INNER JOIN budget b ON bha.budget_id = b.budget_id
                    WHERE b.u_id = ?
                ";
                $bhStmt = $conn->prepare($checkBH);

                if (!$bhStmt) {
                    die("SQL Prepare Error (budget check): " . $conn->error);
                }

                $bhStmt->bind_param("i", $user['u_id']);
                $bhStmt->execute();
                $bhResult = $bhStmt->get_result();
                $bhData = $bhResult->fetch_assoc();

                // Redirect accordingly
                if ($bhData['count'] > 0) {
                    header("Location: dashboard.php");
                } else {
                    header("Location: addbudgethead.php");
                }

                // Close all
                $bhStmt->close();
                $stmt->close();
                $conn->close();
                exit();
            } else {
                $loginError = "Invalid password.";
            }
        } else {
            $loginError = "No account found with that email.";
        }

        $stmt->close();
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Daily Expense Tracker</title>
    <!-- <link rel="stylesheet" href="css/login.css"> -->
     <style>
        body {
    font-family: Arial, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background-color: #f4f4f4;
}


.h2{
    text-align: center;
    margin-bottom: 20px;
    color: #333;
}


.login-container {
  padding: 20px;
    border-radius: 8px; 
    width: 300px;
    text-align: center;
} 
.input-group {
    margin: 15px 0;
    text-align: left;
}
.input-group label {
    display: block;
    font-weight: bold;
}
.input-group input {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 4px;
}
button {
    width: 100%;
    padding: 10px;
    background-color: #0e6181;
    border: none;
    color: white;
    font-size: 16px;
    border-radius: 20px;
    cursor: pointer;
}
button:hover {
    background-color: #098553;
}
</style>

</head>
<body> 
<!-- <div class="header">
  <div class="branding">Budget Buddy</div>
</div> -->



     <div class="login-container"> 
        <h2>Login</h2>

        <?php if (isset($loginError) && !empty($loginError)) { ?>
            <div class="error"><?= htmlspecialchars($loginError); ?></div>
        <?php } ?>

        <form action="login.php" method="POST">
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
            <p> <a href="forgotpassword.php">forgot your password ?</a> </p>
            <p>Don't have an account? <a href="register.php">Register Here</a></p> 
        </form>
    </div>

</body>
</html>

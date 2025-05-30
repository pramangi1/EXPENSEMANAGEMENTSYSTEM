<?php
session_start();
include 'config/database.php'; // Ensure this file defines $servername, $user, $pass, $dbname

$error = ""; // For error messages

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Trim and sanitize input
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm-password'];

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $error = "Name can only contain letters and spaces.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Connect to DB
        $conn = mysqli_connect($servername, $user, $pass, $dbname);
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }

        // Check for existing email
        $check_email_sql = "SELECT * FROM users WHERE u_email = ?";
        $stmt = $conn->prepare($check_email_sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email is already registered.";
        } else {
            $stmt->close(); // Close before new query

            // Insert the user
            $insert_sql = "INSERT INTO users(u_name, u_email, password_hash) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("sss", $name, $email, $hashed_password);

            if ($stmt->execute()) {
                $_SESSION['registration_success'] = true;
                header("Location: register.php");
                exit();
            } else {
                $error = "Error: " . $stmt->error;
            }
        }

        if (isset($stmt)) $stmt->close();
        $conn->close();
    }
}
?>
<!-- HTML PART BELOW -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Daily Expenses Tracker</title>
    <!-- <link rel="stylesheet" href="css/register.css"> -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/register.js"></script>
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.container {
    padding: 30px;
    border-radius: 8px;
    
    width: 400px;
}
.register-flex {
    display: flex;

    border-radius: 10px;

    overflow: hidden;
    max-width: 900px;
    width: 100%;
}

.image-side {
    flex: 1;

    display: flex;
    align-items: center;
    justify-content: center;
}

.image-side img {
    max-width: 100%;
    height: auto;
}

h2 {
    text-align: center;
    margin-bottom: 20px;
}

form {
    display: flex;
    flex-direction: column;
}

label {
    margin: 10px 0 5px;
}

input {
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
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
    background-color:rgb(7, 131, 40);
}


p {
    text-align: center;
    margin-top: 10px;
}

a {
    color: #5c73b8;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>
</head>
<body>
<?php include 'header.php'; ?>

   <div class="register-flex">
    <div class="image-side">
        <img src="image/singup.png" alt="Sign Up Image">
    </div>
    <div class="container">
        <h2>Register</h2>

        <form action="register.php" method="POST">
            <label for="name">Full Name:</label>
            <input type="text" id="name" name="name" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <label for="confirm-password">Confirm Password:</label>
            <input type="password" id="confirm-password" name="confirm-password" required>

            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>

    <?php if (!empty($error)): ?>
        <script>
            showRegistrationError(<?= json_encode($error); ?>);
        </script>
    <?php endif; ?>

    <?php if (isset($_SESSION['registration_success']) && $_SESSION['registration_success']): ?>
        <script>
            showRegistrationSuccess();
        </script>
        <?php unset($_SESSION['registration_success']); ?>
    <?php endif; ?>
</body>
</html>

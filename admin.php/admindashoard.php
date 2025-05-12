<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="styles.css">
    <script src="script.js" defer></script>
</head>
<body>
    <?php
    session_start();
    include 'config/database.php';
    
    // Fetch pending users
    $userQuery = "SELECT id, name, email, status FROM users WHERE status = 'Pending'";
    $result = $conn->query($userQuery);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_user'])) {
        $user_id = $_POST['user_id'];
        $updateQuery = "UPDATE users SET status = 'Verified' WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        header("Location: admin.php"); // Refresh after verification
    }
    ?>
    
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
            <li><a href="#">Dashboard</a></li>
            <li><a href="#">Verify Users</a></li>
            <li><a href="#">Logout</a></li>
        </ul>
    </div>
    <div class="main-content">
        <h1>Welcome to Admin Dashboard</h1>
        <div class="verify-users">
            <h2>Verify Users</h2>
            <table>
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="verify_user">Verify</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</body>
</html>

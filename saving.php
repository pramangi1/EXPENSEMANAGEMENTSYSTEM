<?php
session_start();
include 'db.php';

if (!isset($_SESSION['u_id'])) {
    header("Location: login.php");
    exit();
}

$u_id = $_SESSION['u_id'];

// Fetch summary by budget
$sql = "
    SELECT 
        b.budget_id,
        b.bname AS budget_name,
        SUM(s.allocated_amount) AS total_allocated,
        SUM(s.saved_amount) AS total_saved
    FROM saving s
    JOIN budget_head_amount bha ON s.bha_id = bha.bha_id
    JOIN budget b ON bha.budget_id = b.budget_id
    WHERE b.u_id = ?
    GROUP BY b.budget_id
    ORDER BY b.budget_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $u_id);
$stmt->execute();
$result = $stmt->get_result();
$summary = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Savings Report</title>
    <link rel ="stylesheet" href="css/hello.css">
    <style>
        body {
            background-color: #f5f7fa;
            color: #333;
            font-family: 'Roboto', sans-serif;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 26px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }
        th, td {
            padding: 12px;
            text-align: center;
            border: 1px solid #ccc;
        }
        th {
            background-color: #34495e;
            color: white;
        }
        tr:hover {
            background-color: #ecf0f1;
        }
        .btn {
            padding: 6px 12px;
            background-color: #2980b9;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

    </style>
</head>
<body>
<div class="sidebar">
    <?php include 'header.php';?>
    <ul>
        <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
        <li><a href="list.php" class="<?= basename($_SERVER['PHP_SELF']) == 'list.php' ? 'active' : '' ?>">Expense List</a></li>
        <li><a href="addexpense.php" class="<?= basename($_SERVER['PHP_SELF']) == 'addexpense.php' ? 'active' : '' ?>">Add Expense</a></li>
        <li><a href="budgethead.php" class="<?= basename($_SERVER['PHP_SELF']) == 'budgethead.php' ? 'active' : '' ?>">Budget Head</a></li>
        <li><a href="reports.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">Reports</a></li>
        <li><a href="saving.php" class="<?= basename($_SERVER['PHP_SELF']) == 'saving.php' ? 'active' : '' ?>">Savings</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <h2>Savings per Months</h2>
    <?php if (count($summary) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Budget Name</th>
                    <th>Total Budget</th>
                    <th>Total Saved</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($summary as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['budget_name']) ?></td>
                        <td><?= number_format($row['total_allocated'], 2) ?></td>
                        <td><?= number_format($row['total_saved'], 2) ?></td>
                        <td><a href="summary.php?budget_id=<?= $row['budget_id'] ?>" class="btn">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-data">No savings recorded yet.</p>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
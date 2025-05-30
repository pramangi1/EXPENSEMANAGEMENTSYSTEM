<?php
session_start();
include 'db.php';

if (!isset($_SESSION['u_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['budget_id'])) {
    header("Location: saving.php");
    exit();
}

$budget_id = intval($_GET['budget_id']);
$u_id = $_SESSION['u_id'];

$sql = "
    SELECT 
        s.saved_year,
        s.saved_month,
        s.allocated_amount,
        s.saved_amount,
        bh.title AS budget_head,
        b.bname AS budget_name
    FROM saving s
    JOIN budget_head_amount bha ON s.bha_id = bha.bha_id
    JOIN budget_head bh ON bha.bhid = bh.bhid
    JOIN budget b ON bha.budget_id = b.budget_id
    WHERE b.budget_id = ? AND b.u_id = ?
    ORDER BY s.saved_year DESC, s.saved_month DESC, bh.title ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $budget_id, $u_id);
$stmt->execute();
$result = $stmt->get_result();
$details = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Savings Detail</title>
    <link rel ="stylesheet" href="css/hello.css">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        .main-content {
            margin-left: 250px;
            padding: 30px;
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
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        th, td {
            border: 1px solid #dcdcdc;
            padding: 14px;
            text-align: center;
            font-size: 16px;
        }
        th {
            background-color: #2c3e50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .btn {
            margin-top: 20px;
            padding: 10px 20px;
            background-color:rgb(4, 37, 73);
            color: white;
            text-decoration: none;
            border-radius: 20px;
           cursor: pointer;
            display: inline-block;
            font-size: 16px;
        }
        .btn:hover {
            background-color:rgb(21, 84, 24);
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
    <?php if ($details): ?>
        <h2>Savings Breakdown - <?= htmlspecialchars($details[0]['budget_name']) ?></h2>
        <table>
            <thead>
                <tr>
                    <th>Year</th>
                    <th>Month</th>
                    <th>Budget Head</th>
                    <th>Allocated</th>
                    <th>Saved</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($details as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['saved_year']) ?></td>
                        <td><?= DateTime::createFromFormat('!m', $row['saved_month'])->format('F') ?></td>
                        <td><?= htmlspecialchars($row['budget_head']) ?></td>
                        <td><?= number_format($row['allocated_amount'], 2) ?></td>
                        <td><?= number_format($row['saved_amount'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No savings details available for this budget.</p>
    <?php endif; ?>

    <a href="saving.php" class="btn">Back to Summary</a>
</div>
<?php include 'footer.php';?>
</body>
</html>
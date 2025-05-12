<?php
session_start();
include 'db.php';

if (!isset($_SESSION['u_id'])) {
    header("Location: login.php");
    exit();
}

$u_id = $_SESSION['u_id'];

// Fetch savings with joins for title, budget, and user
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
    WHERE b.u_id = ?
    ORDER BY s.saved_year DESC, s.saved_month DESC, bh.title ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $u_id);
$stmt->execute();
$result = $stmt->get_result();
$savings = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Savings Report</title>
   <!-- <style rel="stylesheet" href="css/dashboard.css"></style> -->
    <style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Roboto', sans-serif;
}

body {
  background-color: #f5f7fa;
  color: #333;
  line-height: 1.6;
  display: flex;
  min-height: 100vh;
}

.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 250px;
    background-color: #2c3e50;
    padding-top: 40px;
    color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.sidebar h3 {
  text-align: center;
  margin-bottom: 30px;
  font-size: 1.5rem;
  color: #ecf0f1;
  padding: 0 20px;
}

.sidebar ul {
  list-style: none;
  padding: 0;
}

.sidebar ul li {
  margin: 5px 0;
}

.sidebar ul li a {
  display: block;
  color: #bdc3c7;
  padding: 12px 20px;
  text-decoration: none;
  transition: all 0.3s;
  font-size: 1rem;
}

.sidebar ul li a:hover {
  background: #34495e;
  color: #ecf0f1;
  padding-left: 25px;
}

.sidebar a.active {
  background-color: #0b73ea;  /* Highlight color */
  color: white;
  font-weight: bold;
  padding-left: 10px;
  border-left: 4px solid #144781; /* Optional indicator */
}


/* ===== Main Content Styles ===== */
.main-content {
  margin-left: 250px;
  width: calc(100% - 250px);
  padding: 20px;
  transition: all 0.3s;
}

        h2 {
            text-align: center;
            color: #2c3e50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px 15px;
            text-align: center;
            border: 1px solid #e0e0e0;
        }
        th {
            background-color: #34495e;
            color: white;
        }
        tr:hover {
            background-color: #ecf0f1;
        }
        .no-data {
            text-align: center;
            padding: 30px;
            font-size: 18px;
            color: #888;
        }
    </style>
</head>
<body>
    <

<div class="sidebar">
    <h3>Budget Buddy</h3>
    <ul>
        <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
        <li><a href="list.php" class="<?= basename($_SERVER['PHP_SELF']) == 'list.php' ? 'active' : '' ?>">Expense List</a></li>
        <li><a href="addexpense.php" class="<?= basename($_SERVER['PHP_SELF']) == 'addexpense.php' ? 'active' : '' ?>">Add Expense</a></li>
        <li><a href="budgethead.php" class="<?= basename($_SERVER['PHP_SELF']) == 'budgethead.php' ? 'active' : '' ?>">Budget Head</a></li>
        <li><a href="reports.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">Reports</a></li>
        <li><a href="saving.php" class="active">Savings</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <h2>Savings Summary</h2>

    <?php if (count($savings) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Year</th>
                    <th>Month</th>
                    <th>Budget Name</th>
                    <th>Budget Head</th>
                    <th>Allocated</th>
                    <th>Saved</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($savings as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['saved_year']) ?></td>
                        <td><?= DateTime::createFromFormat('!m', $row['saved_month'])->format('F') ?></td>
                        <td><?= htmlspecialchars($row['budget_name']) ?></td>
                        <td><?= htmlspecialchars($row['budget_head']) ?></td>
                        <td><?= number_format($row['allocated_amount'], 2) ?></td>
                        <td><?= number_format($row['saved_amount'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-data">No savings recorded yet.</p>
    <?php endif; ?>
</div>
   <?php include 'footer.php';?>
</body>
</html>

<?php
session_start();
include 'db.php';

if (!isset($_SESSION['u_id'])) {
    header("Location: login.php");
    exit();
}

$u_id = $_SESSION['u_id'];

// 🛠 Use filtered values if present, otherwise fallback to current system date
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Convert numeric month to full month name (e.g., "06" → "June")
$month_name = DateTime::createFromFormat('!m', $month)->format('F');
$year_value = $year;

// Fetch month_id from budget_month table
$monthStmt = $conn->prepare("SELECT month_id FROM budget_month WHERE month_name = ?");
$monthStmt->bind_param("s", $month_name);
$monthStmt->execute();
$monthResult = $monthStmt->get_result();
$month_id = ($monthResult->num_rows > 0) ? $monthResult->fetch_assoc()['month_id'] : 0;
$monthStmt->close();

// Fetch year_id from budget_year table
$yearStmt = $conn->prepare("SELECT year_id FROM budget_year WHERE year_name = ?");
$yearStmt->bind_param("s", $year_value);
$yearStmt->execute();
$yearResult = $yearStmt->get_result();
$year_id = ($yearResult->num_rows > 0) ? $yearResult->fetch_assoc()['year_id'] : 0;
$yearStmt->close();

// Fetch filtered expense data
$query = "
    SELECT 
        e.budget_id,
        e.bhid,
        b.bname,
        bh.title,
        bha.allocated_amount,
        SUM(e.amount) AS spent_amount,
        GROUP_CONCAT(e.description SEPARATOR '; ') AS descriptions,
        MAX(e.expense_date) AS last_expense_date
    FROM expenses e
    JOIN budget b ON e.budget_id = b.budget_id
    JOIN budget_head bh ON e.bhid = bh.bhid
    JOIN budget_head_amount bha ON bha.budget_id = e.budget_id AND bha.bhid = e.bhid
    WHERE b.u_id = ? 
    AND b.month_id = ? 
    AND b.year_id = ?
    GROUP BY e.budget_id, e.bhid
    ORDER BY last_expense_date DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $u_id, $month_id, $year_id);
$stmt->execute();
$result = $stmt->get_result();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Expense List</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        table {
            border-collapse: collapse;
            width: 90%;
            margin: 20px auto;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: left;
        }
        th {
            background: rgb(23, 54, 91);
            color: white;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .btn-view {
            background-color: #2e4a72;
            color: white;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
        }
        .btn-view:hover {
            background-color: #1f3a5d;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h3>Budget Buddy</h3>
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

<!-- Main Content -->
<div class="main-content">
    <div class="header">
        <h2>Expense List</h2>
    </div>

    <?php include 'filter.php'; ?>

    <table>
        <thead>
            <tr>
                <th>Budget Name</th>
                <th>Budget Head</th>
                <th>Allocated Amount (NRS)</th>
                <th>Spent Amount (NRS)</th>
                <th>Remaining (NRS)</th>
                <th>Last Expense Date</th>
                <th>Descriptions</th>
                <th>Actions</th> <!-- Added column -->
            </tr>
        </thead>
        <tbody>
        <?php
        if ($result->num_rows == 0) {
            echo "<tr><td colspan='8' style='text-align:center;'>No expenses found.</td></tr>";
        } else {
            $total_allocated = 0;
            $total_spent = 0;

            while ($row = $result->fetch_assoc()) {
                $remaining = $row['allocated_amount'] - $row['spent_amount'];
                $remaining_color = $remaining < 0 ? 'red' : 'green';

                $budget_id = $row['budget_id'];
                $bhid = $row['bhid'];

                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['bname']) . "</td>";
                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                echo "<td>" . number_format($row['allocated_amount'], 2) . "</td>";
                echo "<td>" . number_format($row['spent_amount'], 2) . "</td>";
                echo "<td style='color: $remaining_color; font-weight: bold;'>" . number_format($remaining, 2) . "</td>";
                echo "<td>" . htmlspecialchars($row['last_expense_date']) . "</td>";
                echo "<td>" . htmlspecialchars($row['descriptions']) . "</td>";
                echo "<td><a href='viewexpense.php?budget_id=$budget_id&bhid=$bhid&month=$month&year=$year' class='btn-view'>View</a></td>";

                echo "</tr>";

                $total_allocated += $row['allocated_amount'];
                $total_spent += $row['spent_amount'];
            }

            $total_remaining = $total_allocated - $total_spent;
            $color = $total_remaining < 0 ? 'red' : 'green';

            echo "<tr style='font-weight: bold; background-color: #f8f8f8;'>";
            echo "<td colspan='4' style='text-align:right;'>Total Savings:</td>";
            echo "<td style='color:$color;'>" . number_format($total_remaining, 2) . "</td>";
            echo "<td colspan='3'></td>";
            echo "</tr>";
        }

        $stmt->close();
        ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>

</body>
</html>

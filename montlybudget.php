<?php
session_start();
include 'db.php';

if (!isset($_SESSION['u_id'])) {
    header("Location: login.php");
    exit();
}

$u_id = $_SESSION['u_id'];
$lastMonth = date('n', strtotime('first day of last month'));
$currentYear = date('Y', strtotime('first day of last month'));

$budgetQuery = "SELECT budget_id FROM budget WHERE u_id = ? AND month_id = ? AND year_id = ? ORDER BY budget_id DESC LIMIT 1";

$stmt = $conn->prepare($budgetQuery);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("iii", $u_id, $lastMonth, $currentYear);
$stmt->execute();
$result = $stmt->get_result();
$budgetData = $result->fetch_assoc();
$budgetId = $budgetData ? $budgetData['budget_id'] : null;
$stmt->close();



?>

<!DOCTYPE html>
<html>
<head>
    <title>Last Month's Savings</title>
    <style>
        table {
            width: 90%;
            border-collapse: collapse;
            margin: 20px auto;
            background: #fff;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 12px;
            text-align: center;
        }
        th {
            background-color: #1f6fb2;
            color: white;
        }
    </style>
</head>
<body>
<?php if ($budgetId): ?>
    <h2 style="text-align:center;">Savings Report - <?= date('F Y', strtotime('first day of last month')) ?></h2>
    <table>
        <tr>
            <th>Budget Head</th>
            <th>Allocated Amount</th>
            <th>Spent Amount</th>
            <th>Saved Amount</th>
        </tr>
        <?php
        $query = "
            SELECT 
                bh.title,
                bha.allocated_amount,
                IFNULL(SUM(e.amount), 0) AS spent
            FROM budget_head_amount bha
            JOIN budget_head bh ON bha.bhid = bh.bhid
            LEFT JOIN expenses e ON e.budget_id = bha.budget_id AND e.bhid = bha.bhid
            WHERE bha.budget_id = ?
            GROUP BY bha.bhid
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $budgetId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $saved = $row['allocated_amount'] - $row['spent'];
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>NRS " . number_format($row['allocated_amount'], 2) . "</td>";
            echo "<td>NRS " . number_format($row['spent'], 2) . "</td>";
            echo "<td style='color: green;'>NRS " . number_format($saved, 2) . "</td>";
            echo "</tr>";
        }

        $stmt->close();
        ?>
    </table>
<?php else: ?>
    <p style="text-align:center;">No budget found for last month.</p>
<?php endif; ?>
</body>
</html>

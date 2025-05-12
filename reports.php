<?php
session_start();
include 'db.php';

if (!isset($_SESSION['u_id'])) {
    header("Location: login.php");
    exit();
}

$u_id = $_SESSION['u_id'];

$currentMonthName = date("F");
$currentYearValue = date("Y");
$currentMonth = (int)date("m");
$currentYear = (int)date("Y");

$monthStmt = $conn->prepare("SELECT month_id FROM budget_month WHERE month_name = ?");
$monthStmt->bind_param("s", $currentMonthName);
$monthStmt->execute();
$monthResult = $monthStmt->get_result();
$currentMonthId = ($monthResult->num_rows > 0) ? $monthResult->fetch_assoc()['month_id'] : 0;
$monthStmt->close();

$yearStmt = $conn->prepare("SELECT year_id FROM budget_year WHERE year_name = ?");
$yearStmt->bind_param("s", $currentYearValue);
$yearStmt->execute();
$yearResult = $yearStmt->get_result();
$currentYearId = ($yearResult->num_rows > 0) ? $yearResult->fetch_assoc()['year_id'] : 0;
$yearStmt->close();

$sql = "
    SELECT 
        bh.title AS budget_head,
        COALESCE(SUM(bha.allocated_amount), 0) AS total_allocated,
        COALESCE((
            SELECT SUM(e.amount) 
            FROM expenses e 
            WHERE 
                e.bhid = bh.bhid 
                AND e.budget_id IN (
                    SELECT b.budget_id 
                    FROM budget b 
                    WHERE b.u_id = ? 
                      AND b.month_id = ? 
                      AND b.year_id = ?
                )
                AND MONTH(e.expense_date) = ? 
                AND YEAR(e.expense_date) = ?
        ), 0) AS total_expenses
    FROM 
        budget_head bh
    LEFT JOIN 
        budget_head_amount bha ON bha.bhid = bh.bhid
    LEFT JOIN 
        budget b ON bha.budget_id = b.budget_id
    WHERE 
        b.u_id = ?
        AND b.month_id = ?
        AND b.year_id = ?
    GROUP BY 
        bh.bhid, bh.title
    ORDER BY 
        bh.title
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiiiiii", $u_id, $currentMonthId, $currentYearId, $currentMonth, $currentYear, $u_id, $currentMonthId, $currentYearId);
$stmt->execute();
$result = $stmt->get_result();

$budget_heads = [];
$allocated_amounts = [];
$expenses = [];
$percentage_spent = [];

while ($row = $result->fetch_assoc()) {
    $budget_heads[] = $row['budget_head'];
    $alloc = (float)$row['total_allocated'];
    $spend = (float)$row['total_expenses'];
    $allocated_amounts[] = $alloc;
    $expenses[] = $spend;
    $percentage_spent[] = $alloc > 0 ? round(($spend / $alloc) * 100, 2) : 0;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Expense Report - <?= date("F Y") ?></title>
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; }
        .chart-container { padding: 40px; max-width: 1000px; margin: 0 auto; }
        .chart-box { padding: 30px; border-radius: 10px; }
        h2 { text-align: center; color: #2c3e50; margin-bottom: 30px; }
        canvas { width: 100% !important; height: 400px !important; }
        .export-btn { display: block; margin: 20px auto; padding: 10px 20px; background-color: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>

<div class="sidebar">
    <h3>Budget Buddy</h3>
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="list.php">Expense List</a></li>
        <li><a href="addexpense.php">Add Expense</a></li>
        <li><a href="budgethead.php">Budget Head</a></li>
        <li><a href="reports.php" class="active">Reports</a></li>
        <li><a href="saving.php">Savings</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div>

<div class="chart-container">
    <div class="chart-box">
        <h2>Allocated vs Spent Amount - <?= date("F Y") ?></h2>
        <canvas id="amountBarChart"></canvas>
    </div>

    <div class="chart-box">
        <h2>Spent % per Budget Head</h2>
        <canvas id="percentLineChart"></canvas>
    </div>

    <div class="chart-box">
        <h2>Allocated Amount per Budget Head</h2>
        <canvas id="allocatedLineChart"></canvas>
    </div>

    <button class="export-btn" onclick="exportToExcel()">Download Report as Excel</button>
</div>

<script>
const budgetHeads = <?= json_encode($budget_heads) ?>;
const allocatedAmounts = <?= json_encode($allocated_amounts) ?>;
const spentAmounts = <?= json_encode($expenses) ?>;
const percentSpent = <?= json_encode($percentage_spent) ?>;

// Bar Chart: Allocated vs Spent
new Chart(document.getElementById('amountBarChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: budgetHeads,
        datasets: [
            {
                label: 'Allocated Amount (NRS)',
                data: allocatedAmounts,
                backgroundColor: '#2980b9'
            },
            {
                label: 'Spent Amount (NRS)',
                data: spentAmounts,
                backgroundColor: '#e67e22'
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                title: { display: true, text: 'Amount (NRS)' }
            }
        }
    }
});

// Line Chart: Spent %
new Chart(document.getElementById('percentLineChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: budgetHeads,
        datasets: [{
            label: 'Spent %',
            data: percentSpent,
            borderColor: '#f39c12',
            backgroundColor: 'rgba(243, 156, 18, 0.2)',
            fill: true,
            tension: 0.3,
            pointRadius: 5,
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                max: 120,
                title: { display: true, text: 'Spent (%)' }
            }
        }
    }
});

// Line Chart: Allocated Amount
new Chart(document.getElementById('allocatedLineChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: budgetHeads,
        datasets: [{
            label: 'Allocated Amount (NRS)',
            data: allocatedAmounts,
            borderColor: '#2c3e50',
            backgroundColor: 'rgba(44, 62, 80, 0.2)',
            fill: true,
            tension: 0.3,
            pointRadius: 5,
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                title: { display: true, text: 'Allocated Amount (NRS)' }
            }
        }
    }
});

// Excel Export
function exportToExcel() {
    const data = [["Budget Head", "Allocated (NRS)", "Spent (NRS)", "Spent (%)"]];
    for (let i = 0; i < budgetHeads.length; i++) {
        data.push([
            budgetHeads[i],
            allocatedAmounts[i],
            spentAmounts[i],
            percentSpent[i] + "%"
        ]);
    }
    const worksheet = XLSX.utils.aoa_to_sheet(data);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Report");
    XLSX.writeFile(workbook, "budget_report_<?= date("F_Y") ?>.xlsx");
}
</script>

<?php include 'footer.php'; ?>
</body>
</html>

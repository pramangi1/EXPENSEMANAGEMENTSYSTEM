<?php
session_start();
include 'db.php';

if (!isset($_SESSION['u_id'])) {
    header("Location: login.php");
    exit();
}

$u_id = $_SESSION['u_id'];

$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date("F");
$selectedYear = isset($_GET['year']) ? $_GET['year'] : date("Y");
$currentMonth = date("m", strtotime($selectedMonth));
$currentYear = (int)$selectedYear;

$monthStmt = $conn->prepare("SELECT month_id FROM budget_month WHERE month_name = ?");
$monthStmt->bind_param("s", $selectedMonth);
$monthStmt->execute();
$monthResult = $monthStmt->get_result();
$currentMonthId = ($monthResult->num_rows > 0) ? $monthResult->fetch_assoc()['month_id'] : 0;
$monthStmt->close();

$yearStmt = $conn->prepare("SELECT year_id FROM budget_year WHERE year_name = ?");
$yearStmt->bind_param("s", $selectedYear);
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
$allocatedAmounts = [];
$spentAmounts = [];
$allocation_percentages = [];
$expense_percentages = [];

while ($row = $result->fetch_assoc()) {
    $title = $row['budget_head'];
    $allocated = (float)$row['total_allocated'];
    $spent = (float)$row['total_expenses'];
    $total = $allocated + $spent;

    $budget_heads[] = $title;
    $allocatedAmounts[] = $allocated;
    $spentAmounts[] = $spent;
    $allocation_percentages[] = $total ? round(($allocated / $total) * 100, 2) : 0;
    $expense_percentages[] = $total ? round(($spent / $total) * 100, 2) : 0;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Expense Report - <?= $selectedMonth . ' ' . $selectedYear ?> </title>
    <link  rel="stylesheet" href="css/hello.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        .chart-box { width: 90%; max-width: 800px; margin: 40px auto; }
        .top-controls { display: flex; justify-content: center; align-items: center; gap: 15px; margin-top: 20px; flex-wrap: wrap; }
        .top-controls select, .top-controls button { padding: 6px 12px; font-size: 16px; }
        .export-btn { padding: 6px 12px; background-color: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; }
        #barChartBox, #lineChartBox { display: none; }
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
<form method="GET" class="top-controls">
    <label>Month:
        <select name="month">
            <?php foreach (range(1, 12) as $m) {
                $month = date("F", mktime(0, 0, 0, $m, 10));
                $selected = ($month == $selectedMonth) ? "selected" : "";
                echo "<option value='$month' $selected>$month</option>";
            } ?>
        </select>
    </label>
    <label>Year:
        <select name="year">
            <?php for ($y = date("Y") - 5; $y <= date("Y") + 1; $y++) {
                $selected = ($y == $selectedYear) ? "selected" : "";
                echo "<option value='$y' $selected>$y</option>";
            } ?>
        </select>
    </label>
    <button type="submit">filter</button>
    <button class="export-btn" type="button" onclick="exportToExcel()">Export to Excel</button>
</form>

<div class="top-controls">
    <label>View:
        <select id="chartViewToggle" onchange="toggleCharts()">
            <option value="bar">Bar Chart</option>
            <option value="line">Line Chart</option>
        </select>
    </label>
</div>

<div class="chart-box" id="barChartBox">
    <h2>Allocated vs Spent Amount - <?= $selectedMonth . ' ' . $selectedYear ?></h2>
    <canvas id="barChart"></canvas>
</div>

<div class="chart-box" id="lineChartBox">
    <h2>Per Budget Head: Allocated vs Spent (%) - <?= $selectedMonth . ' ' . $selectedYear ?></h2>
    <canvas id="lineChart"></canvas>
</div>

<script>
const labels = <?= json_encode($budget_heads) ?>;
const allocData = <?= json_encode($allocatedAmounts) ?>;
const spentData = <?= json_encode($spentAmounts) ?>;
const allocPercent = <?= json_encode($allocation_percentages) ?>;
const spentPercent = <?= json_encode($expense_percentages) ?>;

let barChart = new Chart(document.getElementById("barChart"), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            { label: 'Allocated (NRS)', data: allocData, backgroundColor: '#2980b9' },
            { label: 'Spent (NRS)', data: spentData, backgroundColor: '#e67e22' }
        ]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true, title: { display: true, text: 'Amount (NRS)' }}}}
});

let lineChart = new Chart(document.getElementById("lineChart"), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            { label: "Allocated %", data: allocPercent, borderColor: '#2ecc71', backgroundColor: 'rgba(46,204,113,0.2)', fill: true, tension: 0.3 },
            { label: "Spent %", data: spentPercent, borderColor: '#e74c3c', backgroundColor: 'rgba(231,76,60,0.2)', fill: true, tension: 0.3 }
        ]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true, max: 100, title: { display: true, text: 'Percentage (%)' }}}}
});

function toggleCharts() {
    const view = document.getElementById('chartViewToggle').value;
    document.getElementById('barChartBox').style.display = view === 'bar' ? 'block' : 'none';
    document.getElementById('lineChartBox').style.display = view === 'line' ? 'block' : 'none';
}

function exportToExcel() {
    const data = [["Budget Head", "Allocated (NRS)", "Spent (NRS)", "Allocated (%)", "Spent (%)"]];
    for (let i = 0; i < labels.length; i++) {
        data.push([labels[i], allocData[i], spentData[i], allocPercent[i] + "%", spentPercent[i] + "%"]);
    }
    const worksheet = XLSX.utils.aoa_to_sheet(data);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Report");
    XLSX.writeFile(workbook, "budget_report_<?= $selectedMonth . '_' . $selectedYear ?>.xlsx");
}

window.onload = toggleCharts;
</script>

</body>
</html>

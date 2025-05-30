<?php
session_start();
include 'db.php';

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

if (!isset($_SESSION['u_id'])) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
        exit;
    } else {
        header("Location: login.php");
        exit;
    }
}

$u_id = $_SESSION['u_id'];
$u_name = $_SESSION['u_name'];
include 'auto.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['amount'])) {
    if (!$is_ajax) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid request type.']);
        exit;
    }

    header('Content-Type: application/json');

    $amount = floatval($_POST['amount']);
    $bname = trim($_POST['bname']);
    $month_id = intval($_POST['month']);
    $year_id = intval($_POST['year']);

    if (empty($bname) || $amount <= 0 || $month_id <= 0 || $year_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill all fields correctly.']);
        exit;
    }

    // ✅ Fix: Check if budget already exists
    $checkQuery = "SELECT budget_id, total_amount FROM budget WHERE u_id = ? AND month_id = ? AND year_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("iii", $u_id, $month_id, $year_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        // ✅ Budget exists — add to total_amount
        $budget = $checkResult->fetch_assoc();
        $budget_id = $budget['budget_id'];
        $updated_amount = $budget['total_amount'] + $amount;

        $updateQuery = "UPDATE budget SET total_amount = ? WHERE budget_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("di", $updated_amount, $budget_id);

        if ($updateStmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Amount added to existing budget.'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update budget: ' . $updateStmt->error
            ]);
        }

        $updateStmt->close();
    } else {
        // ✅ New budget
        $insertQuery = "INSERT INTO budget (u_id, bname, total_amount, month_id, year_id) VALUES (?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("isdii", $u_id, $bname, $amount, $month_id, $year_id);

        if ($insertStmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'New budget created successfully.'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to insert new budget: ' . $insertStmt->error
            ]);
        }

        $insertStmt->close();
    }

    $checkStmt->close();
    exit();
}



$current_month_name = date("F");
$current_year_value = date("Y");
$calendar_month = (int)date("m");
$calendar_year = (int)date("Y");

$monthStmt = $conn->prepare("SELECT month_id FROM budget_month WHERE month_name = ?");
$monthStmt->bind_param("s", $current_month_name);
$monthStmt->execute();
$monthResult = $monthStmt->get_result();
$current_month_id = ($monthResult->num_rows > 0) ? $monthResult->fetch_assoc()['month_id'] : 0;
$monthStmt->close();

$yearStmt = $conn->prepare("SELECT year_id FROM budget_year WHERE year_name = ?");
$yearStmt->bind_param("s", $current_year_value);
$yearStmt->execute();
$yearResult = $yearStmt->get_result();
$current_year_id = ($yearResult->num_rows > 0) ? $yearResult->fetch_assoc()['year_id'] : 0;
$yearStmt->close();

$budgetQuery = "SELECT SUM(total_amount) AS total_budget FROM budget WHERE u_id = ? AND month_id = ? AND year_id = ?";
$stmtBudget = $conn->prepare($budgetQuery);
$stmtBudget->bind_param('iii', $u_id, $current_month_id, $current_year_id);
$stmtBudget->execute();
$budgetResult = $stmtBudget->get_result();
$total_budget = ($budgetResult->num_rows > 0) ? $budgetResult->fetch_assoc()['total_budget'] : 0;
$stmtBudget->close();


$expenseQuery = "
    SELECT SUM(e.amount) AS total_expenses 
    FROM expenses e
    JOIN budget b ON e.budget_id = b.budget_id
    WHERE b.u_id = ? AND MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?";
$stmtExpense = $conn->prepare($expenseQuery);
$stmtExpense->bind_param("iii", $u_id, $calendar_month, $calendar_year);
$stmtExpense->execute();
$expenseResult = $stmtExpense->get_result();
$total_expenses = ($expenseResult->num_rows > 0) ? $expenseResult->fetch_assoc()['total_expenses'] : 0;
$stmtExpense->close();

$remaining_budget = max(0, $total_budget - $total_expenses);

$query = "
    SELECT bh.title, SUM(e.amount) AS total_expenses
    FROM expenses e
    JOIN budget_head bh ON e.bhid = bh.bhid
    JOIN budget b ON e.budget_id = b.budget_id
    WHERE b.u_id = ? AND MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?
    GROUP BY bh.title";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $u_id, $calendar_month, $calendar_year);
$stmt->execute();
$categories = [];
$expenses = [];
$stmt->bind_result($category, $amount);
while ($stmt->fetch()) {
    if (!empty($category) && $amount !== null) {
        $categories[] = $category;
        $expenses[] = $amount;
    }
}
$stmt->close();

$getLatestBudgetQuery = "SELECT budget_id FROM budget WHERE u_id = ? AND month_id = ? AND year_id = ? ORDER BY budget_id DESC LIMIT 1";
$getLatestBudgetStmt = $conn->prepare($getLatestBudgetQuery);
$getLatestBudgetStmt->bind_param("iii", $u_id, $current_month_id, $current_year_id);
$getLatestBudgetStmt->execute();
$getLatestBudgetResult = $getLatestBudgetStmt->get_result();
$budget_id = ($getLatestBudgetResult->num_rows > 0) ? $getLatestBudgetResult->fetch_assoc()['budget_id'] : 0;
$getLatestBudgetStmt->close();

$query = "SELECT bh.title, bha.allocated_amount, 
                 (SELECT SUM(amount) FROM expenses e WHERE e.bhid = bha.bhid AND e.budget_id = bha.budget_id) AS spent_amount 
          FROM budget_head bh 
          JOIN budget_head_amount bha ON bh.bhid = bha.bhid
          WHERE bha.budget_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $budget_id);
$stmt->execute();
$budgetData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$percentages = [];
foreach ($budgetData as $row) {
    $allocated = floatval($row['allocated_amount']);
    $spent = floatval($row['spent_amount']);
    $percent = ($allocated > 0) ? round(($spent / $allocated) * 100, 1) : 0;

    $percentages[] = [
        'title' => $row['title'],
        'percent' => $percent
    ];
}

$threshold = $total_budget * 0.8;
$show_alert = ($total_budget > 0 && $total_expenses >= $threshold && $total_expenses > 0);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Expense Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- Fonts and CSS -->
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
 <link rel="stylesheet" href="css/hello.css"> 
<!-- <link rel="stylesheet" href="css/piechart.css"> -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<!-- Chart.js and Plugins -->
 
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>

<!-- jQuery and SweetAlert -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Data for Chart (Must come BEFORE piechart.js) -->
<script>
    const chartData = {
        labels: <?php echo json_encode($categories); ?>,
        data: <?php echo json_encode($expenses); ?>,
        userName: <?php echo json_encode($u_name); ?>
    };

document.addEventListener("DOMContentLoaded", function () {
    Chart.register(ChartDataLabels);

    const ctx = document.getElementById("expensesPieChart");

    const filtered = chartData.labels.map((label, i) => ({
        label,
        value: parseFloat(chartData.data[i]) || 0
    })).filter(item => item.value > 0);

    const filteredLabels = filtered.map(item => item.label);
    const filteredData = filtered.map(item => item.value);

    if (filtered.length > 0) {
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: filteredLabels,
                datasets: [{
                    data: filteredData,
                    backgroundColor: [
                        '#f87171', '#4ade80', '#a7f3d0', '#fde68a', '#fdba74', '#93c5fd'
                    ],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            boxWidth: 20,
                            padding: 15
                        }
                    },
                    tooltip: {
                        backgroundColor: '#fff',
                        borderColor: '#e5e7eb',
                        borderWidth: 1,
                        bodyColor: '#111827',
                        titleColor: '#111827',
                        callbacks: {
                            label: function (tooltipItem) {
                                const value = parseFloat(tooltipItem.raw) || 0;
                                const label = tooltipItem.label || 'Unknown';
                                const userName = chartData.userName || 'You';
                                return `${label}: NRS ${value.toFixed(2)} (by ${userName})`;
                            }
                        }
                    },
                    datalabels: {
                        display: false // ❌ hide values inside the chart
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    } else {
        document.getElementById("customLegend").innerHTML = '<em>No data available</em>';
    }
});

$(function () {
    // Unbind first to avoid duplicate binding
    $('#budget-form').off('submit').on('submit', function (e) {
        e.preventDefault();

        var formData = $(this).serialize();

        $.ajax({
            type: 'POST',
            url: 'dashboard.php',
            data: formData,
            dataType: 'json',
            success: function (data) {
                if (data.status === "success") {
                    Swal.fire({
                        icon: "success",
                        title: "Success",
                        text: data.message,
                        confirmButtonText: "OK"
                    }).then(() => {
                        closeBudgetForm();

                        // Update budget display here if needed
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: data.message
                    });
                }
            },
            error: function () {
                Swal.fire({
                    icon: "error",
                    title: "Failed",
                    text: "Something went wrong while saving budget."
                });
            }
        });
    });
});

function toggleNotifications() {
    const dropdown = document.getElementById('notifDropdown');
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

</script>

    

<!-- Your JS Files (defer ensures they run after DOM is ready) -->
<!-- <script src="js/piechart.js" defer></script> -->
 <script src="js/script.js" defer></script> 

<!-- Optional (if needed later) -->
<!-- <script src="js/dashboard.js" defer></script> -->
<style>
   
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 1px solid #e0e0e0;
}

.header h2 {
  color: #2c3e50;
  font-size: 1.8rem;
  position: fixed;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 15px;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background-color: #3498db;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 1.2rem;
}

.user-name {
  font-weight: 500;
}

.header button {
  background: #3498db;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 500;
  transition: background 0.3s;
}

.header button:hover {
  background: #2980b9;
}

.dashboard-section {
  display: flex;
  flex-wrap: wrap;
  gap: 30px;
  padding: 20px;
  justify-content: center;
  align-items: flex-start;
}

.dashboard-card {
   background:rgb(190, 186, 186);
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  flex: 1 1 500px;
  max-width: 600px;
  min-width: 300px;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  text-align: center;
  min-height: 400px; /* ✅ Ensure enough height for canvas */
}



.dashboard-card h2 {
  font-size: 1.4rem;
  margin-bottom: 20px;
  color: #2c3e50;
  font-weight: 600;
  border-bottom: 1px solid #eee;
  padding-bottom: 10px;
  width: 100%;
  text-align: center;
}
canvas#expensesPieChart {
  max-width: 100%;
  width: 100% !important;
  height: 350px !important; /* ✅ Explicit height */
  display: block;
}

.summary-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}

.summary-table th,
.summary-table td {
  padding: 12px;
  border: 1px solid #ddd;
  text-align: center;
  font-size: 0.95rem;
}

.summary-table thead th {
  background-color: rgb(39, 73, 141);
  color: white;
  font-weight: bold;
}

 @media (max-width: 768px) {
  .dashboard-section {
    flex-direction: column;
    align-items: center;
  }

  .dashboard-card {
    max-width: 100%;
  }
} 

/* ===== Cards Styles ===== */
.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.card {
  background: white;
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s;
}

.card:hover {
  transform: translateY(-5px);
}

.card h5 {
  font-size: 1rem;
  color: #7f8c8d;
  margin-bottom: 10px;
}

.card p {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2c3e50;
}

.bg-primary {
  border-left: 5px solid #3498db;
}

.bg-danger {
  border-left: 5px solid #e74c3c;
}

.bg-success {
  border-left: 5px solid #2ecc71;
} 

/* ===== Modal Styles ===== */
.modal {
  display: none;
  position: fixed;
  z-index: 1001;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  transition: all 0.3s;
}

.modal-content {
  background-color: #fefefe;
  margin: 10% auto;
  padding: 30px;
  border-radius: 10px;
  width: 90%;
  max-width: 500px;
  position: relative;
}

.close {
  color: #aaa;
  position: absolute;
  top: 15px;
  right: 25px;
  font-size: 28px;
  font-weight: bold;
  cursor: pointer;
}

.close:hover {
  color: #333;
}

.modal h3 {
  margin-bottom: 20px;
  color: #2c3e50;
}

#budget-form input,
#budget-form select {
  width: 100%;
  padding: 12px 15px;
  margin-bottom: 15px;
  border: 1px solid #ddd;
  border-radius: 5px;
  font-size: 1rem;
}

#budget-form button {
  width: 100%;
  padding: 12px;
  background: #3498db;
  color: white;
  border: none;
  border-radius: 5px;
  font-size: 1rem;
  cursor: pointer;
  transition: background 0.3s;
}

#budget-form button:hover {
  background: #2980b9;
}

/* ===== Responsive Styles ===== */
@media (max-width: 768px) {
  .sidebar {
      width: 0;
      overflow: hidden;
  }

  .sidebar.active {
      width: 250px;
  }

  .main-content {
      margin-left: 0;
      width: 100%;
  }

  .cards {
      grid-template-columns: 1fr;
  }
}  
    .notification {
    position: relative;
    display: inline-block;
    margin-right: 30px;
}

.notification .icon {
    cursor: pointer;
    position: relative;
}

.notification .icon .fa-bell {
    font-size: 22px;
    color: #444;
}

.notification .badge {
    background: red;
    color: white;
    border-radius: 50%;
    padding: 3px 7px;
    font-size: 12px;
    position: absolute;
    top: -5px;
    right: -10px;
}

.notification .dropdown {
    display: none;
    position: absolute;
    right: 0;
    top: 30px;
    background: white;
    border: 1px solid #ccc;
    width: 300px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    border-radius: 6px;
    z-index: 1000;
}

.notification .dropdown h4 {
    padding: 10px;
    margin: 0;
    font-size: 16px;
    border-bottom: 1px solid #eee;
}

.notification .dropdown ul {
    list-style: none;
    margin: 0;
    padding: 10px;
    max-height: 200px;
    overflow-y: auto;
}

.notification .dropdown ul li {
    padding: 6px 10px;
    font-size: 14px;
}

.notification .view-all {
    display: block;
    text-align: center;
    padding: 10px;
    border-top: 1px solid #eee;
    background: #f9f9f9;
    text-decoration: none;
    color: #2980b9;
}
.spent-high {
    color: #dc3545; /* Red */
    font-weight: bold;
}
.spent-warning {
    color: #fd7e14; /* Orange */
    font-weight: bold;
}
.spent-ok {
    color: #28a745; /* Green */
}


.threshold-bar-container {
    width: 300px;
    margin: 20px 0 20px 40px; /* Pushes it left */
    padding: 10px;
    text-align: left;
}

.threshold-labels {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    margin-bottom: 4px;
}

.threshold-bar {
    width: 100%;
    height: 14px;
    background-color: #ccc;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
}

.threshold-fill {
    height: 100%;
    background: linear-gradient(to right, #4caf50 0%, #fbc02d 70%, #f44336 100%);
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 11px;
    font-weight: bold;
}

.threshold-percentage {
    position: absolute;
    width: 100%;
    text-align: center;
}
.dashboard-section {
  display: flex;
  flex-wrap: wrap;
  gap: 30px;
  padding: 20px;
  justify-content: space-between;
}

.dashboard-card {
  background: #ffffff;
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  flex: 1 1 45%;
  min-width: 300px;
  max-width: 48%;
}

.dashboard-card h2 {
  font-size: 1.3rem;
  margin-bottom: 15px;
  color: #2c3e50;
}

.summary-table {
  width: 100%;
  border-collapse: collapse;
}

.summary-table th,
.summary-table td {
  padding: 10px;
  border: 1px solid #ccc;
  text-align: center;
}

.summary-table thead th {
  background-color: rgb(39, 73, 141);
  color: white;
  font-weight: bold;
}



</style>
</head>

<body>

<div class="sidebar">
<?php include 'header.php'; ?>
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
    <div class="header">
        <!-- <h2> Dashboard</h2> -->
        <div class="user-info">
            <div class="user-avatar">
                <span><?php echo strtoupper(substr($u_name, 0, 1)); ?></span>
            </div>
            <div class="user-name"><?php echo $u_name; ?></div>
        </div>
       <div class="notification">
    <div class="icon" onclick="toggleNotifications()">
        <i class="fa fa-bell"></i>
        <span class="badge"><?= count($percentages) ?></span>
    </div>

    <div class="dropdown" id="notifDropdown">
        <h4><?= count($percentages) ?> Budget Allocations</h4>
        <ul>
            <?php foreach ($percentages as $item): 
                $percent = $item['percent'];
                $title = htmlspecialchars($item['title']);

                // Assign a class based on percent spent
                if ($percent >= 100) {
                    $class = "spent-high";     // Red
                } elseif ($percent >= 80) {
                    $class = "spent-warning";  // Orange
                } else {
                    $class = "spent-ok";       // Green
                }
            ?>
            <li class="<?= $class ?>">
                You have spent <strong><?= $percent ?>%</strong> of your allocation on <strong><?= $title ?></strong>.
            </li>
            <?php endforeach; ?>
        </ul>
        <a href="budgethead.php" class="view-all">View Budget Heads</a>
    </div>
</div>

        <button onclick="openBudgetForm()">Set Budget</button>
    </div>
    <div class="threshold-bar-container">
    <div class="threshold-labels">
        <span>Spent: NRS <?= number_format($total_expenses, 2) ?></span>
        <span>Budget: NRS <?= number_format($total_budget, 2) ?></span>
    </div>
    <div class="threshold-bar">
        <div class="threshold-fill" style="width: <?= ($total_budget > 0) ? min(100, ($total_expenses / $total_budget) * 100) : 0 ?>%;">
            <span class="threshold-percentage">
                <?= ($total_budget > 0) ? round(($total_expenses / $total_budget) * 100) : 0 ?>%
            </span>
        </div>
    </div>
</div>


    <div class="cards">
        <div class="card bg-primary">
            <h5>Total Budget</h5>
            <p>NRS <?php echo number_format($total_budget, 2); ?></p>
        </div>
        <div class="card bg-danger">
            <h5>Total Expenses</h5>
            <p>NRS <?php echo number_format($total_expenses, 2); ?></p>
        </div>
        <div class="card bg-success">
            <h5>Remaining Budget</h5>
            <p>NRS <?php echo number_format($remaining_budget, 2); ?></p>
        </div>
    </div>
    <div class="dashboard-section">
  <!-- Pie Chart Section -->
  <div class="dashboard-card">
    <h2>Expenses by Budget Head</h2>
    <canvas id="expensesPieChart"></canvas>
  </div>

  <!-- Table Section -->
  <div class="dashboard-card">
    <h2>Allocated vs Remaining</h2>
    <table class="summary-table">
      <thead>
        <tr>
          <th>Budget Head</th>
          <th>Allocated Amount</th>
          <th>Spent</th>
          <th>Remaining</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($budgetData as $row): 
          $remainingAmount = $row['allocated_amount'] - $row['spent_amount'];
        ?>
        <tr>
          <td><?= htmlspecialchars($row['title']) ?></td>
          <td>NRS<?= number_format($row['allocated_amount'], 2) ?></td>
          <td>NRS<?= number_format($row['spent_amount'], 2) ?></td>
          <td>NRS<?= number_format($remainingAmount, 2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>



    <div id="budget-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeBudgetForm()">&times;</span>
            <h3>Set Budget</h3>
            <form id="budget-form" method="POST" >
                <?php
                    $current_month = date("F");
                    $current_year = date("Y");
                    $default_bname = "Budget $current_month $current_year";
                ?>
                <input type="text" name="bname" value="<?php echo $default_bname; ?>" required>
                <select name="year" required>
                    <option value="">Select Year</option>
                    <?php
                    $sql = "SELECT * FROM budget_year";
                    $result = mysqli_query($conn, $sql);
                    while ($row = mysqli_fetch_assoc($result)) {
                        $selected = ($row['year_name'] == $current_year) ? "selected" : "";
                        echo "<option value='" . $row['year_id'] . "' $selected>" . $row['year_name'] . "</option>";
                    }
                    ?>
                </select>
                <select name="month" required>
                    <option value="">Select Month</option>
                    <?php
                    $sql = "SELECT * FROM budget_month";
                    $result = mysqli_query($conn, $sql);
                    while ($row = mysqli_fetch_assoc($result)) {
                        $selected = ($row['month_name'] == $current_month) ? "selected" : "";
                        echo "<option value='" . $row['month_id'] . "' $selected>" . $row['month_name'] . "</option>";
                    }
                    ?>
                </select>
                <input type="number" name="amount" min="1" placeholder="Amount" required>
                <button type="submit">Save</button>
            </form>
        </div>
    </div>

<?php include 'footer.php'; ?>


</body>
</html>

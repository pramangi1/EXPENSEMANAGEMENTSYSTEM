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
<link rel="stylesheet" href="css/dashboard.css">
<link rel="stylesheet" href="css/piechart.css">

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



</script>

    

<!-- Your JS Files (defer ensures they run after DOM is ready) -->
<!-- <script src="js/piechart.js" defer></script> -->
 <script src="js/script.js" defer></script> 

<!-- Optional (if needed later) -->
<!-- <script src="js/dashboard.js" defer></script> -->
<style>
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

<div class="main-content">
    <div class="header">
        <h2>
        Dashboard</h2>
        <div class="user-info">
            <div class="user-avatar">
                <span><?php echo strtoupper(substr($u_name, 0, 1)); ?></span>
            </div>
            <div class="user-name"><?php echo $u_name; ?></div>
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

    <div class="container">
    <!-- Pie Chart Section -->
    <div class="chart-container" style="width: 55%; float: left;">
        <h2>Expenses by Budget Head</h2>
        <canvas id="expensesPieChart" width="400" height="400"></canvas>
        <div id="customLegend" class="custom-legend"></div>

    </div>

    <!-- Budget Summary Table Section -->
    <div class="table-container" style="width: 40%; float: left;margin-top: 50px;">
        <h2>Allocated vs Remaining</h2>
        <table border="1" cellpadding="10" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color:rgb(39, 73, 141); text-align: center; color: white;">
                    <th>Budget Head</th>
                    <th>Allocated Amount</th>
                    <th>Spent</th>
                    <th>Remaining</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($budgetData as $row) {
                    // Calculate remaining amount
                    $remainingAmount = $row['allocated_amount'] - $row['spent_amount'];
                    echo "<tr>
                            <td>" . htmlspecialchars($row['title']) . "</td>
                            <td>NRS" . number_format($row['allocated_amount'], 2) . "</td>
                            <td>NRS" . number_format($row['spent_amount'], 2) . "</td>
                            <td>NRS" . number_format($remainingAmount, 2) . "</td>
                        </tr>";
                }
                ?>
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

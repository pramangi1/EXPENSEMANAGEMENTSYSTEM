<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['u_id'])) {
    header("Location: login.php");
    exit();
}

$u_id = $_SESSION['u_id'];

// Fetch user data (name and email) from the database
$query = "SELECT u_name, u_email FROM users WHERE u_id = ?";
$stmt = $conn->prepare($query);

// Check if the query preparation was successful
if ($stmt === false) {
    die("Error preparing query: " . $conn->error);
}

$stmt->bind_param("i", $u_id);
$stmt->execute();
$stmt->bind_result($u_name, $u_email);
$stmt->fetch();
$stmt->close();

// Handle Budget Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['amount'])) {
    $amount = floatval($_POST['amount']);
    $bname = $_POST['bname'];
    $month_id = intval($_POST['month']);
    $year_id = intval($_POST['year']);  // Use year_id for the foreign key

    // Basic validation for form fields
    if (empty($bname) || $amount <= 0 || $month_id <= 0 || $year_id <= 0) {
        $_SESSION['budget_message'] = "Please fill all fields correctly!";
        header("Location: dashboard.php");
        exit();
    }

    // Insert Budget Query
    $query = "INSERT INTO budget (u_id, bname, total_amount, month_id, year_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);

    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param('isdii', $u_id, $bname, $amount, $month_id, $year_id);

    // Check for errors in executing the statement
    if (!$stmt->execute()) {
        $_SESSION['budget_message'] = "Failed to set budget: " . $stmt->error;
    } else {
        $_SESSION['budget_message'] = "Budget set successfully!";
    }

    $stmt->close();
    header("Location: dashboard.php");
    exit();
}

// Calculate Budget Summary
$budgetQuery = "SELECT SUM(total_amount) AS total_budget FROM budget WHERE u_id = ?";
$stmtBudget = $conn->prepare($budgetQuery);
if ($stmtBudget === false) {
    die("Error preparing budget query: " . $conn->error);
}
$stmtBudget->bind_param('i', $u_id);
$stmtBudget->execute();
$budgetResult = $stmtBudget->get_result();
$total_budget = ($budgetResult->num_rows > 0) ? $budgetResult->fetch_assoc()['total_budget'] : 0;
$stmtBudget->close();

$expenseQuery = "
    SELECT SUM(e.amount) AS total_expenses 
    FROM expenses e
    JOIN budget b ON e.budget_id = b.budget_id
    WHERE b.u_id = ?";
$stmtExpense = $conn->prepare($expenseQuery);
if ($stmtExpense === false) {
    die("Error preparing expense query: " . $conn->error);
}
$stmtExpense->bind_param('i', $u_id);
$stmtExpense->execute();
$expenseResult = $stmtExpense->get_result();
$total_expenses = ($expenseResult->num_rows > 0) ? $expenseResult->fetch_assoc()['total_expenses'] : 0;
$stmtExpense->close();
// Fetch data for the pie chart, such as budget allocations by category

    $query = "
    SELECT bh.title, SUM(e.amount) AS total_expenses
    FROM expenses e
    JOIN budget_head bh ON e.bhid = bh.bhid
    JOIN budget b ON e.budget_id = b.budget_id
    WHERE b.u_id = ?
    GROUP BY bh.title";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $u_id);
$stmt->execute();
$stmt->bind_result($category, $total_expenses);

$categories = [];
$expenses = [];
while ($stmt->fetch()) {
    $categories[] = $category;
    $expenses[] = $total_expenses;
}
$stmt->close();

// Now pass these values to JavaScript


$remaining_budget = max(0, $total_budget - $total_expenses);
$threshold = $total_budget * 0.8;
$show_alert = ($total_expenses >= $threshold);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Dashboard</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/piechart.css"> <
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/script.js"></script>
    <script>
    const chartData = {
        labels: <?php echo json_encode($categories); ?>,
        data: <?php echo json_encode($expenses); ?>
    };
</script>
<script src="js/piechart.js"></script>

</head>
<body>
<div class="container">
    <div class="sidebar">
        <h3>Expense Manager</h3>
        <ul>
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <li><a href="list.php">Expense list</a></li>
            <li><a href="addexpense.php">Add Expense</a></li>
            <li><a href="budgethead.php">Budgethead</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="header">
            <h2>Dashboard</h2>
            <div class="user-info">
                <div class="user-avatar">
                    <?php
                    // Display the user's avatar or first letter of the name if no avatar exists
                    $initial = strtoupper(substr($u_name, 0, 1));
                    echo "<span>$initial</span>";
                    ?>
                </div>
                <div class="user-name" id="user-name">
                    <?php
                    // Display the full user name
                    echo $u_name;
                    ?>
                </div>
            </div>
            <button onclick="openBudgetForm()">Set Budget</button>
        </div>

        <?php if (isset($_SESSION['budget_message'])): ?>
            <div class="alert"><?php echo $_SESSION['budget_message']; ?></div>
            <?php unset($_SESSION['budget_message']); ?>
        <?php endif; ?>

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
    </div>
</div>

<div id="budget-modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeBudgetForm()">&times;</span>
        <h3>Set Budget</h3>
        <form id="budget-form" method="POST" action="dashboard.php">
            <?php
            $current_month = date("F");
            $current_year = date("Y");
            $default_bname = "Budget $current_month $current_year";
            ?>
            <input type="text" name="bname" placeholder="Budget Name" value="<?php echo $default_bname; ?>" required>

            <select name="year" required>
                <option value="">Select Year</option>
                <?php
                // Fetching Year Data
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
                // Fetching Month Data
                $sql = "SELECT * FROM budget_month";
                $result = mysqli_query($conn, $sql);
                while ($row = mysqli_fetch_assoc($result)) {
                    $selected = ($row['month_name'] == $current_month) ? "selected" : "";
                    echo "<option value='" . $row['month_id'] . "' $selected>" . $row['month_name'] . "</option>";
                }
                ?>
            </select>

            <input type="number" name="amount" placeholder="Amount" step="0.01" required>

            <button type="submit">Set Budget</button>
            <!-- Pie Chart Section -->
<div class="chart-container">
    <h2>Expenses by Category</h2>
    <canvas id="expensePieChart"></canvas>
</div>

        </form>
    </div>
</div>
<!-- <script> -->


 <?php include 'footer.php';?>
</body>
</html>
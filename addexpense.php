<?php
session_start();
include 'db.php';

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

if (!isset($_SESSION['u_id'])) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
        exit();
    } else {
        header("Location: login.php");
        exit();
    }
}

$u_id = $_SESSION['u_id'];
$u_name=$_SESSION['u_name'];
$current_budget_id = null;

$today = new DateTime();
$current_month_name = $today->format('F');
$current_year_value = $today->format('Y');

$month_id = 0;
$year_id = 0;


$monthStmt = $conn->prepare("SELECT month_id FROM budget_month WHERE month_name = ?");
$monthStmt->bind_param("s", $current_month_name);
$monthStmt->execute();
$monthResult = $monthStmt->get_result();
if ($monthResult->num_rows > 0) {
    $month_id = $monthResult->fetch_assoc()['month_id'];
}
$monthStmt->close();

// Get year_id
$yearStmt = $conn->prepare("SELECT year_id FROM budget_year WHERE year_name = ?");
$yearStmt->bind_param("s", $current_year_value);
$yearStmt->execute();
$yearResult = $yearStmt->get_result();
if ($yearResult->num_rows > 0) {
    $year_id = $yearResult->fetch_assoc()['year_id'];
}
$yearStmt->close();

// ✅ Fetch budget for current month and year
$budgetStmt = $conn->prepare("SELECT budget_id, bname FROM budget WHERE u_id = ? AND month_id = ? AND year_id = ?");
$budgetStmt->bind_param("iii", $u_id, $month_id, $year_id);
$budgetStmt->execute();
$budgetResult = $budgetStmt->get_result();
$budgetData = $budgetResult->fetch_assoc();
$budgetStmt->close();

$current_budget_id = $budgetData['budget_id'] ?? null;
$budget_name = $budgetData['bname'] ?? null;

// ✅ Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && $current_budget_id) {
    $budget_id = $current_budget_id;
    $bhid = $_POST['bhid'];
    $amount = floatval($_POST['amount']);
    $description = trim($_POST['description']);
    $expense_date = $_POST['expense_date'] ?? date('Y-m-d');

    if (!is_numeric($amount) || $amount <= 0 || !is_numeric($bhid)) {
        $response = ['status' => 'error', 'message' => 'Invalid input'];
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }
    }

    // Fetch allocated amount
    $stmt1 = $conn->prepare("SELECT allocated_amount FROM budget_head_amount WHERE budget_id = ? AND bhid = ?");
    $stmt1->bind_param("ii", $budget_id, $bhid);
    $stmt1->execute();
    $stmt1->bind_result($allocated_amount);
    $stmt1->fetch();
    $stmt1->close();

    if ($allocated_amount === null) {
        $response = [
            'status' => 'error',
            'message' => 'No allocated budget found for this Budget Head.'
        ];
    } else {
        $stmt2 = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE budget_id = ? AND bhid = ?");
        $stmt2->bind_param("ii", $budget_id, $bhid);
        $stmt2->execute();
        $stmt2->bind_result($spent_amount);
        $stmt2->fetch();
        $stmt2->close();

        $spent_after = $spent_amount + $amount;
        $overage = $spent_after - $allocated_amount;
        $reference = "Allocated: Rs. " . number_format($allocated_amount, 2) .
                     ", Spent: Rs. " . number_format($spent_after, 2);

        if ($spent_after > $allocated_amount) {
            $reference .= ", Over by: Rs. " . number_format($overage, 2);
            $response = [
                'status' => 'error',
                'message' => 'Expense exceeds allocated budget!',
                'reference' => $reference
            ];
        } elseif ($spent_amount >= $allocated_amount) {
            $response = [
                'status' => 'error',
                'message' => 'No budget head amount left for this Budget Head!',
                'reference' => $reference
            ];
        } else {
            $stmt3 = $conn->prepare("INSERT INTO expenses (budget_id, bhid, amount, description, expense_date) VALUES (?, ?, ?, ?, ?)");
            $stmt3->bind_param("iisss", $budget_id, $bhid, $amount, $description, $expense_date);

            if ($stmt3->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Expense added successfully!',
                    'reference' => $reference
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to add expense.',
                    'reference' => $reference
                ];
            }
            $stmt3->close();
        }
    }

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Expense</title>
    <link rel="stylesheet" href="css/hello.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
   <script src="js/expense.js" defer></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const dateInput = document.getElementById('datepicker');
        const today = new Date().toISOString().split('T')[0];
        dateInput.value = today;
        dateInput.addEventListener('change', function () {
            dateInput.value = today; // lock to today
        });
    });
    </script>
    <style>
       .main-content {
    margin-left: 500px;
    padding: 40px 20px;
    min-height: 100vh;

}

/* Flex container: Form left, Reference right */
.form-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 40px;
    max-width: 1000px;
    margin: auto;
}
/* .user-info {
  display: flex;
  align-items: right;
  gap: 12px;
}

.user-avatar {
  background-color: #2d9cdb;
  color: white;
  font-weight: bold;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.user-name {
  font-size: 16px;
  color: #333;
}
 */

/* Expense Form Styling */
.expense-form {
    padding: 30px 40px;
    border-radius: 12px;
    width: 60%;
}

.expense-form h2 {
    text-align: center;
    margin-bottom: 25px;
    font-size: 24px;
    color: #125895;
}

.expense-form label {
    display: block;
    margin-top: 15px;
    font-weight: 600;
    color: #333;
}

.expense-form input,
.expense-form select,
.expense-form textarea {
    margin-top: 8px;
    padding: 10px;
    width: 100%;
    border-radius: 6px;
    font-size: 15px;
    box-sizing: border-box;
    border: 1px bold black;
}

.expense-form textarea {
    resize: vertical;
}

.expense-form input[type="submit"] {
    margin-top: 20px;
        background-color: #1f6fb2;
        color: white;
        padding: 10px 18px;
        border: none;
        border-radius: 20px; /* Rounded for cute button */
        cursor: pointer;
        font-size: 14px;
        display: block;
        width: auto;
        margin-left: auto;
        margin-right: auto;
}

.expense-form input[type="submit"]:hover {
    background-color: #219150;
}

/* Reference box on the right */
.reference-box {
    padding: 20px 25px;
    border-radius: 10px;
    width: 35%;
    font-size: 16px;
    color: #333;

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
    <div class="form-wrapper">
        <div class="expense-form">
          <div class="eader">
            <h2>Add Expense</h2>
            <?php if ($current_budget_id): ?>
                <!-- <div class="user-info">
    <div class="user-avatar">
      <span><?php echo strtoupper(substr($u_name, 0, 1)); ?></span>
    </div>
    <div class="user-name"><?php echo $u_name; ?></div>
  </div> -->
            </div>
            <form method="POST" id="addExpenseForm">
                <!-- Budget info -->
                <label for="budget_id">Budget:</label>
                <input type="text" value="<?php echo isset($budgetData['bname']) ? htmlspecialchars($budgetData['bname']) : 'No budget found'; ?>" readonly>

                <input type="hidden" name="budget_id" value="<?php echo $current_budget_id; ?>">

                <!-- Budget Head -->
                <label for="bhid">Select Budget Head:</label>
                <select name="bhid" required>
                    <option value="">-- Select Head --</option>
                    <?php
                    $headStmt = $conn->prepare("
                        SELECT bh.bhid, bh.title
                        FROM budget_head_amount bha
                        JOIN budget_head bh ON bha.bhid = bh.bhid
                        WHERE bha.budget_id = ? AND (bh.u_id IS NULL OR bh.u_id = ?)
                    ");
                    $headStmt->bind_param("ii", $current_budget_id, $u_id);
                    $headStmt->execute();
                    $headResult = $headStmt->get_result();
                    while ($head = $headResult->fetch_assoc()) {
                        echo "<option value='" . $head['bhid'] . "'>" . htmlspecialchars($head['title']) . "</option>";
                    }
                    $headStmt->close();
                    ?>
                </select>

                <!-- Amount -->
                <label for="amount">Amount:</label>
                <input type="number" name="amount" min="1" placeholder="Your expense" required>

                <!-- Description -->
                <label for="description">Description:</label>
                <textarea name="description" placeholder="Details"></textarea>

                <!-- Date -->
                <label for="expense_date">Date:</label>
                <input type="date" id="datepicker" name="expense_date" required readonly>

                <input type="submit" value="Add Expense">
            </form>
            <?php endif; ?>
        </div>

        <!-- ✅ Reference Box (Right Side) -->
<div class="reference-box" id="referenceBox" style="<?php echo isset($reference) ? 'display:block;' : 'display:none;'; ?>">
    <h4>Reference Info</h4>
    <p id="referenceMessage"><?php echo isset($reference) ? htmlspecialchars($reference) : 'No reference yet.'; ?></p>
</div>



    <script>
    const expenseStatus = {
        success: <?php echo json_encode($success); ?>,
        error: <?php echo json_encode($error); ?>,
        reference: <?php echo json_encode(isset($reference) ? $reference : "") ?>
    };
</script>


    <?php include 'footer.php';?>

</body>
</html>
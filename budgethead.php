 <?php
session_start();
include 'db.php';

if (!isset($_SESSION['u_id'])) {
    header("Location: login.php");
    exit();
}

$u_id = $_SESSION['u_id'];

// Get current date
$today = new DateTime();
$current_month_name = $today->format('F'); // e.g., "May"
$current_year_value = $today->format('Y'); // e.g., "2025"

// ✅ Get month_id and year_id based on current date
$monthStmt = $conn->prepare("SELECT month_id FROM budget_month WHERE month_name = ?");
$monthStmt->bind_param("s", $current_month_name);
$monthStmt->execute();
$monthResult = $monthStmt->get_result();
$month_id = ($monthResult->num_rows > 0) ? $monthResult->fetch_assoc()['month_id'] : 0;
$monthStmt->close();

$yearStmt = $conn->prepare("SELECT year_id FROM budget_year WHERE year_name = ?");
$yearStmt->bind_param("s", $current_year_value);
$yearStmt->execute();
$yearResult = $yearStmt->get_result();
$year_id = ($yearResult->num_rows > 0) ? $yearResult->fetch_assoc()['year_id'] : 0;
$yearStmt->close();

// ✅ Fetch budget for this user for the current month and year
$budgetStmt = $conn->prepare("SELECT * FROM budget WHERE u_id = ? AND month_id = ? AND year_id = ?");
$budgetStmt->bind_param("iii", $u_id, $month_id, $year_id);
$budgetStmt->execute();
$budgetResult = $budgetStmt->get_result();
$budgetData = $budgetResult->fetch_assoc();
$budgetStmt->close();

$budget_id = $budgetData ? $budgetData['budget_id'] : null;
$budget_name = $budgetData ? $budgetData['bname'] : null;
$total_budget = $budgetData ? $budgetData['total_amount'] : 0;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['head_amounts']) && $budget_id) {
    $alreadyAllocated = false;
    $totalAllocated = 0;

    // ✅ Get current total allocated amount for this budget
    $allocatedQuery = "SELECT SUM(allocated_amount) AS total_allocated 
                       FROM budget_head_amount 
                       WHERE budget_id = ?";
    $allocatedStmt = $conn->prepare($allocatedQuery);
    $allocatedStmt->bind_param('i', $budget_id);
    $allocatedStmt->execute();
    $allocatedResult = $allocatedStmt->get_result();
    $allocatedRow = $allocatedResult->fetch_assoc();
    $totalAllocated = $allocatedRow['total_allocated'] ?? 0;
    $allocatedStmt->close();

    $remainingBudget = $total_budget - $totalAllocated;
    $showAlert = false;
    $alertMessage = '';

    foreach ($_POST['head_amounts'] as $bhid => $head_amount) {
        if (empty($bhid) || empty($head_amount) || !is_numeric($head_amount) || $head_amount <= 0) {
            die("Invalid input.");
        }

        if ($head_amount > $remainingBudget) {
            $showAlert = true;
            $alertMessage = "Not enough remaining budget for this allocation!";
            break;
        }

        // ✅ Check if allocation already exists for this budget and head
        $checkQuery = "SELECT 1 FROM budget_head_amount 
                       WHERE budget_id = ? AND bhid = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param('ii', $budget_id, $bhid);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $checkStmt->close();

        if ($result->num_rows > 0) {
            $alreadyAllocated = true;
            continue;
        }

        // ✅ Insert allocation
        $query = "INSERT INTO budget_head_amount (budget_id, bhid, allocated_amount, created_at) 
                  VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $created_at = date('Y-m-d H:i:s');
        $stmt->bind_param('iids', $budget_id, $bhid, $head_amount, $created_at);
        if (!$stmt->execute()) {
            error_log("Insert error: " . $stmt->error);
            echo "Error saving allocation.";
            exit();
        }
        $stmt->close();

        $remainingBudget -= $head_amount;
    }

    $_SESSION['success_message'] = $alreadyAllocated 
        ? "Some heads were already allocated. Others were saved." 
        : "All budget head allocations saved successfully!";

    if ($showAlert) {
        $_SESSION['alert_message'] = $alertMessage;
    }

    header("Location: budgethead.php");
    exit();
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Distribute Budget into Heads</title>
    <link rel="stylesheet" href="css/dashboard.css"> 
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/head.js" defer></script>

<style>

     .main-content{
        margin-left: 350px; /* Adjusted to match sidebar width */
        padding: 20px;

        min-height: 100vh; /* Ensure full height */
     }

    .budget-form-container {
        padding: 30px 40px;
        border-radius: 10px;
        max-width: 900px;
        width: 100%;
    }

    .budget-form-container h2 {
        text-align: center;
        margin-bottom: 25px;
        color: #1f6fb2;
    }

    .head-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr); /* 2 columns for better layout */
        gap: 20px;
        margin-top: 20px;
    }

    .head-item {
        display: flex;
        flex-direction: column;
    }

    .head-item label {
        font-weight: bold ;
        margin-bottom: 5px;
        color: #333;
    }

    .head-item input {
        padding: 10px;
        border: 1px solid  black;
        border-radius: 6px;
        font-size: 14px;
        width: 100%;
    }

    h3 {
        margin-top: 30px;
        color: #1f6fb2;
    }

    button[type="submit"] {
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

    button[type="submit"]:hover {
        background-color: rgb(14, 102, 54);
    }

    a {
        text-decoration: none;
        color: #1f6fb2;
    }

    a:hover {
        text-decoration: underline;
    }
</style>



</head>
<body 
<body 
<?php
if (isset($_SESSION['success_message'])) {
    echo 'data-success="' . htmlspecialchars($_SESSION['success_message']) . '" ';
}
if (isset($_SESSION['alert_message'])) {
    echo 'data-alert-message="' . htmlspecialchars($_SESSION['alert_message']) . '"';
}
?>
>

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
       <div class="budget-form-container"> 
            <form method="POST" action="">
                <h2 style="color:#1f6fb2;">Allocate Amount to Budget Heads</h2>

                <?php
                if ($budget_id) {
                    echo "<p style='text-align:right; font-weight:bold;'>Budget Name:<br><span style='color:blue; font-weight:bold;'>"
                        . htmlspecialchars($budget_name) . "</span></p>";
                    $headQuery = "
    SELECT bh.*, u.u_name 
    FROM budget_head bh
    LEFT JOIN users u ON bh.u_id = u.u_id
    WHERE bh.u_id IS NULL OR bh.u_id = ?
    ORDER BY bh.is_mandatory DESC, bh.title ASC
";
$stmt = $conn->prepare($headQuery);
$stmt->bind_param("i", $u_id);
$stmt->execute();
$headResult = $stmt->get_result();

$mandatoryHTML = "";
$optionalHTML = "";

while ($head = mysqli_fetch_assoc($headResult)) {
    $bhid = $head['bhid'];
    $title = htmlspecialchars($head['title']);
    $createdBy = $head['u_name'] ? "<small style='color:gray;'>(by {$head['u_name']})</small>" : "";

    $checkQuery = "
        SELECT allocated_amount 
        FROM budget_head_amount 
        WHERE budget_id = ? AND bhid = ?
    ";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ii", $budget_id, $bhid);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $allocated = $checkResult->fetch_assoc();
    $isDisabled = $allocated ? "disabled" : "";
    $placeholder = $allocated ? "Already allocated: NRS" . htmlspecialchars($allocated['allocated_amount']) : "Amount for $title";

    $inputHTML = "<div class='head-item'>";
    $inputHTML .= "<label for='head_$bhid'>$title $createdBy</label>";
    $inputHTML .= "<input type='number' id='head_$bhid' name='head_amounts[$bhid]' placeholder='$placeholder' min='1' $isDisabled>";
    $inputHTML .= "</div>";

    if ($head['is_mandatory']) {
        $mandatoryHTML .= $inputHTML;
    } else {
        $optionalHTML .= $inputHTML;
    }
}

                    echo "<div class='head-grid'>$mandatoryHTML</div>";

                    if (!empty($optionalHTML)) {
                        echo "<h3>Optional (Custom) Budget Heads</h3>";
                        echo "<div class='head-grid'>$optionalHTML</div>";
                    }

                    echo "<p><a href='addbudgethead.php' style='color: #1f6fb2;'>Add Custom Budget Head</a></p>";
                    echo '<button type="submit">Save Allocations</button>';
                } else {
                    echo "<p style='color:red;'>Please create <a href='dashboard.php'> a budget first </a> first.</p>";
                }
                ?>
            </form>
        </div>
   

            </div>
<?php include 'footer.php'; ?>


<?php
unset($_SESSION['success_message']);
unset($_SESSION['alert_message']);
?>

</body>
</html>

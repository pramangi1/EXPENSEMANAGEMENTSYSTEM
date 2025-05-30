  <?php
session_start();
include 'db.php';

if (!isset($_SESSION['u_id'])) {
    header("Location: login.php");
    exit();
}

$u_id = $_SESSION['u_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['title'])) {
    $titles = $_POST['title'];
    $mandatories = $_POST['is_mandatory'];

    foreach ($titles as $index => $title) {
        $title_clean = trim($title);
        if ($title_clean === "") continue;

        $is_mandatory = isset($mandatories[$index]) ? (int)$mandatories[$index] : 0;

        $stmt = $conn->prepare("INSERT INTO budget_head (title, is_mandatory, u_id) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $title_clean, $is_mandatory, $u_id);

        if (!$stmt->execute()) {
            $_SESSION['error_message'] = "Error: " . $stmt->error;
            header("Location: budgethead.php");
            exit();
        }
        $stmt->close();
    }

    $_SESSION['success_message'] = "Budget heads added successfully!";
    header("Location: budgethead.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Budget Heads</title>
    <link rel="stylesheet" href="css/hello.css">
    <style>
        .form-container {
            width: 450px;
            margin: 50px auto;
            padding: 30px;
            
            border-radius: 10px;
        }
        h2 {
            text-align: center;
            color: #1f6fb2;
        }
        .form-group {
            margin-bottom: 20px;
        }
        input[type="text"], select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        .add-btn, button[type="submit"] {
            display: block;
            margin: 20px auto;
            padding: 10px 18px;
            background-color: #1f6fb2;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
        }
        .add-btn:hover, button[type="submit"]:hover {
            background-color: #155c99;
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

  

<div class="form-container">
    <h2>Add Budget Heads</h2>
    <form method="POST" action="">
        <div id="budgetHeadFields">
            <div class="form-group budget-head-entry">
                <label>Budget Head Title</label>
                <input type="text" name="title[]" required placeholder="Enter budget head title">
                <label>Type</label>
                <select name="is_mandatory[]" required>
                    <option value="1">Mandatory</option>
                    <option value="0">Optional</option>
                </select>
            </div>
        </div>
        <button type="button" class="add-btn" onclick="addBudgetHeadField()">+ Add Another</button>
        <button type="submit">Submit</button>
    </form>
</div>
<? include 'footer.php';?>

<script>
function addBudgetHeadField() {
    const container = document.getElementById('budgetHeadFields');
    const newField = document.createElement('div');
    newField.classList.add('form-group', 'budget-head-entry');
    newField.innerHTML = `
        <label>Budget Head Title</label>
        <input type="text" name="title[]" required placeholder="Enter budget head title">
        <label>Type</label>
        <select name="is_mandatory[]" required>
            <option value="1">Mandatory</option>
            <option value="0">Optional</option>
        </select>
    `;
    container.appendChild(newField);
}
</script>
 <?php include 'footer.php'; ?>
</body>
</html>

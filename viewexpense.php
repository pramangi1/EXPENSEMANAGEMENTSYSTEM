<?php
session_start();
include 'db.php';

if (!isset($_SESSION['u_id'])) {
    header("Location: login.php");
    exit();
}

$budget_id = $_GET['budget_id'];
$bhid = $_GET['bhid'];
$month = $_GET['month'];
$year = $_GET['year'];

$stmt = $conn->prepare("
    SELECT * FROM expenses 
    WHERE budget_id = ? AND bhid = ? 
    AND MONTH(expense_date) = ? AND YEAR(expense_date) = ?
");
$stmt->bind_param("iiii", $budget_id, $bhid, $month, $year);
$stmt->execute();
$result = $stmt->get_result();
$expenses = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Expenses</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: rgb(4, 37, 70);
            color: white;
            padding: 20px 0;
            height: 100vh;
            position: fixed;
            z-index: 1000;
        }

        .sidebar h3 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.5rem;
            color: #ecf0f1;
            padding: 0 20px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            margin: 5px 0;
        }

        .sidebar ul li a {
            display: block;
            color: #bdc3c7;
            padding: 12px 20px;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 1rem;
        }

        .sidebar ul li a:hover {
            background: white;
            color: rgb(16, 91, 123);
            padding-left: 25px;
        }

        .sidebar a.active {
            background-color: rgb(10, 88, 177);
            color: white;
            font-weight: bold;
            padding-left: 10px;
            border-left: 4px solid #144781;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        table {
            width: 100%;
            margin: 20px auto;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        th, td {
            padding: 12px 16px;
            border: 1px solid #ddd;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background-color: rgb(2, 25, 58);
            color: white;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f2f2f2;
        }

        .edit-btn {
            background-color: #3498db;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .edit-btn:hover {
            background-color: #2980b9;
        }

        .form-group {
            text-align: left;
            margin-bottom: 12px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 6px;
            color: #444;
        }

        .swal2-input.full-width,
        .swal2-textarea.full-width {
            width: 100% !important;
            box-sizing: border-box;
        }

        .custom-swal-popup {
            width: 500px !important;
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
        <h2 style="text-align:center;">Expenses List</h2>
        <table>
            <thead>
                <tr>
                    <th>Amount</th>
                    <th>Description</th>
                    <th>Date</th>
                    <th>Edit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($expenses as $exp): ?>
                    <tr>
                        <td><?= number_format($exp['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($exp['description']) ?></td>
                        <td><?= $exp['expense_date'] ?></td>
                        <td><button class="edit-btn" data-id="<?= $exp['expense_id'] ?>">Edit</button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        window.onload = function () {
            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const id = this.dataset.id;

                    fetch('getexpense.php?id=' + id)
                        .then(res => res.json())
                        .then(data => {
                            Swal.fire({
                                title: 'Edit Expense',
                                html: `
    <div class="form-group">
        <label for="amount">Amount</label>
        <input id="amount" class="swal2-input full-width" placeholder="Amount" value="${data.amount}">
    </div>

    <div class="form-group">
        <label>Budget Head</label>
        <input class="swal2-input full-width" value="${data.title}" readonly>
    </div>

    <div class="form-group">
        <label>Allocated Amount</label>
        <input class="swal2-input full-width" value="${data.allocated_amount}" readonly>
    </div>

    <div class="form-group">
        <label for="date">Date</label>
        <input id="date" type="date" class="swal2-input full-width" value="${data.expense_date}">
    </div>

    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" class="swal2-textarea full-width" placeholder="Description">${data.description}</textarea>
    </div>

    <input type="hidden" id="expense-id" value="${data.expense_id}">
    <input type="hidden" id="bhid" value="${data.bhid}">
    <input type="hidden" id="budget_id" value="${data.budget_id}">
`,

                                showCancelButton: true,
                                confirmButtonText: 'Update',
                                cancelButtonText: 'Cancel',
                                customClass: {
                                    popup: 'custom-swal-popup'
                                },
                                preConfirm: () => {
                                    return fetch('updateexpense.php', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify({
                                            expense_id: document.getElementById('expense-id').value,
                                            bhid: document.getElementById('bhid').value,
                                            budget_id: document.getElementById('budget_id').value,
                                            amount: document.getElementById('amount').value,
                                            expense_date: document.getElementById('date').value,
                                            description: document.getElementById('description').value
                                        })
                                    }).then(res => res.json());
                                }
                            }).then(result => {
                                if (result.isConfirmed && result.value.success) {
                                    Swal.fire('Updated!', 'Expense updated successfully.', 'success')
                                        .then(() => location.reload());
                                } else if (result.value && !result.value.success) {
                                    Swal.fire('Error', result.value.message, 'error');
                                }
                            });
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                            Swal.fire('Error', 'Could not load expense data.', 'error');
                        });
                });
            });
        };
    </script>

    <?php include 'footer.php'; ?>
</body>
</html>

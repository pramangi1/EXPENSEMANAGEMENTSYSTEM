<?php
include 'db.php';

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($data['expense_id'], $data['amount'], $data['bhid'], $data['budget_id'], $data['expense_date'], $data['description'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$expense_id   = (int)$data['expense_id'];
$amount       = (float)$data['amount'];
$bhid         = (int)$data['bhid'];
$budget_id    = (int)$data['budget_id'];
$expense_date = $data['expense_date'];
$description  = trim($data['description']);

// ✅ 1. Fetch allocated amount
$allocStmt = $conn->prepare("SELECT allocated_amount FROM budget_head_amount WHERE bhid = ? AND budget_id = ?");
$allocStmt->bind_param("ii", $bhid, $budget_id);
$allocStmt->execute();
$allocResult = $allocStmt->get_result();
$allocated = ($allocResult->num_rows > 0) ? $allocResult->fetch_assoc()['allocated_amount'] : 0;
$allocStmt->close();

// ✅ 2. Get total spent so far for this bhid/budget_id (excluding current expense)
$spentStmt = $conn->prepare("
    SELECT SUM(amount) AS total_spent 
    FROM expenses 
    WHERE bhid = ? AND budget_id = ? AND expense_id != ?
");
$spentStmt->bind_param("iii", $bhid, $budget_id, $expense_id);
$spentStmt->execute();
$spentResult = $spentStmt->get_result();
$spent = ($spentResult->num_rows > 0) ? $spentResult->fetch_assoc()['total_spent'] : 0;
$spentStmt->close();

$new_total = $spent + $amount;

// ✅ 3. Check if new total exceeds allocation
if ($new_total > $allocated) {
    echo json_encode([
        'success' => false,
        'message' => "Cannot update. Total expenses (NRS " . number_format($new_total, 2) . ") exceed allocated amount (NRS " . number_format($allocated, 2) . ")."
    ]);
    exit();
}

// ✅ 4. Update if valid
$updateStmt = $conn->prepare("
    UPDATE expenses 
    SET amount = ?, bhid = ?, budget_id = ?, expense_date = ?, description = ?
    WHERE expense_id = ?
");
$updateStmt->bind_param("diissi", $amount, $bhid, $budget_id, $expense_date, $description, $expense_id);

if ($updateStmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $updateStmt->error]);
}

$updateStmt->close();
$conn->close();
?>

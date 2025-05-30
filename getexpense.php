<?php
include 'db.php';

// Validate the incoming ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid expense ID']);
    exit;
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT 
        e.expense_id,
        e.budget_id,
        e.bhid,
        e.amount,
        e.expense_date,
        e.description,
        bh.title,
        bha.allocated_amount
    FROM expenses e
    JOIN budget_head bh ON e.bhid = bh.bhid
    JOIN budget_head_amount bha ON e.budget_id = bha.budget_id AND e.bhid = bha.bhid
    WHERE e.expense_id = ?
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Query prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row); // success
} else {
    echo json_encode(['success' => false, 'message' => 'Expense not found']);
}

$stmt->close();
$conn->close();
?>

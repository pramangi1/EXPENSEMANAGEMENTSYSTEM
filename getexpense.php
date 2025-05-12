<?php
include 'db.php';

$id = $_GET['id'];

$stmt = $conn->prepare("
    SELECT e.*, bh.title, bha.allocated_amount
    FROM expenses e
    JOIN budget_head bh ON e.bhid = bh.bhid
    JOIN budget_head_amount bha ON e.budget_id = bha.budget_id AND e.bhid = bha.bhid
    WHERE e.expense_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'Expense not found']);
}
?>

<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['u_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$u_id = $_SESSION['u_id'];

if (!isset($_GET['month_id']) || !isset($_GET['year_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit();
}

$month_id = (int)$_GET['month_id'];
$year_id = (int)$_GET['year_id'];

$sql = "
    SELECT 
        bh.title AS budget_head,
        COALESCE(SUM(bha.allocated_amount), 0) AS total_allocated,
        COALESCE(SUM(e.amount), 0) AS total_expenses
    FROM budget_head bh
    LEFT JOIN budget_head_amount bha 
        ON bha.bhid = bh.bhid 
        AND bha.budget_id IN (
            SELECT budget_id FROM budget 
            WHERE u_id = ? AND month_id = ? AND year_id = ?
        )
    LEFT JOIN expenses e 
        ON e.bhid = bh.bhid 
        AND e.budget_id IN (
            SELECT budget_id FROM budget 
            WHERE u_id = ? AND month_id = ? AND year_id = ?
        )
    GROUP BY bh.bhid, bh.title
    ORDER BY bh.title
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiiii", $u_id, $month_id, $year_id, $u_id, $month_id, $year_id);
$stmt->execute();
$result = $stmt->get_result();

$budget_heads = [];
$allocated_amounts = [];
$expenses = [];

while ($row = $result->fetch_assoc()) {
    $budget_heads[] = $row['budget_head'];
    $allocated_amounts[] = (float)$row['total_allocated'];
    $expenses[] = (float)$row['total_expenses'];
}

$stmt->close();
$conn->close();

if (count($budget_heads) > 0) {
    echo json_encode([
        'success' => true,
        'labels' => $budget_heads,
        'allocation' => $allocated_amounts,
        'spent' => $expenses
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'No data']);
}

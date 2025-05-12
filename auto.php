<?php
include 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$u_id = $_SESSION['u_id'] ?? null;
if (!$u_id) return;

// ✅ Only run on the 1st of the month
$today = new DateTime();
if ($today->format('d') !== '01') return;

// Get previous month details
$lastMonth = new DateTime('first day of last month');
$prev_month_name = $lastMonth->format('F');
$prev_year_value = $lastMonth->format('Y');
$prev_month_num = (int)$lastMonth->format('m');
$prev_year_num = (int)$lastMonth->format('Y');

// Fetch month_id and year_id from DB
$monthStmt = $conn->prepare("SELECT month_id FROM budget_month WHERE month_name = ?");
$monthStmt->bind_param("s", $prev_month_name);
$monthStmt->execute();
$prev_month = $monthStmt->get_result()->fetch_assoc()['month_id'] ?? 0;
$monthStmt->close();

$yearStmt = $conn->prepare("SELECT year_id FROM budget_year WHERE year_name = ?");
$yearStmt->bind_param("s", $prev_year_value);
$yearStmt->execute();
$prev_year = $yearStmt->get_result()->fetch_assoc()['year_id'] ?? 0;
$yearStmt->close();

// ✅ Check if already saved
$checkSaved = $conn->prepare("
    SELECT 1 FROM saving s
    JOIN budget_head_amount bha ON s.bha_id = bha.bha_id
    JOIN budget b ON bha.budget_id = b.budget_id
    WHERE b.u_id = ? AND s.saved_month = ? AND s.saved_year = ?
    LIMIT 1
");
$checkSaved->bind_param("iii", $u_id, $prev_month, $prev_year);
$checkSaved->execute();
$checkSaved->store_result();
if ($checkSaved->num_rows > 0) {
    $checkSaved->close();
    return; // Already saved
}
$checkSaved->close();

// ✅ Fetch all allocations from previous month
$sql = "
    SELECT b.budget_id, bha.bha_id, bha.bhid, bha.allocated_amount
    FROM budget b
    JOIN budget_head_amount bha ON b.budget_id = bha.budget_id
    WHERE b.u_id = ? AND b.month_id = ? AND b.year_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $u_id, $prev_month, $prev_year);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $budget_id = $row['budget_id'];
    $bha_id = $row['bha_id'];
    $bhid = $row['bhid'];
    $allocated = $row['allocated_amount'];

    // Get spent amount for that bhid in last month
    $stmt2 = $conn->prepare("
        SELECT SUM(amount) as total FROM expenses 
        WHERE budget_id = ? AND bhid = ? AND MONTH(expense_date) = ? AND YEAR(expense_date) = ?");
    $stmt2->bind_param("iiii", $budget_id, $bhid, $prev_month_num, $prev_year_num);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $spent = $result2->fetch_assoc()['total'] ?? 0;
    $stmt2->close();

    $leftover = max(0, $allocated - $spent);

    if ($leftover > 0) {
        // Save to savings table
        $check = $conn->prepare("
            SELECT saving_id FROM saving 
            WHERE bha_id = ? AND saved_month = ? AND saved_year = ?");
        $check->bind_param("iii", $bha_id, $prev_month_num, $prev_year_num);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $update = $conn->prepare("
                UPDATE saving 
                SET saved_amount = ?, allocated_amount = ? 
                WHERE bha_id = ? AND saved_month = ? AND saved_year = ?");
            $update->bind_param("ddiii", $leftover, $allocated, $bha_id, $prev_month_num, $prev_year_num);
            $update->execute();
            $update->close();
        } else {
            $insert = $conn->prepare("
                INSERT INTO saving (bha_id, allocated_amount, saved_amount, saved_month, saved_year)
                VALUES (?, ?, ?, ?, ?)");
            $insert->bind_param("ddiii", $bha_id, $allocated, $leftover, $prev_month_num, $prev_year_num);
            $insert->execute();
            $insert->close();
        }

        $check->close();
    }
}
$stmt->close();
?>

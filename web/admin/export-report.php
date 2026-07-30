<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role($pdo, ['admin']);

$batchId = (int) ($_GET['batch_id'] ?? 0);
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="claims-report.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Claimed At', 'Student No', 'Scholar', 'Program', 'Batch', 'Amount', 'Staff']);

$sql = 'SELECT c.claimed_at, s.student_no, s.first_name, s.last_name, p.name AS program_name,
               b.name AS batch_name, v.amount, u.username AS staff_name
        FROM claims c
        JOIN claim_vouchers v ON v.id = c.voucher_id
        JOIN scholars s ON s.id = v.scholar_id
        JOIN distribution_batches b ON b.id = v.batch_id
        JOIN scholarship_programs p ON p.id = b.program_id
        JOIN users u ON u.id = c.staff_user_id';
$params = [];
if ($batchId > 0) {
    $sql .= ' WHERE b.id = ?';
    $params[] = $batchId;
}
$sql .= ' ORDER BY c.claimed_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
while ($row = $stmt->fetch()) {
    fputcsv($out, [
        $row['claimed_at'],
        $row['student_no'],
        scholar_full_name($row),
        $row['program_name'],
        $row['batch_name'],
        $row['amount'],
        $row['staff_name'],
    ]);
}
fclose($out);
exit;

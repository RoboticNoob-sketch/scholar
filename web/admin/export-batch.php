<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role($pdo, ['admin']);

$id = (int) ($_GET['id'] ?? 0);
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="batch-' . $id . '-vouchers.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Student No', 'Scholar Name', 'Voucher Code', 'Amount', 'Status', 'Claimed At']);

$stmt = $pdo->prepare(
    'SELECT s.student_no, s.first_name, s.last_name, v.voucher_code, v.amount, v.status, c.claimed_at
     FROM claim_vouchers v
     JOIN scholars s ON s.id = v.scholar_id
     LEFT JOIN claims c ON c.voucher_id = v.id
     WHERE v.batch_id = ?
     ORDER BY s.last_name'
);
$stmt->execute([$id]);
while ($row = $stmt->fetch()) {
    fputcsv($out, [
        $row['student_no'],
        scholar_full_name($row),
        $row['voucher_code'],
        $row['amount'],
        $row['status'],
        $row['claimed_at'],
    ]);
}
fclose($out);
exit;

<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

$user = require_api_role($pdo, ['staff']);

$stmt = $pdo->prepare(
    'SELECT c.claimed_at, v.amount, s.student_no, s.first_name, s.last_name,
            b.name AS batch_name, p.name AS program_name
     FROM claims c
     JOIN claim_vouchers v ON v.id = c.voucher_id
     JOIN scholars s ON s.id = v.scholar_id
     JOIN distribution_batches b ON b.id = v.batch_id
     JOIN scholarship_programs p ON p.id = b.program_id
     WHERE c.staff_user_id = ? AND DATE(c.claimed_at) = CURDATE()
     ORDER BY c.claimed_at DESC'
);
$stmt->execute([$user['id']]);

$items = [];
foreach ($stmt->fetchAll() as $row) {
    $items[] = [
        'claimed_at' => $row['claimed_at'],
        'claimed_at_formatted' => format_datetime($row['claimed_at']),
        'scholar_name' => scholar_full_name($row),
        'student_no' => $row['student_no'],
        'batch_name' => $row['batch_name'],
        'program_name' => $row['program_name'],
        'amount' => (float) $row['amount'],
        'amount_formatted' => format_money((float) $row['amount']),
    ];
}

json_response(['success' => true, 'items' => $items, 'count' => count($items)]);

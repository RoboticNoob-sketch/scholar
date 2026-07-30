<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

$user = authenticate_api_token($pdo, bearer_token());
if (!$user || $user['role'] !== 'student') {
    json_response(['success' => false, 'error' => 'Unauthorized'], 401);
}

$stmt = $pdo->prepare('SELECT id FROM scholars WHERE user_id = ?');
$stmt->execute([$user['id']]);
$scholarId = (int) $stmt->fetchColumn();
if (!$scholarId) {
    json_response(['success' => false, 'error' => 'Scholar profile not found'], 404);
}

$history = $pdo->prepare(
    'SELECT b.name AS batch_name, p.name AS program_name, v.amount, v.status, c.claimed_at, b.distribution_date
     FROM claim_vouchers v
     JOIN distribution_batches b ON b.id = v.batch_id
     JOIN scholarship_programs p ON p.id = b.program_id
     LEFT JOIN claims c ON c.voucher_id = v.id
     WHERE v.scholar_id = ?
     ORDER BY COALESCE(c.claimed_at, v.created_at) DESC'
);
$history->execute([$scholarId]);

$items = [];
foreach ($history->fetchAll() as $row) {
    $items[] = [
        'batch_name' => $row['batch_name'],
        'program_name' => $row['program_name'],
        'amount' => (float) $row['amount'],
        'amount_formatted' => format_money((float) $row['amount']),
        'status' => $row['status'],
        'date' => $row['claimed_at'] ?: $row['distribution_date'],
        'date_formatted' => format_datetime($row['claimed_at'] ?: ($row['distribution_date'] . ' 00:00:00')),
    ];
}

json_response(['success' => true, 'items' => $items]);

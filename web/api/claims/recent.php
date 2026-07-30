<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

$user = require_api_role($pdo, ['staff']);

$batchId = (int) ($_GET['batch_id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT c.claimed_at, s.first_name, s.last_name
     FROM claims c
     JOIN claim_vouchers v ON v.id = c.voucher_id
     JOIN scholars s ON s.id = v.scholar_id
     WHERE v.batch_id = ?
     ORDER BY c.claimed_at DESC LIMIT 10'
);
$stmt->execute([$batchId]);

$items = [];
foreach ($stmt->fetchAll() as $row) {
    $items[] = [
        'name' => scholar_full_name($row),
        'time' => format_datetime($row['claimed_at']),
    ];
}

json_response(['success' => true, 'items' => $items]);

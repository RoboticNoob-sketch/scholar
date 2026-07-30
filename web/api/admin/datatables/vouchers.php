<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';

use App\Queries\BatchQueries;

$input = require_admin_datatables($pdo);
$batchId = (int) ($_GET['batch_id'] ?? $_POST['batch_id'] ?? 0);
if ($batchId <= 0) {
    datatables_json($input['draw'], 0, 0, []);
}

$batchStmt = $pdo->prepare('SELECT status FROM distribution_batches WHERE id = ?');
$batchStmt->execute([$batchId]);
$batchStatus = (string) ($batchStmt->fetchColumn() ?: 'closed');
$allowSelect = $batchStatus !== 'closed';

$result = BatchQueries::voucherDatatables($pdo, $batchId, $input);

$data = [];
foreach ($result['rows'] as $row) {
    $checkbox = '';
    if ($allowSelect && $row['status'] === 'pending') {
        $checkbox = '<input type="checkbox" name="voucher_ids[]" value="' . (int) $row['id'] . '">';
    }
    $data[] = [
        $checkbox,
        e(scholar_full_name($row)),
        e($row['student_no']),
        e(format_money((float) $row['amount'])),
        '<span class="badge ' . e(badge_class($row['status'])) . '">' . e($row['status']) . '</span>',
        e(format_datetime($row['claimed_at'])),
    ];
}

datatables_json($result['draw'], $result['total'], $result['filtered'], $data);

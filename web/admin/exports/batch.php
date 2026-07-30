<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_role($pdo, ['admin']);

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    flash('error', 'Batch not found.');
    redirect('admin/batches.php');
}

$batchStmt = $pdo->prepare(
    'SELECT b.name, p.name AS program_name FROM distribution_batches b
     JOIN scholarship_programs p ON p.id = b.program_id WHERE b.id = ?'
);
$batchStmt->execute([$id]);
$batch = $batchStmt->fetch();
if (!$batch) {
    flash('error', 'Batch not found.');
    redirect('admin/batches.php');
}

$query = batch_vouchers_sql($id);
$columns = batch_vouchers_columns();

$stats = $pdo->prepare('SELECT status, COUNT(*) AS cnt FROM claim_vouchers WHERE batch_id = ? GROUP BY status');
$stats->execute([$id]);
$counts = ['pending' => 0, 'claimed' => 0, 'void' => 0];
foreach ($stats->fetchAll() as $row) {
    $counts[$row['status']] = (int) $row['cnt'];
}

$meta = report_meta_lines('Batch Voucher Report', [
    'Batch: ' . $batch['name'],
    'Program: ' . $batch['program_name'],
    'Pending: ' . $counts['pending'] . ' · Claimed: ' . $counts['claimed'] . ' · Void: ' . $counts['void'],
]);

stream_csv_from_query(
    $pdo,
    export_filename('batch-' . $id . '-vouchers'),
    $meta,
    $columns,
    $query['sql'],
    $query['params']
);

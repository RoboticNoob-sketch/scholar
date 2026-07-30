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
$stmt = $pdo->prepare($query['sql']);
$stmt->execute($query['params']);

$rows = [];
while ($row = $stmt->fetch()) {
    $line = [];
    foreach ($columns as $column) {
        $line[] = export_row_value($row, $column);
    }
    $rows[] = $line;
}

stream_pdf_table_report(
    'batch-' . $id . '-vouchers-' . date('Y-m-d') . '.pdf',
    'Batch Voucher Report',
    [
        'Batch: ' . $batch['name'],
        'Program: ' . $batch['program_name'],
    ],
    [
        ['label' => 'Student No.', 'width' => 80],
        ['label' => 'Scholar', 'width' => 120],
        ['label' => 'Voucher', 'width' => 110],
        ['label' => 'Amount', 'width' => 70],
        ['label' => 'Status', 'width' => 70],
        ['label' => 'Claimed At', 'width' => 95],
    ],
    $rows,
    'Total vouchers: ' . count($rows)
);

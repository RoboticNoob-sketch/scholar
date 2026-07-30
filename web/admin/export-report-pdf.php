<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role($pdo, ['admin']);

$batchId = (int) ($_GET['batch_id'] ?? 0);
$query = claims_report_sql($batchId);
$summary = claims_report_summary($pdo, $batchId);
$columns = claims_report_columns();

$batchLabel = 'All batches';
if ($batchId > 0) {
    $stmt = $pdo->prepare('SELECT name FROM distribution_batches WHERE id = ?');
    $stmt->execute([$batchId]);
    $batchLabel = (string) ($stmt->fetchColumn() ?: 'Batch #' . $batchId);
}

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
    'claims-report-' . date('Y-m-d') . '.pdf',
    'Claims Disbursement Report',
    [
        'Filter: ' . $batchLabel,
        'Total claims: ' . $summary['total_claims'],
        'Total disbursed: ' . format_money($summary['total_amount']),
    ],
    [
        ['label' => 'Claimed At', 'width' => 95],
        ['label' => 'Student No.', 'width' => 70],
        ['label' => 'Scholar', 'width' => 110],
        ['label' => 'Program', 'width' => 95],
        ['label' => 'Batch', 'width' => 95],
        ['label' => 'Amount', 'width' => 65],
        ['label' => 'Staff', 'width' => 70],
    ],
    $rows,
    'Total disbursed: ' . format_money($summary['total_amount'])
);

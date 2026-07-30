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

$meta = report_meta_lines('Claims Disbursement Report', [
    'Filter: ' . $batchLabel,
    'Total claims: ' . $summary['total_claims'],
    'Total disbursed: ' . format_money($summary['total_amount']),
]);

$footer = [
    '',
    '',
    '',
    '',
    'TOTAL',
    format_money($summary['total_amount']),
    '',
];

stream_csv_from_query(
    $pdo,
    export_filename('claims-report'),
    $meta,
    $columns,
    $query['sql'],
    $query['params'],
    $footer
);

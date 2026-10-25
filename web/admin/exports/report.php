<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_role($pdo, ['admin']);

$filters = claims_report_filters($_GET);
$query = claims_report_sql($filters);
$summary = claims_report_summary($pdo, $filters);
$columns = claims_report_columns();

$meta = report_meta_lines('Claims Disbursement Report', array_merge(
    claims_report_filter_labels($pdo, $filters),
    [
        'Total claims: ' . $summary['total_claims'],
        'Total disbursed: ' . format_money($summary['total_amount']),
    ]
));

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

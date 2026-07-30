<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

$batchId = (int) ($_GET['batch_id'] ?? 0);
$batches = $pdo->query('SELECT id, name FROM distribution_batches ORDER BY distribution_date DESC')->fetchAll();
$summary = claims_report_summary($pdo, $batchId);

render_admin_page($pdo, 'reports', 'Reports', 'admin/reports/index', [
    'batchId' => $batchId,
    'batches' => $batches,
    'summary' => $summary,
], ['datatables' => true]);

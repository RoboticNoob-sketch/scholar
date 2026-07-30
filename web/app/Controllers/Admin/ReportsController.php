<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use PDO;

final class ReportsController
{
    public static function handle(PDO $pdo): void
    {
$batchId = (int) ($_GET['batch_id'] ?? 0);
$batches = $pdo->query('SELECT id, name FROM distribution_batches ORDER BY distribution_date DESC')->fetchAll();
$summary = claims_report_summary($pdo, $batchId);

render_admin_page($pdo, 'reports', 'Reports', 'admin/reports/index', [
    'batchId' => $batchId,
    'batches' => $batches,
    'summary' => $summary,
], ['datatables' => true]);

    }
}

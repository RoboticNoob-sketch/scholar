<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use PDO;

final class ReportsController
{
    public static function handle(PDO $pdo): void
    {
        $filters = claims_report_filters($_GET);
        $batches = $pdo->query(
            'SELECT id, name, program_id FROM distribution_batches ORDER BY distribution_date DESC'
        )->fetchAll();
        $programs = $pdo->query('SELECT id, name FROM scholarship_programs ORDER BY name')->fetchAll();
        $staff = $pdo->query(
            'SELECT DISTINCT u.id, u.username FROM claims c JOIN users u ON u.id = c.staff_user_id ORDER BY u.username'
        )->fetchAll();

        render_admin_page($pdo, 'reports', 'Reports', 'admin/reports/index', [
            'filters' => $filters,
            'batches' => $batches,
            'programs' => $programs,
            'staff' => $staff,
            'summary' => claims_report_summary($pdo, $filters),
            'trend' => claims_report_trend($pdo, $filters),
            'breakdown' => claims_report_breakdown($pdo, $filters),
            'exportQuery' => http_build_query(array_filter($filters, static fn($v) => $v !== '' && $v !== 0)),
        ], ['datatables' => true]);
    }
}

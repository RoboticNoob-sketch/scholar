<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Queries\DashboardQueries;
use PDO;

final class DashboardController
{
    public static function handle(PDO $pdo): void
    {
        $stats = DashboardQueries::stats($pdo);

        render_admin_page($pdo, 'dashboard', 'Dashboard', 'admin/dashboard', [
    'kpis' => [
        [
            'label' => 'Active Scholars',
            'value' => (string) $stats['active_scholars'],
            'hint' => $stats['total_scholars'] . ' total registered',
            'icon' => 'users-round',
            'href' => 'admin/scholars.php',
            'tone' => '',
        ],
        [
            'label' => 'Active Programs',
            'value' => (string) $stats['active_programs'],
            'hint' => 'Current scholarship cycle',
            'icon' => 'graduation-cap',
            'href' => 'admin/programs.php',
            'tone' => '',
        ],
        [
            'label' => 'Open Batches',
            'value' => (string) $stats['open_batches'],
            'hint' => $stats['pending_vouchers'] . ' pending vouchers',
            'icon' => 'package-open',
            'href' => 'admin/batches.php',
            'tone' => 'warning',
        ],
        [
            'label' => 'Claims Today',
            'value' => (string) $stats['claims_today'],
            'hint' => format_money($stats['disbursed_month']) . ' this month',
            'icon' => 'badge-dollar-sign',
            'href' => 'admin/reports.php',
            'tone' => 'muted',
            'muted' => true,
        ],
    ],
    'attention' => DashboardQueries::attention($pdo),
    'trend' => DashboardQueries::claimsTrend($pdo, 14),
    'batchProgress' => DashboardQueries::openBatchProgress($pdo),
    'recentClaims' => DashboardQueries::recentClaims($pdo, 12),
    'activity' => DashboardQueries::recentActivity($pdo),
], ['datatables' => true]);

    }
}

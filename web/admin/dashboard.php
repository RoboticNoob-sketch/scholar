<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

use App\Queries\DashboardQueries;

$stats = DashboardQueries::stats($pdo);

render_admin_page($pdo, 'dashboard', 'Dashboard', 'admin/dashboard', [
    'kpis' => [
        [
            'label' => 'Active Scholars',
            'value' => (string) $stats['active_scholars'],
            'hint' => $stats['total_scholars'] . ' total registered',
            'href' => 'admin/scholars.php',
            'tone' => '',
        ],
        [
            'label' => 'Active Programs',
            'value' => (string) $stats['active_programs'],
            'hint' => 'Current scholarship cycle',
            'href' => 'admin/programs.php',
            'tone' => '',
        ],
        [
            'label' => 'Open Batches',
            'value' => (string) $stats['open_batches'],
            'hint' => $stats['pending_vouchers'] . ' pending vouchers',
            'href' => 'admin/batches.php',
            'tone' => 'warning',
        ],
        [
            'label' => 'Claims Today',
            'value' => (string) $stats['claims_today'],
            'hint' => format_money($stats['disbursed_month']) . ' this month',
            'href' => 'admin/reports.php',
            'tone' => 'muted',
            'muted' => true,
        ],
    ],
    'batchProgress' => DashboardQueries::openBatchProgress($pdo),
    'recentClaims' => DashboardQueries::recentClaims($pdo),
    'activity' => DashboardQueries::recentActivity($pdo),
]);

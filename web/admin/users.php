<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

use App\Queries\UserQueries;

$roleFilter = (string) ($_GET['role'] ?? '');
$statusFilter = (string) ($_GET['status'] ?? '');
$stats = UserQueries::stats($pdo);

render_admin_page($pdo, 'users', 'Users', 'admin/users/index', [
    'roleFilter' => $roleFilter,
    'statusFilter' => $statusFilter,
    'statsCards' => [
        ['label' => 'Total users', 'value' => $stats['total'], 'tone' => ''],
        ['label' => 'Active', 'value' => $stats['active'], 'tone' => 'accent'],
        ['label' => 'Admins', 'value' => $stats['admin'], 'tone' => ''],
        ['label' => 'Staff', 'value' => $stats['staff'], 'tone' => 'warning'],
        ['label' => 'Students', 'value' => $stats['student'], 'tone' => ''],
    ],
], ['datatables' => true]);

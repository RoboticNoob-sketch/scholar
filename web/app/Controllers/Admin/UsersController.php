<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Queries\UserQueries;
use PDO;

final class UsersController
{
    public static function handle(PDO $pdo): void
    {
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

    }
}

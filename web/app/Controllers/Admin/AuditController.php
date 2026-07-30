<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use PDO;

final class AuditController
{
    public static function handle(PDO $pdo): void
    {
render_admin_page($pdo, 'audit', 'Audit Logs', 'admin/audit/index', [], ['datatables' => true]);

    }
}

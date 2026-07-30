<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use PDO;

final class BatchesController
{
    public static function handle(PDO $pdo): void
    {
render_admin_page($pdo, 'batches', 'Distribution Batches', 'admin/batches/index', [], ['datatables' => true]);

    }
}

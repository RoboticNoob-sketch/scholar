<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use PDO;

final class ScholarsController
{
    public static function handle(PDO $pdo): void
    {
$programId = (int) ($_GET['program_id'] ?? 0);
$programs = $pdo->query('SELECT id, name FROM scholarship_programs WHERE status = "active" ORDER BY name')->fetchAll();

render_admin_page($pdo, 'scholars', 'Scholars', 'admin/scholars/index', [
    'programs' => $programs,
    'programId' => $programId,
], ['datatables' => true]);

    }
}

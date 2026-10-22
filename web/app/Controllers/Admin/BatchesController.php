<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use PDO;

final class BatchesController
{
    public static function handle(PDO $pdo): void
    {
        $programId = (int) ($_GET['program_id'] ?? 0);
        $programStmt = $pdo->prepare('SELECT name FROM scholarship_programs WHERE id = ?');
        $programStmt->execute([$programId]);
        $programName = $programId > 0 ? (string) ($programStmt->fetchColumn() ?: '') : '';

        render_admin_page($pdo, 'batches', 'Distribution Batches', 'admin/batches/index', [
            'programId' => $programName !== '' ? $programId : 0,
            'programName' => $programName,
        ], ['datatables' => true]);

    }
}

<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Queries\ProgramQueries;
use PDO;

final class ProgramFlowController
{
    public static function handle(PDO $pdo): void
    {
        require_role($pdo, ['admin']);
        $programId = (int) ($_GET['id'] ?? 0);
        $program = ProgramQueries::flow($pdo, $programId);

        if (!$program) {
            flash('error', 'Program not found.');
            redirect('admin/programs.php');
        }

        render_admin_page($pdo, 'programs', $program['name'] . ' Money Flow', 'admin/programs/flow', [
            'program' => $program,
            'batches' => ProgramQueries::batchFlow($pdo, $programId),
        ]);
    }
}
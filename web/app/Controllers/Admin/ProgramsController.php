<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use PDO;
use App\Queries\ProgramQueries;
use App\Services\ApprovalService;

final class ProgramsController
{
    public static function handle(PDO $pdo): void
    {
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_role($pdo, ['admin']);
    verify_csrf_token();
    $id = (int) ($_POST['id'] ?? 0);
    $payload = [
        'name' => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'amount' => (float) ($_POST['amount'] ?? 0),
        'total_budget' => max(0, (float) ($_POST['total_budget'] ?? 0)),
        'academic_year' => trim($_POST['academic_year'] ?? ''),
        'semester' => trim($_POST['semester'] ?? ''),
        'status' => $_POST['status'] ?? 'active',
        'scholar_ids' => array_values(array_unique(array_filter(array_map('intval', $_POST['scholar_ids'] ?? []), static fn (int $scholarId): bool => $scholarId > 0))),
    ];

    try {
        ApprovalService::submit($pdo, $id > 0 ? 'program_update' : 'program_create', $payload, (int) $user['id'], $id > 0 ? $id : null);
        flash('success', 'Program request submitted. It will take effect after an approver reviews it.');
    } catch (\Throwable $error) {
        flash('error', $error->getMessage());
        redirect($id > 0 ? 'admin/program-form.php?id=' . $id : 'admin/program-form.php');
    }
    redirect('admin/requests.php');
}

render_admin_page($pdo, 'programs', 'Programs', 'admin/programs/index', [
    'overview' => ProgramQueries::overview($pdo),
], ['datatables' => true]);

    }
}

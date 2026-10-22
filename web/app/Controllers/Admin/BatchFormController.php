<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use PDO;

final class BatchFormController
{
    public static function handle(PDO $pdo): void
    {
$user = require_role($pdo, ['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $programId = (int) ($_POST['program_id'] ?? 0);
    $printAfterCreate = isset($_POST['create_and_print']);
    $payload = [
        'program_id' => $programId,
        'name' => trim($_POST['name'] ?? ''),
        'distribution_date' => $_POST['distribution_date'] ?? '',
        'venue' => trim($_POST['venue'] ?? ''),
        'generate_vouchers' => isset($_POST['generate_vouchers']) || $printAfterCreate,
        'print_after_approval' => $printAfterCreate,
    ];
    try {
        \App\Services\ApprovalService::submit($pdo, 'batch_create', $payload, (int) $user['id']);
        flash('success', 'Batch request submitted. It will be created after an approver reviews it.');
    } catch (\Throwable $error) {
        flash('error', $error->getMessage());
        redirect('admin/batch-form.php');
    }
    redirect('admin/requests.php');
}

$programs = $pdo->query(
    'SELECT p.id, p.name, p.amount, p.total_budget,
            (SELECT COUNT(*) FROM enrollments e WHERE e.program_id = p.id AND e.status = "active") AS enrolled_count,
            (SELECT COALESCE(SUM(v.amount), 0) FROM claim_vouchers v JOIN distribution_batches b ON b.id = v.batch_id WHERE b.program_id = p.id AND v.status IN ("pending", "claimed")) AS committed_amount
     FROM scholarship_programs p
     WHERE p.status = "active"
     ORDER BY p.name'
)->fetchAll();

render_admin_layout($pdo, 'batches', 'Create Batch', function () use ($programs): void {
    echo '<div class="page-header"><h1 class="page-title">Create Distribution Batch</h1></div>';
    echo '<form method="post" class="card form-grid form-wide batch-request-form" data-batch-budget-form>';
    echo csrf_field();
    echo '<div><label class="field-label">Program</label><select class="select" name="program_id" required>';
    foreach ($programs as $p) {
        $disabled = (float) $p['total_budget'] <= 0 ? ' disabled' : '';
        echo '<option value="' . (int) $p['id'] . '" data-budget="' . e((string) $p['total_budget']) . '" data-committed="' . e((string) $p['committed_amount']) . '" data-award="' . e((string) $p['amount']) . '" data-enrolled="' . (int) $p['enrolled_count'] . '"' . $disabled . '>' . e($p['name'] . ' (' . format_money((float) $p['amount']) . ' per scholar)') . '</option>';
    }
    echo '</select></div>';
    echo '<div class="batch-budget-status" data-batch-budget-status role="status" aria-live="polite"></div>';
    echo '<div><label class="field-label">Batch name</label><input class="input" name="name" placeholder="1st Sem AY 2025-2026 Distribution" required></div>';
    echo '<div class="form-row">';
    echo '<div><label class="field-label">Distribution date</label><input class="input" type="date" name="distribution_date" required></div>';
    echo '<div><label class="field-label">Venue</label><input class="input" name="venue" placeholder="Main Campus Gym" required></div>';
    echo '</div>';
    echo '<label class="checkbox-row"><input type="checkbox" name="generate_vouchers" checked> Generate vouchers when the batch request is approved</label>';
    echo '<p class="form-hint">An approver must review the batch before it is created. Budget is rechecked on approval; printing starts only after approval.</p>';
    echo '<div class="form-actions">';
    echo '<button class="btn btn-secondary btn-md" type="submit" name="create_batch" value="1" data-submit-batch disabled>SUBMIT FOR APPROVAL</button>';
    echo '<button class="btn btn-primary btn-md" type="submit" name="create_and_print" value="1" data-submit-print disabled><i data-lucide="printer"></i> SUBMIT &amp; PRINT AFTER APPROVAL</button>';
    echo '</div>';
    echo '</form>';
});

    }
}

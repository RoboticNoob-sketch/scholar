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
    $programId = (int) ($_POST['program_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $date = $_POST['distribution_date'] ?? '';
    $venue = trim($_POST['venue'] ?? '');
    $generate = isset($_POST['generate_vouchers']);

    $pdo->prepare(
        'INSERT INTO distribution_batches (program_id, name, distribution_date, venue, status, created_by)
         VALUES (?, ?, ?, ?, "draft", ?)'
    )->execute([$programId, $name, $date, $venue, $user['id']]);
    $batchId = (int) $pdo->lastInsertId();

    if ($generate) {
        $count = generate_missing_vouchers_for_batch($pdo, $batchId);
        audit_log($pdo, (int) $user['id'], 'vouchers_generated', 'distribution_batch', $batchId, "$count vouchers generated");
    }

    flash('success', 'Batch created. Open the batch when ready so scholars see vouchers on mobile.');
    redirect('admin/batch-view.php?id=' . $batchId);
}

$programs = $pdo->query('SELECT id, name, amount FROM scholarship_programs WHERE status="active" ORDER BY name')->fetchAll();

render_admin_layout($pdo, 'batches', 'Create Batch', function () use ($programs): void {
    echo '<div class="page-header"><h1 class="page-title">Create Distribution Batch</h1></div>';
    echo '<form method="post" class="card form-grid form-narrow">';
    echo '<div><label class="field-label">Program</label><select class="select" name="program_id" required>';
    foreach ($programs as $p) {
        echo '<option value="' . (int) $p['id'] . '">' . e($p['name'] . ' (' . format_money((float) $p['amount']) . ')') . '</option>';
    }
    echo '</select></div>';
    echo '<div><label class="field-label">Batch name</label><input class="input" name="name" placeholder="1st Sem AY 2025-2026 Distribution" required></div>';
    echo '<div class="form-row">';
    echo '<div><label class="field-label">Distribution date</label><input class="input" type="date" name="distribution_date" required></div>';
    echo '<div><label class="field-label">Venue</label><input class="input" name="venue" placeholder="Main Campus Gym" required></div>';
    echo '</div>';
    echo '<label class="checkbox-row"><input type="checkbox" name="generate_vouchers" checked> Generate vouchers for all enrolled scholars</label>';
    echo '<button class="btn btn-primary btn-md" type="submit">CREATE BATCH</button>';
    echo '</form>';
});

    }
}

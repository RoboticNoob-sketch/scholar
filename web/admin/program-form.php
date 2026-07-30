<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

$id = (int) ($_GET['id'] ?? 0);
$program = null;
$enrolledIds = [];
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM scholarship_programs WHERE id = ?');
    $stmt->execute([$id]);
    $program = $stmt->fetch();
    $stmt = $pdo->prepare('SELECT scholar_id FROM enrollments WHERE program_id = ? AND status = "active"');
    $stmt->execute([$id]);
    $enrolledIds = array_column($stmt->fetchAll(), 'scholar_id');
}

$scholars = $pdo->query('SELECT id, student_no, first_name, last_name FROM scholars WHERE status="active" ORDER BY last_name')->fetchAll();

render_admin_layout($pdo, 'programs', $program ? 'Edit Program' : 'Add Program', function () use ($program, $scholars, $enrolledIds, $id): void {
    echo '<div class="page-header"><h1 class="page-title">' . ($program ? 'Edit Program' : 'Add Program') . '</h1></div>';
    echo '<form method="post" action="' . base_url('admin/programs.php') . '" class="card form-grid form-wide">';
    if ($id > 0) {
        echo '<input type="hidden" name="id" value="' . $id . '">';
    }
    $defaults = $program ?: ['name' => '', 'description' => '', 'amount' => '0', 'academic_year' => '2025-2026', 'semester' => '1st Semester', 'status' => 'active'];
    echo '<div><label class="field-label">Program name</label><input class="input" name="name" value="' . e($defaults['name']) . '" required></div>';
    echo '<div><label class="field-label">Description</label><textarea class="textarea" name="description">' . e($defaults['description']) . '</textarea></div>';
    echo '<div class="form-row">';
    echo '<div><label class="field-label">Amount</label><input class="input" type="number" step="0.01" name="amount" value="' . e((string) $defaults['amount']) . '" required></div>';
    echo '<div><label class="field-label">Academic year</label><input class="input" name="academic_year" value="' . e($defaults['academic_year']) . '" required></div>';
    echo '<div><label class="field-label">Semester</label><input class="input" name="semester" value="' . e($defaults['semester']) . '" required></div>';
    echo '</div>';
    echo '<div><label class="field-label">Status</label><select class="select" name="status">';
    foreach (['active', 'inactive'] as $s) {
        $sel = $defaults['status'] === $s ? ' selected' : '';
        echo '<option value="' . $s . '"' . $sel . '>' . ucfirst($s) . '</option>';
    }
    echo '</select></div>';
    echo '<div class="card-title">Enroll scholars</div>';
    echo '<div class="checklist">';
    foreach ($scholars as $s) {
        $checked = in_array((string) $s['id'], array_map('strval', $enrolledIds), true) ? ' checked' : '';
        echo '<label class="checklist-item">';
        echo '<input type="checkbox" name="scholar_ids[]" value="' . (int) $s['id'] . '"' . $checked . '>';
        echo e($s['student_no'] . ' — ' . scholar_full_name($s));
        echo '</label>';
    }
    echo '</div>';
    echo '<button class="btn btn-primary btn-md" type="submit">SAVE PROGRAM</button>';
    echo '</form>';
});

<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use PDO;

final class ProgramFormController
{
    public static function handle(PDO $pdo): void
    {
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

$scholars = $pdo->query('SELECT id, student_no, first_name, last_name, course, year_level, photo_path FROM scholars WHERE status="active" ORDER BY last_name, first_name')->fetchAll();

render_admin_layout($pdo, 'programs', $program ? 'Edit Program' : 'Add Program', function () use ($program, $scholars, $enrolledIds, $id): void {
    echo '<div class="page-header"><h1 class="page-title">' . ($program ? 'Edit Program' : 'Add Program') . '</h1></div>';
    echo '<form method="post" action="' . base_url('admin/programs.php') . '" class="card form-grid form-wide program-form">';
    echo csrf_field();
    if ($id > 0) {
        echo '<input type="hidden" name="id" value="' . $id . '">';
    }
    $defaults = $program ?: ['name' => '', 'description' => '', 'amount' => '0', 'total_budget' => '0', 'academic_year' => '2025-2026', 'semester' => '1st Semester', 'status' => 'active'];
    echo '<div><label class="field-label">Program name</label><input class="input" name="name" value="' . e($defaults['name']) . '" required></div>';
    echo '<div><label class="field-label">Description</label><textarea class="textarea" name="description">' . e($defaults['description']) . '</textarea></div>';
    echo '<div class="form-row">';
    echo '<div><label class="field-label">Award per scholar</label><input class="input" type="number" min="0" step="0.01" name="amount" value="' . e((string) $defaults['amount']) . '" required></div>';
    echo '<div><label class="field-label">Total program budget</label><input class="input" type="number" min="0" step="0.01" name="total_budget" value="' . e((string) $defaults['total_budget']) . '" required></div>';
    echo '<div><label class="field-label">Academic year</label><input class="input" name="academic_year" value="' . e($defaults['academic_year']) . '" required></div>';
    echo '<div><label class="field-label">Semester</label><input class="input" name="semester" value="' . e($defaults['semester']) . '" required></div>';
    echo '</div>';
    echo '<div><label class="field-label">Status</label><select class="select" name="status">';
    foreach (['active', 'inactive'] as $s) {
        $sel = $defaults['status'] === $s ? ' selected' : '';
        echo '<option value="' . $s . '"' . $sel . '>' . ucfirst($s) . '</option>';
    }
    echo '</select></div>';
    echo '<section class="program-scholar-picker" data-program-scholar-picker aria-labelledby="program-scholar-picker-title">';
    echo '<div class="scholar-picker-heading"><div><h2 id="program-scholar-picker-title">Select scholars</h2><p>Choose who is enrolled in this program. Already generated batch vouchers remain valid; manage them from the batch.</p></div><span class="scholar-picker-count" data-scholar-selected aria-live="polite">0 selected</span></div>';
    echo '<div class="scholar-picker-toolbar">';
    echo '<label class="scholar-picker-search"><i data-lucide="search"></i><input type="search" data-scholar-search placeholder="Search name, student number, or course" aria-label="Search scholars"></label>';
    echo '<span class="scholar-picker-visible" data-scholar-visible></span>';
    echo '<div class="scholar-picker-filters" role="group" aria-label="Filter scholars">';
    echo '<button class="is-active" type="button" data-scholar-filter="all" aria-pressed="true">All <span data-filter-count="all">0</span></button>';
    echo '<button type="button" data-scholar-filter="selected" aria-pressed="false">Selected <span data-filter-count="selected">0</span></button>';
    echo '<button type="button" data-scholar-filter="unselected" aria-pressed="false">Not selected <span data-filter-count="unselected">0</span></button>';
    echo '</div>';
    echo '<button class="btn btn-outline btn-sm" type="button" data-scholar-select-visible><i data-lucide="list-checks"></i> Select visible</button>';
    echo '<button class="btn btn-ghost btn-sm" type="button" data-scholar-clear>Clear selected</button>';
    echo '</div>';
    echo '<div class="scholar-picker-list" data-scholar-list>';
    foreach ($scholars as $s) {
        $checked = in_array((string) $s['id'], array_map('strval', $enrolledIds), true) ? ' checked' : '';
        $name = scholar_full_name($s);
        $nameParts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = strtoupper(substr($nameParts[0] ?? '', 0, 1) . substr($nameParts[count($nameParts) - 1] ?? '', 0, 1));
        $photoUrl = scholar_photo_url($s['photo_path'] ?? null, (int) $s['id']);
        echo '<label class="scholar-picker-row" data-scholar-row>';
        echo '<span class="scholar-picker-avatar"><span>' . e($initials) . '</span>';
        if ($photoUrl) {
            echo '<img src="' . e($photoUrl) . '" alt="" loading="lazy">';
        }
        echo '</span>';
        echo '<input class="program-scholar-option" type="checkbox" name="scholar_ids[]" value="' . (int) $s['id'] . '"' . $checked . '>';
        echo '<span class="scholar-picker-person"><strong>' . e($name) . '</strong><span>' . e($s['student_no']) . '</span></span>';
        echo '<span class="scholar-picker-course">' . e(trim(($s['course'] ?? '') . ' · ' . ($s['year_level'] ?? ''), ' ·')) . '</span>';
        echo '</label>';
    }
    echo '</div>';
    echo '<div class="scholar-picker-empty" data-scholar-empty hidden>No active scholars match this search.</div>';
    echo '</section>';
    echo '<p class="form-hint">Program creation and changes take effect only after an approver reviews them. The submitter cannot approve their own request.</p>';
    echo '<button class="btn btn-primary btn-md" type="submit">SUBMIT FOR APPROVAL</button>';
    echo '</form>';
});

    }
}

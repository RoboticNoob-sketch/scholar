<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role($pdo, ['admin']);
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $amount = (float) ($_POST['amount'] ?? 0);
    $academicYear = trim($_POST['academic_year'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $scholarIds = array_map('intval', $_POST['scholar_ids'] ?? []);

    if ($id > 0) {
        $pdo->prepare(
            'UPDATE scholarship_programs SET name=?, description=?, amount=?, academic_year=?, semester=?, status=? WHERE id=?'
        )->execute([$name, $description, $amount, $academicYear, $semester, $status, $id]);
        $message = 'Program updated.';
    } else {
        $pdo->prepare(
            'INSERT INTO scholarship_programs (name, description, amount, academic_year, semester, status) VALUES (?,?,?,?,?,?)'
        )->execute([$name, $description, $amount, $academicYear, $semester, $status]);
        $id = (int) $pdo->lastInsertId();
        $message = 'Program created.';
    }

    if ($id > 0) {
        $pdo->prepare('UPDATE enrollments SET status="removed" WHERE program_id=? AND status="active"')->execute([$id]);
    }

    if ($scholarIds) {
        $insert = $pdo->prepare(
            'INSERT INTO enrollments (scholar_id, program_id, status) VALUES (?, ?, "active")
             ON DUPLICATE KEY UPDATE status="active"'
        );
        foreach ($scholarIds as $sid) {
            $insert->execute([$sid, $id]);
        }
    }

    if ($id > 0) {
        $voucherCount = sync_vouchers_for_program($pdo, $id);
        if ($voucherCount > 0) {
            $message .= " $voucherCount voucher(s) added to open distribution batch(es).";
        }
    }

    flash('success', $message);
    redirect('admin/programs.php');
}

$page = pagination_page();
$sql = 'SELECT p.*, COUNT(DISTINCT e.scholar_id) AS enrolled_count
        FROM scholarship_programs p
        LEFT JOIN enrollments e ON e.program_id = p.id AND e.status = "active"
        GROUP BY p.id ORDER BY p.name';
$result = paginate($pdo, $sql, [], $page);
$programs = $result['rows'];

render_admin_layout($pdo, 'programs', 'Programs', function () use ($programs, $result): void {
    echo '<div class="breadcrumb">Admin / Programs</div>';
    echo '<div class="page-header"><h1 class="page-title">Scholarship Programs</h1>';
    echo '<a class="btn btn-primary btn-sm" href="' . base_url('admin/program-form.php') . '">ADD PROGRAM</a></div>';

    echo '<div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr>';
    echo '<th>Program</th><th>Amount</th><th>Academic Year</th><th>Semester</th><th>Enrolled</th><th>Status</th><th>Actions</th>';
    echo '</tr></thead><tbody>';
    foreach ($programs as $p) {
        echo '<tr>';
        echo '<td>' . e($p['name']) . '</td>';
        echo '<td>' . e(format_money((float) $p['amount'])) . '</td>';
        echo '<td>' . e($p['academic_year']) . '</td>';
        echo '<td>' . e($p['semester']) . '</td>';
        echo '<td>' . (int) $p['enrolled_count'] . '</td>';
        echo '<td><span class="badge ' . badge_class($p['status']) . '">' . e($p['status']) . '</span></td>';
        echo '<td><a class="link-action" href="' . base_url('admin/program-form.php?id=' . (int) $p['id']) . '">Edit</a></td>';
        echo '</tr>';
    }
    if (!$programs) {
        echo '<tr><td colspan="7"><div class="empty-state">No programs found.</div></td></tr>';
    }
    echo '</tbody></table></div></div>';
    render_table_footer('admin/programs.php', $result, 'programs');
});

<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

$search = trim($_GET['q'] ?? '');
$programId = (int) ($_GET['program_id'] ?? 0);

$sql = 'SELECT s.*, GROUP_CONCAT(DISTINCT p.name SEPARATOR ", ") AS programs
        FROM scholars s
        LEFT JOIN enrollments e ON e.scholar_id = s.id AND e.status = "active"
        LEFT JOIN scholarship_programs p ON p.id = e.program_id
        WHERE 1=1';
$params = [];

if ($search !== '') {
    $sql .= ' AND (s.student_no LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)';
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like]);
}
if ($programId > 0) {
    $sql .= ' AND EXISTS (SELECT 1 FROM enrollments e2 WHERE e2.scholar_id = s.id AND e2.program_id = ? AND e2.status = "active")';
    $params[] = $programId;
}
$sql .= ' GROUP BY s.id ORDER BY s.last_name, s.first_name';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$scholars = $stmt->fetchAll();
$programs = $pdo->query('SELECT id, name FROM scholarship_programs WHERE status = "active" ORDER BY name')->fetchAll();

render_admin_layout($pdo, 'scholars', 'Scholars', function () use ($scholars, $programs, $search, $programId): void {
    echo '<div class="breadcrumb">Admin / Scholars</div>';
    echo '<div class="page-header"><h1 class="page-title">Scholars</h1>';
    echo '<a class="btn btn-primary btn-sm" href="' . base_url('admin/scholar-form.php') . '">ADD SCHOLAR</a></div>';

    echo '<form class="filter-bar" method="get">';
    echo '<div><label class="field-label">Search</label><input class="input" name="q" placeholder="Name or student no." value="' . e($search) . '"></div>';
    echo '<div><label class="field-label">Program</label><select class="select" name="program_id"><option value="0">All programs</option>';
    foreach ($programs as $p) {
        $sel = $programId === (int) $p['id'] ? ' selected' : '';
        echo '<option value="' . (int) $p['id'] . '"' . $sel . '>' . e($p['name']) . '</option>';
    }
    echo '</select></div><button class="btn btn-secondary btn-sm" type="submit">FILTER</button></form>';

    echo '<div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr>';
    echo '<th>Student No.</th><th>Name</th><th>Program</th><th>Status</th><th>Actions</th>';
    echo '</tr></thead><tbody>';
    foreach ($scholars as $s) {
        echo '<tr>';
        echo '<td>' . e($s['student_no']) . '</td>';
        echo '<td>' . e(scholar_full_name($s)) . '</td>';
        echo '<td>' . e($s['programs'] ?: '—') . '</td>';
        echo '<td><span class="badge ' . badge_class($s['status']) . '">' . e($s['status']) . '</span></td>';
        echo '<td><a class="link-action" href="' . base_url('admin/scholar-view.php?id=' . (int) $s['id']) . '">View</a></td>';
        echo '</tr>';
    }
    if (!$scholars) {
        echo '<tr><td colspan="5"><div class="empty-state">No scholars found.</div></td></tr>';
    }
    echo '</tbody></table></div></div>';
    echo '<div class="table-meta">Showing ' . count($scholars) . ' scholars</div>';
});

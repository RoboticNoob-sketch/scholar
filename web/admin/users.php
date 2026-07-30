<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

$search = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$sql = 'SELECT u.*, s.id AS scholar_id, s.student_no, s.first_name, s.last_name
        FROM users u
        LEFT JOIN scholars s ON s.user_id = u.id
        WHERE 1=1';
$params = [];

if ($search !== '') {
    $sql .= ' AND u.username LIKE ?';
    $params[] = '%' . $search . '%';
}
if (in_array($roleFilter, ['admin', 'staff', 'student'], true)) {
    $sql .= ' AND u.role = ?';
    $params[] = $roleFilter;
}
if (in_array($statusFilter, ['active', 'inactive'], true)) {
    $sql .= ' AND u.status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY FIELD(u.role, "admin", "staff", "student"), u.username';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$counts = $pdo->query(
    'SELECT role, status, COUNT(*) AS cnt FROM users GROUP BY role, status'
)->fetchAll();
$stats = ['total' => 0, 'active' => 0, 'admin' => 0, 'staff' => 0, 'student' => 0];
foreach ($counts as $row) {
    $stats['total'] += (int) $row['cnt'];
    if ($row['status'] === 'active') {
        $stats['active'] += (int) $row['cnt'];
        $stats[$row['role']] = ($stats[$row['role']] ?? 0) + (int) $row['cnt'];
    }
}

render_admin_layout($pdo, 'users', 'Users', function () use ($users, $search, $roleFilter, $statusFilter, $stats): void {
    echo '<div class="breadcrumb">Admin / Users</div>';
    echo '<div class="page-header"><h1 class="page-title">User Accounts</h1>';
    echo '<a class="btn btn-primary btn-sm" href="' . base_url('admin/user-form.php') . '">ADD USER</a></div>';

    echo '<div class="kpi-grid">';
    foreach ([
        ['Total users', $stats['total'], ''],
        ['Active', $stats['active'], 'accent'],
        ['Admins', $stats['admin'], ''],
        ['Staff', $stats['staff'], 'warning'],
        ['Students', $stats['student'], ''],
    ] as [$label, $value, $tone]) {
        $toneClass = $tone ? ' ' . e($tone) : '';
        echo '<div class="card kpi-card' . $toneClass . '"><div class="label">' . e($label) . '</div>';
        echo '<div class="value">' . (int) $value . '</div></div>';
    }
    echo '</div>';

    echo '<form class="filter-bar" method="get">';
    echo '<div><label class="field-label">Search</label><input class="input" name="q" placeholder="Username" value="' . e($search) . '"></div>';
    echo '<div><label class="field-label">Role</label><select class="select" name="role">';
    echo '<option value="">All roles</option>';
    foreach (['admin', 'staff', 'student'] as $r) {
        $sel = $roleFilter === $r ? ' selected' : '';
        echo '<option value="' . $r . '"' . $sel . '>' . e(role_label($r)) . '</option>';
    }
    echo '</select></div>';
    echo '<div><label class="field-label">Status</label><select class="select" name="status">';
    echo '<option value="">All statuses</option>';
    foreach (['active', 'inactive'] as $s) {
        $sel = $statusFilter === $s ? ' selected' : '';
        echo '<option value="' . $s . '"' . $sel . '>' . ucfirst($s) . '</option>';
    }
    echo '</select></div>';
    echo '<button class="btn btn-secondary btn-sm" type="submit">FILTER</button></form>';

    echo '<div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr>';
    echo '<th>Username</th><th>Role</th><th>Status</th><th>Linked profile</th><th>Created</th><th>Actions</th>';
    echo '</tr></thead><tbody>';
    foreach ($users as $u) {
        $linked = '—';
        if ($u['scholar_id']) {
            $linked = e($u['student_no'] . ' · ' . scholar_full_name($u));
        } elseif ($u['role'] === 'student') {
            $linked = '<span style="color:var(--text-secondary)">No scholar record</span>';
        }

        echo '<tr>';
        echo '<td><strong>' . e($u['username']) . '</strong></td>';
        echo '<td><span class="badge ' . badge_class($u['role']) . '">' . e(role_label($u['role'])) . '</span></td>';
        echo '<td><span class="badge ' . badge_class($u['status']) . '">' . e($u['status']) . '</span></td>';
        echo '<td>' . $linked . '</td>';
        echo '<td>' . e(format_date($u['created_at'])) . '</td>';
        echo '<td><a class="link-action" href="' . base_url('admin/user-form.php?id=' . (int) $u['id']) . '">Edit</a></td>';
        echo '</tr>';
    }
    if (!$users) {
        echo '<tr><td colspan="6"><div class="empty-state">No users found.</div></td></tr>';
    }
    echo '</tbody></table></div></div>';
    echo '<div class="table-meta">Showing ' . count($users) . ' users · Student logins are also created from the Scholars form</div>';
});

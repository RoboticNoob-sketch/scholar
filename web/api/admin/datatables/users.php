<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';

use App\Queries\UserQueries;

$input = require_admin_datatables($pdo);
$role = (string) ($_GET['role'] ?? $_POST['role'] ?? '');
$status = (string) ($_GET['status'] ?? $_POST['status'] ?? '');
$result = UserQueries::datatables($pdo, $input, $role, $status);

$data = [];
foreach ($result['rows'] as $row) {
    $linked = '—';
    if ($row['scholar_id']) {
        $linked = e($row['student_no'] . ' · ' . scholar_full_name($row));
    } elseif ($row['role'] === 'student') {
        $linked = '<span style="color:var(--text-secondary)">No scholar record</span>';
    }

    $isSystem = !empty($row['is_system']);
    $data[] = [
        '<strong>' . e($row['username']) . '</strong>' . ($isSystem ? ' <span class="badge badge-info">System</span>' : ''),
        '<span class="badge ' . e(badge_class($row['role'])) . '">' . ($isSystem ? 'System Admin' : e(role_label($row['role']))) . '</span>',
        '<span class="badge ' . e(badge_class($row['status'])) . '">' . e($row['status']) . '</span>',
        $linked,
        e(format_date($row['created_at'])),
        $isSystem
            ? '<span style="color:var(--text-secondary)">Protected</span>'
            : '<a class="link-action" href="' . e(base_url('admin/user-form.php?id=' . (int) $row['id'])) . '">Edit</a>',
    ];
}

datatables_json($result['draw'], $result['total'], $result['filtered'], $data);

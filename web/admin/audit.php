<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

$page = pagination_page();
$sql = 'SELECT a.*, u.username FROM audit_logs a
        LEFT JOIN users u ON u.id = a.user_id
        ORDER BY a.created_at DESC';
$result = paginate($pdo, $sql, [], $page);
$logs = $result['rows'];

render_admin_layout($pdo, 'audit', 'Audit Logs', function () use ($logs, $result): void {
    echo '<div class="breadcrumb">Admin / Audit Logs</div>';
    echo '<div class="page-header"><h1 class="page-title">Audit Logs</h1></div>';
    echo '<div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr>';
    echo '<th>Time</th><th>User</th><th>Action</th><th>Details</th><th>IP</th>';
    echo '</tr></thead><tbody>';
    foreach ($logs as $log) {
        echo '<tr>';
        echo '<td>' . e(format_datetime($log['created_at'])) . '</td>';
        echo '<td>' . e($log['username'] ?? 'System') . '</td>';
        echo '<td><span class="badge badge-accent">' . e(str_replace('_', ' ', $log['action'])) . '</span></td>';
        echo '<td>' . e($log['details'] ?? ($log['entity_type'] . ' #' . $log['entity_id'])) . '</td>';
        echo '<td>' . e($log['ip_address'] ?? '—') . '</td>';
        echo '</tr>';
    }
    if (!$logs) {
        echo '<tr><td colspan="5"><div class="empty-state">No audit entries found.</div></td></tr>';
    }
    echo '</tbody></table></div></div>';
    render_table_footer('admin/audit.php', $result, 'log entries');
});

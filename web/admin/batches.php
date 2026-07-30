<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

$page = pagination_page();
$sql = 'SELECT b.*, p.name AS program_name
        FROM distribution_batches b
        JOIN scholarship_programs p ON p.id = b.program_id
        ORDER BY b.distribution_date DESC';
$result = paginate($pdo, $sql, [], $page);
$batches = $result['rows'];

render_admin_layout($pdo, 'batches', 'Distribution Batches', function () use ($batches, $result): void {
    echo '<div class="breadcrumb">Admin / Distribution Batches</div>';
    echo '<div class="page-header"><h1 class="page-title">Distribution Batches</h1>';
    echo '<a class="btn btn-primary btn-sm" href="' . base_url('admin/batch-form.php') . '">CREATE BATCH</a></div>';

    echo '<div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr>';
    echo '<th>Batch</th><th>Program</th><th>Date</th><th>Venue</th><th>Status</th><th>Actions</th>';
    echo '</tr></thead><tbody>';
    foreach ($batches as $b) {
        echo '<tr>';
        echo '<td>' . e($b['name']) . '</td>';
        echo '<td>' . e($b['program_name']) . '</td>';
        echo '<td>' . e(format_date($b['distribution_date'])) . '</td>';
        echo '<td>' . e($b['venue']) . '</td>';
        echo '<td><span class="badge ' . badge_class($b['status']) . '">' . e($b['status']) . '</span></td>';
        echo '<td><a class="link-action" href="' . base_url('admin/batch-view.php?id=' . (int) $b['id']) . '">View</a></td>';
        echo '</tr>';
    }
    if (!$batches) {
        echo '<tr><td colspan="6"><div class="empty-state">No batches found.</div></td></tr>';
    }
    echo '</tbody></table></div></div>';
    render_table_footer('admin/batches.php', $result, 'batches');
});

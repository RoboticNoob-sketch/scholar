<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

$batchId = (int) ($_GET['batch_id'] ?? 0);
$page = pagination_page();
$batches = $pdo->query('SELECT id, name FROM distribution_batches ORDER BY distribution_date DESC')->fetchAll();

$query = claims_report_sql($batchId);
$summary = claims_report_summary($pdo, $batchId);
$result = paginate($pdo, $query['sql'], $query['params'], $page);
$rows = $result['rows'];
$filterKeys = ['batch_id'];

render_admin_layout($pdo, 'reports', 'Reports', function () use ($rows, $batches, $batchId, $summary, $result, $filterKeys): void {
    echo '<div class="breadcrumb">Admin / Reports</div>';
    echo '<div class="page-header"><h1 class="page-title">Reports</h1>';
    echo '<div class="table-actions btn-group">';
    echo '<a class="btn btn-outline btn-sm" href="' . base_url('admin/export-report.php?batch_id=' . $batchId) . '">EXPORT CSV</a>';
    echo '<a class="btn btn-outline btn-sm" href="' . base_url('admin/export-report-pdf.php?batch_id=' . $batchId) . '">EXPORT PDF</a>';
    echo '</div></div>';

    echo '<form class="filter-bar" method="get">';
    echo '<div><label class="field-label">Batch</label><select class="select" name="batch_id"><option value="0">All batches</option>';
    foreach ($batches as $b) {
        $sel = $batchId === (int) $b['id'] ? ' selected' : '';
        echo '<option value="' . (int) $b['id'] . '"' . $sel . '>' . e($b['name']) . '</option>';
    }
    echo '</select></div><button class="btn btn-secondary btn-sm" type="submit">FILTER</button></form>';

    echo '<div class="kpi-grid">';
    echo '<div class="card kpi-card"><div class="label">Total Claims</div><div class="value">' . $summary['total_claims'] . '</div></div>';
    echo '<div class="card kpi-card"><div class="label">Total Disbursed</div><div class="value">' . e(format_money($summary['total_amount'])) . '</div></div>';
    echo '</div>';

    echo '<div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr>';
    echo '<th>Date</th><th>Scholar</th><th>Program</th><th>Batch</th><th>Amount</th><th>Staff</th>';
    echo '</tr></thead><tbody>';
    foreach ($rows as $r) {
        echo '<tr>';
        echo '<td>' . e(format_datetime($r['claimed_at'])) . '</td>';
        echo '<td>' . e(scholar_full_name($r) . ' (' . $r['student_no'] . ')') . '</td>';
        echo '<td>' . e($r['program_name']) . '</td>';
        echo '<td>' . e($r['batch_name']) . '</td>';
        echo '<td>' . e(format_money((float) $r['amount'])) . '</td>';
        echo '<td>' . e($r['staff_name']) . '</td>';
        echo '</tr>';
    }
    if (!$rows) {
        echo '<tr><td colspan="6"><div class="empty-state">No claims recorded for this filter.</div></td></tr>';
    }
    echo '</tbody></table></div></div>';
    render_table_footer('admin/reports.php', $result, 'claims', $filterKeys);
});

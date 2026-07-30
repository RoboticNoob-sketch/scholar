<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

$batchId = (int) ($_GET['batch_id'] ?? 0);
$batches = $pdo->query('SELECT id, name FROM distribution_batches ORDER BY distribution_date DESC')->fetchAll();

$sql = 'SELECT c.claimed_at, v.amount, v.voucher_code, v.status, b.name AS batch_name, p.name AS program_name,
               s.student_no, s.first_name, s.last_name, u.username AS staff_name
        FROM claims c
        JOIN claim_vouchers v ON v.id = c.voucher_id
        JOIN distribution_batches b ON b.id = v.batch_id
        JOIN scholarship_programs p ON p.id = b.program_id
        JOIN scholars s ON s.id = v.scholar_id
        JOIN users u ON u.id = c.staff_user_id
        WHERE 1=1';
$params = [];
if ($batchId > 0) {
    $sql .= ' AND b.id = ?';
    $params[] = $batchId;
}
$sql .= ' ORDER BY c.claimed_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$totalAmount = array_sum(array_map(fn ($r) => (float) $r['amount'], $rows));

render_admin_layout($pdo, 'reports', 'Reports', function () use ($rows, $batches, $batchId, $totalAmount): void {
    echo '<div class="breadcrumb">Admin / Reports</div>';
    echo '<div class="page-header"><h1 class="page-title">Reports</h1>';
    echo '<div class="table-actions">';
    echo '<a class="btn btn-outline btn-sm" href="' . base_url('admin/export-report.php?batch_id=' . $batchId) . '">EXPORT CSV</a>';
    echo '</div></div>';

    echo '<form class="filter-bar" method="get">';
    echo '<div><label class="field-label">Batch</label><select class="select" name="batch_id"><option value="0">All batches</option>';
    foreach ($batches as $b) {
        $sel = $batchId === (int) $b['id'] ? ' selected' : '';
        echo '<option value="' . (int) $b['id'] . '"' . $sel . '>' . e($b['name']) . '</option>';
    }
    echo '</select></div><button class="btn btn-secondary btn-sm" type="submit">FILTER</button></form>';

    echo '<div class="kpi-grid">';
    echo '<div class="card kpi-card"><div class="label">Total Claims</div><div class="value">' . count($rows) . '</div></div>';
    echo '<div class="card kpi-card"><div class="label">Total Disbursed</div><div class="value">' . e(format_money($totalAmount)) . '</div></div>';
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
});

<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role($pdo, ['staff']);

$stmt = $pdo->prepare(
    'SELECT c.claimed_at, v.amount, s.student_no, s.first_name, s.last_name, b.name AS batch_name, p.name AS program_name
     FROM claims c
     JOIN claim_vouchers v ON v.id = c.voucher_id
     JOIN scholars s ON s.id = v.scholar_id
     JOIN distribution_batches b ON b.id = v.batch_id
     JOIN scholarship_programs p ON p.id = b.program_id
     WHERE c.staff_user_id = ? AND DATE(c.claimed_at) = CURDATE()
     ORDER BY c.claimed_at DESC'
);
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll();

render_staff_layout($pdo, 'claims', "Today's Claims", function () use ($rows): void {
    echo '<div class="page-header"><h1 class="page-title">Today\'s Claims</h1></div>';
    echo '<div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr>';
    echo '<th>Time</th><th>Scholar</th><th>Program</th><th>Batch</th><th>Amount</th>';
    echo '</tr></thead><tbody>';
    foreach ($rows as $r) {
        echo '<tr>';
        echo '<td>' . e(format_datetime($r['claimed_at'])) . '</td>';
        echo '<td>' . e(scholar_full_name($r) . ' (' . $r['student_no'] . ')') . '</td>';
        echo '<td>' . e($r['program_name']) . '</td>';
        echo '<td>' . e($r['batch_name']) . '</td>';
        echo '<td>' . e(format_money((float) $r['amount'])) . '</td>';
        echo '</tr>';
    }
    if (!$rows) {
        echo '<tr><td colspan="5"><div class="empty-state">No claims recorded today.</div></td></tr>';
    }
    echo '</tbody></table></div></div>';
});

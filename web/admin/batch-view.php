<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role($pdo, ['admin']);
$id = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'open') {
        $count = generate_missing_vouchers_for_batch($pdo, $id);
        $pdo->prepare('UPDATE distribution_batches SET status="open" WHERE id=?')->execute([$id]);
        audit_log($pdo, (int) $user['id'], 'batch_opened', 'distribution_batch', $id, 'Batch opened');
        flash('success', $count > 0
            ? "Batch opened. $count voucher(s) generated for newly enrolled scholars."
            : 'Batch opened for distribution.');
    } elseif ($action === 'close') {
        $pdo->prepare('UPDATE distribution_batches SET status="closed" WHERE id=?')->execute([$id]);
        audit_log($pdo, (int) $user['id'], 'batch_closed', 'distribution_batch', $id, 'Batch closed');
        flash('success', 'Batch closed.');
    } elseif ($action === 'generate') {
        $count = generate_missing_vouchers_for_batch($pdo, $id);
        flash('success', $count > 0 ? "$count vouchers generated." : 'All enrolled scholars already have vouchers.');
    } elseif ($action === 'void') {
        if (empty($_POST['voucher_ids'])) {
            flash('error', 'Select at least one pending voucher to void.');
        } else {
            $ids = array_map('intval', (array) $_POST['voucher_ids']);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge($ids, [$id]);
            $pdo->prepare("UPDATE claim_vouchers SET status='void' WHERE id IN ($placeholders) AND batch_id = ? AND status = 'pending'")
                ->execute($params);
            flash('success', 'Selected vouchers voided.');
        }
    }
    redirect('admin/batch-view.php?id=' . $id);
}

$stmt = $pdo->prepare(
    'SELECT b.*, p.name AS program_name, p.amount AS program_amount
     FROM distribution_batches b
     JOIN scholarship_programs p ON p.id = b.program_id
     WHERE b.id = ?'
);
$stmt->execute([$id]);
$batch = $stmt->fetch();
if (!$batch) {
    flash('error', 'Batch not found.');
    redirect('admin/batches.php');
}

$stats = $pdo->prepare('SELECT status, COUNT(*) AS cnt FROM claim_vouchers WHERE batch_id = ? GROUP BY status');
$stats->execute([$id]);
$counts = ['pending' => 0, 'claimed' => 0, 'expired' => 0, 'void' => 0, 'total' => 0];
foreach ($stats->fetchAll() as $row) {
    $counts[$row['status']] = (int) $row['cnt'];
    $counts['total'] += (int) $row['cnt'];
}

$voucherQuery = batch_vouchers_sql($id);
$page = pagination_page();
$result = paginate($pdo, $voucherQuery['sql'], $voucherQuery['params'], $page);
$rows = $result['rows'];

render_admin_layout($pdo, 'batches', $batch['name'], function () use ($batch, $counts, $rows, $id, $result): void {
    echo '<div class="breadcrumb">Admin / Distribution Batches / ' . e($batch['name']) . '</div>';
    echo '<div class="page-header"><div><h1 class="page-title">' . e($batch['name']) . '</h1>';
    echo '<div class="page-subtitle">' . e($batch['program_name']) . ' · ' . e($batch['venue']) . ' · ' . e(format_date($batch['distribution_date'])) . '</div></div>';
    echo '<div class="table-actions btn-group">';
    echo '<span class="badge ' . badge_class($batch['status']) . '">' . e($batch['status']) . '</span>';
    if ($batch['status'] === 'draft') {
        echo '<form method="post"><input type="hidden" name="action" value="open"><button class="btn btn-primary btn-sm" type="submit">OPEN BATCH</button></form>';
    }
    if ($batch['status'] === 'draft' || $batch['status'] === 'open') {
        echo '<form method="post"><input type="hidden" name="action" value="generate"><button class="btn btn-outline btn-sm" type="submit">GENERATE VOUCHERS</button></form>';
    }
    if ($batch['status'] === 'open') {
        echo '<form method="post"><input type="hidden" name="action" value="close"><button class="btn btn-secondary btn-sm" type="submit">CLOSE BATCH</button></form>';
    }
    echo '<a class="btn btn-outline btn-sm" href="' . base_url('admin/export-batch.php?id=' . $id) . '">EXPORT CSV</a>';
    echo '<a class="btn btn-outline btn-sm" href="' . base_url('admin/export-batch-pdf.php?id=' . $id) . '">EXPORT PDF</a>';
    echo '</div></div>';

    echo '<div class="kpi-grid">';
    foreach ([
        ['Total', $counts['total'], ''],
        ['Pending', $counts['pending'], 'warning'],
        ['Claimed', $counts['claimed'], ''],
        ['Void', $counts['void'], 'negative'],
    ] as [$label, $value, $tone]) {
        $toneClass = $tone ? ' ' . e($tone) : '';
        echo '<div class="card kpi-card' . $toneClass . '"><div class="label">' . e($label) . '</div>';
        echo '<div class="value">' . (int) $value . '</div></div>';
    }
    echo '</div>';

    echo '<form method="post">';
    echo '<input type="hidden" name="action" value="void">';
    echo '<div class="page-header"><h2 class="page-title page-title-sm">Scholar Vouchers</h2>';
    if ($batch['status'] !== 'closed') {
        echo '<button class="btn btn-outline btn-sm" type="submit">VOID SELECTED</button>';
    }
    echo '</div>';

    if (!$rows) {
        echo '<div class="empty-state">No vouchers generated yet — confirm eligible scholars to generate.</div>';
    } else {
        echo '<div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr>';
        echo '<th></th><th>Scholar</th><th>Student No.</th><th>Amount</th><th>Status</th><th>Claimed At</th>';
        echo '</tr></thead><tbody>';
        foreach ($rows as $v) {
            echo '<tr>';
            echo '<td>';
            if ($v['status'] === 'pending' && $batch['status'] !== 'closed') {
                echo '<input type="checkbox" name="voucher_ids[]" value="' . (int) $v['id'] . '">';
            }
            echo '</td>';
            echo '<td>' . e(scholar_full_name($v)) . '</td>';
            echo '<td>' . e($v['student_no']) . '</td>';
            echo '<td>' . e(format_money((float) $v['amount'])) . '</td>';
            echo '<td><span class="badge ' . badge_class($v['status']) . '">' . e($v['status']) . '</span></td>';
            echo '<td>' . e(format_datetime($v['claimed_at'])) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div></div>';
        render_table_footer('admin/batch-view.php', $result, 'vouchers', ['id']);
    }
    echo '</form>';
});

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

render_admin_page($pdo, 'batches', $batch['name'], 'admin/batches/view', [
    'batch' => $batch,
    'counts' => $counts,
    'countCards' => [
        ['label' => 'Total', 'value' => $counts['total'], 'tone' => ''],
        ['label' => 'Pending', 'value' => $counts['pending'], 'tone' => 'warning'],
        ['label' => 'Claimed', 'value' => $counts['claimed'], 'tone' => ''],
        ['label' => 'Void', 'value' => $counts['void'], 'tone' => 'negative'],
    ],
], ['datatables' => true]);

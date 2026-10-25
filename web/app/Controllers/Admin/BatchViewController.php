<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use PDO;

final class BatchViewController
{
    public static function handle(PDO $pdo): void
    {
$user = require_role($pdo, ['admin']);
$id = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $action = $_POST['action'] ?? '';
    if ($action === 'open') {
        try {
            $count = generate_missing_vouchers_for_batch($pdo, $id);
        } catch (\RuntimeException $error) {
            flash('error', $error->getMessage());
            redirect('admin/batch-view.php?id=' . $id);
        }
        $pdo->prepare('UPDATE distribution_batches SET status="open" WHERE id=?')->execute([$id]);
        audit_log($pdo, (int) $user['id'], 'batch_opened', 'distribution_batch', $id, 'Batch opened');
        flash('success', $count > 0
            ? "Batch opened. $count voucher(s) generated for newly enrolled scholars."
            : 'Batch opened for distribution.');
    } elseif ($action === 'close') {
        $batchStatus = $pdo->prepare('SELECT status FROM distribution_batches WHERE id = ?');
        $batchStatus->execute([$id]);
        if ($batchStatus->fetchColumn() !== 'open') {
            flash('error', 'Only an open batch can be closed.');
            redirect('admin/batch-view.php?id=' . $id);
        }
        if (($_POST['confirm_close'] ?? '') !== '1') {
            flash('error', 'Please confirm that you want to close and sign off this batch.');
            redirect('admin/batch-view.php?id=' . $id);
        }
        $pdo->beginTransaction();
        $expire = $pdo->prepare('UPDATE claim_vouchers SET status="expired" WHERE batch_id = ? AND status = "pending"');
        $expire->execute([$id]);
        $expired = $expire->rowCount();
        $pdo->prepare('UPDATE distribution_batches SET status="closed" WHERE id=?')->execute([$id]);
        $pdo->commit();
        audit_log($pdo, (int) $user['id'], 'batch_closed', 'distribution_batch', $id, "Batch closed and signed off; $expired unclaimed voucher(s) expired");
        flash('success', $expired > 0
            ? "Batch closed and signed off. $expired unclaimed voucher(s) were marked expired."
            : 'Batch closed and signed off. All vouchers were claimed or voided.');
    } elseif ($action === 'generate') {
        try {
            $count = generate_missing_vouchers_for_batch($pdo, $id);
        } catch (\RuntimeException $error) {
            flash('error', $error->getMessage());
            redirect('admin/batch-view.php?id=' . $id);
        }
        audit_log($pdo, (int) $user['id'], 'vouchers_generated', 'distribution_batch', $id, "$count vouchers generated");
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
            audit_log($pdo, (int) $user['id'], 'vouchers_voided', 'distribution_batch', $id, count($ids) . ' voucher(s) voided');
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
], ['datatables' => true, 'qr_preview' => true]);

    }
}

<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';

use App\Queries\BatchQueries;

try {
    $input = require_admin_datatables($pdo);
    $batchId = (int) ($_GET['batch_id'] ?? $_POST['batch_id'] ?? 0);
    if ($batchId <= 0) {
        datatables_json($input['draw'], 0, 0, []);
    }

    $batchStmt = $pdo->prepare('SELECT status FROM distribution_batches WHERE id = ?');
    $batchStmt->execute([$batchId]);
    $batchStatus = (string) ($batchStmt->fetchColumn() ?: 'closed');
    $allowSelect = $batchStatus !== 'closed';

    $result = BatchQueries::voucherDatatables($pdo, $batchId, $input);

    $data = [];
    foreach ($result['rows'] as $row) {
        $checkbox = '';
        if ($allowSelect && $row['status'] === 'pending') {
            $checkbox = '<input type="checkbox" name="voucher_ids[]" value="' . (int) $row['id'] . '">';
        }
        $voucherCode = (string) $row['voucher_code'];
        $scholarName = scholar_full_name($row);
        $voucherCell = '<div class="voucher-code-cell">'
            . '<code class="voucher-code-value">' . e($voucherCode) . '</code>'
            . '<button type="button" class="voucher-preview-trigger"'
            . ' data-voucher-code="' . e($voucherCode) . '"'
            . ' data-voucher-name="' . e($scholarName) . '"'
            . ' data-voucher-student-no="' . e($row['student_no']) . '"'
            . ' data-voucher-amount="' . e(format_money((float) $row['amount'])) . '"'
            . ' aria-label="Preview voucher for ' . e($scholarName) . '">View QR</button>'
            . '</div>';
        $data[] = [
            $checkbox,
            e($scholarName),
            e($row['student_no']),
            $voucherCell,
            e(format_money((float) $row['amount'])),
            '<span class="badge ' . e(badge_class($row['status'])) . '">' . e($row['status']) . '</span>',
            e(format_datetime($row['claimed_at'] ?? null)),
        ];
    }

    datatables_json($result['draw'], $result['total'], $result['filtered'], $data);
} catch (Throwable $e) {
    error_log('Vouchers datatables failed: ' . $e->getMessage());
    datatables_error((int) ($_POST['draw'] ?? $_GET['draw'] ?? 0), 'Could not load vouchers. Please refresh the page.');
}

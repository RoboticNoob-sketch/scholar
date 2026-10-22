<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';

use App\Queries\ProgramQueries;

$input = require_admin_datatables($pdo);
$result = ProgramQueries::datatables($pdo, $input);

$data = [];
foreach ($result['rows'] as $row) {
    $batchCount = (int) $row['batch_count'];
    $batchLabel = $batchCount === 1 ? 'batch' : 'batches';
    $programBudget = (float) $row['total_budget'];
    $availableBudget = (float) $row['available_budget'];
    $committedCount = (int) $row['pending_voucher_count'] + (int) $row['claimed_voucher_count'];
    $committedLabel = $committedCount === 1 ? 'active voucher' : 'active vouchers';
    $programCell = '<a class="program-flow-link" href="' . e(base_url('admin/program-view.php?id=' . (int) $row['id'])) . '"><strong>' . e($row['name']) . '</strong><span>' . e($row['academic_year']) . ' · ' . e($row['semester']) . '</span></a>';
    $availableCell = $programBudget <= 0
        ? '<div class="program-value-cell"><strong>Budget not set</strong><span>edit program</span></div>'
        : ($availableBudget >= 0
        ? '<div class="program-value-cell"><strong>' . e(format_money($availableBudget)) . '</strong><span>available</span></div>'
        : '<div class="program-value-cell is-over-budget"><strong>Over by ' . e(format_money(abs($availableBudget))) . '</strong><span>budget exceeded</span></div>');
    $data[] = [
        $programCell,
        '<div class="program-value-cell"><strong>' . e(format_money((float) $row['amount'])) . '</strong><span>per scholar</span></div>',
        '<div class="program-value-cell"><strong>' . e(format_money((float) $row['total_budget'])) . '</strong><span>total budget</span></div>',
        (string) (int) $row['enrolled_count'],
        '<a class="program-batch-link" href="' . e(base_url('admin/batches.php?program_id=' . (int) $row['id'])) . '"><strong>' . $batchCount . ' ' . $batchLabel . '</strong><span>' . (int) $row['open_batch_count'] . ' open</span></a>',
        '<div class="program-value-cell"><strong>' . e(format_money((float) $row['committed_amount'])) . '</strong><span>' . $committedCount . ' ' . $committedLabel . '</span></div>',
        '<div class="program-value-cell"><strong>' . (int) $row['claimed_voucher_count'] . ' claimed</strong><span>' . e(format_money((float) $row['claimed_amount'])) . ' disbursed</span></div>',
        $availableCell,
        '<span class="badge ' . e(badge_class($row['status'])) . '">' . e($row['status']) . '</span>',
        '<a class="link-action" href="' . e(base_url('admin/program-form.php?id=' . (int) $row['id'])) . '">Edit</a>',
    ];
}

datatables_json($result['draw'], $result['total'], $result['filtered'], $data);

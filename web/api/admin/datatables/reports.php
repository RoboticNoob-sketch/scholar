<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';

use App\Queries\ReportQueries;

$input = require_admin_datatables($pdo);
$batchId = (int) ($_GET['batch_id'] ?? $_POST['batch_id'] ?? 0);
$result = ReportQueries::datatables($pdo, $input, $batchId);

$data = [];
foreach ($result['rows'] as $row) {
    $data[] = [
        e(format_datetime($row['claimed_at'])),
        e(scholar_full_name($row) . ' (' . $row['student_no'] . ')'),
        e($row['program_name']),
        e($row['batch_name']),
        e(format_money((float) $row['amount'])),
        e($row['staff_name']),
    ];
}

datatables_json($result['draw'], $result['total'], $result['filtered'], $data);

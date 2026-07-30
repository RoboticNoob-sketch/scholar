<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';

use App\Queries\ProgramQueries;

$input = require_admin_datatables($pdo);
$result = ProgramQueries::datatables($pdo, $input);

$data = [];
foreach ($result['rows'] as $row) {
    $data[] = [
        e($row['name']),
        e(format_money((float) $row['amount'])),
        e($row['academic_year']),
        e($row['semester']),
        (string) (int) $row['enrolled_count'],
        '<span class="badge ' . e(badge_class($row['status'])) . '">' . e($row['status']) . '</span>',
        '<a class="link-action" href="' . e(base_url('admin/program-form.php?id=' . (int) $row['id'])) . '">Edit</a>',
    ];
}

datatables_json($result['draw'], $result['total'], $result['filtered'], $data);

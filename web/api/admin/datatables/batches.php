<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';

use App\Queries\BatchQueries;

$input = require_admin_datatables($pdo);
$result = BatchQueries::datatables($pdo, $input);

$data = [];
foreach ($result['rows'] as $row) {
    $data[] = [
        e($row['name']),
        e($row['program_name']),
        e(format_date($row['distribution_date'])),
        e($row['venue']),
        '<span class="badge ' . e(badge_class($row['status'])) . '">' . e($row['status']) . '</span>',
        '<a class="link-action" href="' . e(base_url('admin/batch-view.php?id=' . (int) $row['id'])) . '">View</a>',
    ];
}

datatables_json($result['draw'], $result['total'], $result['filtered'], $data);

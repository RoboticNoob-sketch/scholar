<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';

use App\Queries\ScholarQueries;

$input = require_admin_datatables($pdo);
$programId = (int) ($_GET['program_id'] ?? $_POST['program_id'] ?? 0);
$result = ScholarQueries::datatables($pdo, $input, $programId);

$data = [];
foreach ($result['rows'] as $row) {
    $data[] = [
        e($row['student_no']),
        e(scholar_full_name($row)),
        e($row['programs'] ?: '—'),
        '<span class="badge ' . e(badge_class($row['status'])) . '">' . e($row['status']) . '</span>',
        '<a class="link-action" href="' . e(base_url('admin/scholar-view.php?id=' . (int) $row['id'])) . '">View</a>',
    ];
}

datatables_json($result['draw'], $result['total'], $result['filtered'], $data);

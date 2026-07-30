<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';

use App\Queries\AuditQueries;

$input = require_admin_datatables($pdo);
$result = AuditQueries::datatables($pdo, $input);

$data = [];
foreach ($result['rows'] as $row) {
    $data[] = [
        e(format_datetime($row['created_at'])),
        e($row['username'] ?? 'System'),
        '<span class="badge badge-accent">' . e(str_replace('_', ' ', $row['action'])) . '</span>',
        e($row['details'] ?? ($row['entity_type'] . ' #' . $row['entity_id'])),
        e($row['ip_address'] ?? '—'),
    ];
}

datatables_json($result['draw'], $result['total'], $result['filtered'], $data);

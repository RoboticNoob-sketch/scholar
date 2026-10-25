<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';

use App\Queries\AuditQueries;

$input = require_admin_datatables($pdo);
$result = AuditQueries::datatables($pdo, $input, [
    'action' => (string) ($_GET['action'] ?? $_POST['action'] ?? ''),
    'user' => (string) ($_GET['user'] ?? $_POST['user'] ?? ''),
    'date_from' => (string) ($_GET['date_from'] ?? $_POST['date_from'] ?? ''),
    'date_to' => (string) ($_GET['date_to'] ?? $_POST['date_to'] ?? ''),
]);

$failureActions = AuditQueries::failureActions();
$data = [];
foreach ($result['rows'] as $row) {
    $badge = in_array($row['action'], $failureActions, true) ? 'badge-negative' : 'badge-accent';
    $data[] = [
        e(format_datetime($row['created_at'])),
        '<span title="' . e($row['username'] ?? '') . '">' . e($row['display_name'] ?? 'System') . '</span>',
        '<span class="badge ' . $badge . '">' . e(str_replace('_', ' ', $row['action'])) . '</span>',
        e($row['details'] ?? ($row['entity_type'] . ' #' . $row['entity_id'])),
        e($row['ip_address'] ?? '—'),
    ];
}

datatables_json($result['draw'], $result['total'], $result['filtered'], $data);

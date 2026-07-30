<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

require_api_role($pdo, ['staff']);

$batches = $pdo->query(
    'SELECT b.id, b.name, b.distribution_date, b.venue, b.status, p.name AS program_name
     FROM distribution_batches b
     JOIN scholarship_programs p ON p.id = b.program_id
     WHERE b.status = "open"
     ORDER BY b.distribution_date DESC'
)->fetchAll();

$items = [];
foreach ($batches as $b) {
    $items[] = [
        'id' => (int) $b['id'],
        'name' => $b['name'],
        'program_name' => $b['program_name'],
        'distribution_date' => $b['distribution_date'],
        'venue' => $b['venue'],
        'status' => $b['status'],
    ];
}

json_response(['success' => true, 'items' => $items]);

<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use PDO;

final class RequestsController
{
    public static function handle(PDO $pdo): void
    {
        $admin = require_role($pdo, ['admin']);
        $stmt = $pdo->prepare(
            'SELECT a.*, reviewer.username AS reviewer_name
             FROM approval_requests a
             LEFT JOIN users reviewer ON reviewer.id = a.reviewed_by
             WHERE a.submitted_by = ?
             ORDER BY a.created_at DESC, a.id DESC'
        );
        $stmt->execute([(int) $admin['id']]);
        $requests = $stmt->fetchAll() ?: [];
        foreach ($requests as &$request) {
            $request['request_payload'] = json_decode($request['payload'], true) ?: [];
            $request['scholar_name'] = '';
            if ($request['request_type'] === 'scholar_enrollment_update') {
                $scholar = $pdo->prepare('SELECT first_name, last_name FROM scholars WHERE id = ?');
                $scholar->execute([(int) $request['entity_id']]);
                $scholarRow = $scholar->fetch();
                $request['scholar_name'] = $scholarRow ? scholar_full_name($scholarRow) : 'Scholar';
            }
        }
        unset($request);

        render_admin_page($pdo, 'requests', 'My Requests', 'admin/requests/index', [
            'requests' => $requests,
        ]);
    }
}
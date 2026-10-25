<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$user = current_user($pdo);
if ($user) {
    $destination = match ($user['role']) {
        'admin' => 'admin/dashboard.php',
        'approver' => 'admin/approvals.php',
        'staff' => 'staff/dashboard.php',
        'student' => 'student/applications.php',
        default => 'login.php',
    };
    if ($destination !== null) {
        redirect($destination);
    }
}

App\Controllers\PublicHomeController::handle($pdo);

<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$user = current_user($pdo);
if ($user) {
    redirect(match ($user['role']) {
        'admin' => 'admin/dashboard.php',
        'staff' => 'staff/dashboard.php',
        default => 'login.php',
    });
}

redirect('login.php');

<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (current_user($pdo)) {
    audit_log($pdo, (int) $_SESSION['user_id'], 'logout', 'user', (int) $_SESSION['user_id'], 'User logged out');
}
logout_user();
redirect('login.php');

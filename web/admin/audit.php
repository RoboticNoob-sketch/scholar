<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

render_admin_page($pdo, 'audit', 'Audit Logs', 'admin/audit/index', [], ['datatables' => true]);

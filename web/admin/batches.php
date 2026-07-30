<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

render_admin_page($pdo, 'batches', 'Distribution Batches', 'admin/batches/index', [], ['datatables' => true]);
